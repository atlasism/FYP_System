from io import BytesIO
from pathlib import Path
from tempfile import TemporaryDirectory
from xml.sax.saxutils import escape
from pypdf import PdfReader, PdfWriter
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.utils import ImageReader
from reportlab.pdfgen import canvas
from reportlab.platypus import SimpleDocTemplate, Paragraph, PageBreak

BASE = Path(__file__).resolve().parents[3]
PDF = BASE / 'assets/manuals/english/complete_user_manual_en.pdf'
LOGO_DIR = BASE / 'assets/image'

NAVY = colors.HexColor('#123D69')
BLUE = colors.HexColor('#1F63AA')
INK = colors.HexColor('#243247')
MUTED = colors.HexColor('#596579')
styles = getSampleStyleSheet()
styles.add(ParagraphStyle(name='AppendixTitle', parent=styles['Title'], fontName='Helvetica-Bold', fontSize=14, leading=18, textColor=NAVY, spaceAfter=3))
styles.add(ParagraphStyle(name='AppendixSub', parent=styles['Normal'], fontName='Helvetica', fontSize=8, leading=11, textColor=MUTED, spaceAfter=6))
styles.add(ParagraphStyle(name='AppendixHead', parent=styles['Heading2'], fontName='Helvetica-Bold', fontSize=9.5, leading=12, textColor=BLUE, spaceBefore=5, spaceAfter=2, keepWithNext=True))
styles.add(ParagraphStyle(name='AppendixBody', parent=styles['BodyText'], fontName='Helvetica', fontSize=7.8, leading=10.1, textColor=INK, leftIndent=8, firstLineIndent=-6, spaceAfter=2.2))

# Each page adds operational details for one role without replacing the core guide or screenshots.
PAGES = [
('STUDENT USER GUIDE | REGISTRATION AND ACCOUNT DETAILS', [
('Before you register', [
'Use My Project only when your account is not already linked to a project. If the page redirects to the existing project summary, registration is already complete for that account.',
'The active registration form creates a new group with the signed-in student as Leader. It does not provide a Join Existing Project action. Do not use older instructions that describe joining through this screen.',
'Prepare a unique project title, choose the closest JTMK IT category, confirm the academic session, and prepare a description of no more than 500 characters before submitting.'
]),
('Add optional members', [
'Student #1 is fixed to the logged-in account. Student #2 and #3 are optional; leave unused member fields empty.',
'Enter a registered JTMK matric number and leave the field. Wait for the lookup result before continuing. On success, check the returned name, IC, track, class, phone, and email.',
'If lookup fails, verify the matric number and ask Admin whether the account exists. A student cannot be added twice or belong to two project groups.'
]),
('Submit and verify', [
'Complete all required project fields, tick the originality declaration, and submit once. The system validates the session token, title, category, session, description, and group members.',
'A successful save creates the project and member-role records together and returns to My Project. Verify the title, category, leader/member roles, and all intended members.',
'If submission fails, read the alert and correct the indicated field. Do not retry repeatedly when the success redirect has already happened.'
])]),
('STUDENT USER GUIDE | DOCUMENTS AND PROGRESS DETAILS', [
('Upload decision checklist', [
'Choose the exact rubric category first, then select a PDF, DOCX, or ZIP file within the displayed limit. The server checks both file extension and MIME type.',
'Each rubric category is submitted once for the group. A disabled category means another member already submitted it; coordinate with that member instead of trying another category.',
'After upload, check for the success alert and a row in Submitted Documents List. Pending awaits review, Approved is accepted, and Rejected needs follow-up. Download retrieves the stored file and does not replace it.',
'Rubric components are Proposal 10%, Demo 1 10%, Demo 2 10%, Demo 3 15%, Poster 15%, Final Presentation 15%, and Technical Report 15%. Uploading a file alone does not award marks.'
]),
('Statuses, deadlines, and searches', [
'Evaluation Marks currently shows Demo 1 and Demo 2 verification only. Pending means no result saved; Passed/Not Passed are milestone outcomes, not numerical grades. The former logbook card is removed.',
'Dashboard Total Score/Rank appears when marks are recorded. Demo 3 marks come from the panel QR flow, separately from the student document upload.',
'Deadline Reminders: Active is open, Expired is past due, Waiting for Admin has no announced date. Check exact date and time; reminders do not automatically change marks.',
'Group List searches the system by project title, student name, or matric. Reset clears filters. Past Projects Archive filters by keyword/session and opens View Details; archive entries are references, not the current project.'
])]),
('SUPERVISOR USER GUIDE | ASSIGNMENT AND DOCUMENT REVIEW', [
('Find the correct assigned project', [
'Dashboard counts and Recent Student Submissions & Groups are summaries. Open Supervised Projects for the full assigned list.',
'Search by title, student name, or matric. Confirm the session, leader, group members, category, and department before reviewing.',
'If a project is missing, do not borrow another supervisor account. Ask Admin to verify the project leader/member and supervisor assignment.'
]),
('Review files without changing their status', [
'Open Review Documents, select a supervised project, and check category, original filename, Pending/Approved/Rejected status, and upload date.',
'Select View to open the stored file in a new tab. Return to Projects to inspect another group. No documents submitted means the project has no available upload rows.',
'This screen is read-only: viewing a file does not approve/reject it, save marks, or update Demo status. Report a broken file link to Admin.'
]),
('Understand the available marking routes', [
'Student Verification controls Demo 1 and Demo 2 statuses. Demo 3 numerical marks are entered by an external panel through QR.',
'The legacy Project Marks/Evaluate Project route may redirect to Student Verification; it is not an active numerical supervisor-marking route.',
'The old Verify Logbook route redirects to Student Verification. No weekly logbook approval screen is available in the current Supervisor workflow.'
])]),
('SUPERVISOR USER GUIDE | MILESTONE, PROFILE, AND RECOVERY', [
('Save a milestone status', [
'In Student Verification, confirm student name, matric number, and session, then select Milestones on that student row.',
'Choose Passed or Not Passed for Demo 1 and/or Demo 2. A blank field means keep that status unchanged. At least one valid status must be selected before Save Status succeeds.',
'Wait for the success/error message, return to the list, and confirm the updated value. These statuses are verification only, never numerical grades.'
]),
('Profile and other menu pages', [
'Profile permits a unique IC/Staff ID and valid email update. Full Name and Department are read-only. New passwords require eight characters; leave blank to retain the current password.',
'Profile pictures must be JPG/PNG/WEBP and at most 2 MB. Save and check the result alert.',
'Past Projects and Deadline Reminders are reference pages. A deadline does not automatically mark a Demo as Passed.'
]),
('When something goes wrong', [
'Student missing: confirm matric/session and ask Admin to check the supervisor link. Status unchanged: verify the student row and selected Demo, save, then check the alert.',
'Document missing: confirm the correct project and category. Legacy page redirects are expected for unavailable workflows; report them rather than recording a substitute status.'
])]),
('EXTERNAL PANEL USER GUIDE | QR, IDENTITY, AND GROUP SELECTION', [
('Register for a QR session', [
'Open the Admin-provided QR/link and confirm Panel Evaluation and DFT50114. Each QR maps to specific projects and normally expires after seven days.',
'Panel Name is required; Email Address is optional unless the form says otherwise. Continue to Groups opens the group list. Split QR sessions can have a seat limit; shared sessions are open to assigned panel members.',
'Use the same name/email consistently. The system blocks the same normalized identity from scoring the same project again through a different QR.'
]),
('Select a project and favourite', [
'Group List contains only projects mapped to that QR. Check title and members, then choose Assess Group.',
'Complete means an evaluation by this panel identity already exists. Do not attempt a duplicate assessment.',
'The star toggles Panel\'s Choice for the current QR session only. Star selection is separate from scoring: neither action automatically performs the other.'
])]),
('EXTERNAL PANEL USER GUIDE | RUBRIC, SUBMISSION, AND RECOVERY', [
('Score all students consistently', [
'Before scoring, confirm project title, supervisor, student names, and matric numbers. Each of eight criteria needs a score 1–4 for every student.',
'Criteria: project achievement/objective; user requirements; construction/functionality; feasibility; originality; marketability; creativity; system security/features/testing.',
'Use the on-screen level descriptions: 4 Very Good, 3 Good, 2 Fair, 1 Weak. Each criterion is 12.5 points of total /100; Demo 3 /15 is calculated as total/100 x 15.',
'Navigate with Criteria tabs, Previous, and Next Criteria. The progress indicator shows incomplete areas. Comments are optional and cannot replace missing scores.'
]),
('Save and complete the batch', [
'Check all 8 criteria for every student and confirm the selected group. Choose Done - Save Scores once and wait for Scores saved.',
'Return to Group List and confirm Complete before moving to the next project. At batch end, use Done/Evaluation Complete if shown.',
'Missing score: return to incomplete criteria. Already assessed: the normalized identity has scored this project; contact Admin if identity records need checking. Expired/full QR or missing groups: request the correct current link.',
'If the browser closes after submission, reopen the QR and check Complete first. A QR being generated does not mean marks exist; ranking appears after evaluations are saved.'
])]),
('ADMIN USER GUIDE | IMPORT AND USER ACCOUNT CONTROL', [
('Prepare and validate an import', [
'Manage Users accepts CSV/XLSX up to 5 MB with Name, IC No, Matric No, Session, Department headers. Only Department=JTMK imports; other departments are ignored.',
'Rows are skipped for missing name/IC/matric/session, invalid non-alphanumeric matric, duplicate IC/matric inside the file, or an existing IC/matric/generated email. Read the summary and skipped-row list.',
'Imported username and initial password are the IC number. Tell students to change it at first login; store and transfer the source file securely.',
'Find Student requires at least two characters and returns at most 20 matches. Edit Student permits IC/matric/email/session and optional password reset; full name is read-only.'
]),
('Deletion and supervisor directory', [
'Delete asks for confirmation and is blocked if the student has a project, group membership, or supervisor assignment. Resolve relationships through the appropriate workflow; do not bypass database dependencies.',
'Lecturers/Supervisors is a directory display on Manage Users. This page does not create supervisor accounts.',
'After edits/imports, verify the displayed account/session and check the success/error result before moving to another record.'
])]),
('ADMIN USER GUIDE | PROJECT APPROVAL AND SUPERVISOR ASSIGNMENT', [
('Assign a supervisor to a whole group', [
'Manage Projects cards show project title/category/session, status, members, current supervisor, and individual Demo 1/Demo 2 states.',
'Select a JTMK Supervisor and press Assign SV. The change applies to the project and its members. Confirm the correct group and supervisor, then verify Current Supervisor after success.',
'If assignment fails, confirm the project is JTMK DFT50114 and the selected account has Supervisor role and JTMK department.'
]),
('Approve a submitted project', [
'Approve appears only for Submitted projects. Check the group, title, and session, then select Approve and confirm the prompt.',
'The status moves from Submitted to Approved. Draft projects cannot be approved here. A stale status or invalid form token is rejected; reload and check current state.',
'The milestone table is Demo 1/Demo 2 verification. It is not the external panel Demo 3 scoring form.'
])]),
('ADMIN USER GUIDE | QR GENERATION AND REPORTS', [
('Shared and split QR rules', [
'External Panel QR loads numbered groups from the latest project session. Confirm the group list/session before generation.',
'Shared mode creates one QR for all groups. Split mode requires total panel members, QR batch count, groups per batch, and panel members per batch.',
'Every split batch needs at least one group and two panel members. Group counts must equal all current groups; assignments must cover all panel members. A panel may be assigned to multiple batches.',
'Review the live included-group preview and totals before Generate Split QRs. Each generated link/token is normally valid for seven days. Share only with assigned panel members.'
]),
('Read Reports correctly', [
'Project Ranking by QR Batch lists sessions with evaluation marks, newest evaluation first. Select a date/time and Show Ranking to filter the results.',
'No submitted scores means no ranked groups. QR generation alone creates no marks. Panel\'s Choices are stars saved to a QR session, separate from marks.',
'External Panel Demo 3 Evaluations shows projects for the latest generated QR and available submissions. Panel breakdown reveals assessor, criteria, student marks, and feedback.'
])]),
('ADMIN USER GUIDE | SETTINGS, PROFILE, AND SAFETY', [
('Fixed settings and profile', [
'Settings is read-only and summarizes SPInE, JTMK Information Technology, DFT50114 Integrated Project, project scope, and assessment model.',
'Admin Profile permits IC/email changes if unique; full name and department are read-only. New passwords require eight characters. Leave blank to retain the current password.',
'Profile images accept JPG/PNG/WEBP up to 2 MB. Save and check the success/error message.'
]),
('Operational safety checklist', [
'Confirm session/group/supervisor before assigning or approving. Review the Submitted state before approval and use confirmation prompts for approval/deletion/logout.',
'Protect imports, IC numbers, credentials, and QR tokens. Do not expose live tokens in screenshots or public manuals.',
'For import issues check headers/type/size/required fields/duplicates. For QR issues check all group totals and minimum panel counts. For empty rankings verify that the selected QR has saved evaluations.'
])])
]

with TemporaryDirectory() as temporary:
    temporary = Path(temporary)
    appendix_pages=[]
    for page_title, subsections in PAGES:
        buffer=BytesIO()
        doc=SimpleDocTemplate(buffer,pagesize=A4,leftMargin=17*mm,rightMargin=17*mm,topMargin=19*mm,bottomMargin=17*mm)
        story=[Paragraph(page_title,styles['AppendixTitle']),Paragraph('DETAILED WORKFLOW NOTES | JTMK | DFT50114',styles['AppendixSub'])]
        for heading,bullets in subsections:
            story.append(Paragraph(escape(heading),styles['AppendixHead']))
            story.extend(Paragraph('&bull; '+escape(bullet),styles['AppendixBody']) for bullet in bullets)
        doc.build(story)
        buffer.seek(0)
        reader=PdfReader(buffer)
        if len(reader.pages)!=1: raise RuntimeError(f'Appendix overflow: {page_title} uses {len(reader.pages)} pages')
        appendix_pages.append(reader.pages[0])

    # Current core: TOC + Student 2-7 + Supervisor 8-13 + Panel 14-16 + Admin 17-23.
    source=PdfReader(str(PDF))
    if len(source.pages)!=23: raise RuntimeError(f'Expected 23 core pages, found {len(source.pages)}')
    placements={7:[0,1],13:[2,3],16:[4,5],23:[6,7,8]}
    appendix_refs={}
    content_sequence=[]
    new_page_number=2
    role_starts={'student':2,'supervisor':None,'panel':None,'admin':None}
    for original_number in range(2,24):
        if original_number == 8:
            role_starts['supervisor']=new_page_number
        elif original_number == 14:
            role_starts['panel']=new_page_number
        elif original_number == 17:
            role_starts['admin']=new_page_number
        content_sequence.append(source.pages[original_number-1])
        new_page_number+=1
        if original_number in placements:
            key={7:'student',13:'supervisor',16:'panel',23:'admin'}[original_number]
            appendix_refs[key]=[]
            for appendix_index in placements[original_number]:
                appendix_refs[key].append(new_page_number)
                content_sequence.append(appendix_pages[appendix_index])
                new_page_number+=1

    total=new_page_number-1
    # Rebuild TOC page with real page numbers after inserted details.
    toc_styles=getSampleStyleSheet()
    toc_styles.add(ParagraphStyle(name='LongTOCTitle',parent=toc_styles['Title'],fontName='Helvetica-Bold',fontSize=20,leading=25,textColor=colors.HexColor('#123D69'),spaceAfter=6))
    toc_styles.add(ParagraphStyle(name='LongTOCIntro',parent=toc_styles['Normal'],fontName='Helvetica',fontSize=9,leading=13,textColor=colors.HexColor('#596579'),spaceAfter=8))
    toc_styles.add(ParagraphStyle(name='LongTOCHead',parent=toc_styles['Heading2'],fontName='Helvetica-Bold',fontSize=10.5,leading=13,textColor=colors.HexColor('#1F63AA'),spaceBefore=5,spaceAfter=1.5,keepWithNext=True))
    toc_styles.add(ParagraphStyle(name='LongTOCBody',parent=toc_styles['BodyText'],fontName='Helvetica',fontSize=8.2,leading=10.2,textColor=colors.HexColor('#243247'),leftIndent=8,spaceAfter=1))
    toc_buffer=BytesIO()
    toc_doc=SimpleDocTemplate(toc_buffer,pagesize=A4,leftMargin=24*mm,rightMargin=24*mm,topMargin=20*mm,bottomMargin=17*mm)
    toc_story=[Paragraph('TABLE OF CONTENTS',toc_styles['LongTOCTitle']),Paragraph('SPInE Student Project System | JTMK | DFT50114',toc_styles['LongTOCIntro'])]
    info=[('student','STUDENT USER GUIDE','Registration, account, documents, marks, deadlines, groups',2),('supervisor','SUPERVISOR USER GUIDE','Assigned projects, document review, milestones, profile',10),('panel','EXTERNAL PANEL USER GUIDE','QR registration, rubric, scoring and submission',17),('admin','ADMIN USER GUIDE','Users, projects, QR batches, reports, settings, profile',22)]
    for key,title,description,start in info:
        detail_pages=appendix_refs[key]
        screenshot_token={'student':'STUDENT | ACTUAL SYSTEM SCREENSHOT','supervisor':'SUPERVISOR | ACTUAL SYSTEM SCREENSHOT','panel':'EXTERNAL PANEL | ACTUAL SYSTEM SCREENSHOT','admin':'ADMIN | ACTUAL SYSTEM SCREENSHOT'}[key]
        screenshots=', '.join(str(index+2) for index,page in enumerate(content_sequence) if screenshot_token in (page.extract_text() or ''))
        detail_range=f'{detail_pages[0]}-{detail_pages[-1]}'
        toc_story.append(Paragraph(title,toc_styles['LongTOCHead']))
        toc_story.append(Paragraph(f'{description} <b>........................ {start}</b>',toc_styles['LongTOCBody']))
        toc_story.append(Paragraph(f'Workflow detail/checklists <b>.................... {detail_range}</b>',toc_styles['LongTOCBody']))
        toc_story.append(Paragraph(f'Screenshots near related steps <b>................ {screenshots}</b>',toc_styles['LongTOCBody']))
    toc_doc.build(toc_story)
    toc_buffer.seek(0)
    toc_reader=PdfReader(toc_buffer)
    if len(toc_reader.pages)!=1: raise RuntimeError('TOC overflowed beyond one page')
    writer=PdfWriter()
    writer.add_page(toc_reader.pages[0])
    for page in content_sequence:
        writer.add_page(page)

    def footer_overlay(number):
        buffer=BytesIO();c=canvas.Canvas(buffer,pagesize=A4)
        c.setFillColor(colors.white);c.rect(0,0,A4[0],46,fill=1,stroke=0)
        c.setStrokeColor(colors.HexColor('#D8E1EA'));c.line(18*mm,41,A4[0]-18*mm,41)
        c.setFillColor(colors.HexColor('#596579'));c.setFont('Helvetica',7.5)
        c.drawString(18*mm,24,'SPInE Student Project System | Politeknik Besut | DFT50114')
        label=f'Page {number} of {total}';c.drawRightString(A4[0]-18*mm,24,label);c.save();buffer.seek(0)
        return PdfReader(buffer).pages[0]
    for number,page in enumerate(writer.pages,1): page.merge_page(footer_overlay(number))
    for key,title,_,_ in info: writer.add_outline_item(title.title(),role_starts[key]-1)
    writer.add_metadata({'/Title':'Complete SPInE System User Manual - Detailed Edition','/Author':'SPInE Politeknik Besut','/Subject':'Detailed English workflows and actual screenshots for Student, Supervisor, External Panel, and Admin'})
    temp=PDF.with_suffix('.expanded.tmp.pdf')
    with temp.open('wb') as output: writer.write(output)
    temp.replace(PDF)
    print('Expanded PDF:',PDF,'pages:',total,'details:',appendix_refs)