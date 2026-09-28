<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Supervisor']);

$supervisor_id = (int) ($_SESSION['user_id'] ?? 0);
$student_id = (int) ($_GET['student_id'] ?? $_POST['student_id'] ?? 0);
$message = '';
$error = '';
$csrf_token = $_SESSION['csrf_verify_logbook'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_verify_logbook'] = $csrf_token;

$student_stmt = $conn->prepare("SELECT u.id, u.full_name, u.matric_no, u.department, u.class_name, ss.session FROM supervisor_students ss INNER JOIN users u ON u.id = ss.student_id AND u.role = 'Student' WHERE ss.supervisor_id = ? AND u.id = ? LIMIT 1");
$student_stmt->bind_param('ii', $supervisor_id, $student_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();

if (!$student) {
    http_response_code(404);
    exit('Student is not assigned to this supervisor.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form session. Please try again.';
    } else {
        $conn->begin_transaction();
        try {
            $logbook_stmt = $conn->prepare("INSERT INTO supervisor_logbook (supervisor_id, student_id, week_no, is_verified, verified_at) VALUES (?, ?, ?, ?, IF(? = 1, NOW(), NULL)) ON DUPLICATE KEY UPDATE is_verified = VALUES(is_verified), verified_at = IF(VALUES(is_verified) = 1, NOW(), NULL)");
            for ($week = 1; $week <= 14; $week++) {
                $is_verified = isset($_POST['week_' . $week]) ? 1 : 0;
                $logbook_stmt->bind_param('iiiii', $supervisor_id, $student_id, $week, $is_verified, $is_verified);
                $logbook_stmt->execute();
            }
            $conn->commit();
            $message = 'Log book verification updated successfully.';
        } catch (Throwable $exception) {
            $conn->rollback();
            $error = 'Unable to update the log book verification.';
        }
    }
}

$verified_weeks = [];
$logbook_query = $conn->prepare("SELECT week_no, is_verified, verified_at FROM supervisor_logbook WHERE supervisor_id = ? AND student_id = ?");
$logbook_query->bind_param('ii', $supervisor_id, $student_id);
$logbook_query->execute();
$logbook_result = $logbook_query->get_result();
while ($row = $logbook_result->fetch_assoc()) {
    $verified_weeks[(int) $row['week_no']] = $row;
}

include_once '../includes/header.php';
include_once '../includes/sidebar_supervisor.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1"><i class="fas fa-book me-2"></i>Log Book Verification</h3>
            <p class="text-muted mb-0">Weekly activity checklist for <?= sanitize($student['full_name']); ?>.</p>
        </div>
        <a href="my_students.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Students</a>
    </div>

    <div class="card card-custom border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-1"><?= sanitize($student['full_name']); ?></h5>
            <span class="text-muted">Matrix No: <?= sanitize($student['matric_no'] ?: 'Not Set'); ?> | Session: <?= sanitize($student['session'] ?? '-'); ?></span>
        </div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= sanitize($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error); ?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token); ?>">
        <input type="hidden" name="student_id" value="<?= $student_id; ?>">
        <div class="card card-custom border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Weekly Checklist</h5>
                <span class="badge bg-info text-dark">Weeks 1 - 14</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th class="ps-4">Week</th><th>Verification</th><th>Last Verified</th></tr>
                        </thead>
                        <tbody>
                            <?php for ($week = 1; $week <= 14; $week++): ?>
                                <?php $entry = $verified_weeks[$week] ?? null; $checked = $entry && (int) $entry['is_verified'] === 1; ?>
                                <tr>
                                    <td class="ps-4 fw-bold">Week <?= $week; ?></td>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="week_<?= $week; ?>" id="week_<?= $week; ?>" <?= $checked ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="week_<?= $week; ?>">Activity verified</label>
                                        </div>
                                    </td>
                                    <td class="text-muted small"><?= $checked && !empty($entry['verified_at']) ? date('d/m/Y h:i A', strtotime($entry['verified_at'])) : 'Not verified'; ?></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-end py-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Verification</button>
            </div>
        </div>
    </form>
</div>

<?php include_once '../includes/footer.php'; ?>
