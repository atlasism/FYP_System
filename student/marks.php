<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Student']);

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$student_id = $_SESSION['user_id'] ?? 0;

// Cari project_id pelajar ini sama ada sebagai ketua (projects.student_id) atau ahli (project_members)
$project_query = "
    SELECT p.id as project_id 
    FROM projects p 
    LEFT JOIN project_members pm ON p.id = pm.project_id 
    WHERE p.student_id = ? OR pm.student_id = ? 
    LIMIT 1
";
$stmt = $conn->prepare($project_query);
$stmt->bind_param("ii", $student_id, $student_id);
$stmt->execute();
$project_res = $stmt->get_result();

$verification = [
    'Demo 1' => 'Pending',
    'Demo 2' => 'Pending'
];
$verified_weeks = 0;
if ($project_res->num_rows > 0) {
    $project = $project_res->fetch_assoc();
    $project_id = $project['project_id'];

    $status_stmt = $conn->prepare("SELECT demo_type, status FROM student_demo_status WHERE student_id = ? ORDER BY demo_type");
    $status_stmt->bind_param('i', $student_id);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    while ($status = $status_result->fetch_assoc()) {
        $verification[$status['demo_type']] = $status['status'];
    }

    $logbook_stmt = $conn->prepare("SELECT COUNT(*) AS verified_weeks FROM supervisor_logbook WHERE student_id = ? AND is_verified = 1");
    $logbook_stmt->bind_param('i', $student_id);
    $logbook_stmt->execute();
    $verified_weeks = (int) ($logbook_stmt->get_result()->fetch_assoc()['verified_weeks'] ?? 0);
}

include_once '../includes/header.php';
include_once '../includes/sidebar_student.php'; // Sesuaikan dengan sidebar pelajar anda
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-primary mb-1"><i class="fas fa-flag-checkered me-2"></i>Milestone Verification Status</h4>
            <p class="text-muted mb-0">Supervisor verification status for your project milestones and log book.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body text-center p-4 d-flex flex-column justify-content-between">
                    <div>
                        <span class="text-uppercase fw-bold text-muted small d-block mb-3">Log Book Verification</span>
                        <h1 class="fw-bold text-primary display-4 mb-3"><?= $verified_weeks; ?><span class="fs-4 text-muted"> / 14 weeks</span></h1>
                        <span class="badge <?= $verified_weeks === 14 ? 'bg-success' : 'bg-warning text-dark'; ?> px-3 py-2"><?= $verified_weeks === 14 ? 'Complete' : 'In Progress'; ?></span>
                    </div>
                    <div class="text-start mt-4 pt-3 border-top">
                        <small class="text-muted">Your supervisor verifies weekly log book activities. No numerical marks are used.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-light py-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-list-check me-2 text-primary"></i>Demo Milestone Status</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary small">
                                <tr>
                                    <th class="py-3 ps-4">Milestone</th>
                                    <th class="py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($verification as $demo => $status): ?>
                                    <tr>
                                        <td class="ps-4"><i class="fas fa-presentation-screen text-primary fa-lg me-3"></i><span class="fw-bold text-dark"><?= sanitize($demo); ?></span></td>
                                        <td class="text-center"><span class="badge <?= $status === 'Passed' ? 'bg-success' : ($status === 'Not Passed' ? 'bg-danger' : 'bg-secondary'); ?> px-3 py-2"><?= sanitize($status); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>