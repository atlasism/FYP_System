from io import BytesIO
from pathlib import Path
from tempfile import TemporaryDirectory
from xml.sax.saxutils import escape

from PIL import Image
from pypdf import PdfReader, PdfWriter
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas
from reportlab.platypus import Flowable, Image as PDFImage, PageBreak, Paragraph, SimpleDocTemplate, Spacer

ROOT = Path(__file__).resolve().parents[3]
OUTPUT = ROOT / 'assets/manuals/english/complete_user_manual_en.pdf'
SCREENSHOTS = ROOT / 'assets/manuals/english/screenshots'
ASSETS = ROOT / 'assets/image'

FONT = Path(r'C:\Windows\Fonts\arial.ttf')
FONT_BOLD = Path(r'C:\Windows\Fonts\arialbd.ttf')
if FONT.exists() and FONT_BOLD.exists():
    pdfmetrics.registerFont(TTFont('ManualSans', str(FONT)))
    pdfmetrics.registerFont(TTFont('ManualSansBold', str(FONT_BOLD)))
    REGULAR, BOLD = 'ManualSans', 'ManualSansBold'
else:
    REGULAR, BOLD = 'Helvetica', 'Helvetica-Bold'

NAVY = colors.HexColor('#123D69')
BLUE = colors.HexColor('#1F63AA')
INK = colors.HexColor('#243247')
MUTED = colors.HexColor('#596579')

styles = getSampleStyleSheet()
styles.add(ParagraphStyle(name='RoleTitle', parent=styles['Title'], fontName=BOLD, fontSize=20, leading=25, textColor=NAVY, spaceAfter=4))
styles.add(ParagraphStyle(name='RoleSub', parent=styles['Normal'], fontName=REGULAR, fontSize=9, leading=13, textColor=MUTED, spaceAfter=7))
styles.add(ParagraphStyle(name='SectionTitle', parent=styles['Heading2'], fontName=BOLD, fontSize=12, leading=16, textColor=BLUE, spaceBefore=9, spaceAfter=4, keepWithNext=True))
styles.add(ParagraphStyle(name='GuideBody', parent=styles['BodyText'], fontName=REGULAR, fontSize=9, leading=13.2, textColor=INK, leftIndent=10, firstLineIndent=-8, spaceAfter=4))
styles.add(ParagraphStyle(name='GuideIntro', parent=styles['BodyText'], fontName=REGULAR, fontSize=9.3, leading=13.5, textColor=INK, spaceAfter=7))
styles.add(ParagraphStyle(name='ContentsTitle', parent=styles['Title'], fontName=BOLD, fontSize=21, leading=26, textColor=NAVY, spaceAfter=5))
styles.add(ParagraphStyle(name='ContentsIntro', parent=styles['Normal'], fontName=REGULAR, fontSize=9, leading=13, textColor=MUTED, spaceAfter=10))
styles.add(ParagraphStyle(name='ContentsHeading', parent=styles['Heading2'], fontName=BOLD, fontSize=11, leading=14, textColor=BLUE, spaceBefore=7, spaceAfter=2, keepWithNext=True))
styles.add(ParagraphStyle(name='ContentsBody', parent=styles['BodyText'], fontName=REGULAR, fontSize=8.8, leading=11.4, textColor=INK, leftIndent=8, spaceAfter=2))
styles.add(ParagraphStyle(name='ScreenshotHeading', parent=styles['Heading2'], fontName=BOLD, fontSize=12, leading=16, textColor=BLUE, spaceAfter=7))
styles.add(ParagraphStyle(name='ScreenshotCaption', parent=styles['BodyText'], fontName=REGULAR, fontSize=8.6, leading=12, textColor=INK, alignment=1, spaceBefore=5))

roles = [
    {
        'key': 'student',
        'toc': 'STUDENT USER GUIDE',
        'title': 'STUDENT USER GUIDE',
        'intro': 'A route-accurate guide to the Student portal: account access, the active project-registration form, document submission, marks, milestones, deadlines, group search, archives, and profile management.',
        'sections': [
            ('1. Sign in and navigate the portal', [
                'Open the SPInE home page and select Login. Enter the IC number and password assigned to your student account. Imported accounts initially use the IC number as the password; change it after signing in.',
                'The Student side menu contains Dashboard, My Project, Upload Documents, Evaluation Marks, Deadline Reminders, Group List, and Past Projects Archive. The top bar contains language, appearance, Home, and Profile controls.',
                'Use Logout when finished, particularly on a shared computer. If login fails, verify the IC number and password and contact Admin rather than creating a second account.'
            ]),
            ('2. Dashboard: understand each status', [
                'Dashboard provides Project Status, Total Score, Project Rank, Document Submission Status, and project summary. Not Registered means no current project is linked to the account. In Progress means the project is not marked complete. Complete & Ready means the project record is marked ready for evaluation.',
                'Total Score and Project Rank appear only when mark data is available. Not Evaluated Yet or a dash means there is no applicable result yet; it does not indicate that the project registration failed.',
                'Document cards show Uploaded or Not Uploaded for the listed rubric components. Use Upload File to open the upload page. Check the project title, session, and supervisor in Project Summary Information.'
            ]),
            ('3. My Project: the active registration route', [
                'Select My Project from the Student menu. The active menu opens register_project.php. If your account already belongs to a project, the registration route redirects to the existing My Project summary.',
                'For a student without a project, the active registration page creates a new project group with the signed-in student as Project Leader. It does not offer a join-existing-project option on this route.',
                'Section A identifies Student #1 as the logged-in leader. Name, IC, track, class, phone, and email are prefilled from the account and read-only for Student #1. Student #2 and Student #3 are optional.',
                'To add an optional member, enter that student\'s registered matric number. Leave the field and wait for the lookup message. The system fills the matching student details. If the number is not found or duplicates someone already chosen, correct it before submission.',
                'Section B requires Project Title, Project Category, Academic Session, and Project Description. Department, program, and course code are fixed to JTMK, JTMK - Information Technology, and DFT50114. The description is limited to 500 characters.',
                'Review all members, title, category, and description. Submit Project Registration once. A successful registration is saved as a project plus member records and redirects to My Project. If an error appears, correct the highlighted information and resubmit.'
            ]),
            ('4. My Profile: update account details', [
                'Open Profile from the top bar. Full Name and Department are read-only. IC Number, Matric Number, and Email can be edited when valid and unique.',
                'Matric numbers accept letters and numbers only. If an IC or matric value is already used by another account, the system rejects the update.',
                'To change the password, enter at least eight characters. Leave it blank to keep the current password. A profile image accepts JPG, PNG, or WEBP up to 2 MB. Select Save Profile and read the success/error message.'
            ]),
            ('5. Upload Documents: categories, rules, and status', [
                'Open Upload Documents. Select a Document Type, choose the file, then select Upload Now. Supported file extensions are PDF, DOCX, and ZIP; the system validates extension, MIME type, and upload size.',
                'The rubric categories are A Proposal Presentation (10%), B Project Demonstration 1 (10%), C Project Demonstration 2 (10%), D Project Demonstration 3 (15%), E Final Presentation - Poster (15%), F Final Presentation (15%), and Technical Report (15%). Use the exact category shown in the dropdown.',
                'A document type can be submitted once for the group. If another member submitted it, the category is disabled and labelled as submitted by a group member. Ask the member who uploaded it for the original copy if needed.',
                'Submitted Documents List shows category, file name, Pending/Approved/Rejected status, upload date, and Download. Pending means awaiting review; Approved means accepted; Rejected means follow up with the supervisor. Download opens the stored file.',
                'If upload fails, check the alert, permitted format, file size, selected category, and whether the group already submitted that type. Do not repeat the upload unless the system confirms the previous attempt failed.'
            ]),
            ('6. Evaluation Marks: Demo status and total marks', [
                'Evaluation Marks displays the supervisor verification status for Demo 1 and Demo 2: Pending, Passed, or Not Passed. Pending means no status has been saved; these are verification results, not numerical grades.',
                'The weekly logbook verification card is no longer shown in the Student view. This page focuses on Demo 1 and Demo 2 milestones.',
                'Numerical Total Score and Project Rank are shown on Dashboard when their data is available. Demo 3 marks are produced by the external panel QR evaluation flow, not by uploading the Demo 3 supporting document.'
            ]),
            ('7. Deadline Reminders', [
                'Deadline Reminders lists the document type, instructions, due date/time, and status. Active means the deadline has not passed; Expired means it has passed; Waiting for Admin means a date is not yet announced.',
                'Check the time as well as the date. A deadline is a reminder and does not automatically assign a numerical mark or Demo status.',
                'If there is no date for a document, check again later or contact Admin. Submit required files before the displayed deadline and confirm they appear in Submitted Documents List.'
            ]),
            ('8. Group List and Past Projects Archive', [
                'Group List is a system-wide project search. Search by title, student name, or matric number. Select Search to apply; select Reset to clear filters. Check the result title and member list before using it as a reference.',
                'Past Projects Archive has keyword and academic-session filters. Search matches project title or description. View Details opens the project record and available reference material.',
                'Archive projects are references, not the logged-in student\'s current project. Use My Project to check current membership.'
            ]),
            ('9. Student troubleshooting checklist', [
                'My Project redirects: the account already belongs to a project; the registration form is intentionally unavailable to existing members.',
                'Member lookup fails: verify the matric number and confirm that the person is a registered JTMK student and is not already selected.',
                'Document option disabled: another member already submitted that rubric category for the group.',
                'Marks are blank: check the registration and wait for the supervisor/panel workflow that owns the result. Contact the supervisor or Admin if the saved group or status is incorrect.'
            ])
        ],
        'screenshots': [
            (1, 'student_dashboard.png', 'Dashboard overview. Student account name is masked.'),
            (4, 'student_upload.png', 'Upload Documents form. Private submitted-file list is hidden.'),
            (5, 'student_milestones.png', 'Evaluation Marks showing Demo 1 and Demo 2 status.')
        ]
    },
    {
        'key': 'supervisor',
        'toc': 'SUPERVISOR USER GUIDE',
        'title': 'SUPERVISOR USER GUIDE',
        'intro': 'A route-accurate guide to the Supervisor portal: dashboard, assigned projects, read-only document review, Demo 1/Demo 2 status, deadlines, past projects, and profile.',
        'sections': [
            ('1. Sign in and navigate the portal', [
                'Sign in with an account assigned the Supervisor role. Confirm the displayed account name before reviewing or updating student records.',
                'The Supervisor side menu contains Dashboard, Supervised Projects, Student Verification, Past Projects, Review Documents, and Deadline Reminders. The top bar provides Home, Profile, language, and appearance controls.',
                'Use Logout when finished, especially on a shared computer.'
            ]),
            ('2. Dashboard and Supervised Projects', [
                'Dashboard shows the number of assigned students and supervised groups, plus a Recent Student Submissions & Groups summary. The summary is limited; use View All or Supervised Projects for the full list.',
                'Supervised Projects lists project title, leader, group members/matric numbers, category, session, and department. Search accepts a project title, student name, or matric number.',
                'Select Search Project to filter. Clear the query and submit again for the full list. If a project is missing, verify the student/group identity and ask Admin to check the supervisor assignment.'
            ]),
            ('3. Review Documents: read-only inspection', [
                'Open Review Documents, then choose a project from the supervised-project list. The page is restricted to groups associated with the signed-in supervisor.',
                'The table displays document category, original file name, Pending/Approved/Rejected status, and upload date. Select View to open a file in a new tab. Use Projects to return to the list.',
                'This page is read-only: it does not save a mark or change document approval status. If there are no files, the table reports no documents submitted.',
                'Review files against the correct project and category. If the file is unreadable or the category is wrong, follow up with the project group through the usual communication channel.'
            ]),
            ('4. Student Verification: Demo 1 and Demo 2', [
                'Open Student Verification. Check the student name, matric number, session, and current Demo 1/Demo 2 statuses.',
                'Select Milestones on the correct student row. The status form is limited to a student assigned to the signed-in supervisor.',
                'Choose Passed or Not Passed for each Demo being updated. Leave a field blank to keep that milestone unchanged. The form rejects a submission when both fields are blank or an invalid status is selected.',
                'Select Save Status and wait for the success message. Return to Student Verification and confirm the new status. These values are milestone statuses, not numerical marks.'
            ]),
            ('5. Marks, logbook, and unavailable routes', [
                'Demo 3 numerical scores are entered by external panel members through an External Panel QR session. They are not entered on Student Verification or Review Documents.',
                'The former week-by-week logbook verification route redirects to Student Verification; no active logbook approval screen is available in the current Supervisor workflow.',
                'The legacy Project Marks / Evaluate Project route may redirect to Student Verification. Do not promise or use a numerical supervisor-mark form through that route in the current system.',
                'Students see Demo 1/Demo 2 status on Evaluation Marks. Their overall numerical Total Score/Rank is a separate result.'
            ]),
            ('6. Past Projects and Deadline Reminders', [
                'Past Projects displays earlier project records for reference. Confirm the session and title before discussing an archived item as a current project.',
                'Deadline Reminders displays published document schedules. Check the due date and exact time; a reminder does not automatically update a milestone status.',
                'If a student is absent from the list or a deadline is unclear, verify the student assignment and ask Admin to check the relevant record.'
            ]),
            ('7. Supervisor Profile', [
                'Open Profile from the top bar. Full Name and Department are read-only. IC/Staff ID and Email can be updated if valid and not duplicated.',
                'A new password must contain at least eight characters; leave it blank to keep the current password. Profile pictures accept JPG, PNG, or WEBP up to 2 MB.',
                'Select Save Profile and check the result message. If an identifier is already in use, resolve the duplicate before retrying.'
            ]),
            ('8. Supervisor troubleshooting', [
                'Student not listed: confirm the matric number, current session, and supervisor assignment; ask Admin to correct the assignment if needed.',
                'Status did not change: confirm the correct student and Demo, select a valid value, save, and check the success/error alert.',
                'Document will not open: return to Review Documents, verify the listed file, and report a broken link to Admin.',
                'An old route redirects: use the active Student Verification and Review Documents routes described above.'
            ])
        ],
        'screenshots': [
            (1, 'supervisor_dashboard.png', 'Supervisor Dashboard with account name masked.'),
            (3, 'supervisor_verification.png', 'Assigned-student list with names and matric numbers masked.'),
            (3, 'supervisor_milestones.png', 'Demo milestone status form with student identity masked.')
        ]
    },
    {
        'key': 'panel',
        'toc': 'EXTERNAL PANEL USER GUIDE',
        'title': 'EXTERNAL PANEL USER GUIDE',
        'intro': 'A full QR-batch workflow for panel registration, group selection, Panel\'s Choice, the eight-criteria rubric, saving marks, and resolving submission errors.',
        'sections': [
            ('1. Open the QR and register as a panel member', [
                'Open the QR/link supplied by Admin. Confirm the page is Panel Evaluation for DFT50114. A QR session contains only its assigned project groups and expires on the displayed expiry date (normally seven days after creation).',
                'On Panel Member Registration, enter Panel Name. Email Address is optional unless the form marks it required. Select Continue to Groups.',
                'A split batch may have a maximum panel-member allocation; a shared QR can be used by all panel members. If the QR is full, expired, or has no groups, contact Admin for the correct link.',
                'Use the same name and email across QR sessions. The system normalizes the identity and blocks a duplicate evaluation of the same project across different QR batches.'
            ]),
            ('2. Group List and Panel\'s Choice', [
                'Group List contains the groups assigned to the current QR. Review the project title and members before selecting Assess Group.',
                'The star toggles Panel\'s Choice for this QR batch. A selected star is highlighted; select it again to remove the choice. It is independent from marks.',
                'A Complete badge means an evaluation for your panel identity is already saved. You cannot submit another evaluation for the same group using the same name and email, even via another QR.',
                'If a group is missing, check that the correct QR is open. Do not submit scores against a different group to compensate.'
            ]),
            ('3. Navigate the evaluation form', [
                'Before scoring, verify the project title, supervisor, student names, and matric numbers. Cancel and return to Group List if the wrong project is open.',
                'The form contains eight criteria. For each criterion, choose 1, 2, 3, or 4 for every student in the group. Every student/criterion pair must have a score.',
                'Use Criteria 1–8 tabs, Previous, and Next Criteria. The progress indicator shows completed criteria. You can revisit a completed tab and change a score before submission.',
                'Read the rubric descriptions for Very Good (4), Good (3), Fair (2), and Weak (1) under each criterion. Use comments for context; comments do not replace required scores.'
            ]),
            ('4. Eight criteria and score calculation', [
                'Project achievement and objective: completion and achievement of stated objectives.',
                'User Requirements: whether the project meets the identified requirements.',
                'Construction and functionality: how the system was built and how it operates.',
                'Feasibility: practicality of construction and implementation.',
                'Originality: whether the idea is genuine and not copied.',
                'Marketability: evidence such as surveys, clients, laboratory tests, MyIPO, or testimonials.',
                'Creativity: originality and inventiveness of the ideas.',
                'System Security, Features and Testing: user controls, validation, system features, and evidence of testing.',
                'Each criterion contributes 12.5 points to each student\'s total out of 100. Demo 3 out of 15 is calculated as total/100 x 15. The system stores individual marks and computes the group ranking inputs.'
            ]),
            ('5. Save and finish the QR batch', [
                'Before submission, confirm all eight criteria are filled for every student and the displayed group is correct.',
                'Enter optional Comments / Feedback, then select Done - Save Scores once. Wait for Scores saved or read any validation error.',
                'Return to Group List and check that the group shows Complete. Continue with each remaining group in the QR.',
                'When all assigned groups are complete, the system shows Evaluation Complete or a Done button. Select Done if displayed.'
            ]),
            ('6. Panel troubleshooting', [
                'Missing score error: revisit each of the eight criteria and ensure a value is selected for every student.',
                'Already assessed message: a matching name/email already evaluated the project. Do not change the name/email to bypass the duplicate safeguard; contact Admin if the saved identity is incorrect.',
                'No QR groups or expired link: request a current QR from Admin. QR links should only be shared with their assigned panel members.',
                'Scores saved but no ranking row: ranking appears after the evaluation is stored and the selected QR batch is processed. Return to Group List and verify Complete.'
            ])
        ],
        'screenshots': [
            (0, 'panel_registration.png', 'Live Panel Member Registration page; no panel name, email, or scores were submitted.')
        ]
    },
    {
        'key': 'admin',
        'toc': 'ADMIN USER GUIDE',
        'title': 'ADMIN USER GUIDE',
        'intro': 'A detailed operating guide for the Admin Panel: dashboards, JTMK user records, student imports, supervisor assignments, approvals, QR setup, ranking/report interpretation, settings, and profile safety.',
        'sections': [
            ('1. Access and navigate the Admin Panel', [
                'Sign in with an account assigned the Admin role. The Admin Panel is restricted to authorized administrators.',
                'The sidebar opens Dashboard, Manage Users, Manage Projects, External Panel QR, Reports, and Settings. Logout is at the bottom. The sidebar toggle collapses it on desktop and opens it on smaller screens.',
                'The top bar provides language and appearance controls, Home, signed-in account, and Profile. Confirm you are operating within JTMK | DFT50114 before making updates.'
            ]),
            ('2. Dashboard overview', [
                'Dashboard shows counts for JTMK students, supervisors/lecturers, and registered projects. Overall Project Status separates Draft, Submitted, and Approved.',
                'Top 5 Project Ranking displays overall panel-mark results when data exists. Select View Full Ranking to open the batch-based report.',
                'Panel\'s Choices shows the current global count and links to its report. These dashboard values are summaries; use the dedicated Admin pages for changes.'
            ]),
            ('3. Manage JTMK users and import students', [
                'Find Student searches by student name, IC number, or matric number. Enter at least two characters. The lookup can show up to 20 matches; refine a broad query.',
                'Import Student JTMK accepts CSV or XLSX files up to 5 MB. The header row must include Name, IC No, Matric No, Session, and Department.',
                'Only rows whose Department value is JTMK are imported; other departments are ignored. Missing name/IC/matric/session, non-alphanumeric matric numbers, duplicates in the file, or existing IC/matric/generated email cause a row to be skipped.',
                'After import, read the summary and skipped-row details. If migration/setup errors are reported, verify student_import_migration.sql is installed before repeating the import.',
                'Imported student usernames and initial passwords use the IC number. Passwords are stored as hashes. Advise students to change the initial password and protect the source spreadsheet because it contains identity data.',
                'The student table is grouped by academic session. Edit Student allows account fields to be updated; full name is read-only, matric must be alphanumeric/unique, and password resets require at least eight characters.',
                'Delete asks for confirmation and is blocked if the student is linked to a project, project_members, or supervisor assignment. The Lecturers / Supervisors table is read-only on this page; it does not create supervisor accounts.'
            ]),
            ('4. Search, edit, and protect student records', [
                'Use Find Student for a quick lookup without scrolling the full session-grouped list. Wait for results; refine the search if more than 20 matches are returned.',
                'Before editing, compare the student name, IC, matric, email, and academic session. Save only verified corrections.',
                'If Delete is blocked, inspect the project/group/supervisor relationship and resolve it through the appropriate Admin workflow instead of attempting a direct database deletion.'
            ]),
            ('5. Manage projects, assign supervisors, approve submissions', [
                'Manage Projects lists JTMK DFT50114 project title, category, session, status, group members, current supervisor, and individual Demo 1/Demo 2 status.',
                'To assign a supervisor, select a JTMK Supervisor on the project card and choose Assign SV. The assignment updates the project and all group-member supervisor assignments. Confirm the group and selected supervisor before submitting.',
                'Only Submitted projects show Approve. Review title, members, session, and status, then choose Approve and confirm the prompt. The system changes Submitted to Approved and the dashboard count updates after reload.',
                'Draft projects cannot be approved here. If the project status changed before approval, the conditional update rejects it; reload and check the current status.',
                'The milestone table is status monitoring for Demo 1 and Demo 2. It does not contain the external panel Demo 3 scoring rubric.'
            ]),
            ('6. Create shared or split External Panel QR batches', [
                'External Panel QR uses the latest academic-session groups with assigned group numbers. Confirm the current group list before generating a link.',
                'Generate QR creates one shared QR for all groups. The shared QR has no fixed seat count and is normally valid for seven days.',
                'For split QR batches, open Set up split QR batches. Enter Total panel members and Number of QR batches. Set a Groups count and Panel members count for every batch; the live preview lists included group numbers.',
                'Each split QR must contain at least one group and at least two panel members. Group counts must add up exactly to all groups. Panel assignments must cover the total panel count; one panel member may be assigned to multiple batches.',
                'Check the group total and panel-assignment total before Generate Split QRs. The system creates a different session/token for each batch and displays QR images and links.',
                'Copy/share only the correct batch link with the assigned panel members. Do not publish tokens. If generation fails, verify group counts, panel counts, current groups, and database migrations; retry only after correcting validation errors.'
            ]),
            ('7. Reports, rankings, and panel results', [
                'Reports summarizes Projects by Category, Project Registration Status, and Demo Verification Summary for Demo 1 and Demo 2.',
                'Project Ranking by QR Batch lists batches with evaluation marks. The newest evaluated batch is selected by default. Choose another date/time and press Show Ranking to inspect a different batch.',
                'A batch with no submitted scores has no ranked groups. Newly generated QR sessions do not rank until panel evaluations are saved. Use the batch date/time and submission count to understand an empty result.',
                'Panel\'s Choices reports stars saved for the most recently evaluated QR session. Star choices are independent from marks and should not be interpreted as a numerical ranking.',
                'External Panel Demo 3 Evaluations lists the latest generated QR groups and available submissions. Open Panel breakdown for assessor, individual student marks, criterion ratings, comments, and calculation details.'
            ]),
            ('8. Fixed Settings and Admin Profile', [
                'Settings is read-only. It displays fixed system name, department/program, DFT50114 course, project scope, and assessment model. Account, project, and supervisor information are managed from their respective Admin pages.',
                'Open Profile from the top bar. IC number and email can be updated if valid and not used by another account. Full Name is read-only.',
                'A changed password must be at least eight characters. Leave it blank to keep the current password. Profile photos accept JPG, PNG, or WEBP up to 2 MB. Select Save Profile and check the result message.'
            ]),
            ('9. Admin safety and troubleshooting', [
                'Use confirmation dialogs for project approval, student deletion, and logout. Do not remove accounts that still have project or assignment dependencies.',
                'Keep CSV/XLSX imports, IC information, passwords, and panel QR URLs private. Screenshots shared outside the Admin role should mask student names and IDs.',
                'Import errors: verify header names, allowed file type/size, JTMK department value, required fields, matric format, and duplicates.',
                'QR errors: verify all project groups are allocated, each split batch has at least two panel members, and assignment totals cover the configured panel count.',
                'Empty ranking: choose a batch with submitted evaluations. QR generation alone does not create score records or ranks.'
            ])
        ],
        'screenshots': [
            (1, 'admin_dashboard.png', 'Admin Dashboard: student/supervisor/project totals, registration status, ranking and panel-choice summary.'),
            (2, 'admin_manage_users.png', 'Manage Users: search and import tools. Identity values are masked.'),
            (4, 'admin_manage_projects.png', 'Manage Projects: group details, supervisor assignment, milestones, and submission approval. Identity values are masked.'),
            (5, 'admin_panel_qr.png', 'External Panel QR: shared access and split-batch configuration. No QR is generated in this example.'),
            (6, 'admin_ranking_report.png', 'Reports: QR-batch ranking filter. Project values are masked.'),
            (7, 'admin_settings.png', 'Settings: fixed system configuration.')
        ]
    }
]

LOGO_SYSTEM = ASSETS / 'logosistem.png'
LOGO_INSTITUTE = ASSETS / 'logo.png'

def draw_header(c, doc):
    height, gap = 25, 8
    width_a, width_b = height * 621 / 497, height * 1615 / 597
    x, y = (A4[0] - width_a - gap - width_b) / 2, A4[1] - 38
    c.drawImage(ImageReader(str(LOGO_SYSTEM)), x, y, width=width_a, height=height, mask='auto', preserveAspectRatio=True)
    c.drawImage(ImageReader(str(LOGO_INSTITUTE)), x + width_a + gap, y, width=width_b, height=height, mask='auto', preserveAspectRatio=True)

with TemporaryDirectory() as temp_dir:
    temp_dir = Path(temp_dir)
    role_pdfs = []
    page_number = 2
    for role in roles:
        role_pdf = temp_dir / f'{role["key"]}.pdf'
        document = SimpleDocTemplate(str(role_pdf), pagesize=A4, leftMargin=20*mm, rightMargin=20*mm, topMargin=18*mm, bottomMargin=18*mm, title=role['title'], author='SPInE Politeknik Besut')
        story = [Paragraph('SPInE STUDENT PROJECT SYSTEM', styles['RoleSub']), Spacer(1, 1*mm), Paragraph(role['title'], styles['RoleTitle']), Paragraph('JTMK | DFT50114', styles['RoleSub']), Paragraph(escape(role['intro']), styles['GuideIntro'])]
        screenshots_by_section = {}
        for section_index, filename, caption in role['screenshots']:
            screenshots_by_section.setdefault(section_index, []).append((filename, caption))

        def append_screenshot(filename, caption):
            screenshot = SCREENSHOTS / filename
            if not screenshot.exists():
                raise FileNotFoundError(screenshot)
            story.append(PageBreak())
            story.append(Paragraph(f'{role["title"]} | ACTUAL SYSTEM SCREENSHOT', styles['ScreenshotHeading']))
            with Image.open(screenshot) as image:
                image_width, image_height = image.size
            max_width, max_height = A4[0] - 40*mm, A4[1] - 85*mm
            scale = min(max_width/image_width, max_height/image_height)
            story.append(PDFImage(str(screenshot), width=image_width*scale, height=image_height*scale))
            story.append(Paragraph(escape(caption), styles['ScreenshotCaption']))

        for section_index, (heading, instructions) in enumerate(role['sections']):
            story.append(Paragraph(escape(heading), styles['SectionTitle']))
            story.extend(Paragraph('&bull; ' + escape(instruction), styles['GuideBody']) for instruction in instructions)
            for filename, caption in screenshots_by_section.get(section_index, []):
                append_screenshot(filename, caption)
        for filename, caption in screenshots_by_section.get(-1, []):
            append_screenshot(filename, caption)
        document.build(story, onFirstPage=draw_header, onLaterPages=draw_header)
        reader = PdfReader(str(role_pdf))
        role_start, role_end = page_number, page_number + len(reader.pages) - 1
        screenshot_pages = []
        for offset, page in enumerate(reader.pages):
            if 'ACTUAL SYSTEM SCREENSHOT' in (page.extract_text() or ''):
                screenshot_pages.append(role_start + offset)
        role['page_start'], role['page_end'] = role_start, role_end
        role['screenshot_pages'] = screenshot_pages
        role_pdfs.append((role, reader))
        page_number = role_end + 1
    total_pages = page_number - 1

    toc_pdf = temp_dir / 'contents.pdf'
    toc_doc = SimpleDocTemplate(str(toc_pdf), pagesize=A4, leftMargin=24*mm, rightMargin=24*mm, topMargin=22*mm, bottomMargin=18*mm, title='Table of Contents')
    toc_story = [Paragraph('TABLE OF CONTENTS', styles['ContentsTitle']), Paragraph('SPInE Student Project System | JTMK | DFT50114', styles['ContentsIntro'])]
    for role in roles:
        toc_story.append(Paragraph(escape(role['toc']), styles['ContentsHeading']))
        short_description = {
            'student': 'Registration, account, files, marks, milestones, deadlines, groups, archive',
            'supervisor': 'Projects, document review, Demo status, profile and workflow limits',
            'panel': 'QR registration, stars, rubric, scoring, submission and duplicate checks',
            'admin': 'Users, projects, approvals, QR batches, reports, settings and profile'
        }[role['key']]
        toc_story.append(Paragraph(f'{escape(short_description)} <b>................................ {role["page_start"]}</b>', styles['ContentsBody']))
        screenshot_list = ', '.join(map(str, role['screenshot_pages'])) or 'none'
        toc_story.append(Paragraph(f'Actual system screenshots <b>....................... {screenshot_list}</b>', styles['ContentsBody']))
    toc_doc.build(toc_story, onFirstPage=draw_header, onLaterPages=draw_header)
    toc_reader = PdfReader(str(toc_pdf))
    if len(toc_reader.pages) != 1:
        raise RuntimeError('Table of contents unexpectedly spans multiple pages.')

    writer = PdfWriter()
    writer.add_page(toc_reader.pages[0])
    for _, reader in role_pdfs:
        for page in reader.pages:
            writer.add_page(page)

    def footer_overlay(number):
        buffer = BytesIO()
        c = canvas.Canvas(buffer, pagesize=A4)
        c.setFillColor(colors.white)
        c.rect(0, 0, A4[0], 45, fill=1, stroke=0)
        c.setStrokeColor(colors.HexColor('#D8E1EA'))
        c.line(18*mm, 40, A4[0]-18*mm, 40)
        c.setFont(REGULAR, 7.5)
        c.setFillColor(MUTED)
        c.drawString(18*mm, 23, 'SPInE Student Project System | Politeknik Besut | DFT50114')
        page_label = f'Page {number} of {total_pages}'
        c.drawRightString(A4[0]-18*mm, 23, page_label)
        c.save()
        buffer.seek(0)
        return PdfReader(buffer).pages[0]

    for index, page in enumerate(writer.pages, 1):
        page.merge_page(footer_overlay(index))
    for role in roles:
        writer.add_outline_item(role['toc'].title(), role['page_start']-1)
    writer.add_metadata({'/Title':'Complete SPInE System User Manual', '/Author':'SPInE Politeknik Besut', '/Subject':'Detailed English workflows for Student, Supervisor, External Panel, and Admin with real system screenshots'})
    temporary_output = OUTPUT.with_suffix('.tmp.pdf')
    with temporary_output.open('wb') as stream:
        writer.write(stream)
    temporary_output.replace(OUTPUT)

print(f'Created {OUTPUT} with {total_pages} pages')
for role in roles:
    print(role['toc'], role['page_start'], '-', role['page_end'])
