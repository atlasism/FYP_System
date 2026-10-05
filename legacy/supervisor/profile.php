<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/profile_picture.php';
check_access(['Supervisor']);

$supervisor_id = $_SESSION['user_id'];
$message = '';
$error = '';
$csrf_token = $_SESSION['csrf_supervisor_profile'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_supervisor_profile'] = $csrf_token;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $ic_number = trim($_POST['ic_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $picture_result = save_profile_picture($conn, (int) $supervisor_id, $_FILES['profile_picture'] ?? []);

    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form session. Please reload the page and try again.';
    } elseif ($picture_result['error'] !== '') {
        $error = $picture_result['error'];
    } elseif (empty($full_name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($ic_number)) {
        $error = 'Please complete your IC / Staff ID, name and email.';
    } elseif ($password !== '' && strlen($password) < 8) {
        $error = 'New password must be at least 8 characters.';
    } else {
        $check_ic = $conn->prepare("SELECT id FROM users WHERE ic_number = ? AND id <> ? LIMIT 1");
        $check_ic->bind_param('si', $ic_number, $supervisor_id);
        $check_ic->execute();
        if ($check_ic->get_result()->num_rows > 0) {
            $error = 'That IC / Staff ID is already in use.';
        }
    }

    if ($error === '') {
        if ($password !== '') {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, ic_number = ?, full_name = ?, email = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $ic_number, $ic_number, $full_name, $email, $hashed, $supervisor_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, ic_number = ?, full_name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $ic_number, $ic_number, $full_name, $email, $supervisor_id);
        }

        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['username'] = $ic_number;
            $_SESSION['matric_no'] = $ic_number;
            if (!empty($picture_result['filename'])) $_SESSION['profile_picture'] = $picture_result['filename'];
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

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
                    <div class="mb-3 text-center"><label class="form-label fw-bold d-block">Profile Picture</label><?php if (!empty($user['profile_picture'])): ?><img src="../uploads/profile/<?= rawurlencode($user['profile_picture']); ?>" alt="Profile picture" class="rounded-circle mb-2" style="width:96px;height:96px;object-fit:cover;"><?php else: ?><div class="rounded-circle bg-primary text-white d-inline-grid place-items-center mb-2" style="width:96px;height:96px;font-size:2rem;"><i class="fas fa-user"></i></div><?php endif; ?><input type="file" name="profile_picture" class="form-control" accept="image/jpeg,image/png,image/webp"><div class="form-text">JPG, PNG or WEBP, maximum 2 MB.</div></div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">IC / Staff ID</label>
                        <input type="text" name="ic_number" class="form-control" value="<?= htmlspecialchars($user['ic_number'] ?? $user['username'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Department</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['department'] ?? '-'); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? ''); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Change Password (Leave blank if not changing)</label>
                        <input type="password" name="password" class="form-control" placeholder="******">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><span data-i18n="Save Profile">Save Profile</span></button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>