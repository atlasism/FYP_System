<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Supervisor']);

header('Location: my_students.php');
exit();

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$project_id = intval($_GET['id'] ?? 0);
$supervisor_id = $_SESSION['user_id'] ?? 0;
$success_msg = '';
$error_msg = '';

// Semak dan ambil maklumat projek serta ahli kumpulan di bawah seliaan SV
$project_query = "
    SELECT 
        p.id as project_id,
        p.title,
        p.session,
        COALESCE(p.department, u_leader.department, 'GENERAL') as department,
        u_leader.full_name as leader_name,
        u_leader.matric_no as leader_matric,
        GROUP_CONCAT(DISTINCT CONCAT(u_member.full_name, ' (', u_member.matric_no, ')') SEPARATOR '||') as members_list
    FROM projects p
    LEFT JOIN users u_leader ON p.student_id = u_leader.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    LEFT JOIN users u_member ON pm.student_id = u_member.id AND u_member.id <> p.student_id
    JOIN supervisor_students ss ON u_leader.id = ss.student_id
    WHERE p.id = ? AND ss.supervisor_id = ?
    GROUP BY p.id
";

$stmt_p = $conn->prepare($project_query);
$stmt_p->bind_param("ii", $project_id, $supervisor_id);
$stmt_p->execute();
$project_res = $stmt_p->get_result();

if ($project_res->num_rows === 0) {
    echo "<script>alert('Unauthorized access or project not found.'); window.location='project_marks.php';</script>";
    exit;
}

$project = $project_res->fetch_assoc();

// AMBIL SENARAI DOKUMEN DAN FILE_PATH BERSAMA-SAMA
$submitted_docs = [];
$stmt_sub = $conn->prepare("SELECT doc_type, file_path FROM project_documents WHERE project_id = ?");
$stmt_sub->bind_param("i", $project_id);
$stmt_sub->execute();
$res_sub = $stmt_sub->get_result();
while ($row = $res_sub->fetch_assoc()) {
    $submitted_docs[trim($row['doc_type'])] = $row['file_path'];
}

// Proses Simpan / Kemaskini Markah secara manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    $proposal_presentation = min(10, max(0, floatval($_POST['proposal_presentation'] ?? 0)));
    $demonstration_1 = min(10, max(0, floatval($_POST['demonstration_1'] ?? 0)));
    $demonstration_2 = min(10, max(0, floatval($_POST['demonstration_2'] ?? 0)));
    $demonstration_3 = min(15, max(0, floatval($_POST['demonstration_3'] ?? 0)));
    $final_poster = min(15, max(0, floatval($_POST['final_poster'] ?? 0)));
    $final_presentation = min(15, max(0, floatval($_POST['final_presentation'] ?? 0)));
    $log_book = min(10, max(0, floatval($_POST['log_book'] ?? 0)));
    $technical_report = min(15, max(0, floatval($_POST['technical_report'] ?? 0)));

    $total_score = $proposal_presentation + $demonstration_1 + $demonstration_2 + $demonstration_3 + $final_poster + $final_presentation + $log_book + $technical_report;

    $check_exist = $conn->prepare("SELECT project_id FROM project_marks WHERE project_id = ?");
    $check_exist->bind_param("i", $project_id);
    $check_exist->execute();
    $exist_res = $check_exist->get_result();

    if ($exist_res->num_rows > 0) {
        $sql = "UPDATE project_marks SET 
                proposal_presentation = ?, 
                demonstration_1 = ?, 
                demonstration_2 = ?, 
                demonstration_3 = ?, 
                final_poster = ?, 
                final_presentation = ?, 
                log_book = ?, 
                technical_report = ?, 
                    total_score = ? 
                WHERE project_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dddddddddi", $proposal_presentation, $demonstration_1, $demonstration_2, $demonstration_3, $final_poster, $final_presentation, $log_book, $technical_report, $total_score, $project_id);
    } else {
        $sql = "INSERT INTO project_marks (project_id, proposal_presentation, demonstration_1, demonstration_2, demonstration_3, final_poster, final_presentation, log_book, technical_report, total_score) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iddddddddd", $project_id, $proposal_presentation, $demonstration_1, $demonstration_2, $demonstration_3, $final_poster, $final_presentation, $log_book, $technical_report, $total_score);
    }
    
    if ($stmt->execute()) {
        $success_msg = "Project marks successfully saved and updated!";
    } else {
        $error_msg = "Failed to save marks. Error: " . $stmt->error;
    }
}

// Ambil markah sedia ada
$marks_query = "SELECT * FROM project_marks WHERE project_id = ?";
$stmt_m = $conn->prepare($marks_query);
$stmt_m->bind_param("i", $project_id);
$stmt_m->execute();
$marks = $stmt_m->get_result()->fetch_assoc();

include_once '../includes/header.php';
include_once '../includes/sidebar_supervisor.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-primary mb-1"><i class="fas fa-clipboard-check me-2"></i>Project Evaluation & Marks</h4>
            <p class="text-muted mb-0">Evaluate student document breakdown components based on the 100% weight criteria.</p>
        </div>
        <a href="project_marks.php" class="btn btn-outline-secondary fw-bold">
            <i class="fas fa-arrow-left me-1"></i> Back to List
        </a>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> <?= $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> <?= $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4 bg-white rounded-3">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="badge bg-primary mb-2"><?= sanitize($project['session'] ?? '-'); ?> | <?= sanitize($project['department'] ?? 'GENERAL'); ?></span>
                    <h3 class="fw-bold text-dark mb-3"><?= sanitize($project['title']); ?></h3>
                    
                    <div>
                        <strong class="text-muted small d-block mb-1">GROUP MEMBERS:</strong>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if (!empty($project['leader_name'])): ?>
                                <span class="badge bg-primary text-white p-2">
                                    <i class="fas fa-user-shield me-1"></i> <?= sanitize($project['leader_name']); ?> (<?= sanitize($project['leader_matric']); ?>) [Leader]
                                </span>
                            <?php endif; ?>
                            <?php 
                                if (!empty($project['members_list'])) {
                                    $members = explode('||', $project['members_list']);
                                    foreach ($members as $m) {
                                        if (!empty(trim($m)) && strpos($m, $project['leader_matric']) === false) {
                                            echo '<span class="badge bg-light text-dark border p-2"><i class="fas fa-user me-1"></i> ' . sanitize($m) . '</span>';
                                        }
                                    }
                                }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 text-md-end mt-4 mt-md-0 border-start-md ps-md-4">
                    <div class="p-3 bg-light rounded-3 border">
                        <small class="text-muted fw-bold d-block mb-1 text-uppercase" style="font-size: 11px;">Total Overall Score</small>
                        <h1 class="fw-bold text-primary mb-0" id="display_total_score">
                            <?= number_format($marks['total_score'] ?? 0, 1); ?> <span class="fs-5 text-muted">/ 100%</span>
                        </h1>
                        <span class="badge bg-secondary mt-2" id="score_status_badge">
                            <?= isset($marks['total_score']) && $marks['total_score'] > 0 ? 'Evaluated' : 'Not Fully Graded'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="" method="POST" id="evaluationForm">
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
            <div class="card-header bg-light py-3">
                <h5 class="fw-bold text-dark mb-0"><i class="fas fa-list-alt me-2 text-primary"></i>Score Breakdown by Document Type (100%)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small">
                            <tr>
                                <th class="py-3 ps-4" style="width: 40%;">Document Type</th>
                                <th class="py-3 text-center" style="width: 20%;">Weight (%)</th>
                                <th class="py-3 text-center" style="width: 25%;">Score Given</th>
                                <th class="py-3 pe-4 text-end" style="width: 15%;">Max Marks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rubrics = [
                                [
                                    'name' => 'proposal_presentation',
                                    'db_keys' => ['A'],
                                    'label' => 'A: Proposal Presentation',
                                    'desc' => 'Proposal presentation evaluation',
                                    'max' => 10
                                ],
                                [
                                    'name' => 'demonstration_1', 'db_keys' => ['B'], 'label' => 'B: Project Demonstration 1', 'desc' => 'First project demonstration', 'max' => 10
                                ],
                                [
                                    'name' => 'demonstration_2', 'db_keys' => ['C'], 'label' => 'C: Project Demonstration 2', 'desc' => 'Second project demonstration', 'max' => 10
                                ],
                                [
                                    'name' => 'demonstration_3', 'db_keys' => ['D'], 'label' => 'D: Project Demonstration 3', 'desc' => 'Third project demonstration', 'max' => 15
                                ],
                                [
                                    'name' => 'final_poster', 'db_keys' => ['E'], 'label' => 'E: Final Presentation - Poster', 'desc' => 'Final poster presentation', 'max' => 15
                                ],
                                [
                                    'name' => 'final_presentation', 'db_keys' => ['F'], 'label' => 'F: Final Presentation', 'desc' => 'Final project presentation', 'max' => 15
                                ],
                                [
                                    'name' => 'log_book', 'db_keys' => ['LOG_BOOK'], 'label' => 'Log Book', 'desc' => 'Project activity log book', 'max' => 10
                                ],
                                [
                                    'name' => 'technical_report', 'db_keys' => ['TECHNICAL_REPORT'], 'label' => 'Technical Report', 'desc' => 'Final technical report',
                                    'max' => 15
                                ],
                            ];

                            foreach ($rubrics as $r):
                                $val = $marks[$r['name']] ?? 0;
                                
                                $is_submitted = false;
                                $file_path = '';
                                foreach ($r['db_keys'] as $k) {
                                    if (isset($submitted_docs[$k])) {
                                        $is_submitted = true;
                                        $file_path = '../uploads/documents/' . ltrim($submitted_docs[$k], '/');
                                        break;
                                    }
                                }
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= $r['label']; ?></div>
                                    <small class="text-muted d-block"><?= $r['desc']; ?></small>
                                    <div>
                                        <?php if ($is_submitted): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-1 px-2 py-1" style="font-size: 11px;" data-bs-toggle="modal" data-bs-target="#docModal<?= $r['name']; ?>">
                                                <i class="fas fa-eye me-1"></i> View Document
                                            </button>
                                            <span class="badge bg-success ms-1 mt-1" style="font-size: 10px;"><i class="fas fa-check-circle me-1"></i> Submitted</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger mt-1" style="font-size: 10px;"><i class="fas fa-times-circle me-1"></i> Not Submitted</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info text-dark px-2 py-1"><?= $r['max']; ?>%</span>
                                </td>
                                <td class="text-center">
                                    <?php if ($is_submitted): ?>
                                        <input type="number" 
                                               step="0.1" 
                                               min="0" 
                                               max="<?= $r['max']; ?>" 
                                               name="<?= $r['name']; ?>" 
                                               class="form-control text-center fw-bold score-input mx-auto" 
                                               style="max-width: 130px;" 
                                               value="<?= $val; ?>" 
                                               data-max="<?= $r['max']; ?>"
                                               required>
                                    <?php else: ?>
                                        <input type="text" 
                                               class="form-control text-center text-muted bg-light mx-auto" 
                                               style="max-width: 130px;" 
                                               value="Locked" 
                                               disabled>
                                        <input type="hidden" name="<?= $r['name']; ?>" value="0">
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end fw-bold text-muted">
                                    / <?= $r['max']; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                <span class="text-muted small"><i class="fas fa-info-circle me-1"></i> Marks can only be filled in if the student has submitted the relevant document.</span>
                <button type="submit" name="save_marks" class="btn btn-success px-5 py-2 fw-bold shadow-sm">
                    <i class="fas fa-save me-1"></i> Save Marks
                </button>
            </div>
        </div>
    </form>
</div>

<!-- MODAL UNTUK SETIAP DOKUMEN -->
<?php foreach ($rubrics as $r): ?>
    <?php 
        $is_submitted = false;
        $file_path = '';
        foreach ($r['db_keys'] as $k) {
            if (isset($submitted_docs[$k])) {
                $is_submitted = true;
                $file_path = '../uploads/documents/' . ltrim($submitted_docs[$k], '/');
                break;
            }
        }
        if ($is_submitted):
    ?>
    <div class="modal fade" id="docModal<?= $r['name']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="height: 90vh;">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-alt me-2"></i><?= $r['label']; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe src="<?= $file_path; ?>" class="w-100 h-100" style="border:none;"></iframe>
                </div>
                <div class="modal-footer bg-light">
                    <a href="<?= $file_path; ?>" target="_blank" class="btn btn-outline-primary btn-sm fw-bold">
                        <i class="fas fa-external-link-alt me-1"></i> Open in New Tab
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scoreInputs = document.querySelectorAll('.score-input');
    const displayTotal = document.getElementById('display_total_score');
    const badgeStatus = document.getElementById('score_status_badge');

    function calculateTotal() {
        let total = 0;
        scoreInputs.forEach(input => {
            let val = parseFloat(input.value) || 0;
            let max = parseFloat(input.getAttribute('data-max')) || 0;
            
            if (val > max) {
                input.value = max;
                val = max;
            }
            if (val < 0) {
                input.value = 0;
                val = 0;
            }

            total += val;
        });

        displayTotal.innerHTML = total.toFixed(1) + ' <span class="fs-5 text-muted">/ 100%</span>';
        if (total > 0) {
            badgeStatus.className = 'badge bg-success mt-2';
            badgeStatus.textContent = 'Evaluated';
        } else {
            badgeStatus.className = 'badge bg-secondary mt-2';
            badgeStatus.textContent = 'Not Fully Graded';
        }
    }

    scoreInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>