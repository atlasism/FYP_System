(function () {
    const isPortal = document.body.classList.contains('user-portal');
    const isHome = document.body.classList.contains('home-page');
    if (!isPortal && !isHome) return;
    document.documentElement.classList.add('portal-scroll');

    const storageKey = 'spine-user-preferences';
    const translations = {
        en: {
            'Dashboard': 'Dashboard',
            'My Project': 'My Project',
            'Upload Documents': 'Upload Documents',
            'Evaluation Marks': 'Evaluation Marks',
            'Deadline Reminders': 'Deadline Reminders',
            'Group List': 'Group List',
            'Past Projects Archive': 'Past Projects Archive',
            'Supervised Projects': 'Supervised Projects',
            'Student Verification': 'Student Verification',
            'Past Projects': 'Past Projects',
            'Review Documents': 'Review Documents',
            'Manage Users': 'Manage Users',
            'Manage Projects': 'Manage Projects',
            'External Panel QR': 'External Panel QR',
            'Reports': 'Reports',
            'Settings': 'Settings',
            'Logout': 'Logout',
            'Main Page': 'Main Page',
            'Home': 'Home',
            'Welcome': 'Welcome',
            'Signed in as': 'Signed in as',
            'Light': 'Light',
            'Dark': 'Dark'
        },
        ms: {
            'Dashboard': 'Papan Pemuka',
            'My Project': 'Projek Saya',
            'Upload Documents': 'Muat Naik Dokumen',
            'Evaluation Marks': 'Markah Penilaian',
            'Deadline Reminders': 'Peringatan Tarikh Akhir',
            'Group List': 'Senarai Kumpulan',
            'Past Projects Archive': 'Arkib Projek Lepas',
            'Supervised Projects': 'Projek Seliaan',
            'Student Verification': 'Pengesahan Pelajar',
            'Past Projects': 'Projek Lepas',
            'Review Documents': 'Semak Dokumen',
            'Manage Users': 'Urus Pengguna',
            'Manage Projects': 'Urus Projek',
            'External Panel QR': 'QR Panel Luar',
            'Reports': 'Laporan',
            'Settings': 'Tetapan',
            'Logout': 'Log Keluar',
            'Main Page': 'Halaman Utama',
            'Home': 'Laman Utama',
            'Welcome': 'Selamat Datang',
            'Signed in as': 'Log masuk sebagai',
            'Light': 'Cerah',
            'Dark': 'Gelap'
        }
    };

    Object.assign(translations.en, {
        'JTMK Students': 'JTMK Students',
        'Project Registration': 'Project Registration',
        'Project Information': 'Project Information',
        'Project Description': 'Project Description',
        'Supervisor': 'Supervisor',
        'Name': 'Name',
        'Email': 'Email',
        'Profile': 'Profile',
        'Save Profile': 'Save Profile',
        'Submit': 'Submit',
        'Search': 'Search',
        'Documents': 'Documents',
        'Student': 'Student',
        'Students': 'Students',
        'Lecturers / Supervisors': 'Lecturers / Supervisors',
        'Academic Session': 'Academic Session',
        'IC Number': 'IC Number',
        'Matric Number': 'Matric Number',
        'Here is the overview of your supervision activities, student progress, and final year project updates for this session.': 'Here is the overview of your supervision activities, student progress, and final year project updates for this session.',
        'Manage your supervised students, review pending project submissions, and evaluate academic milestones efficiently in one centralized place.': 'Manage your supervised students, review pending project submissions, and evaluate academic milestones efficiently in one centralized place.',
        'TOTAL STUDENTS': 'TOTAL STUDENTS',
        'Total individual students across all groups': 'Total individual students across all groups',
        'Recent Student Submissions & Groups': 'Recent Student Submissions & Groups',
        'No recent student submissions found.': 'No recent student submissions found.',
        'Group Members:': 'Group Members:',
        'Pending': 'Pending',
        'Submitted': 'Submitted',
        'Milestones': 'Milestones',
        'Welcome back,': 'Welcome back,',
        'Project Title': 'Project Title',
        'Project Category': 'Project Category',
        'Department': 'Department',
        'Program': 'Program',
        'Course Code': 'Course Code',
        'Project Team': 'Project Team',
        'Project Details': 'Project Details',
        'Student Name': 'Student Name',
        'Matrix No': 'Matrix No',
        'I/C No': 'I/C No',
        'Phone No': 'Phone No',
        'Class': 'Class',
        'Track': 'Track',
        'No Projects / Students Supervised': 'No Projects / Students Supervised'
    });
    Object.assign(translations.ms, {
        'JTMK Students': 'Pelajar JTMK',
        'Academic Session': 'Sesi Akademik',
        'IC Number': 'Nombor IC',
        'Matric Number': 'Nombor Matriks',
        'Here is the overview of your supervision activities, student progress, and final year project updates for this session.': 'Ini ialah ringkasan aktiviti penyeliaan, kemajuan pelajar dan kemas kini projek tahun akhir untuk sesi ini.',
        'Manage your supervised students, review pending project submissions, and evaluate academic milestones efficiently in one centralized place.': 'Urus pelajar seliaan, semak penghantaran projek yang belum selesai dan nilai pencapaian akademik dengan mudah di satu tempat.',
        'TOTAL STUDENTS': 'JUMLAH PELAJAR',
        'Total individual students across all groups': 'Jumlah pelajar individu bagi semua kumpulan',
        'Recent Student Submissions & Groups': 'Penghantaran dan Kumpulan Pelajar Terkini',
        'No recent student submissions found.': 'Tiada penghantaran pelajar terkini ditemui.',
        'Group Members:': 'Ahli Kumpulan:',
        'Pending': 'Menunggu',
        'Submitted': 'Dihantar',
        'Milestones': 'Pencapaian',
        'Welcome back,': 'Selamat kembali,',
        'Project Title': 'Tajuk Projek',
        'Project Category': 'Kategori Projek',
        'Department': 'Jabatan',
        'Program': 'Program',
        'Course Code': 'Kod Kursus',
        'Project Team': 'Kumpulan Projek',
        'Project Details': 'Butiran Projek',
        'Student Name': 'Nama Pelajar',
        'Matrix No': 'No. Matriks',
        'I/C No': 'No. Kad Pengenalan',
        'Phone No': 'No. Telefon',
        'Class': 'Kelas',
        'Track': 'Trek',
        'No Projects / Students Supervised': 'Tiada Projek / Pelajar Seliaan'
    });
    Object.assign(translations.en, {
        'Supervised Projects Monitoring': 'Supervised Projects Monitoring',
        'List of project groups and registered members under your supervision.': 'List of project groups and registered members under your supervision.',
        'Project Title / System': 'Project Title / System',
        'Group Members (Name & Matric No.)': 'Group Members (Name & Matric No.)',
        'Submission Date': 'Submission Date',
        'Read-only document review for JTMK projects. No numerical marks or grades are entered here.': 'Read-only document review for JTMK projects. No numerical marks or grades are entered here.',
        'Document Submission Deadline Reminders': 'Document Submission Deadline Reminders',
        'Schedule by Document Type': 'Schedule by Document Type',
        'Deadline Date & Time': 'Deadline Date & Time',
        'Status': 'Status',
        'Review Student Documents': 'Review Student Documents',
        'Review document statuses according to project groups under your supervision.': 'Review document statuses according to project groups under your supervision.',
        'Assigned Students': 'Assigned Students',
        'Verify Demo 1/Demo 2 milestone status.': 'Verify Demo 1/Demo 2 milestone status.',
        'Project Evaluation & Marks': 'Project Evaluation & Marks',
        'Project Marks Evaluation': 'Project Marks Evaluation',
        'Select a project group below to evaluate or update their marks.': 'Select a project group below to evaluate or update their marks.',
        'Supervised Projects': 'Supervised Projects',
        'No supervised projects found.': 'No supervised projects found.',
        'No Projects / Students Supervised': 'No Projects / Students Supervised',
        'Document Submission Status': 'Document Submission Status',
        'Upload Project Documents': 'Upload Project Documents',
        'Document Type': 'Document Type',
        'Project Group Members (Max 3 Members):': 'Project Group Members (Max 3 Members):',
        'Create New Project (Leader)': 'Create New Project (Leader)',
        'Join Existing Project (Member)': 'Join Existing Project (Member)',
        'View All': 'View All',
        'Back to Students': 'Back to Students',
        'No recent student submissions found.': 'No recent student submissions found.'
    });
    Object.assign(translations.ms, {
        'Supervised Projects Monitoring': 'Pemantauan Projek Seliaan',
        'List of project groups and registered members under your supervision.': 'Senarai kumpulan projek dan ahli berdaftar di bawah seliaan anda.',
        'Project Title / System': 'Tajuk Projek / Sistem',
        'Group Members (Name & Matric No.)': 'Ahli Kumpulan (Nama & No. Matriks)',
        'Submission Date': 'Tarikh Penghantaran',
        'Read-only document review for JTMK projects. No numerical marks or grades are entered here.': 'Semakan dokumen baca sahaja untuk projek JTMK. Tiada markah atau gred berangka dimasukkan di sini.',
        'Document Submission Deadline Reminders': 'Peringatan Tarikh Akhir Penghantaran Dokumen',
        'Schedule by Document Type': 'Jadual Mengikut Jenis Dokumen',
        'Deadline Date & Time': 'Tarikh dan Masa Akhir',
        'Status': 'Status',
        'Review Student Documents': 'Semak Dokumen Pelajar',
        'Review document statuses according to project groups under your supervision.': 'Semak status dokumen mengikut kumpulan projek di bawah seliaan anda.',
        'Assigned Students': 'Pelajar Ditugaskan',
        'Verify Demo 1/Demo 2 milestone status.': 'Sahkan status pencapaian Demo 1/Demo 2.',
        'Project Evaluation & Marks': 'Penilaian Projek & Markah',
        'Project Marks Evaluation': 'Penilaian Markah Projek',
        'Select a project group below to evaluate or update their marks.': 'Pilih kumpulan projek di bawah untuk menilai atau mengemas kini markah.',
        'Supervised Projects': 'Projek Seliaan',
        'No supervised projects found.': 'Tiada projek seliaan ditemui.',
        'Document Submission Status': 'Status Penghantaran Dokumen',
        'Upload Project Documents': 'Muat Naik Dokumen Projek',
        'Document Type': 'Jenis Dokumen',
        'Project Group Members (Max 3 Members):': 'Ahli Kumpulan Projek (Maksimum 3 Ahli):',
        'Create New Project (Leader)': 'Cipta Projek Baharu (Ketua)',
        'Join Existing Project (Member)': 'Sertai Projek Sedia Ada (Ahli)',
        'View All': 'Lihat Semua',
        'Back to Students': 'Kembali ke Pelajar',
        'No recent student submissions found.': 'Tiada penghantaran pelajar terkini ditemui.'
    });
    Object.assign(translations.en, {
        'Manage submission dates for the official DFT50114 document categories. This page does not record marks or grades.': 'Manage submission dates for the official DFT50114 document categories. This page does not record marks or grades.',
        'Description / Instructions': 'Description / Instructions',
        'Action (SV)': 'Action (SV)',
        'A: Proposal Presentation': 'A: Proposal Presentation',
        'B: Project Demonstration 1': 'B: Project Demonstration 1',
        'C: Project Demonstration 2': 'C: Project Demonstration 2',
        'D: Project Demonstration 3': 'D: Project Demonstration 3',
        'E: Final Presentation - Poster': 'E: Final Presentation - Poster',
        'F: Final Presentation': 'F: Final Presentation',
        'Technical Report': 'Technical Report',
        'Submit the proposal presentation document for supervisor verification.': 'Submit the proposal presentation document for supervisor verification.',
        'Submit the Demo 1 supporting document before the scheduled deadline.': 'Submit the Demo 1 supporting document before the scheduled deadline.',
        'Submit the Demo 2 supporting document before the scheduled deadline.': 'Submit the Demo 2 supporting document before the scheduled deadline.',
        'Submit the Demo 3 supporting document before the scheduled deadline.': 'Submit the Demo 3 supporting document before the scheduled deadline.',
        'Submit the final presentation poster for project documentation.': 'Submit the final presentation poster for project documentation.',
        'Submit the final presentation document for project completion.': 'Submit the final presentation document for project completion.',
        'Submit the final technical report for project documentation.': 'Submit the final technical report for project documentation.',
        'To Be Announced': 'To Be Announced',
        'Expired': 'Expired',
        'Waiting for Admin': 'Waiting for Admin',
        'Set Date': 'Set Date',
        'Supervisor Control Notice:': 'Supervisor Control Notice:',
        'Set the submission date and exact time for students. Verification outcomes are managed separately as Passed or Not Passed.': 'Set the submission date and exact time for students. Verification outcomes are managed separately as Passed or Not Passed.'
    });
    Object.assign(translations.en, {
        'Past Project References': 'Past Project References',
        'Gallery of documentation, final reports, and system previews from alumni and previous students\' projects.': 'Gallery of documentation, final reports, and system previews from alumni and previous students\' projects.',
        'Keyword / title...': 'Keyword / title...',
        '-- All Categories --': '-- All Categories --',
        '-- All Dept --': '-- All Dept --',
        'Proposal / Web': 'Proposal / Web',
        'Mobile App': 'Mobile App',
        'IoT / Hardware': 'IoT / Hardware',
        'AI / Machine Learning': 'AI / Machine Learning',
        'Showing records:': 'Showing records:',
        'project(s) found.': 'project(s) found.',
        'No Preview Available': 'No Preview Available',
        'View Details': 'View Details',
        'Category:': 'Category:',
        'Project Session:': 'Project Session:',
        'Project Leader:': 'Project Leader:',
        'Project Description / Abstract:': 'Project Description / Abstract:',
        'Project Team Members:': 'Project Team Members:',
        'Submitted Reference Documents:': 'Submitted Reference Documents:',
        'Open File': 'Open File',
        'No documents have been uploaded for this project yet.': 'No documents have been uploaded for this project yet.',
        'Close': 'Close',
        'No Past Project Records Found': 'No Past Project Records Found',
        'No project archives were found based on your search or filter criteria.': 'No project archives were found based on your search or filter criteria.'
    });
    Object.assign(translations.ms, {
        'Past Project References': 'Rujukan Projek Lepas',
        'Gallery of documentation, final reports, and system previews from alumni and previous students\' projects.': 'Galeri dokumentasi, laporan akhir dan pratonton sistem daripada projek alumni serta pelajar terdahulu.',
        'Keyword / title...': 'Kata kunci / tajuk...',
        '-- All Categories --': '-- Semua Kategori --',
        '-- All Dept --': '-- Semua Jabatan --',
        'Proposal / Web': 'Cadangan / Web',
        'Mobile App': 'Aplikasi Mudah Alih',
        'IoT / Hardware': 'IoT / Perkakasan',
        'AI / Machine Learning': 'AI / Pembelajaran Mesin',
        'Showing records:': 'Memaparkan rekod:',
        'project(s) found.': 'projek ditemui.',
        'No Preview Available': 'Tiada Pratonton',
        'View Details': 'Lihat Butiran',
        'Category:': 'Kategori:',
        'Project Session:': 'Sesi Projek:',
        'Project Leader:': 'Ketua Projek:',
        'Project Description / Abstract:': 'Penerangan / Abstrak Projek:',
        'Project Team Members:': 'Ahli Kumpulan Projek:',
        'Submitted Reference Documents:': 'Dokumen Rujukan Dihantar:',
        'Open File': 'Buka Fail',
        'No documents have been uploaded for this project yet.': 'Tiada dokumen dimuat naik untuk projek ini.',
        'Close': 'Tutup',
        'No Past Project Records Found': 'Tiada Rekod Projek Lepas Ditemui',
        'No project archives were found based on your search or filter criteria.': 'Tiada arkib projek ditemui berdasarkan kriteria carian atau penapis anda.'
    });
    Object.assign(translations.en, {
        'Reset Filter': 'Reset Filter',
        'Keyword / title...': 'Keyword / title...',
        'Project Session (e.g. Session 1 2026/2027)': 'Project Session (e.g. Session 1 2026/2027)',
        'Records shown:': 'Records shown:',
        'projects found.': 'projects found.',
        'Supervisor N/A': 'Supervisor N/A',
        'No reference projects found': 'No reference projects found',
        'No reference project records match your search criteria or the database does not contain past projects yet.': 'No reference project records match your search criteria or the database does not contain past projects yet.'
    });
    Object.assign(translations.ms, {
        'Reset Filter': 'Tetap Semula Penapis',
        'Keyword / title...': 'Kata kunci / tajuk...',
        'Project Session (e.g. Session 1 2026/2027)': 'Sesi Projek (contoh: Session 1 2026/2027)',
        'Records shown:': 'Rekod dipaparkan:',
        'projects found.': 'projek ditemui.',
        'Supervisor N/A': 'Penyelia Tiada',
        'No reference projects found': 'Tiada projek rujukan ditemui',
        'No reference project records match your search criteria or the database does not contain past projects yet.': 'Tiada rekod projek rujukan sepadan dengan carian anda atau pangkalan data belum mempunyai projek lepas.'
    });
    Object.assign(translations.en, {
        'Welcome,': 'Welcome,',
        'Here is the summary of your project progress and document submissions.': 'Here is the summary of your project progress and document submissions.',
        'PROJECT STATUS': 'PROJECT STATUS',
        'TOTAL SCORE': 'TOTAL SCORE',
        'PROJECT RANK': 'PROJECT RANK',
        'Document Submission Status': 'Document Submission Status',
        'My Project Information': 'My Project Information',
        'Manage your Final Year Project / SPInE details.': 'Manage your Final Year Project / SPInE details.',
        'You Are Already in a Group': 'You Are Already in a Group',
        'Project Group Members (Max 3 Members):': 'Project Group Members (Max 3 Members):',
        'Create New Project (Leader)': 'Create New Project (Leader)',
        'Register a new project and become the group leader.': 'Register a new project and become the group leader.',
        'Join Existing Project (Member)': 'Join Existing Project (Member)',
        'Join an existing project group.': 'Join an existing project group.',
        'Register New Project Details': 'Register New Project Details',
        'Join Current Project Group': 'Join Current Project Group',
        'FYP Group & Project Monitoring': 'FYP Group & Project Monitoring',
        'Search and filter project groups and members across the system.': 'Search and filter project groups and members across the system.',
        'Project Title / System': 'Project Title / System',
        'Group Members (Name & Matric No.)': 'Group Members (Name & Matric No.)',
        'Milestone Verification Status': 'Milestone Verification Status',
        'Supervisor verification status for your project milestones and log book.': 'Supervisor verification status for your project milestones and log book.',
        'Log Book Verification': 'Log Book Verification',
        'Document Submission Deadline Reminders': 'Document Submission Deadline Reminders',
        'Please pay close attention to the project document final submission dates and exact times categorized by document type.': 'Please pay close attention to the project document final submission dates and exact times categorized by document type.',
        'Upload Project Documents': 'Upload Project Documents',
        'Submit each official DFT50114 rubric component as a PDF, DOCX, or ZIP file (maximum 20 MB).': 'Submit each official DFT50114 rubric component as a PDF, DOCX, or ZIP file (maximum 20 MB).',
        'Upload Form': 'Upload Form',
        'My Profile': 'My Profile',
        'Change Password': 'Change Password',
        'Save Profile': 'Save Profile',
        'Past Project References': 'Past Project References',
        'Project Registration': 'Project Registration',
        'Project group registration for one to three students.': 'Project group registration for one to three students.',
        'SECTION A: PROJECT TEAM': 'SECTION A: PROJECT TEAM',
        'SECTION B: PROJECT INFORMATION': 'SECTION B: PROJECT INFORMATION',
        'Project Title *': 'Project Title *',
        'Project Category *': 'Project Category *',
        'Project Description *': 'Project Description *',
        'Academic Session *': 'Academic Session *',
        'Supervisor\'s Name *': 'Supervisor\'s Name *',
        'Submit Project Registration': 'Submit Project Registration'
    });
    Object.assign(translations.ms, {
        'Welcome,': 'Selamat Datang,',
        'Here is the summary of your project progress and document submissions.': 'Ini ialah ringkasan kemajuan projek dan penghantaran dokumen anda.',
        'PROJECT STATUS': 'STATUS PROJEK',
        'TOTAL SCORE': 'JUMLAH SKOR',
        'PROJECT RANK': 'KEDUDUKAN PROJEK',
        'Document Submission Status': 'Status Penghantaran Dokumen',
        'My Project Information': 'Maklumat Projek Saya',
        'Manage your Final Year Project / SPInE details.': 'Urus butiran Projek Tahun Akhir / SPInE anda.',
        'You Are Already in a Group': 'Anda Sudah Berada Dalam Kumpulan',
        'Project Group Members (Max 3 Members):': 'Ahli Kumpulan Projek (Maksimum 3 Ahli):',
        'Create New Project (Leader)': 'Cipta Projek Baharu (Ketua)',
        'Register a new project and become the group leader.': 'Daftar projek baharu dan menjadi ketua kumpulan.',
        'Join Existing Project (Member)': 'Sertai Projek Sedia Ada (Ahli)',
        'Join an existing project group.': 'Sertai kumpulan projek sedia ada.',
        'Register New Project Details': 'Daftar Butiran Projek Baharu',
        'Join Current Project Group': 'Sertai Kumpulan Projek Semasa',
        'FYP Group & Project Monitoring': 'Pemantauan Kumpulan & Projek FYP',
        'Search and filter project groups and members across the system.': 'Cari dan tapis kumpulan projek serta ahli di seluruh sistem.',
        'Project Title / System': 'Tajuk Projek / Sistem',
        'Group Members (Name & Matric No.)': 'Ahli Kumpulan (Nama & No. Matriks)',
        'Milestone Verification Status': 'Status Pengesahan Pencapaian',
        'Supervisor verification status for your project milestones and log book.': 'Status pengesahan penyelia untuk pencapaian projek dan buku log anda.',
        'Log Book Verification': 'Pengesahan Buku Log',
        'Document Submission Deadline Reminders': 'Peringatan Tarikh Akhir Penghantaran Dokumen',
        'Please pay close attention to the project document final submission dates and exact times categorized by document type.': 'Sila beri perhatian kepada tarikh dan masa tepat penghantaran akhir dokumen projek mengikut jenis dokumen.',
        'Upload Project Documents': 'Muat Naik Dokumen Projek',
        'Submit each official DFT50114 rubric component as a PDF, DOCX, or ZIP file (maximum 20 MB).': 'Hantar setiap komponen rubrik rasmi DFT50114 sebagai fail PDF, DOCX atau ZIP (maksimum 20 MB).',
        'Upload Form': 'Borang Muat Naik',
        'My Profile': 'Profil Saya',
        'Change Password': 'Tukar Kata Laluan',
        'Save Profile': 'Simpan Profil',
        'Past Project References': 'Rujukan Projek Lepas',
        'Project Registration': 'Pendaftaran Projek',
        'Project group registration for one to three students.': 'Pendaftaran kumpulan projek untuk satu hingga tiga pelajar.',
        'SECTION A: PROJECT TEAM': 'BAHAGIAN A: KUMPULAN PROJEK',
        'SECTION B: PROJECT INFORMATION': 'BAHAGIAN B: MAKLUMAT PROJEK',
        'Project Title *': 'Tajuk Projek *',
        'Project Category *': 'Kategori Projek *',
        'Project Description *': 'Penerangan Projek *',
        'Academic Session *': 'Sesi Akademik *',
        'Supervisor\'s Name *': 'Nama Penyelia *',
        'Submit Project Registration': 'Hantar Pendaftaran Projek'
    });
    Object.assign(translations.en, {
        'In Progress': 'In Progress',
        'Not Evaluated Yet': 'Not Evaluated Yet',
        'Uploaded': 'Uploaded',
        'Not Uploaded': 'Not Uploaded',
        'Upload File': 'Upload File',
        'Project Summary Information': 'Project Summary Information',
        'Complete': 'Complete',
        'Pending Review': 'Pending Review'
    });
    Object.assign(translations.ms, {
        'In Progress': 'Sedang Berjalan',
        'Not Evaluated Yet': 'Belum Dinilai',
        'Uploaded': 'Telah Dimuat Naik',
        'Not Uploaded': 'Belum Dimuat Naik',
        'Upload File': 'Muat Naik Fail',
        'Project Summary Information': 'Maklumat Ringkasan Projek',
        'Complete': 'Lengkap',
        'Pending Review': 'Menunggu Semakan'
    });
    Object.assign(translations.en, {
        'Supervisor Profile': 'Supervisor Profile',
        'Admin Profile': 'Admin Profile',
        'My Profile': 'My Profile',
        'Profile Picture': 'Profile Picture',
        'IC / Staff ID': 'IC / Staff ID',
        'Full Name': 'Full Name',
        'Change Password (Leave blank if not changing)': 'Change Password (Leave blank if not changing)',
        'Change Password (Leave blank to keep your current password)': 'Change Password (Leave blank to keep your current password)',
        'Change Password': 'Change Password',
        'Update your administrator account details and password.': 'Update your administrator account details and password.',
        'JPG, PNG or WEBP, maximum 2 MB.': 'JPG, PNG or WEBP, maximum 2 MB.',
        'Leave blank to keep current password': 'Leave blank to keep current password',
        'Password must contain at least 8 characters.': 'Password must contain at least 8 characters.'
    });
    Object.assign(translations.ms, {
        'Supervisor Profile': 'Profil Penyelia',
        'Admin Profile': 'Profil Admin',
        'My Profile': 'Profil Saya',
        'Profile Picture': 'Gambar Profil',
        'IC / Staff ID': 'No. Kad Pengenalan / ID Staf',
        'Full Name': 'Nama Penuh',
        'Change Password (Leave blank if not changing)': 'Tukar Kata Laluan (Biarkan kosong jika tidak mahu menukar)',
        'Change Password (Leave blank to keep your current password)': 'Tukar Kata Laluan (Biarkan kosong untuk kekalkan kata laluan semasa)',
        'Change Password': 'Tukar Kata Laluan',
        'Update your administrator account details and password.': 'Kemas kini butiran akaun pentadbir dan kata laluan anda.',
        'JPG, PNG or WEBP, maximum 2 MB.': 'JPG, PNG atau WEBP, maksimum 2 MB.',
        'Leave blank to keep current password': 'Biarkan kosong untuk kekalkan kata laluan semasa',
        'Password must contain at least 8 characters.': 'Kata laluan mesti mempunyai sekurang-kurangnya 8 aksara.'
    });
    Object.assign(translations.en, {
        'Edit Student': 'Edit Student',
        'Update student account information for JTMK.': 'Update student account information for JTMK.',
        'Manage Users': 'Manage Users',
        'Complete all required student fields with a valid email address.': 'Complete all required student fields with a valid email address.',
        'Complete the IC number, matric number and student name fields.': 'Complete the IC number, matric number and student name fields.',
        'Please enter a valid email address.': 'Please enter a valid email address.',
        'The matric number can contain letters and numbers only.': 'The matric number can contain letters and numbers only.',
        'The IC number, matric number or email is already in use.': 'The IC number, matric number or email is already in use.',
        'Password must be at least 8 characters.': 'Password must be at least 8 characters.',
        'Academic Session': 'Academic Session',
        'Reset Password': 'Reset Password',
        'Save Student': 'Save Student',
        'Student account updated successfully.': 'Student account updated successfully.',
        'Unable to update the student account.': 'Unable to update the student account.'
    });
    Object.assign(translations.ms, {
        'Edit Student': 'Edit Pelajar',
        'Update student account information for JTMK.': 'Kemas kini maklumat akaun pelajar JTMK.',
        'Manage Users': 'Urus Pengguna',
        'Complete all required student fields with a valid email address.': 'Lengkapkan semua medan pelajar dengan alamat e-mel yang sah.',
        'Complete the IC number, matric number and student name fields.': 'Lengkapkan medan nombor IC, nombor matriks dan nama pelajar.',
        'Please enter a valid email address.': 'Sila masukkan alamat e-mel yang sah.',
        'The matric number can contain letters and numbers only.': 'Nombor matriks hanya boleh mengandungi huruf dan nombor.',
        'The IC number, matric number or email is already in use.': 'Nombor IC, nombor matriks atau e-mel sudah digunakan.',
        'Password must be at least 8 characters.': 'Kata laluan mesti mempunyai sekurang-kurangnya 8 aksara.',
        'Academic Session': 'Sesi Akademik',
        'Reset Password': 'Tetap Semula Kata Laluan',
        'Save Student': 'Simpan Pelajar',
        'Student account updated successfully.': 'Akaun pelajar berjaya dikemas kini.',
        'Unable to update the student account.': 'Akaun pelajar tidak dapat dikemas kini.'
    });
    Object.assign(translations.en, {
        'Find Student': 'Find Student',
        'Search by student name, IC number, or matric number': 'Search by student name, IC number, or matric number',
        'Type at least 2 characters...': 'Type at least 2 characters...',
        'Search results will appear here.': 'Search results will appear here.',
        'Import Student JTMK': 'Import Student JTMK',
        'Upload CSV or XLSX with Name, IC No, Matric No, Session and Department columns. Only rows marked JTMK are imported; other departments are ignored.': 'Upload CSV or XLSX with Name, IC No, Matric No, Session and Department columns. Only rows marked JTMK are imported; other departments are ignored.',
        'Account setup:': 'Account setup:',
        'imported students use their IC number as their initial password. Passwords are stored as secure hashes; students should change the initial password after signing in.': 'imported students use their IC number as their initial password. Passwords are stored as secure hashes; students should change the initial password after signing in.',
        'Student data file': 'Student data file',
        'Import Students': 'Import Students',
        'Skipped rows': 'Skipped rows',
        'JTMK Users': 'JTMK Users',
        'Lecturers / Supervisors': 'Lecturers / Supervisors'
    });
    Object.assign(translations.ms, {
        'Find Student': 'Cari Pelajar',
        'Search by student name, IC number, or matric number': 'Cari berdasarkan nama pelajar, nombor IC atau nombor matriks',
        'Type at least 2 characters...': 'Taip sekurang-kurangnya 2 aksara...',
        'Search results will appear here.': 'Hasil carian akan dipaparkan di sini.',
        'Import Student JTMK': 'Import Pelajar JTMK',
        'Upload CSV or XLSX with Name, IC No, Matric No, Session and Department columns. Only rows marked JTMK are imported; other departments are ignored.': 'Muat naik CSV atau XLSX yang mengandungi lajur Nama, No. IC, No. Matriks, Sesi dan Jabatan. Hanya rekod bertanda JTMK akan diimport; jabatan lain diabaikan.',
        'Account setup:': 'Tetapan akaun:',
        'imported students use their IC number as their initial password. Passwords are stored as secure hashes; students should change the initial password after signing in.': 'pelajar yang diimport menggunakan nombor IC sebagai kata laluan awal. Kata laluan disimpan sebagai hash selamat; pelajar perlu menukarnya selepas log masuk.',
        'Student data file': 'Fail data pelajar',
        'Import Students': 'Import Pelajar',
        'Skipped rows': 'Rekod yang dilangkau',
        'JTMK Users': 'Pengguna JTMK',
        'Lecturers / Supervisors': 'Pensyarah / Penyelia'
    });
    Object.assign(translations.en, {
        'SPInE Student Project System': 'SPInE Student Project System',
        'JTMK | DFT50114 Integrated Project': 'JTMK | DFT50114 Integrated Project',
        'Top 5 Project Ranking': 'Top 5 Project Ranking',
        "Panel's Choices": "Panel's Choices",
        'Featured Projects': 'Featured Projects',
        'Current Announcement:': 'Current Announcement:',
        'Project Deadlines': 'Project Deadlines',
        'Current Projects In Progress': 'Current Projects In Progress'
    });
    Object.assign(translations.ms, {
        'SPInE Student Project System': 'Sistem Projek Pelajar SPInE',
        'JTMK | DFT50114 Integrated Project': 'Projek Bersepadu JTMK | DFT50114',
        'Top 5 Project Ranking': '5 Kedudukan Projek Teratas',
        "Panel's Choices": 'Pilihan Panel',
        'Featured Projects': 'Projek Pilihan',
        'Current Announcement:': 'Pengumuman Semasa:',
        'Project Deadlines': 'Tarikh Akhir Projek',
        'Current Projects In Progress': 'Projek Semasa Dalam Proses'
    });
    Object.assign(translations.en, {
        'FYP Group & Project Monitoring': 'FYP Group & Project Monitoring',
        'Search and filter project groups and members across the system.': 'Search and filter project groups and members across the system.',
        'Keyword / Title / Name': 'Keyword / Title / Name',
        'Category Selection': 'Category Selection',
        'Search title, name...': 'Search title, name...',
        '-- Select Session --': '-- Select Session --',
        '-- All Categories --': '-- All Categories --',
        'Project Title / System': 'Project Title / System',
        'Group Members (Name & Matric No.)': 'Group Members (Name & Matric No.)',
        'Category / Session': 'Category / Session',
        'Session': 'Session',
        'Department': 'Department',
        'Session:': 'Session:',
        '[Leader]': '[Leader]',
        'No records found': 'No records found',
        'Try adjusting your filter options or click Reset to view all.': 'Try adjusting your filter options or click Reset to view all.',
        'Reset': 'Reset'
    });
    Object.assign(translations.ms, {
        'FYP Group & Project Monitoring': 'Pemantauan Kumpulan & Projek FYP',
        'Search and filter project groups and members across the system.': 'Cari dan tapis kumpulan projek serta ahli di seluruh sistem.',
        'Keyword / Title / Name': 'Kata Kunci / Tajuk / Nama',
        'Category Selection': 'Pilihan Kategori',
        'Search title, name...': 'Cari tajuk, nama...',
        '-- Select Session --': '-- Pilih Sesi --',
        '-- All Categories --': '-- Semua Kategori --',
        'Project Title / System': 'Tajuk Projek / Sistem',
        'Group Members (Name & Matric No.)': 'Ahli Kumpulan (Nama & No. Matriks)',
        'Category / Session': 'Kategori / Sesi',
        'Session': 'Sesi',
        'Department': 'Jabatan',
        'Session:': 'Sesi:',
        '[Leader]': '[Ketua]',
        'No records found': 'Tiada rekod ditemui',
        'Try adjusting your filter options or click Reset to view all.': 'Cuba ubah pilihan penapis atau tekan Reset untuk melihat semua.',
        'Reset': 'Tetap Semula'
    });
    Object.assign(translations.ms, {
        'Manage submission dates for the official DFT50114 document categories. This page does not record marks or grades.': 'Urus tarikh penghantaran bagi kategori dokumen rasmi DFT50114. Halaman ini tidak merekod markah atau gred.',
        'Description / Instructions': 'Penerangan / Arahan',
        'Action (SV)': 'Tindakan (SV)',
        'A: Proposal Presentation': 'A: Pembentangan Cadangan',
        'B: Project Demonstration 1': 'B: Demonstrasi Projek 1',
        'C: Project Demonstration 2': 'C: Demonstrasi Projek 2',
        'D: Project Demonstration 3': 'D: Demonstrasi Projek 3',
        'E: Final Presentation - Poster': 'E: Pembentangan Akhir - Poster',
        'F: Final Presentation': 'F: Pembentangan Akhir',
        'Technical Report': 'Laporan Teknikal',
        'Submit the proposal presentation document for supervisor verification.': 'Hantar dokumen pembentangan cadangan untuk pengesahan penyelia.',
        'Submit the Demo 1 supporting document before the scheduled deadline.': 'Hantar dokumen sokongan Demo 1 sebelum tarikh akhir yang ditetapkan.',
        'Submit the Demo 2 supporting document before the scheduled deadline.': 'Hantar dokumen sokongan Demo 2 sebelum tarikh akhir yang ditetapkan.',
        'Submit the Demo 3 supporting document before the scheduled deadline.': 'Hantar dokumen sokongan Demo 3 sebelum tarikh akhir yang ditetapkan.',
        'Submit the final presentation poster for project documentation.': 'Hantar poster pembentangan akhir untuk dokumentasi projek.',
        'Submit the final presentation document for project completion.': 'Hantar dokumen pembentangan akhir untuk melengkapkan projek.',
        'Submit the final technical report for project documentation.': 'Hantar laporan teknikal akhir untuk dokumentasi projek.',
        'To Be Announced': 'Akan Diumumkan',
        'Expired': 'Tamat Tempoh',
        'Waiting for Admin': 'Menunggu Admin',
        'Set Date': 'Tetapkan Tarikh',
        'Supervisor Control Notice:': 'Notis Kawalan Penyelia:',
        'Set the submission date and exact time for students. Verification outcomes are managed separately as Passed or Not Passed.': 'Tetapkan tarikh dan masa tepat penghantaran untuk pelajar. Keputusan pengesahan diuruskan secara berasingan sebagai Lulus atau Tidak Lulus.'
    });
    Object.assign(translations.ms, {
        'Project Registration': 'Pendaftaran Projek',
        'Project Information': 'Maklumat Projek',
        'Project Description': 'Penerangan Projek',
        'Supervisor': 'Penyelia',
        'Name': 'Nama',
        'Email': 'E-mel',
        'Profile': 'Profil',
        'Save Profile': 'Simpan Profil',
        'Submit': 'Hantar',
        'Search': 'Cari',
        'Documents': 'Dokumen',
        'Student': 'Pelajar',
        'Students': 'Pelajar',
        'Lecturers / Supervisors': 'Pensyarah / Penyelia',
        'Academic Session': 'Sesi Akademik'
    });

    function loadPreferences() {
        try {
            return Object.assign({ theme: 'light', language: 'en' }, JSON.parse(localStorage.getItem(storageKey) || '{}'));
        } catch (error) {
            return { theme: 'light', language: 'en' };
        }
    }

    function savePreferences(preferences) {
        localStorage.setItem(storageKey, JSON.stringify(preferences));
    }

    function translatePage(language) {
        document.querySelectorAll('[data-i18n]').forEach(function (element) {
            const key = element.dataset.i18n;
            if (translations[language] && translations[language][key]) {
                element.textContent = translations[language][key];
            }
        });
        const lookup = {};
        const replacements = [];
        Object.keys(translations.en).forEach(function (key) {
            const target = translations[language][key] || translations.en[key];
            lookup[translations.en[key]] = target;
            lookup[translations.ms[key]] = target;
            replacements.push([translations.en[key], target]);
            replacements.push([translations.ms[key], target]);
        });
        replacements.sort(function (left, right) { return right[0].length - left[0].length; });
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        const textNodes = [];
        while (walker.nextNode()) textNodes.push(walker.currentNode);
        textNodes.forEach(function (node) {
            if (node.parentElement.closest('script, style, [data-i18n]')) return;
            const text = node.nodeValue.trim();
            if (!text) return;
            let translated = text;
            replacements.forEach(function (replacement) {
                if (replacement[0] && replacement[0] !== replacement[1] && !replacement[1].includes(replacement[0])) {
                    translated = translated.split(replacement[0]).join(replacement[1]);
                }
            });
            if (translated !== text) node.nodeValue = node.nodeValue.replace(text, translated);
        });
        document.querySelectorAll('[placeholder]').forEach(function (element) {
            const value = element.getAttribute('placeholder');
            if (lookup[value]) element.setAttribute('placeholder', lookup[value]);
        });
        document.documentElement.lang = language === 'ms' ? 'ms' : 'en';
        document.querySelectorAll('[data-language-option]').forEach(function (button) {
            button.classList.toggle('active', button.dataset.languageOption === language);
        });
    }

    function applyTheme(theme) {
        if (!isPortal) return;
        document.body.classList.toggle('portal-dark', theme === 'dark');
        document.querySelectorAll('[data-theme-icon]').forEach(function (icon) {
            icon.className = theme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
        });
    }

    function setupControls(preferences) {
        const themeButton = document.querySelector('[data-theme-toggle]');
        const languageSelect = document.querySelector('[data-language-select]');
        if (themeButton && isPortal) {
            themeButton.addEventListener('click', function () {
                preferences.theme = preferences.theme === 'dark' ? 'light' : 'dark';
                savePreferences(preferences);
                document.body.classList.add('theme-changing');
                applyTheme(preferences.theme);
                window.setTimeout(function () {
                    document.body.classList.remove('theme-changing');
                }, 420);
            });
        }
        if (languageSelect) {
            languageSelect.value = preferences.language;
            languageSelect.addEventListener('change', function () {
                preferences.language = languageSelect.value;
                savePreferences(preferences);
                document.body.classList.add('language-changing');
                window.setTimeout(function () {
                    translatePage(preferences.language);
                    window.requestAnimationFrame(function () {
                        document.body.classList.remove('language-changing');
                    });
                }, 220);
            });
        }
        document.querySelectorAll('[data-language-option]').forEach(function (button) {
            button.addEventListener('click', function () {
                const language = button.dataset.languageOption;
                if (!language || language === preferences.language) return;
                preferences.language = language;
                savePreferences(preferences);
                document.body.classList.add('language-changing');
                window.setTimeout(function () {
                    translatePage(preferences.language);
                    window.requestAnimationFrame(function () {
                        document.body.classList.remove('language-changing');
                    });
                }, 220);
            });
        });
    }

    const preferences = loadPreferences();
    applyTheme(preferences.theme);
    translatePage(preferences.language);
    document.addEventListener('DOMContentLoaded', function () {
        setupControls(preferences);
        translatePage(preferences.language);
    });
})();
