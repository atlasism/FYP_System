<?php
require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Safety function if sanitize() is missing
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Redirect if already logged in based on role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Student') {
        header("Location: student/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'Supervisor') {
        header("Location: supervisor/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'Admin') {
        header("Location: admin/dashboard.php");
        exit();
    }
}

$login_error = '';

// Process Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_login'])) {
    $matric_no = trim($_POST['username']); 
    $password = trim($_POST['password']);

    if (!empty($matric_no) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, username, password, full_name, role, department, ic_number FROM users WHERE BINARY ic_number = ? OR BINARY email = ?");
        $stmt->bind_param("ss", $matric_no, $matric_no);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']     = $user['id'];
                $_SESSION['username']    = $user['username'];
                $_SESSION['full_name']   = $user['full_name'];
                $_SESSION['role']        = $user['role'];
                $_SESSION['department']  = $user['department'];
                $_SESSION['matric_no']   = $user['ic_number'];

                // Redirect according to role
                if ($user['role'] === 'Student') {
                    header("Location: student/dashboard.php");
                    exit();
                } elseif ($user['role'] === 'Supervisor') {
                    header("Location: supervisor/dashboard.php");
                    exit();
                } elseif ($user['role'] === 'Admin') {
                    header("Location: admin/dashboard.php");
                    exit();
                }
            } else {
                $login_error = "Incorrect password entered.";
            }
        } else {
            $login_error = "Email or IC not found. Please check your details.";
        }
    } else {
        $login_error = "Please fill in both IC and password fields.";
    }
}

include_once 'includes/header.php';
?>

<main class="login-stage">
    <div class="login-glass">
                
                <!-- Title & Subtitle -->
                <div class="text-center mb-4">
                    <img src="assets/image/logo.png" class="login-brand-mark" alt="SPInE Politeknik Besut">
                    <h3 class="fw-bold mb-1">FYP Inventory<br>System</h3>
                    <small class="text-muted">Repository &amp; Management Platform</small>
                </div>

                <!-- Login Error Display -->
                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger py-2 small fw-bold text-center">
                        <i class="fas fa-exclamation-triangle me-1"></i> <?= sanitize($login_error); ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="">
                    <input type="hidden" name="action_login" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">IC Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-id-card text-secondary"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="Enter your IC number" required autocomplete="off">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-secondary"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                        <i class="fas fa-sign-in-alt me-1"></i> Login
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <small class="text-muted">&copy; <?= date('Y'); ?> Politeknik Besut</small>
                    <a href="index.php" class="login-home-link d-block mt-2 text-decoration-none fw-semibold"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
                </div>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>í