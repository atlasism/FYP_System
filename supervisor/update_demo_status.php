<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Supervisor']);

$supervisor_id = (int) ($_SESSION['user_id'] ?? 0);
$student_id = (int) ($_GET['student_id'] ?? $_POST['student_id'] ?? 0);
$message = '';
$error = '';
$csrf_token = $_SESSION['csrf_demo_status'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_demo_status'] = $csrf_token;

$student_stmt = $conn->prepare("SELECT u.id, u.full_name, u.matric_no, u.department, u.class_name, ss.session FROM supervisor_students ss INNER JOIN users u ON u.id = ss.student_id AND u.role = 'Student' WHERE ss.supervisor_id = ? AND u.id = ? LIMIT 1");
$student_stmt->bind_param('ii', $supervisor_id, $student_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();

if (!$student) {
    http_response_code(404);
    exit('Student is not assigned to this supervisor.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $demo_1_status = $_POST['demo_1_status'] ?? '';
    $demo_2_status = $_POST['demo_2_status'] ?? '';
    $valid_statuses = ['Passed', 'Not Passed'];

    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form session. Please try again.';
    } elseif (!in_array($demo_1_status, $valid_statuses, true) || !in_array($demo_2_status, $valid_statuses, true)) {
        $error = 'Please select Passed or Not Passed for both milestones.';
    } else {
        $conn->begin_transaction();
        try {
            $status_stmt = $conn->prepare("INSERT INTO student_demo_status (supervisor_id, student_id, demo_type, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()");
            $demo_type = 'Demo 1';
            $status_stmt->bind_param('iiss', $supervisor_id, $student_id, $demo_type, $demo_1_status);
            $status_stmt->execute();
            $demo_type = 'Demo 2';
            $status_stmt->bind_param('iiss', $supervisor_id, $student_id, $demo_type, $demo_2_status);
            $status_stmt->execute();
            $conn->commit();
            $message = 'Demo milestone status updated successfully.';
        } catch (Throwable $exception) {
            $conn->rollback();
            $error = 'Unable to update the demo milestone status.';
        }
    }
}

$current_status = ['Demo 1' => 'Pending', 'Demo 2' => 'Pending'];
$status_query = $conn->prepare("SELECT demo_type, status FROM student_demo_status WHERE supervisor_id = ? AND student_id = ?");
$status_query->bind_param('ii', $supervisor_id, $student_id);
$status_query->execute();
$status_result = $status_query->get_result();
while ($row = $status_result->fetch_assoc()) {
    $current_status[$row['demo_type']] = $row['status'];
}

include_once '../includes/header.php';
include_once '../includes/sidebar_supervisor.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1"><i class="fas fa-flag-checkered me-2"></i>Demo Milestone Status</h3>
            <p class="text-muted mb-0">Update Passed or Not Passed status only. No numerical marks are entered.</p>
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

    <form method="POST" class="card card-custom border-0 shadow-sm">
        <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token); ?>">
        <input type="hidden" name="student_id" value="<?= $student_id; ?>">
        <div class="card-body p-4">
            <div class="alert alert-info"><i class="fas fa-shield-alt me-2"></i>These are milestone verification statuses, not grades or marks.</div>
            <div class="row g-4">
                <?php foreach (['Demo 1' => 'demo_1_status', 'Demo 2' => 'demo_2_status'] as $demo_label => $field_name): ?>
                    <div class="col-md-6">
                        <label class="form-label fw-bold" for="<?= $field_name; ?>"><?= $demo_label; ?> Status</label>
                        <select class="form-select form-select-lg" name="<?= $field_name; ?>" id="<?= $field_name; ?>" required>
                            <option value="">-- Select Status --</option>
                            <option value="Passed" <?= $current_status[$demo_label] === 'Passed' ? 'selected' : ''; ?>>Passed</option>
                            <option value="Not Passed" <?= $current_status[$demo_label] === 'Not Passed' ? 'selected' : ''; ?>>Not Passed</option>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-footer bg-white text-end py-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Status</button>
        </div>
    </form>
</div>

<?php include_once '../includes/footer.php'; ?>
