<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Supervisor']);

header('Location: my_students.php');
exit();

$supervisor_id = $_SESSION['user_id'] ?? 0;
$selected_project_id = intval($_GET['project_id'] ?? 0);
$msg = '';

// Standard document types required in the system
$all_document_types = [
    'Proposal'              => 'Project Proposal',
    'Final Report'          => 'Final Report',
    'Presentation Slides'   => 'Presentation Slides',
    'Poster'                => 'Project Poster',
    'Source Code'           => 'Gantt Chart / Source Code',
    'Project Image'         => 'System Preview / Image',
    'Document Lain'         => 'Other Documents'
];

// PROCESS SAVE MARKS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    $doc_id = intval($_POST['doc_id']);
    $mark = floatval($_POST['mark']);
    
    $stmt = $conn->prepare("INSERT INTO doc_marks (doc_id, supervisor_id, mark) VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE mark = ?");
    $stmt->bind_param("iidd", $doc_id, $supervisor_id, $mark, $mark);
    
    if ($stmt->execute()) {
        $msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">Mark updated successfully!<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    } else {
        $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Failed to save mark.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}

// 1. Fetch supervised projects along with uploaded documents
$projects_query = "SELECT p.*, GROUP_CONCAT(u.full_name SEPARATOR '___') as student_names
                   FROM projects p
                   JOIN users u ON p.student_id = u.id
                   JOIN supervisor_students ss ON u.id = ss.student_id
                   WHERE ss.supervisor_id = ?
                   GROUP BY p.id";
$stmt_p = $conn->prepare($projects_query);
$stmt_p->bind_param("i", $supervisor_id);
$stmt_p->execute();
$projects_result = $stmt_p->get_result();

// 2. If supervisor clicks a specific project, fetch detailed documents
$documents_result = null;
$selected_project_title = '';
if ($selected_project_id > 0) {
    $proj_info_q = $conn->prepare("SELECT title, student_id FROM projects WHERE id = ?");
    $proj_info_q->bind_param("i", $selected_project_id);
    $proj_info_q->execute();
    $proj_info = $proj_info_q->get_result()->fetch_assoc();
    if ($proj_info) {
        $selected_project_title = $proj_info['title'];
        $student_id = $proj_info['student_id'];

        $doc_query = "SELECT d.*, m.mark 
                      FROM project_documents d 
                      LEFT JOIN doc_marks m ON d.id = m.doc_id 
                      WHERE d.student_id = ? OR d.project_id = ? 
                      ORDER BY d.uploaded_at DESC";
        $stmt_d = $conn->prepare($doc_query);
        $stmt_d->bind_param("ii", $student_id, $selected_project_id);
        $stmt_d->execute();
        $documents_result = $stmt_d->get_result();
    }
}

include_once '../includes/header.php';
include_once '../includes/sidebar_supervisor.php';
?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <h2><i class="fas fa-folder-open text-primary me-2"></i> Review Student Documents</h2>
        <p class="text-muted">Review document statuses according to project groups under your supervision.</p>
    </div>

    <?= $msg; ?>

    <?php if ($selected_project_id == 0): ?>
        <!-- Supervised Project Cards Grid -->
        <div class="row g-4">
            <?php if ($projects_result && $projects_result->num_rows > 0): ?>
                <?php while ($proj = $projects_result->fetch_assoc()): 
                    $current_proj_id = $proj['id'];
                    
                    // Check submitted documents for this project
                    $chk_docs = $conn->prepare("SELECT doc_type, file_path FROM project_documents WHERE project_id = ?");
                    $chk_docs->bind_param("i", $current_proj_id);
                    $chk_docs->execute();
                    $res_docs = $chk_docs->get_result();
                    $uploaded_map = [];
                    while($d_row = $res_docs->fetch_assoc()) {
                        $uploaded_map[$d_row['doc_type']] = $d_row['file_path'];
                    }
                ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card h-100 shadow-sm border-0 rounded-3 bg-white d-flex flex-column">
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge bg-primary text-white"><i class="fas fa-users me-1"></i> Group Project</span>
                                </div>
                                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($proj['title']); ?></h5>
                                <p class="text-muted small mb-3">
                                    <strong>Members:</strong> <?= htmlspecialchars(str_replace('___', ', ', $proj['student_names'])); ?>
                                </p>

                                <!-- Document Status List (Neutral styling for pending items instead of red) -->
                                <div class="mb-3">
                                    <span class="d-block fw-bold text-secondary small mb-2">Document Status:</span>
                                    <div class="d-flex flex-column gap-1">
                                        <?php foreach ($all_document_types as $key_type => $label_name): 
                                            $is_uploaded = isset($uploaded_map[$key_type]);
                                        ?>
                                            <div class="d-flex align-items-center justify-content-between p-1 px-2 rounded small <?= $is_uploaded ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-light text-muted border'; ?>">
                                                <span>
                                                    <i class="fas <?= $is_uploaded ? 'fa-check-circle text-success' : 'fa-clock text-secondary'; ?> me-1"></i> 
                                                    <?= $label_name; ?>
                                                </span>
                                                <span class="badge <?= $is_uploaded ? 'bg-success' : 'bg-secondary'; ?>" style="font-size: 10px;">
                                                    <?= $is_uploaded ? 'Submitted' : 'Pending'; ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="mt-auto pt-3 border-top text-end">
                                    <a href="project_marks.php?project_id=<?= $current_proj_id; ?>" class="btn btn-primary btn-sm fw-bold px-3 w-100">
                                        <i class="fas fa-eye me-1"></i> Review & Evaluate Marks
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm p-5 text-center bg-white">
                        <div class="card-body">
                            <i class="fas fa-folder-open fa-3x mb-3 text-secondary"></i>
                            <h4 class="fw-bold text-dark">No Projects / Students Supervised</h4>
                            <p class="text-muted mb-0">You do not have any student projects under your supervision at the moment.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>