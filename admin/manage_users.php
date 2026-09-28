<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Admin']);

$message = '';
$error = '';
$csrf_token = $_SESSION['csrf_admin_users'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_admin_users'] = $csrf_token;
$roles = ['Admin', 'Supervisor', 'Student'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $ic_number = trim($_POST['ic_number'] ?? '');
    $matric_no = trim($_POST['matric_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form session.';
    } elseif (empty($full_name) || empty($ic_number) || !in_array($role, $roles, true) || strlen($password) < 8 || ($role === 'Student' && empty($matric_no))) {
        $error = 'Complete all required fields. Student accounts require Matrix No and password must be at least 8 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $check = $conn->prepare('SELECT id FROM users WHERE ic_number = ? OR email = ? OR (? <> "" AND matric_no = ?) LIMIT 1');
        $check->bind_param('ssss', $ic_number, $email, $matric_no, $matric_no);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'IC, email or Matrix No is already registered.';
        } else {
            $username = $ic_number;
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $program_name = 'JTMK - Information Technology';
            $course_code = 'DFT50114';
            $stmt = $conn->prepare('INSERT INTO users (username, ic_number, matric_no, full_name, email, password, role, department, program_name, course_code) VALUES (?, ?, ?, ?, ?, ?, ?, "JTMK", ?, ?)');
            $stmt->bind_param('sssssssss', $username, $ic_number, $matric_no, $full_name, $email, $hashed_password, $role, $program_name, $course_code);
            if ($stmt->execute()) {
                $message = 'User added successfully.';
            } else {
                $error = 'Unable to add user.';
            }
        }
    }
}

$users = $conn->query("SELECT id, full_name, ic_number, matric_no, email, role, department, program_name, course_code FROM users WHERE department = 'JTMK' ORDER BY FIELD(role, 'Admin', 'Supervisor', 'Student'), full_name");

include_once '../includes/admin_header.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div><h3 class="fw-bold text-primary mb-1"><i class="bi bi-people-fill me-2"></i>Manage JTMK Users</h3><p class="text-muted mb-0">Admin-only account management for JTMK and DFT50114.</p></div>
        <a href="dashboard.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= sanitize($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error); ?></div><?php endif; ?>

    <div class="card admin-card p-4 mb-4">
        <h5 class="fw-bold mb-3"><i class="fas fa-user-plus text-primary me-2"></i>Add User</h5>
        <form method="POST" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token); ?>">
            <div class="col-md-6"><label class="form-label fw-bold">Full Name *</label><input name="full_name" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label fw-bold">I/C No *</label><input name="ic_number" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label fw-bold">Matrix No</label><input name="matric_no" class="form-control" placeholder="Student only"></div>
            <div class="col-md-4"><label class="form-label fw-bold">Email *</label><input type="email" name="email" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label fw-bold">Role *</label><select name="role" class="form-select" required><option value="">-- Select Role --</option><?php foreach ($roles as $role_option): ?><option value="<?= $role_option; ?>"><?= $role_option === 'Supervisor' ? 'Supervisor / Lecturer' : $role_option; ?></option><?php endforeach; ?></select></div>
            <div class="col-md-3"><label class="form-label fw-bold">Temporary Password *</label><input type="password" name="password" class="form-control" minlength="8" required></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i class="fas fa-save me-1"></i> Add</button></div>
        </form>
    </div>

    <div class="card admin-card p-4">
        <h5 class="fw-bold mb-3"><i class="fas fa-list text-primary me-2"></i>JTMK Users</h5>
        <div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Name</th><th>I/C No</th><th>Matrix No</th><th>Email</th><th>Role</th><th>Programme</th></tr></thead><tbody>
        <?php while ($user = $users->fetch_assoc()): ?><tr><td class="fw-bold"><?= sanitize($user['full_name']); ?></td><td><?= sanitize($user['ic_number']); ?></td><td><?= sanitize($user['matric_no'] ?: '-'); ?></td><td><?= sanitize($user['email']); ?></td><td><span class="badge <?= $user['role'] === 'Student' ? 'bg-info text-dark' : ($user['role'] === 'Supervisor' ? 'bg-success' : 'bg-primary'); ?>"><?= sanitize($user['role'] === 'Supervisor' ? 'Supervisor / Lecturer' : $user['role']); ?></span></td><td><?= sanitize($user['course_code'] . ' - ' . $user['program_name']); ?></td></tr><?php endwhile; ?>
        </tbody></table></div>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
