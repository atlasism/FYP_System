from pathlib import Path
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.enum.section import WD_SECTION
from PIL import Image

ROOT = Path(__file__).resolve().parents[3]
OUT = ROOT / 'assets/manuals/english/complete_user_manual_en.docx'
IMG = ROOT / 'assets/image'
SHOTS = ROOT / 'assets/manuals/english/screenshots'
BLACK = RGBColor(0, 0, 0)
FONT = 'Times New Roman'

def set_run(run, size=12, bold=False, underline=False, italic=False):
    run.font.name = FONT
    run._element.rPr.rFonts.set(qn('w:eastAsia'), FONT)
    run.font.size = Pt(size)
    run.font.bold = bold
    run.font.underline = underline
    run.font.italic = italic
    run.font.color.rgb = BLACK

def set_cell_shading(cell, fill='EDEDED'):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement('w:shd')
    shd.set(qn('w:fill'), fill)
    tc_pr.append(shd)

def set_cell_border(cell):
    tc_pr = cell._tc.get_or_add_tcPr()
    borders = OxmlElement('w:tcBorders')
    for edge in ('top', 'left', 'bottom', 'right', 'insideH', 'insideV'):
        tag = 'w:{}'.format(edge)
        element = OxmlElement(tag)
        element.set(qn('w:val'), 'single')
        element.set(qn('w:sz'), '6')
        element.set(qn('w:space'), '0')
        element.set(qn('w:color'), '000000')
        borders.append(element)
    tc_pr.append(borders)

def add_page_field(paragraph, instruction):
    run = paragraph.add_run()
    begin = OxmlElement('w:fldChar')
    begin.set(qn('w:fldCharType'), 'begin')
    code = OxmlElement('w:instrText')
    code.set(qn('xml:space'), 'preserve')
    code.text = instruction
    separate = OxmlElement('w:fldChar')
    separate.set(qn('w:fldCharType'), 'separate')
    text = OxmlElement('w:t')
    text.text = '1'
    end = OxmlElement('w:fldChar')
    end.set(qn('w:fldCharType'), 'end')
    run._r.extend([begin, code, separate, text, end])
    set_run(run)

def add_toc_field(paragraph):
    run = paragraph.add_run()
    begin = OxmlElement('w:fldChar')
    begin.set(qn('w:fldCharType'), 'begin')
    code = OxmlElement('w:instrText')
    code.set(qn('xml:space'), 'preserve')
    code.text = ' TOC \\o "1-3" \\h \\z \\u '
    separate = OxmlElement('w:fldChar')
    separate.set(qn('w:fldCharType'), 'separate')
    placeholder = OxmlElement('w:t')
    placeholder.text = 'Open in Word and update this table to refresh page numbers.'
    end = OxmlElement('w:fldChar')
    end.set(qn('w:fldCharType'), 'end')
    run._r.extend([begin, code, separate, placeholder, end])
    set_run(run)

def add_label_line(doc, label, text):
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(4)
    p.paragraph_format.line_spacing = 1.08
    r = p.add_run(label + ': ')
    set_run(r, bold=True, underline=True)
    r = p.add_run(text)
    set_run(r)
    return p

def add_bullet(doc, text, numbered=False):
    p = doc.add_paragraph(style='List Number' if numbered else 'List Bullet')
    p.paragraph_format.space_after = Pt(3)
    p.paragraph_format.line_spacing = 1.08
    r = p.add_run(text)
    set_run(r)
    return p

def add_screenshot(doc, filename, caption):
    path = SHOTS / filename
    if not path.exists():
        return
    doc.add_page_break()
    p = doc.add_paragraph()
    r = p.add_run('SYSTEM SCREENSHOT')
    set_run(r, size=12, bold=True)
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    with Image.open(path) as image:
        width, height = image.size
    max_width, max_height = 6.2, 7.0
    scale = min(max_width / (width / 96), max_height / (height / 96))
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.add_run().add_picture(str(path), width=Inches(width / 96 * scale), height=Inches(height / 96 * scale))
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_after = Pt(8)
    set_run(p.add_run(caption), size=10, italic=True)

def add_workflow(doc, title, purpose, role, steps, required, outcome, notifications, screenshot=None):
    doc.add_heading(title, level=2)
    add_label_line(doc, 'Purpose', purpose)
    add_label_line(doc, 'Who can use', role)
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(3)
    set_run(p.add_run('Steps'), bold=True, underline=True)
    for step in steps:
        add_bullet(doc, step, numbered=True)
    add_label_line(doc, 'Required Inputs', required)
    add_label_line(doc, 'Outcome / Output', outcome)
    add_label_line(doc, 'Status / Notifications', notifications)
    if screenshot:
        add_screenshot(doc, screenshot[0], screenshot[1])

def set_document_styles(doc):
    for style_name in ['Normal', 'Body Text', 'List Paragraph']:
        style = doc.styles[style_name]
        style.font.name = FONT
        style._element.rPr.rFonts.set(qn('w:eastAsia'), FONT)
        style.font.size = Pt(12)
        style.font.color.rgb = BLACK
        style.paragraph_format.space_after = Pt(5)
        style.paragraph_format.line_spacing = 1.08
    for name, size, underline in [('Heading 1', 16, False), ('Heading 2', 13, True), ('Heading 3', 12, True)]:
        style = doc.styles[name]
        style.font.name = FONT
        style._element.rPr.rFonts.set(qn('w:eastAsia'), FONT)
        style.font.size = Pt(size)
        style.font.bold = True
        style.font.underline = underline
        style.font.color.rgb = BLACK
        style.paragraph_format.space_before = Pt(10)
        style.paragraph_format.space_after = Pt(5)
        style.paragraph_format.keep_with_next = True

def add_workflow_table(doc, rows):
    table = doc.add_table(rows=1, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.style = 'Table Grid'
    table.rows[0].cells[0].text = 'User Role'
    table.rows[0].cells[1].text = 'Main responsibilities in SPInE'
    for cell in table.rows[0].cells:
        set_cell_shading(cell)
        for run in cell.paragraphs[0].runs:
            set_run(run, bold=True)
    for role, responsibility in rows:
        cells = table.add_row().cells
        cells[0].text, cells[1].text = role, responsibility
        for cell in cells:
            set_cell_border(cell)
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
            for paragraph in cell.paragraphs:
                for run in paragraph.runs:
                    set_run(run)
    return table


doc = Document()
set_document_styles(doc)
section = doc.sections[0]
section.page_width, section.page_height = Inches(8.27), Inches(11.69)
section.top_margin = section.bottom_margin = section.left_margin = section.right_margin = Inches(1)
section.header_distance = Inches(.45)
section.footer_distance = Inches(.45)

# Keep the cover entirely editable; only the logos are image objects.
cover = doc.add_paragraph()
cover.alignment = WD_ALIGN_PARAGRAPH.CENTER
cover.paragraph_format.space_before = Pt(30)
cover.add_run().add_picture(str(IMG / 'logosistem.png'), width=Inches(.9))
cover.add_run('    ')
cover.add_run().add_picture(str(IMG / 'logo.png'), width=Inches(1.8))
for text, size, bold in [
    ('USER MANUAL DOCUMENT', 22, True),
    ('SPInE STUDENT PROJECT SYSTEM', 18, True),
    ('JTMK INTEGRATED PROJECT MANAGEMENT SYSTEM', 15, True),
    ('POLITEKNIK BESUT | DFT50114', 14, True),
]:
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_after = Pt(8)
    set_run(p.add_run(text), size=size, bold=bold)

cover_table = doc.add_table(rows=0, cols=2)
cover_table.alignment = WD_TABLE_ALIGNMENT.CENTER
cover_table.style = 'Table Grid'
for label, value in [
    ('Prepared for', '[Insert department / audience]'),
    ('Prepared by', '[Insert name / team]'),
    ('Academic session', '[Insert academic session]'),
    ('Document version', '[Insert version]'),
    ('Date', '[Insert date]'),
]:
    cells = cover_table.add_row().cells
    cells[0].text, cells[1].text = label, value
    for cell in cells:
        set_cell_border(cell)
        for paragraph in cell.paragraphs:
            for run in paragraph.runs:
                set_run(run)

p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
p.paragraph_format.space_before = Pt(26)
set_run(p.add_run('STUDENT  |  SUPERVISOR  |  EXTERNAL PANEL  |  ADMIN'), size=12, bold=True)
doc.add_page_break()

# Editable TOC field: update it in Word after opening to refresh page numbers.
doc.add_heading('TABLE OF CONTENTS', level=1)
p = doc.add_paragraph()
add_toc_field(p)
doc.add_page_break()

doc.add_heading('SECTION 1 — SYSTEM INTRODUCTION & OVERALL FLOW', level=1)
doc.add_heading('1.1 Introduction to SPInE', level=2)
doc.add_paragraph('SPInE is the Politeknik Besut project system for JTMK DFT50114. It connects student project registration and documents, supervisor monitoring and milestone verification, external panel Demo 3 evaluation, and Admin operations in one web application.')
doc.add_heading('1.2 Purpose and main functions', level=2)
for item in [
    'Maintain JTMK student project groups and member records.',
    'Track project document submissions and show current statuses.',
    'Let supervisors review assigned project documents and record Demo 1/Demo 2 milestone outcomes.',
    'Let external panel members evaluate Demo 3 using QR-assigned groups and an eight-criteria rubric.',
    'Let Admin import/manage student records, assign supervisors, approve submitted projects, generate panel QR batches, and review reports.'
]: add_bullet(doc, item)
doc.add_heading('1.3 User roles and responsibilities', level=2)
add_workflow_table(doc, [
    ('Student', 'Register a new project group, maintain profile, upload rubric documents, check marks/statuses, deadlines, group search, and past-project references.'),
    ('Supervisor', 'Monitor assigned projects, view uploaded documents, and record Demo 1/Demo 2 status. Current logbook and numeric-mark routes are unavailable/legacy.'),
    ('External Panel', 'Register through a QR link, choose assigned groups, optionally mark Panel\'s Choice, score all students for eight criteria, and submit Demo 3.'),
    ('Admin', 'Manage/import JTMK students, assign group supervisors, approve submitted projects, generate shared/split QR batches, review reports, and update own profile.')
])
doc.add_heading('1.4 Overall system flow', level=2)
for item in [
    'Student signs in → registers a new group → uploads documents → checks project and milestone status.',
    'Admin imports/maintains users → assigns project supervisor → reviews and approves Submitted project records → creates external-panel QR sessions.',
    'Supervisor reviews assigned groups/documents → records Demo 1/Demo 2 Passed or Not Passed status.',
    'Panel opens QR → registers name/email → evaluates each assigned project and student → submits scores; Reports and ranking use saved panel evaluations.'
]: add_bullet(doc, item)
doc.add_heading('1.5 How to use this manual', level=2)
doc.add_paragraph('The cover fields are editable. Update the table of contents in Word after editing the document. Each role chapter uses the same procedure structure: Purpose, Who can use, Steps, Required Inputs, Outcome/Output, and Status/Notifications. System screenshots are placed immediately after relevant procedures; names, matric numbers, project names, and live QR tokens are masked or excluded.')

doc.add_heading('SECTION 2 — STUDENT USER GUIDE', level=1)
add_workflow(doc, '2.1 Sign in and open the Student Dashboard', 'Access the Student workspace and understand project progress at a glance.', 'Registered Student', [
    'Open the SPInE home page and select Login.',
    'Enter the registered IC number and password. Imported accounts initially use the IC number as password; change it after the first sign-in.',
    'After login, choose Dashboard from the side menu.',
    'Review Project Status, Total Score, Project Rank, document submission cards, project title, session, and supervisor.',
    'If the account has no project, the Dashboard offers Register Project. If a group is already linked, review its summary.'
], 'IC number and password. No project information is entered on the Dashboard.', 'The Student Dashboard opens. Total Score/Rank appear only when mark data is available; Not Evaluated Yet or a dash means no result is recorded.', 'A login error means verify the IC/password. Contact Admin for account access; do not create a duplicate account.', ('student_dashboard.png', 'Dashboard with student identity masked.'))

add_workflow(doc, '2.2 Register a new project group (My Project)', 'Create a JTMK project group with the signed-in student as Project Leader.', 'Student account not yet linked to a project', [
    'Open My Project from the Student menu. If a project is already linked, the system redirects to the existing project summary; this is expected.',
    'Student #1 is the logged-in student and is fixed as Project Leader. Add Student #2 and Student #3 only when needed.',
    'For each optional member, enter a registered JTMK matric number and leave the field. Wait for the lookup result and confirm the returned name, IC, track, class, phone, and email.',
    'Enter Project Title, select Project Category, confirm Academic Session, and write Project Description. Department, program, and course code are fixed by the system.',
    'Tick the originality declaration and select Submit Project Registration once. Check the success message and the resulting My Project summary.'
], 'A unique title; one allowed category; session; project description (up to 500 characters); optional registered matric numbers for up to two additional students; originality declaration.', 'The system creates the project and member-role records together and redirects to My Project. A group can contain one to three students.', 'Missing required fields/category or invalid session is rejected. A matric lookup error means the student was not found or is a duplicate. The active route does not offer Join Existing Project.', ('student_dashboard.png', 'Student workspace shown in the Dashboard screenshot.'))

add_workflow(doc, '2.3 Update Student Profile', 'Keep account identifiers, email, password, and profile picture current.', 'Signed-in Student', [
    'Open Profile from the top bar.',
    'Review IC Number, Matric Number, Email, Department, and Full Name. Full Name and Department are read-only.',
    'Edit IC, matric number, or email if required. Matric numbers may contain letters and numbers only.',
    'To change password, enter at least eight characters; leave the field blank to keep the current password.',
    'Optionally choose a JPG, PNG, or WEBP profile picture no larger than 2 MB.',
    'Select Save Profile and read the result alert.'
], 'IC number; alphanumeric matric number; valid email; optional password (8+ characters); optional JPG/PNG/WEBP image up to 2 MB.', 'Valid account details are saved and the current session name/username is refreshed.', 'Duplicate IC/matric, invalid email, short password, unsupported image, or oversized image is rejected. Correct the field named in the error.', None)

add_workflow(doc, '2.4 Upload and check project documents', 'Submit the group file for a rubric component and verify its stored status.', 'Student member of a registered project group', [
    'Open Upload Documents. Select the exact Document Type.',
    'Choose a PDF, DOCX, or ZIP file within the size limit displayed by the application.',
    'Select Upload Now and wait for the result alert.',
    'Check Submitted Documents List for category, status, and upload date. Select Download to retrieve the stored file.',
    'Coordinate with group members: each category is submitted once for the entire group.'
], 'A project group; document category; supported file (PDF/DOCX/ZIP) under the configured size limit.', 'The file is saved against the project and a row appears in Submitted Documents List. Rubric categories: Proposal 10%, Demo 1 10%, Demo 2 10%, Demo 3 15%, Poster 15%, Final Presentation 15%, Technical Report 15%.', 'Pending awaits review; Approved means accepted; Rejected requires follow-up. A disabled category means another member already submitted it. Invalid format/MIME/size or missing project produces an error.', ('student_upload.png', 'Upload Documents form; private submitted-file list is hidden.'))

add_workflow(doc, '2.5 Check marks and Demo milestone statuses', 'View supervisor verification and distinguish status outcomes from numerical marks.', 'Signed-in Student', [
    'Open Evaluation Marks from the side menu.',
    'Read Demo 1 and Demo 2 statuses: Pending, Passed, or Not Passed.',
    'For numerical Total Score and Project Rank, return to Dashboard. Demo 3 marks come from the external panel QR workflow.',
    'If a status does not match your understanding, contact the assigned supervisor.'
], 'No inputs; this is a read-only Student view.', 'Current Demo 1/Demo 2 statuses are displayed. The weekly logbook verification card has been removed from the Student view.', 'Pending means no status saved; Passed/Not Passed are verification results, not grades. A blank numerical total/rank means marks are not yet available.', ('student_milestones.png', 'Evaluation Marks showing Demo 1 and Demo 2 statuses.'))

add_workflow(doc, '2.6 Use Deadline Reminders, Group List, and Past Projects', 'Check due dates, search groups, and consult archived project references.', 'Signed-in Student', [
    'Open Deadline Reminders and read each document type, instructions, due date/time, and status.',
    'Open Group List. Search by project title, student name, or matric number. Select Search to apply; select Reset to clear filters.',
    'Open Past Projects Archive. Search by keyword and/or academic session, then select View Details for the selected reference.',
    'Confirm the current academic session before treating a project as current; archive projects are historical references.'
], 'Optional keyword and session filters; no data is changed.', 'Deadline reminders and project/group references are displayed.', 'Active means before deadline; Expired means past due; Waiting for Admin means no date announced. Reminders do not assign marks automatically.', None)

add_workflow(doc, '2.7 Student troubleshooting checklist', 'Resolve common account, group, and upload issues safely.', 'Signed-in Student', [
    'If My Project redirects to the group summary, the account already belongs to a project; the active route creates new groups and does not offer Join Existing Project.',
    'If an optional member lookup fails, verify the matric number and registered JTMK account. Do not submit the same student twice.',
    'If a document option is disabled, ask the group which member submitted that rubric category. Do not create a duplicate upload.',
    'Select Logout after finishing, especially on a shared device.'
], 'The relevant account/project information and on-screen message.', 'The user can identify whether to correct data, contact the supervisor, or contact Admin.', 'Do not repeatedly submit after a success redirect. Record the page name and error text when asking for help.', None)

doc.add_heading('SECTION 3 — SUPERVISOR USER GUIDE', level=1)
add_workflow(doc, '3.1 Sign in and review the Supervisor Dashboard', 'Check assigned workload and recent submissions.', 'Signed-in Supervisor', [
    'Sign in with a Supervisor account.',
    'Review the Dashboard totals for assigned students and groups.',
    'Treat Recent Student Submissions & Groups as a limited summary. Select View All or open Supervised Projects for the full list.',
    'Use the top bar for Home, Profile, language, and appearance; use the side menu for Supervisor modules.'
], 'Supervisor account credentials.', 'The dashboard displays the current supervisor assignment summary.', 'If expected students/groups are missing, check Supervised Projects and ask Admin to verify assignments.', ('supervisor_dashboard.png', 'Supervisor Dashboard with account name masked.'))

add_workflow(doc, '3.2 Find an assigned project', 'Locate a group before inspecting documents or statuses.', 'Signed-in Supervisor', [
    'Open Supervised Projects.',
    'Search by project title, student name, or matric number and select Search Project.',
    'Confirm title, leader/member names, category, session, and department.',
    'Clear the search and submit again to restore the full list.'
], 'Optional search string.', 'The assigned-project list is filtered; no records are changed.', 'If a group is absent, verify identity/session and request an Admin assignment check. Do not use another supervisor account.', None)

add_workflow(doc, '3.3 Review submitted documents (read-only)', 'Inspect a group\'s uploaded files without changing marks or statuses.', 'Supervisor assigned to the selected group', [
    'Open Review Documents and select a project from the supervised list.',
    'Review category, original filename, status, and upload date.',
    'Select View to open the stored file; return to Projects to choose another group.',
    'Compare the file with the expected category and follow up with the group if it is unreadable or incorrect.'
], 'Select a supervised project; no form inputs are required.', 'Document contents can be reviewed. This page is read-only and cannot approve/reject a file or enter numerical marks.', 'No documents submitted means no file row exists. Report broken file links to Admin.', ('supervisor_verification.png', 'Supervisor student list with identifying values masked; Review Documents is a separate read-only page.'))

add_workflow(doc, '3.4 Verify Demo 1 and Demo 2', 'Save the milestone result for an assigned student.', 'Supervisor assigned to the student', [
    'Open Student Verification and confirm the student name, matric number, and session.',
    'Select Milestones on that student row.',
    'Choose Passed or Not Passed for each Demo to be updated. Leave a field blank to keep that milestone unchanged.',
    'Select Save Status and wait for the success message, then return to the student list and verify the displayed status.'
], 'At least one valid status (Passed or Not Passed) for Demo 1 and/or Demo 2.', 'The selected milestone status is saved. Blank fields preserve the existing result.', 'Both fields blank, an invalid option, wrong student, or invalid form session prevents the update. These statuses are not numerical marks.', ('supervisor_milestones.png', 'Demo milestone status form with student identity masked.'))

add_workflow(doc, '3.5 Supervisor pages, profile, and workflow limits', 'Understand which modules are active and which legacy routes are unavailable.', 'Signed-in Supervisor', [
    'Past Projects and Deadline Reminders are reference pages.',
    'Profile allows IC/Staff ID and email updates; name and department are read-only. Password changes require at least eight characters; image uploads accept JPG/PNG/WEBP up to 2 MB.',
    'The old Verify Logbook route redirects to Student Verification; there is no active week-by-week logbook approval screen.',
    'Project Marks/Evaluate Project is a legacy route and may redirect to Student Verification. Demo 3 scoring belongs to External Panel QR.'
], 'Profile fields as applicable; no milestone inputs on the profile/reference pages.', 'Profile updates save after validation. Reference pages do not alter marks.', 'If a legacy route redirects, use the active Student Verification/Review Documents workflow and report the unavailable route to Admin.', None)

doc.add_heading('SECTION 4 — EXTERNAL PANEL USER GUIDE', level=1)
add_workflow(doc, '4.1 Open QR and register panel identity', 'Register the panel identity for the groups assigned to a QR session.', 'External panel member with a valid QR link', [
    'Open the QR/link supplied by Admin and confirm Panel Evaluation and DFT50114.',
    'Enter Panel Name. Email Address is optional unless required on the form.',
    'Select Continue to Groups and wait for Group List.',
    'Use the same normalized name and email across QR sessions so the duplicate safeguard can identify submissions.'
], 'Panel name; email if requested; active QR session.', 'The system creates or resumes a panel assessor registration scoped to this QR.', 'Expired link, full split-batch capacity, or missing groups requires a current QR from Admin. Do not publish the token.', ('panel_registration.png', 'Panel Member Registration page; no identity or score was submitted.'))

add_workflow(doc, '4.2 Select a group and Panel\'s Choice', 'Choose an assigned project and optionally mark it as a panel favourite.', 'Registered panel member', [
    'Check project title and student members in Group List.',
    'Select Assess Group to open the evaluation form.',
    'Use the star to add or remove Panel\'s Choice for this QR batch. Star selection is separate from scoring.',
    'If a group is marked Complete, do not attempt another evaluation for the same identity.'
], 'A project included in the current QR session.', 'The project opens for scoring; the star selection is stored per QR session.', 'A matching normalized name and email cannot assess the same project again, including through another QR.', None)

add_workflow(doc, '4.3 Score students using the eight criteria', 'Evaluate each student consistently and completely for Demo 3.', 'Registered panel member on an unassessed group', [
    'Confirm project title, supervisor, each student name, and matric number.',
    'For every student, select a score from 1 to 4 for each criterion: achievement/objectives; user requirements; construction/functionality; feasibility; originality; marketability; creativity; system security/features/testing.',
    'Read the on-screen level descriptions for Very Good (4), Good (3), Fair (2), and Weak (1).',
    'Use the criteria tabs, Previous, and Next Criteria. The progress indicator helps locate incomplete criteria.',
    'Enter optional Comments / Feedback. Comments do not replace the required scores.'
], 'Eight criterion ratings (1–4) for every student in the group; comments are optional.', 'Each criterion contributes 12.5 points to total /100. Demo 3 /15 is calculated as total/100 x 15.', 'Any missing score prevents submission. Check all students and tabs before saving.', None)

add_workflow(doc, '4.4 Save an evaluation and complete the QR batch', 'Store marks once and complete all groups assigned to the QR.', 'Registered panel member', [
    'Recheck the group and score completeness.',
    'Select Done - Save Scores once and wait for Scores saved.',
    'Return to Group List and verify Complete; then assess remaining groups.',
    'When all assigned groups are finished, select Done if displayed.'
], 'Complete rubric for all group members; optional feedback.', 'Evaluation and student marks are stored; the group becomes Complete.', 'Already-assessed errors indicate a duplicate identity. If the browser closes after saving, reopen the QR and check Complete before retrying.', None)

doc.add_heading('SECTION 5 — ADMIN USER GUIDE', level=1)
add_workflow(doc, '5.1 Admin Dashboard and navigation', 'Monitor system counts, registration states, ranking, and panel selections.', 'Signed-in Admin', [
    'Use the sidebar to open Dashboard, Manage Users, Manage Projects, External Panel QR, Reports, and Settings.',
    'Review JTMK student, supervisor, and project totals.',
    'Check Draft/Submitted/Approved counts and Top 5 Project Ranking when evaluation results exist.',
    'Use links to the full ranking and Panel\'s Choices report for details.'
], 'Admin account session.', 'Dashboard values summarize current database records.', 'An empty ranking may mean no panel evaluations exist; QR creation alone does not create scores.', ('admin_dashboard.png', 'Admin Dashboard with summary totals.'))

add_workflow(doc, '5.2 Find and import JTMK students', 'Search accounts and create student accounts from validated roster files.', 'Signed-in Admin', [
    'Find Student requires at least two characters and searches name, IC number, or matric. Refine broad searches; maximum results are limited.',
    'Prepare a CSV/XLSX file up to 5 MB with Name, IC No, Matric No, Session, and Department columns.',
    'Choose the file and select Import Students. Only Department=JTMK imports; other departments are ignored.',
    'Read imported, ignored, and skipped counts plus row errors. Skips may result from missing fields, invalid matric, or duplicate IC/matric/email.',
    'Imported students use the IC number as initial password. Advise them to change it after signing in.'
], 'Validated roster file; required headers; JTMK department values.', 'Valid accounts are created and listed by session; skipped rows are reported.', 'Check migration errors, column names, file size/type, required values, matric pattern, and duplicates before retrying.', ('admin_manage_users.png', 'Manage Users search and import interface; identities are masked.'))

add_workflow(doc, '5.3 Edit or delete student records', 'Correct an existing JTMK student record while respecting project dependencies.', 'Signed-in Admin', [
    'Locate the student in the session-grouped table and select Edit.',
    'Review IC, matric, email, and academic session. Full Name is read-only.',
    'Use Reset Password only when needed; the new password must be at least eight characters.',
    'Select Save Student and read the result message.',
    'Use Delete only when the account is not linked to a project, group membership, or supervisor assignment; confirm the prompt.'
], 'Student account selected by ID; unique IC/matric/email; optional valid password.', 'Account changes save, or deletion succeeds only if there are no dependencies.', 'Duplicate identity, invalid matric/email/password, or linked records prevent the update/delete.', None)

add_workflow(doc, '5.4 Assign supervisor and approve projects', 'Assign a JTMK supervisor to a project group and approve Submitted registrations.', 'Signed-in Admin', [
    'Open Manage Projects and confirm project title, category/session, group members, and current status.',
    'Choose a JTMK Supervisor and select Assign SV. Assignment updates the whole group.',
    'Review the per-student Demo 1/Demo 2 status table.',
    'For Submitted status only, select Approve and confirm.'
], 'A Submitted project for approval; a Supervisor account with the Supervisor role and JTMK department for assignment.', 'Supervisor mapping applies to group members; Submitted changes to Approved.', 'Stale status, invalid supervisor, or form-session validation prevents changes. Demo 3 evaluation is handled by the panel.', ('admin_manage_projects.png', 'Manage Projects assignment and approval controls; identities are masked.'))

add_workflow(doc, '5.5 Generate shared or split QR batches', 'Create secure external-panel links for current project groups.', 'Signed-in Admin', [
    'Open External Panel QR and confirm the latest-session group count and group numbers.',
    'Generate QR creates one shared session for all groups.',
    'For split QR, open Set up split QR batches. Enter Total panel members and Number of QR batches.',
    'For every batch, enter Groups and Panel members. Check included-group previews and total summaries.',
    'Select Generate Split QRs only when every group is allocated once and panel assignments cover the requested total.',
    'Copy/share each generated link only with its assigned panel members. QR links normally expire after seven days.'
], 'Current numbered groups; shared or split mode; split-batch group/panel counts (minimum two panel members per split QR).', 'Panel sessions, project mappings, QR images, and URLs are created.', 'Group totals must equal available groups. Every split batch needs at least one group and two panels; assignments must cover the total panel count. Keep token URLs private.', ('admin_panel_qr.png', 'Split-batch controls; no QR was generated for this screenshot.'))

add_workflow(doc, '5.6 Read Reports, ranking, and panel results', 'Review project distribution, status, evaluated QR batches, and Panel\'s Choices.', 'Signed-in Admin', [
    'Projects by Category and Project Registration Status summarize JTMK/DFT50114 records.',
    'Demo Verification Summary shows Demo 1/Demo 2 Passed, Not Passed, and Pending counts.',
    'Project Ranking by QR Batch lists sessions with saved scores. The newest evaluated session is selected by default; choose date/time and select Show Ranking to filter.',
    'Panel\'s Choices shows stars for the most recently evaluated batch. External Panel Demo 3 Evaluations displays submissions for the latest generated QR group set.',
    'Open Panel breakdown for assessor, student criteria, scores, and comments.'
], 'Selected evaluated batch for ranking; saved panel evaluations for score breakdown.', 'Reports display records matching their source and selected batch.', 'No ranking means the chosen batch has no submitted scores. QR generation alone does not create ranking data.', ('admin_ranking_report.png', 'QR-batch ranking control in Reports; project values are masked.'))

add_workflow(doc, '5.7 Settings and Admin Profile', 'Review fixed configuration and maintain the signed-in Admin account.', 'Signed-in Admin', [
    'Settings is read-only: system name, department, course, project scope, and assessment model are fixed.',
    'Open Profile from the top bar. IC and email can be updated if valid and unique; Full Name and Department are read-only.',
    'Leave Change Password blank to keep the current one. A replacement password requires at least eight characters.',
    'Optional profile picture must be JPG/PNG/WEBP and at most 2 MB. Select Save Profile.'
], 'Valid unique IC/email; optional password and image within constraints.', 'Profile updates save and the session display refreshes.', 'Duplicate IC/email, invalid email, short password, or unsupported/oversized image is rejected.', ('admin_settings.png', 'Fixed system settings.'))

doc.add_heading('SECTION 6 — SYSTEM FLOW AND CROSS-ROLE HANDOFFS', level=1)
add_workflow(doc, '6.1 Student-to-supervisor-to-panel flow', 'Understand how records move between roles and why one action does not replace another.', 'All system roles', [
    'Student registers a project group and uploads rubric documents.',
    'Admin assigns the group to a Supervisor; the Supervisor reviews documents and records Demo 1/Demo 2 statuses.',
    'Admin creates a shared or split QR session for external Panel members.',
    'Panel registers a name/email, optionally selects Panel\'s Choice, scores assigned groups, and submits Demo 3.',
    'Dashboard and Reports display saved statuses, scores, rankings, and batch-specific choices.'
], 'Role-appropriate account or QR access; project registration; submitted documents; supervisor assignment; QR session.', 'Each role updates a separate record: registration, document, milestone, panel evaluation, or star choice.', 'Document upload is not a mark; milestone status is not a numerical grade; QR creation is not an evaluation. Check the owning workflow when a result is missing.', None)

doc.add_heading('SECTION 7 — USER SUPPORT AND DOCUMENT CONTROL', level=1)
add_workflow(doc, '7.1 Safe troubleshooting and escalation', 'Help users report issues with enough context to investigate without exposing credentials.', 'All roles; Admin coordinates resolution', [
    'Record the role, page/menu, project/session or QR batch (do not include a live QR token), exact time, and the visible error message.',
    'Check whether the action succeeded before retrying. Look for success alerts, a changed status, a new document row, a Complete badge, or an updated project assignment.',
    'Student/project identity issues go to Admin; academic milestone decisions go to the Supervisor; panel QR/score issues go to Admin after checking the panel completion state.',
    'Never include passwords, full IC numbers, private student files, or live panel tokens in support screenshots.'
], 'Page/menu, role, sanitized message, session, and non-sensitive record reference.', 'The right owner can reproduce and resolve the issue.', 'Follow the page validation message. Avoid repeated submits that could create duplicate actions.' , None)

section.different_first_page_header_footer = True
footer = section.footer.paragraphs[0]
footer.alignment = WD_ALIGN_PARAGRAPH.CENTER
set_run(footer.add_run('SPInE User Manual | Page '), size=12)
add_page_field(footer, 'PAGE')
set_run(footer.add_run(' of '), size=12)
add_page_field(footer, 'NUMPAGES')

settings = doc.settings._element
update_fields = OxmlElement('w:updateFields')
update_fields.set(qn('w:val'), 'true')
settings.append(update_fields)

OUT = Path('assets/manuals/english/complete_user_manual_en_editable.docx')
doc.save(OUT)
print('Created editable DOCX:', OUT)