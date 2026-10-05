<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Admin']);

$student_id = (int) ($_GET['id'] ?? $_POST['student_id'] ?? 0);
$message = '';
$error = '';
$csrf_token = $_SESSION['csrf_edit_student'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_edit_student'] = $csrf_token;
$session_options = [
    'I : 2023/2024',
    'II : 2023/2024',
    'I : 2024/2025',
    'II : 2024/2025',
    'I : 2025/2026',
    'II : 2025/2026',
    'I : 2026/2027'
];
$current_session_statement = $conn->prepare("SELECT academic_session FROM users WHERE id = ? AND role = 'Student' AND department = 'JTMK' LIMIT 1");
$current_session_statement->bind_param('i', $student_id);
$current_session_statement->execute();
$current_session = $current_session_statement->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $ic_number = trim($_POST['ic_number'] ?? '');
    $matric_no = trim($_POST['matric_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $academic_session = trim($_POST['academic_session'] ?? '') ?: trim($current_session['academic_session'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form session.';
    } elseif ($full_name === '' || $ic_number === '' || $matric_no === '') {
        $error = 'Complete the IC number, matric number and student name fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[A-Za-z0-9]+$/', $matric_no)) {
        $error = 'The matric number can contain letters and numbers only.';
    } elseif ($password !== '' && strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $duplicate = $conn->prepare("SELECT id FROM users WHERE (ic_number = ? OR matric_no = ? OR email = ?) AND id <> ? LIMIT 1");
        $duplicate->bind_param('sssi', $ic_number, $matric_no, $email, $student_id);
        $duplicate->execute();
        if ($duplicate->get_result()->num_rows > 0) {
            $error = 'The IC number, matric number or email is already in use.';
        } else {
            if ($password !== '') {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $statement = $conn->prepare("UPDATE users SET username = ?, ic_number = ?, matric_no = ?, email = ?, academic_session = ?, password = ? WHERE id = ? AND role = 'Student' AND department = 'JTMK'");
                $statement->bind_param('ssssssi', $ic_number, $ic_number, $matric_no, $email, $academic_session, $hashed_password, $student_id);
            } else {
                $statement = $conn->prepare("UPDATE users SET username = ?, ic_number = ?, matric_no = ?, email = ?, academic_session = ? WHERE id = ? AND role = 'Student' AND department = 'JTMK'");
                $statement->bind_param('sssssi', $ic_number, $ic_number, $matric_no, $email, $academic_session, $student_id);
            }

            if ($statement->execute()) {
                $message = 'Student account updated successfully.';
            } else {
                $error = 'Unable to update the student account.';
            }
        }
    }
}

$student_statement = $conn->prepare("SELECT id, full_name, ic_number, matric_no, email, department, academic_session FROM users WHERE id = ? AND role = 'Student' AND department = 'JTMK' LIMIT 1");
$student_statement->bind_param('i', $student_id);
$student_statement->execute();
$student = $student_statement->get_result()->fetch_assoc();
if (!$student) {
    http_response_code(404);
    exit('Student account not found.');
}

include_once '../includes/admin_header.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div><h3 class="fw-bold text-primary mb-1"><i class="bi bi-pencil-square me-2"></i>Edit Student</h3><p class="text-muted mb-0">Update student account information for JTMK.</p></div>
        <a href="manage_users.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-1"></i> Manage Users</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= sanitize($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error); ?></div><?php endif; ?>

    <div class="row justify-content-center"><div class="col-lg-8"><div class="card admin-card p-4">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token); ?>">
            <input type="hidden" name="student_id" value="<?= $student_id; ?>">
            <div class="mb-3"><label class="form-label fw-bold">Full Name</label><input type="text" name="full_name" class="form-control" value="<?= sanitize($student['full_name']); ?>" readonly></div>
            <div class="mb-3"><label class="form-label fw-bold">IC Number</label><input name="ic_number" class="form-control" value="<?= sanitize($student['ic_number']); ?>" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Matric Number</label><input name="matric_no" class="form-control" value="<?= sanitize($student['matric_no']); ?>" required pattern="[A-Za-z0-9]+"></div>
            <div class="mb-3"><label class="form-label fw-bold">Email</label><input type="email" name="email" class="form-control" value="<?= sanitize($student['email']); ?>" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Academic Session</label><select name="academic_session" class="form-select"><option value="">-- Select Session --</option><?php if (!empty($student['academic_session']) && !in_array($student['academic_session'], $session_options, true)): ?><option value="<?= sanitize($student['academic_session']); ?>" selected><?= sanitize($student['academic_session']); ?></option><?php endif; ?><?php foreach ($session_options as $session_option): ?><option value="<?= sanitize($session_option); ?>" <?= ($student['academic_session'] ?? '') === $session_option ? 'selected' : ''; ?>><?= sanitize($session_option); ?></option><?php endforeach; ?></select></div>
            <div class="mb-4"><label class="form-label fw-bold">Reset Password</label><input type="password" name="password" class="form-control" minlength="8" placeholder="Leave blank to keep current password"></div>
            <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fas fa-save me-1"></i> Save Student</button>
        </form>
    </div></div></div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
