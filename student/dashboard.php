<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Student']);

$student_id = $_SESSION['user_id'];

// 1. Ambil Maklumat Projek & Penyelia (Semak sama ada Ketua ATAU Ahli Kumpulan)
$stmt = $conn->prepare("
    SELECT p.*, s.full_name as supervisor_name 
    FROM projects p 
    LEFT JOIN project_members pm ON p.id = pm.project_id
    LEFT JOIN users s ON p.supervisor_id = s.id 
    WHERE p.student_id = ? OR pm.student_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $student_id, $student_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

// 2. Kira Jumlah Markah & Kedudukan (Rank) Projek
$total_score = null; 
$rank = '-';
$total_projects_count = 0;

if ($project) {
    // Ambil jumlah markah projek ini dari jadual evaluations/marks (guna COALESCE jika nama lajur berbeza)
    // Query ini mengira markah terkumpul atau purata markah projek
    $stmt_score = $conn->prepare("
        SELECT SUM(marks) as total_marks 
        FROM project_evaluations 
        WHERE project_id = ?
    ");
    
    // Jika jadual anda guna nama lain (cth: evaluations), sesuaikan query di atas. 
    // Sebagai fallback, jika lajur total_score wujud terus di jadual projects:
    if (isset($project['total_score'])) {
        $total_score = $project['total_score'];
    } else if ($stmt_score) {
        $stmt_score->bind_param("i", $project['id']);
        $stmt_score->execute();
        $res_score = $stmt_score->get_result()->fetch_assoc();
        $total_score = $res_score['total_marks'] ?? null;
    }

    // Kira Kedudukan (Rank) projek berbanding projek-projek lain mengikut sesi
    $stmt_rank = $conn->prepare("
        SELECT id, total_score,
               RANK() OVER (ORDER BY COALESCE(total_score, 0) DESC) as project_rank
        FROM projects
        WHERE session = ?
    ");
    if ($stmt_rank) {
        $stmt_rank->bind_param("s", $project['session']);
        $stmt_rank->execute();
        $res_rank = $stmt_rank->get_result();
        $total_projects_count = $res_rank->num_rows;
        while ($r = $res_rank->fetch_assoc()) {
            if ($r['id'] == $project['id']) {
                $rank = '#' . $r['project_rank'];
                break;
            }
        }
    }
}

// 3. Ambil Senarai & Status Dokumen yang Telah Dimuat Naik
$uploaded_docs = [];
if ($project) {
    $stmt_doc = $conn->prepare("SELECT doc_type, file_path FROM project_documents WHERE project_id = ?");
    $stmt_doc->bind_param("i", $project['id']);
    $stmt_doc->execute();
    $res = $stmt_doc->get_result();
    while ($row = $res->fetch_assoc()) {
        $uploaded_docs[$row['doc_type']] = $row['file_path'];
    }
}

// Senarai dokumen mengikut kategori rasmi rubrik DFT50114
$doc_list = [
    'A'                 => 'A: Proposal Presentation (10%)',
    'B'                 => 'B: Project Demonstration 1 (10%)',
    'C'                 => 'C: Project Demonstration 2 (10%)',
    'D'                 => 'D: Project Demonstration 3 (15%)',
    'E'                 => 'E: Final Presentation - Poster (15%)',
    'F'                 => 'F: Final Presentation (15%)',
    'LOG_BOOK'          => 'Log Book (10%)',
    'TECHNICAL_REPORT'  => 'Technical Report (15%)',
];

include_once '../includes/sidebar_student.php';
?>

<!-- Tajuk Dashboard -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">Welcome, <?= sanitize($_SESSION['full_name'] ?? 'Student'); ?>! 👋</h3>
        <p class="text-muted mb-0">Here is the summary of your project progress and document submissions.</p>
    </div>
    <?php if (!$project): ?>
        <a href="register_project.php" class="btn btn-primary fw-bold">
            <i class="fas fa-plus-circle me-1"></i> Register Project
        </a>
    <?php endif; ?>
</div>

<!-- Kad Widget Ringkasan Utama (Project Status, Total Score, Rank) -->
<div class="row g-3 mb-4">
    <!-- 1. PROJECT STATUS -->
    <div class="col-md-4">
        <div class="card card-custom p-3 border-start border-4 border-warning h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold d-block mb-1">PROJECT STATUS</small>
                    <h5 class="fw-bold mb-0 text-dark">
                        <?php 
                        if (!$project) {
                            echo '<span class="badge bg-secondary">Not Registered</span>';
                        } elseif ($project['is_complete_for_evaluation'] ?? 0) {
                            echo '<span class="badge bg-success">Complete & Ready</span>';
                        } else {
                            echo '<span class="badge bg-warning text-dark">In Progress</span>';
                        }
                        ?>
                    </h5>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                    <i class="fas fa-tasks fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. TOTAL SCORE -->
    <div class="col-md-4">
        <div class="card card-custom p-3 border-start border-4 border-success h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold d-block mb-1">TOTAL SCORE</small>
                    <h4 class="fw-bold mb-0 text-success">
                        <?= ($total_score !== null) ? number_format($total_score, 1) . ' <small class="fs-6 text-muted">/ 100</small>' : '<span class="fs-6 text-muted fw-normal">Not Evaluated Yet</span>'; ?>
                    </h4>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                    <i class="fas fa-star fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. RANK -->
    <div class="col-md-4">
        <div class="card card-custom p-3 border-start border-4 border-primary h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold d-block mb-1">PROJECT RANK</small>
                    <h4 class="fw-bold mb-0 text-primary">
                        <?= $rank; ?> 
                        <?php if ($total_projects_count > 0 && $rank !== '-'): ?>
                            <small class="fs-6 text-muted fw-normal">out of <?= $total_projects_count; ?> projects</small>
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                    <i class="fas fa-trophy fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Penghantaran Dokumen -->
<div class="card card-custom p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0"><i class="fas fa-file-upload text-primary me-2"></i>Document Submission Status</h5>
        <a href="upload_doc.php" class="btn btn-outline-primary btn-sm fw-bold">
            <i class="fas fa-upload me-1"></i> Upload File
        </a>
    </div>

    <div class="row g-3">
        <?php foreach ($doc_list as $db_value => $label): ?>
            <?php 
                $is_uploaded = false;
                foreach ($uploaded_docs as $doc_type => $path) {
                    if (strcasecmp(trim($doc_type), trim($db_value)) === 0) {
                        $is_uploaded = true;
                        break;
                    }
                }
            ?>
            <div class="col-md-4 col-6">
                <div class="p-3 border rounded-3 d-flex align-items-center justify-content-between bg-light">
                    <div>
                        <span class="fw-bold d-block text-dark small"><?= htmlspecialchars($label); ?></span>
                        <small class="<?= $is_uploaded ? 'text-success fw-bold' : 'text-danger'; ?>">
                            <i class="fas <?= $is_uploaded ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                            <?= $is_uploaded ? 'Uploaded' : 'Not Uploaded'; ?>
                        </small>
                    </div>
                    <div>
                        <?php if ($is_uploaded): ?>
                            <span class="badge bg-success"><i class="fas fa-check"></i></span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><i class="fas fa-clock"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Maklumat Ringkas Projek -->
<?php if ($project): ?>
<div class="card card-custom p-4">
    <h5 class="fw-bold text-dark mb-2"><i class="fas fa-info-circle text-info me-2"></i>Project Summary Information</h5>
    <div class="row">
        <div class="col-md-8">
            <p class="mb-1"><strong>Project Title:</strong> <?= sanitize($project['title'] ?? '-'); ?></p>
            <p class="mb-1 text-muted small"><strong>Supervisor:</strong> <?= sanitize($project['supervisor_name'] ?? 'Not Assigned Yet'); ?></p>
            <p class="mb-1 text-muted small"><strong>Short Description:</strong> <?= sanitize($project['description'] ?? 'No description provided.'); ?></p>
        </div>
        <div class="col-md-4 text-md-end border-start">
            <p class="mb-1 small text-muted"><strong>Category:</strong> <?= sanitize($project['category'] ?? '-'); ?></p>
            <p class="mb-1 small text-muted"><strong>Activity Session:</strong> <?= sanitize($project['session'] ?? 'Current Session'); ?></p>
            <a href="my_project.php" class="btn btn-sm btn-outline-secondary mt-2">View Full Details</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include_once '../includes/footer.php'; ?>