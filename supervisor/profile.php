<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Supervisor']);

$supervisor_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($full_name) && !empty($email)) {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssi", $full_name, $email, $hashed, $supervisor_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $full_name, $email, $supervisor_id);
        }

        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $message = "Profile successfully updated.";
        } else {
            $error = "Error updating profile.";
        }
    }
}

// Fetch current supervisor information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $supervisor_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

include_once '../includes/header.php';
include_once '../includes/sidebar_supervisor.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <h4 class="fw-bold mb-3"><i class="fas fa-id-card me-2 text-primary"></i>Supervisor Profile</h4>
                
                <?php if ($message): ?><div class="alert alert-success py-2"><?= htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error); ?></div><?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">IC / Staff ID</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['ic_number'] ?? $user['username'] ?? '-'); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Department</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['department'] ?? '-'); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Change Password (Leave blank if not changing)</label>
                        <input type="password" name="password" class="form-control" placeholder="******">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Save Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>