<?php
require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Location: login.php');
exit();

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$error_msg = '';
$success_msg = '';
$jtmk_department = 'JTMK';
$it_program = 'JTMK - Information Technology';
$default_course_code = 'DFT50114';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_btn'])) {
    $full_name       = trim($_POST['full_name']);
    $ic_number       = trim($_POST['ic_number']);
    $matric_no       = trim($_POST['matric_no'] ?? '');
    $email           = trim($_POST['email']);
    $password        = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role            = $_POST['role'] ?? '';
    $department      = $jtmk_department;

    // Check if passwords match
    if ($password !== $confirm_password) {
        $error_msg = "Passwords do not match. Please check again.";
    } elseif (empty($full_name) || empty($ic_number) || ($role === 'Student' && empty($matric_no)) || empty($password) || empty($role)) {
        $error_msg = "Please complete all mandatory fields (*).";
    } else {
        // Check if IC number or Matrix No is already registered
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE ic_number = ? OR (? <> '' AND matric_no = ?)");
        $check_stmt->bind_param("sss", $ic_number, $matric_no, $matric_no);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();

        if ($check_res->num_rows > 0) {
            $error_msg = "This IC is already registered in the system.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Use IC value as the username value to satisfy database constraints if needed
            $username = $ic_number;

            $stmt = $conn->prepare("INSERT INTO users (username, ic_number, matric_no, full_name, email, password, role, department, program_name, course_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $username, $ic_number, $matric_no, $full_name, $email, $hashed_password, $role, $department, $it_program, $default_course_code);

            if ($stmt->execute()) {
                $success_msg = "Registration successful! Please log in.";
                echo "<script>setTimeout(function() { window.location='login.php'; }, 2000);</script>";
            } else {
                $error_msg = "Registration error: " . $stmt->error;
            }
        }
    }
}

include_once 'includes/header.php';
include_once 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card card-custom p-4 shadow border-0 rounded-3 bg-white">
                
                <div class="text-center mb-4">
                    <div class="mb-2">
                        <i class="fas fa-user-plus fa-3x text-primary"></i>
                    </div>
                    <h4 class="fw-bold">New Account Registration</h4>
                    <small class="text-muted">SPInE Portal Politeknik Besut</small>
                </div>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger py-2 small fw-bold text-center">
                        <i class="fas fa-exclamation-triangle me-1"></i> <?= sanitize($error_msg); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success py-2 small fw-bold text-center">
                        <i class="fas fa-check-circle me-1"></i> <?= sanitize($success_msg); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row">
                        <div class="mb-3 col-12">
                            <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" placeholder="E.g: AMINAH JUNAIDI " required autocomplete="off">
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">IC <span class="text-danger">*</span></label>
                            <input type="text" name="ic_number" class="form-control" placeholder="E.g: 340101011234" required autocomplete="off">
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">Matrix No <span class="text-muted small">(Student only)</span></label>
                            <input type="text" name="matric_no" class="form-control" placeholder="E.g: 34DIT2xFxxx" autocomplete="off">
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="name@gmail.com">
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lock text-secondary"></i></span>
                                <input type="password" name="password" class="form-control" placeholder="******" required>
                            </div>
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lock text-secondary"></i></span>
                                <input type="password" name="confirm_password" class="form-control" placeholder="******" required>
                            </div>
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="">-- Select Role --</option>
                                <option value="Student">Student</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">Department</label>
                            <input type="text" class="form-control" value="JTMK - Department of Information and Communication Technology" readonly>
                        </div>

                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-bold">Programme / Course</label>
                            <input type="text" class="form-control" value="<?= sanitize($it_program . ' | ' . $default_course_code . ' - Integrated Project'); ?>" readonly>
                        </div>
                    </div>

                    <button type="submit" name="register_btn" class="btn btn-success w-100 fw-bold py-2 shadow-sm mt-3">
                        <i class="fas fa-user-plus me-1"></i> Register New Account
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <small class="text-muted d-block mb-2">Already have an account?</small>
                    <a href="login.php" class="btn btn-outline-primary btn-sm fw-bold px-3">
                        <i class="fas fa-sign-in-alt me-1"></i> Login to Account
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>