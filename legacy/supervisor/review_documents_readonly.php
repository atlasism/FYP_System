<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Supervisor']);

$supervisor_id = (int) ($_SESSION['user_id'] ?? 0);
$selected_project_id = (int) ($_GET['project_id'] ?? 0);
$document_types = [
    'A' => 'A: Proposal Presentation',
    'B' => 'B: Project Demonstration 1',
    'C' => 'C: Project Demonstration 2',
    'D' => 'D: Project Demonstration 3',
    'E' => 'E: Final Presentation - Poster',
    'F' => 'F: Final Presentation',
    'LOG_BOOK' => 'Log Book',
    'TECHNICAL_REPORT' => 'Technical Report'
];

$projects_stmt = $conn->prepare("SELECT DISTINCT p.id, p.title, p.category, p.session FROM projects p LEFT JOIN supervisor_students ss ON ss.student_id = p.student_id WHERE p.department = 'JTMK' AND p.course_code = 'DFT50114' AND (p.supervisor_id = ? OR ss.supervisor_id = ?) ORDER BY p.project_group_no ASC");
$projects_stmt->bind_param('ii', $supervisor_id, $supervisor_id);
$projects_stmt->execute();
$projects = $projects_stmt->get_result();

$selected_project = null;
$documents = [];
if ($selected_project_id > 0) {
    $project_stmt = $conn->prepare("SELECT DISTINCT p.id, p.title, p.category, p.session FROM projects p LEFT JOIN supervisor_students ss ON ss.student_id = p.student_id WHERE p.id = ? AND p.department = 'JTMK' AND p.course_code = 'DFT50114' AND (p.supervisor_id = ? OR ss.supervisor_id = ?)");
    $project_stmt->bind_param('iii', $selected_project_id, $supervisor_id, $supervisor_id);
    $project_stmt->execute();
    $selected_project = $project_stmt->get_result()->fetch_assoc();

    if ($selected_project) {
        $document_stmt = $conn->prepare('SELECT doc_type, file_path, original_name, status, uploaded_at FROM project_documents WHERE project_id = ? ORDER BY uploaded_at DESC');
        $document_stmt->bind_param('i', $selected_project_id);
        $document_stmt->execute();
        $document_result = $document_stmt->get_result();
        while ($document = $document_result->fetch_assoc()) {
            $documents[] = $document;
        }
    }
}

include_once '../includes/header.php';
include_once '../includes/sidebar_supervisor.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div><h3 class="fw-bold text-primary mb-1"><i class="fas fa-file-alt me-2"></i>Review Documents</h3><p class="text-muted mb-0">Read-only document review for JTMK projects. No numerical marks or grades are entered here.</p></div>
    </div>

    <?php if (!$selected_project): ?>
        <div class="card card-custom border-0 shadow-sm">
            <div class="card-header bg-white"><h5 class="fw-bold mb-0">Supervised Projects</h5></div>
            <div class="list-group list-group-flush">
                <?php if ($projects->num_rows === 0): ?><div class="p-4 text-muted">No supervised projects found.</div><?php endif; ?>
                <?php while ($project = $projects->fetch_assoc()): ?><a class="list-group-item list-group-item-action p-3" href="review_documents_readonly.php?project_id=<?= (int) $project['id']; ?>"><span class="fw-bold d-block"><?= sanitize($project['title']); ?></span><small class="text-muted"><?= sanitize($project['category']); ?> | <?= sanitize($project['session']); ?></small><i class="fas fa-chevron-right float-end text-primary mt-1"></i></a><?php endwhile; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="fw-bold mb-1"><?= sanitize($selected_project['title']); ?></h4><span class="badge bg-info text-dark"><?= sanitize($selected_project['category']); ?></span></div><a href="review_documents_readonly.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Projects</a></div>
        <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Document viewing only. Milestone results are managed separately as Passed or Not Passed.</div>
        <div class="card card-custom border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Category</th><th>File</th><th>Status</th><th>Uploaded</th><th class="text-end">Action</th></tr></thead><tbody>
        <?php if (!$documents): ?><tr><td colspan="5" class="text-center text-muted py-4">No documents submitted.</td></tr><?php else: foreach ($documents as $document): ?><tr><td class="fw-bold"><?= sanitize($document_types[$document['doc_type']] ?? $document['doc_type']); ?></td><td><?= sanitize($document['original_name'] ?: basename($document['file_path'])); ?></td><td><span class="badge <?= ($document['status'] ?? 'Pending') === 'Approved' ? 'bg-success' : (($document['status'] ?? 'Pending') === 'Rejected' ? 'bg-danger' : 'bg-secondary'); ?>"><?= sanitize($document['status'] ?? 'Pending'); ?></span></td><td><?= !empty($document['uploaded_at']) ? date('d/m/Y h:i A', strtotime($document['uploaded_at'])) : '-'; ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="../uploads/documents/<?= rawurlencode(basename($document['file_path'])); ?>" target="_blank"><i class="fas fa-eye me-1"></i> View</a></td></tr><?php endforeach; endif; ?>
        </tbody></table></div></div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>
