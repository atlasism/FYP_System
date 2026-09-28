<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Student']);

$student_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $matric_no = trim($_POST['matric_no'] ?? '');
    $password = $_POST['password'];

    if (!empty($full_name) && !empty($email) && !empty($matric_no)) {
        $check_matric = $conn->prepare("SELECT id FROM users WHERE matric_no = ? AND id <> ?");
        $check_matric->bind_param("si", $matric_no, $student_id);
        $check_matric->execute();

        if ($check_matric->get_result()->num_rows > 0) {
            $error = "That matric number is already in use by another student.";
        } elseif (!preg_match('/^[A-Za-z0-9]+$/', $matric_no)) {
            $error = "The matric number can contain letters and numbers only.";
        } else {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, matric_no = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $full_name, $email, $matric_no, $hashed, $student_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, matric_no = ? WHERE id = ?");
            $stmt->bind_param("sssi", $full_name, $email, $matric_no, $student_id);
        }

        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $message = "Profile updated successfully.";
        } else {
            $error = "Unable to update the profile.";
        }
        }
    } else {
        $error = "Please enter your name, email address, and matric number.";
    }
}

// Ambil maklumat semasa
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

include_once '../includes/header.php';
include_once '../includes/navbar.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-custom p-4">
                <h4 class="fw-bold mb-3"><i class="fas fa-id-card me-2 text-primary"></i>My Profile</h4>
                
                <?php if ($message): ?><div class="alert alert-success py-2"><?= sanitize($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger py-2"><?= sanitize($error); ?></div><?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">IC Number</label>
                        <input type="text" class="form-control" value="<?= sanitize($user['ic_number']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Matric Number</label>
                        <input type="text" name="matric_no" class="form-control" value="<?= sanitize($user['matric_no'] ?? ''); ?>" placeholder="Example: 34DIT2xFxxx" pattern="[A-Za-z0-9]+" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Department</label>
                        <input type="text" class="form-control" value="<?= sanitize($user['department']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= sanitize($user['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= sanitize($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Change Password (Leave blank to keep your current password)</label>
                        <input type="password" name="password" class="form-control" placeholder="******">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Save Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>