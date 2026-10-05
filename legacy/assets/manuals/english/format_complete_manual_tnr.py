from io import BytesIO
from pathlib import Path
import re
from tempfile import TemporaryDirectory
import tempfile

import fitz
from PIL import Image
from pypdf import PdfReader, PdfWriter
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas
from reportlab.platypus import Image as PDFImage, PageBreak, Paragraph, SimpleDocTemplate, Spacer
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from xml.sax.saxutils import escape

ROOT = Path(__file__).resolve().parents[3]
SOURCE = ROOT / 'assets/manuals/english/complete_user_manual_en.pdf'
OUTPUT = SOURCE
SCREENSHOTS = ROOT / 'assets/manuals/english/screenshots'
ASSETS = ROOT / 'assets/image'

FONT_DIR = Path(r'C:\Windows\Fonts')
FONT_REGULAR = FONT_DIR / 'times.ttf'
FONT_BOLD = FONT_DIR / 'timesbd.ttf'
FONT_ITALIC = FONT_DIR / 'timesi.ttf'
FONT_BOLD_ITALIC = FONT_DIR / 'timesbi.ttf'
if not all(item.exists() for item in [FONT_REGULAR, FONT_BOLD, FONT_ITALIC, FONT_BOLD_ITALIC]):
    raise FileNotFoundError('Times New Roman font files were not found in C:\\Windows\\Fonts.')
pdfmetrics.registerFont(TTFont('TimesNewRoman', str(FONT_REGULAR)))
pdfmetrics.registerFont(TTFont('TimesNewRoman-Bold', str(FONT_BOLD)))
pdfmetrics.registerFont(TTFont('TimesNewRoman-Italic', str(FONT_ITALIC)))
pdfmetrics.registerFont(TTFont('TimesNewRoman-BoldItalic', str(FONT_BOLD_ITALIC)))

BLACK = colors.black
styles = getSampleStyleSheet()
styles.add(ParagraphStyle(name='TNRBody', parent=styles['BodyText'], fontName='TimesNewRoman', fontSize=12, leading=15, textColor=BLACK, spaceAfter=5))
styles.add(ParagraphStyle(name='TNRBullet', parent=styles['BodyText'], fontName='TimesNewRoman', fontSize=12, leading=15, textColor=BLACK, leftIndent=14, firstLineIndent=-10, spaceAfter=5))
styles.add(ParagraphStyle(name='TNRMainHeading', parent=styles['Heading2'], fontName='TimesNewRoman-Bold', fontSize=12, leading=15, textColor=BLACK, spaceBefore=10, spaceAfter=5, keepWithNext=True))
styles.add(ParagraphStyle(name='TNRSubHeading', parent=styles['Heading3'], fontName='TimesNewRoman', fontSize=12, leading=15, textColor=BLACK, spaceBefore=7, spaceAfter=4, keepWithNext=True))
styles.add(ParagraphStyle(name='TNRRoleTitle', parent=styles['Heading1'], fontName='TimesNewRoman-Bold', fontSize=12, leading=15, textColor=BLACK, spaceAfter=4, keepWithNext=True))
styles.add(ParagraphStyle(name='TNRRoleSubtitle', parent=styles['Normal'], fontName='TimesNewRoman', fontSize=12, leading=15, textColor=BLACK, spaceAfter=7))
styles.add(ParagraphStyle(name='TNRContentsTitle', parent=styles['Heading1'], fontName='TimesNewRoman-Bold', fontSize=12, leading=15, textColor=BLACK, spaceAfter=6))
styles.add(ParagraphStyle(name='TNRContentsRole', parent=styles['Heading2'], fontName='TimesNewRoman-Bold', fontSize=12, leading=15, textColor=BLACK, spaceBefore=7, spaceAfter=3, keepWithNext=True))
styles.add(ParagraphStyle(name='TNRContentsText', parent=styles['BodyText'], fontName='TimesNewRoman', fontSize=12, leading=15, textColor=BLACK, leftIndent=8, spaceAfter=2))
styles.add(ParagraphStyle(name='TNRScreenshotTitle', parent=styles['Heading2'], fontName='TimesNewRoman-Bold', fontSize=12, leading=15, textColor=BLACK, spaceAfter=6))
styles.add(ParagraphStyle(name='TNRScreenshotCaption', parent=styles['BodyText'], fontName='TimesNewRoman', fontSize=12, leading=15, textColor=BLACK, alignment=1, spaceBefore=5))

ROLE_RANGES = [
    ('STUDENT', 'STUDENT USER GUIDE', 2, 9),
    ('SUPERVISOR', 'SUPERVISOR USER GUIDE', 10, 17),
    ('EXTERNAL PANEL', 'EXTERNAL PANEL USER GUIDE', 18, 22),
    ('ADMIN', 'ADMIN USER GUIDE', 23, 32),
]
SCREENSHOT_PAGES = {
    3: ('student_dashboard.png', 'Student Dashboard. Account name is masked.'),
    5: ('student_upload.png', 'Upload Documents form. Private submitted-file list is hidden.'),
    6: ('student_milestones.png', 'Evaluation Marks showing Demo 1 and Demo 2 status.'),
    11: ('supervisor_dashboard.png', 'Supervisor Dashboard. Account name is masked.'),
    13: ('supervisor_verification.png', 'Assigned-student list. Names and matric numbers are masked.'),
    14: ('supervisor_milestones.png', 'Demo milestone form. Student identity is masked.'),
    19: ('panel_registration.png', 'Live Panel Member Registration. No details or scores were submitted.'),
    24: ('admin_dashboard.png', 'Admin Dashboard overview.'),
    25: ('admin_manage_users.png', 'Manage Users. Identity values are masked.'),
    26: ('admin_manage_projects.png', 'Manage Projects. Identity values are masked.'),
    27: ('admin_panel_qr.png', 'External Panel QR split-batch setup; no QR generated.'),
    28: ('admin_ranking_report.png', 'Reports and QR-batch ranking. Project values are masked.'),
    29: ('admin_settings.png', 'Fixed system settings.'),
}

ROLE_DESCRIPTIONS = {
    'STUDENT': 'Student workflow and troubleshooting',
    'SUPERVISOR': 'Supervisor workflow and troubleshooting',
    'EXTERNAL PANEL': 'Panel QR and evaluation workflow',
    'ADMIN': 'Administration and system operations',
}


def get_blocks(page):
    dict_blocks = {block.get('number', index): block for index, block in enumerate(page.get_text('dict')['blocks']) if block.get('type') == 0}
    result = []
    for block in page.get_text('blocks', sort=True):
        if len(block) < 7 or block[6] != 0:
            continue
        x0, y0, x1, y1, raw_text, block_number = block[:6]
        raw_text = re.sub(r'\s+', ' ', raw_text).strip()
        if not raw_text or y0 > page.rect.height - 55:
            continue
        if raw_text in ('SPInE STUDENT PROJECT SYSTEM', 'JTMK | DFT50114 Integrated Project') or raw_text.startswith('SPInE Student Project System | Politeknik Besut'):
            continue
        meta = dict_blocks.get(block_number, {})
        spans = [span for line in meta.get('lines', []) for span in line.get('spans', [])]
        char_count = sum(len(span.get('text', '')) for span in spans) or 1
        bold_chars = sum(len(span.get('text', '')) for span in spans if span.get('flags', 0) & 16)
        bold_ratio = bold_chars / char_count
        max_font_size = max((span.get('size', 0) for span in spans), default=0)
        result.append((raw_text, bold_ratio, max_font_size))
    return result


def classify_block(text, bold_ratio, max_size):
    if text.startswith('Page ') or text == 'JTMK | DFT50114' or text == 'SPInE STUDENT PROJECT SYSTEM':
        return 'skip'
    if text.isupper() and ('USER GUIDE' in text or 'SYSTEM USER MANUAL' in text):
        return 'skip'
    if text.startswith('•'):
        return 'bullet'
    if re.match(r'^[A-Z]\s', text):
        return 'body'
    if re.match(r'^\d+\.\s+', text) or re.match(r'^[A-Z]\.?\s+', text):
        return 'sub'
    if text.isupper() and len(text) < 90:
        return 'main'
    if bold_ratio >= 0.65 and len(text) < 150:
        return 'sub'
    return 'body'


def safe_para(text, style, underline=False):
    markup = escape(text)
    if underline:
        markup = f'<u>{markup}</u>'
    return Paragraph(markup, style)


def draw_logos(c, doc):
    height, gap = 24, 8
    width_a, width_b = height * 621 / 497, height * 1615 / 597
    x, y = (A4[0] - width_a - gap - width_b) / 2, A4[1] - 36
    c.drawImage(ImageReader(str(ASSETS / 'logosistem.png')), x, y, width=width_a, height=height, mask='auto', preserveAspectRatio=True)
    c.drawImage(ImageReader(str(ASSETS / 'logo.png')), x + width_a + gap, y, width=width_b, height=height, mask='auto', preserveAspectRatio=True)


def page_footer(c, page_number, total_pages):
    c.saveState()
    c.setStrokeColor(colors.black)
    c.setLineWidth(0.5)
    c.line(18 * mm, 42, A4[0] - 18 * mm, 42)
    c.setFillColor(colors.black)
    c.setFont('TimesNewRoman', 12)
    c.drawString(18 * mm, 23, 'SPInE Student Project System | Politeknik Besut | DFT50114')
    c.drawRightString(A4[0] - 18 * mm, 23, f'Page {page_number} of {total_pages}')
    c.restoreState()


with fitz.open(SOURCE) as source_doc:
    body_story = []
    role_titles = []
    for role_index, (role_key, role_title, first_page, last_page) in enumerate(ROLE_RANGES):
        if role_index:
            body_story.append(PageBreak())
        body_story.append(safe_para(role_title, styles['TNRRoleTitle']))
        body_story.append(safe_para('JTMK | DFT50114 Integrated Project', styles['TNRRoleSubtitle']))
        role_titles.append(role_title)
        for source_page_number in range(first_page, last_page + 1):
            page = source_doc[source_page_number - 1]
            if source_page_number in SCREENSHOT_PAGES:
                filename, caption = SCREENSHOT_PAGES[source_page_number]
                image_path = SCREENSHOTS / filename
                if not image_path.exists():
                    raise FileNotFoundError(image_path)
                body_story.append(PageBreak())
                body_story.append(safe_para(f'{role_key} | Actual System Screenshot', styles['TNRScreenshotTitle']))
                with Image.open(image_path) as image:
                    image_width, image_height = image.size
                max_width, max_height = A4[0] - 40 * mm, A4[1] - 95 * mm
                scale = min(max_width / image_width, max_height / image_height)
                body_story.append(PDFImage(str(image_path), width=image_width * scale, height=image_height * scale))
                body_story.append(safe_para(caption, styles['TNRScreenshotCaption']))
                body_story.append(PageBreak())
                continue

            for text, bold_ratio, font_size in get_blocks(page):
                if text == role_title or text in ('JTMK | DFT50114', 'SPInE STUDENT PROJECT SYSTEM'):
                    continue
                kind = classify_block(text, bold_ratio, font_size)
                if kind == 'skip':
                    continue
                if kind == 'main':
                    body_story.append(safe_para(text, styles['TNRMainHeading']))
                elif kind == 'sub':
                    body_story.append(safe_para(text, styles['TNRSubHeading'], underline=True))
                elif kind == 'bullet':
                    body_story.append(safe_para(text, styles['TNRBullet']))
                else:
                    body_story.append(safe_para(text, styles['TNRBody']))

with TemporaryDirectory() as temp_dir:
    temp_dir = Path(temp_dir)
    body_path = temp_dir / 'body.pdf'
    body_doc = SimpleDocTemplate(str(body_path), pagesize=A4, leftMargin=20 * mm, rightMargin=20 * mm, topMargin=18 * mm, bottomMargin=19 * mm, title='SPInE User Manual Content', author='SPInE Politeknik Besut')
    body_doc.build(body_story, onFirstPage=draw_logos, onLaterPages=draw_logos)
    body_reader = PdfReader(str(body_path))
    body_text = [page.extract_text() or '' for page in body_reader.pages]

    role_start_body = {}
    for role_title in role_titles:
        found = next((index for index, text in enumerate(body_text) if role_title in text), None)
        if found is None:
            raise RuntimeError(f'Role title not found in generated body: {role_title}')
        role_start_body[role_title] = found

    screenshot_body_pages = {}
    for role_key, role_title, first_page, last_page in ROLE_RANGES:
        for source_page_number in range(first_page, last_page + 1):
            if source_page_number not in SCREENSHOT_PAGES:
                continue
            filename, _ = SCREENSHOT_PAGES[source_page_number]
            marker = f'{role_key} | Actual System Screenshot'
            found = next((index for index, text in enumerate(body_text) if marker in text and filename.replace('_', ' ').split('.')[0] in text), None)
            if found is None:
                # The generated screenshot heading is unique by role and order; fall back to the next matching heading.
                role_matches = [index for index, text in enumerate(body_text) if marker in text]
                screenshot_index = [n for n in range(first_page, source_page_number + 1) if n in SCREENSHOT_PAGES].index(source_page_number)
                if screenshot_index >= len(role_matches):
                    raise RuntimeError(f'Screenshot page not found in generated body: {filename}')
                found = role_matches[screenshot_index]
            screenshot_body_pages[filename] = found

    total_pages = len(body_reader.pages) + 1
    role_start_final = {title: body_index + 2 for title, body_index in role_start_body.items()}
    screenshot_final = {filename: body_index + 2 for filename, body_index in screenshot_body_pages.items()}

    toc_path = temp_dir / 'contents.pdf'
    toc_doc = SimpleDocTemplate(str(toc_path), pagesize=A4, leftMargin=20 * mm, rightMargin=20 * mm, topMargin=18 * mm, bottomMargin=18 * mm, title='Table of Contents')
    toc_story = [safe_para('TABLE OF CONTENTS', styles['TNRRoleTitle']), safe_para('SPInE Student Project System | JTMK | DFT50114', styles['TNRRoleSubtitle'])]
    toc_descriptions = {
        'STUDENT USER GUIDE': 'Student account, active project registration, documents, marks, reminders, groups, archive, and troubleshooting.',
        'SUPERVISOR USER GUIDE': 'Assigned projects, read-only documents, Demo verification, account, and workflow limits.',
        'EXTERNAL PANEL USER GUIDE': 'QR registration, Panel\'s Choice, eight-criteria scoring, submission, and duplicate safeguards.',
        'ADMIN USER GUIDE': 'User imports, project assignments/approval, QR batches, ranking/reports, settings, and profile.'
    }
    for title in role_titles:
        start = role_start_final[title]
        toc_story.append(safe_para(title, styles['TNRMainHeading']))
        toc_story.append(safe_para(f'{toc_descriptions[title]}  |  Page {start}', styles['TNRBody']))
        role_key = next(key for key, role_title, _, _ in ROLE_RANGES if role_title == title)
        role_screenshots = []
        source_range = next((range(first, last + 1) for key, _, first, last in ROLE_RANGES if key == role_key), range(0))
        for source_page_number in source_range:
            screenshot_info = SCREENSHOT_PAGES.get(source_page_number)
            if screenshot_info:
                filename = screenshot_info[0]
                role_screenshots.append((filename, screenshot_final[filename]))
        page_numbers = sorted(page_no for _, page_no in role_screenshots)
        if page_numbers:
            toc_story.append(safe_para('Screenshots: ' + ', '.join(map(str, page_numbers)), styles['TNRBody']))
    toc_doc.build(toc_story, onFirstPage=draw_logos, onLaterPages=draw_logos)
    toc_reader = PdfReader(str(toc_path))
    if len(toc_reader.pages) != 1:
        raise RuntimeError('Table of contents no longer fits on one page at 12 pt.')

    writer = PdfWriter()
    writer.add_page(toc_reader.pages[0])
    for page in body_reader.pages:
        writer.add_page(page)
    final_total = len(writer.pages)
    for page_number, page in enumerate(writer.pages, start=1):
        footer_buffer = BytesIO()
        footer_canvas = canvas.Canvas(footer_buffer, pagesize=A4)
        footer_canvas.setFillColor(colors.white)
        footer_canvas.rect(0, 0, A4[0], 52, fill=1, stroke=0)
        footer_canvas.setStrokeColor(colors.black)
        footer_canvas.setLineWidth(0.5)
        footer_canvas.line(18*mm, 45, A4[0]-18*mm, 45)
        footer_canvas.setFillColor(colors.black)
        footer_canvas.setFont('TimesNewRoman', 12)
        footer_canvas.drawString(18*mm, 24, 'SPInE Student Project System | Politeknik Besut | DFT50114')
        page_label = f'Page {page_number} of {final_total}'
        footer_canvas.drawRightString(A4[0]-18*mm, 24, page_label)
        footer_canvas.save()
        footer_buffer.seek(0)
        page.merge_page(PdfReader(footer_buffer).pages[0])

    for title in role_titles:
        writer.add_outline_item(title.title(), role_start_final[title]-1)
    writer.add_metadata({'/Title':'Complete SPInE System User Manual','/Author':'SPInE Politeknik Besut','/Subject':'Times New Roman 12 pt, black text, bold headings, underlined subheadings; complete role workflows and screenshots'})
    temporary_output = OUTPUT.with_suffix('.tnr.tmp.pdf')
    with temporary_output.open('wb') as output:
        writer.write(output)
    temporary_output.replace(OUTPUT)
    print(f'Formatted {OUTPUT}; {final_total} pages; Times New Roman 12 pt; black text.')
