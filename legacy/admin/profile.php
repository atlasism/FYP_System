<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/profile_picture.php';
check_access(['Admin']);

$admin_id = (int) ($_SESSION['user_id'] ?? 0);
$message = '';
$error = '';
$csrf_token = $_SESSION['csrf_admin_profile'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_admin_profile'] = $csrf_token;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $ic_number = trim($_POST['ic_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $picture_result = save_profile_picture($conn, $admin_id, $_FILES['profile_picture'] ?? []);

    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form session. Please reload the page and try again.';
    } elseif ($picture_result['error'] !== '') {
        $error = $picture_result['error'];
    } elseif ($full_name === '' || $ic_number === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please complete your IC number, name and valid email address.';
    } elseif ($password !== '' && strlen($password) < 8) {
        $error = 'New password must be at least 8 characters.';
    } else {
        $duplicate = $conn->prepare('SELECT id FROM users WHERE (ic_number = ? OR email = ?) AND id <> ? LIMIT 1');
        $duplicate->bind_param('ssi', $ic_number, $email, $admin_id);
        $duplicate->execute();
        if ($duplicate->get_result()->num_rows > 0) {
            $error = 'That IC number or email is already in use.';
        } else {
            if ($password !== '') {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $statement = $conn->prepare('UPDATE users SET username = ?, ic_number = ?, full_name = ?, email = ?, password = ? WHERE id = ? AND role = \'Admin\'');
                $statement->bind_param('sssssi', $ic_number, $ic_number, $full_name, $email, $hashed_password, $admin_id);
            } else {
                $statement = $conn->prepare('UPDATE users SET username = ?, ic_number = ?, full_name = ?, email = ? WHERE id = ? AND role = \'Admin\'');
                $statement->bind_param('ssssi', $ic_number, $ic_number, $full_name, $email, $admin_id);
            }

            if ($statement->execute()) {
                $_SESSION['full_name'] = $full_name;
                $_SESSION['username'] = $ic_number;
                $_SESSION['matric_no'] = $ic_number;
                if (!empty($picture_result['filename'])) $_SESSION['profile_picture'] = $picture_result['filename'];
                $message = 'Admin profile updated successfully.';
            } else {
                $error = 'Unable to update the admin profile.';
            }
        }
    }
}

$statement = $conn->prepare("SELECT full_name, email, ic_number, department, role FROM users WHERE id = ? AND role = 'Admin' LIMIT 1");
$statement->bind_param('i', $admin_id);
$statement->execute();
$user = $statement->get_result()->fetch_assoc();

include_once '../includes/admin_header.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div><h3 class="fw-bold text-primary mb-1"><i class="bi bi-person-gear me-2"></i>Admin Profile</h3><p class="text-muted mb-0">Update your administrator account details and password.</p></div>
        <a href="dashboard.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= sanitize($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error); ?></div><?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card admin-card p-4">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token); ?>">
                    <div class="mb-3 text-center"><label class="form-label fw-bold d-block">Profile Picture</label><?php if (!empty($user['profile_picture'])): ?><img src="../uploads/profile/<?= rawurlencode($user['profile_picture']); ?>" alt="Profile picture" class="rounded-circle mb-2" style="width:96px;height:96px;object-fit:cover;"><?php else: ?><div class="rounded-circle bg-primary text-white d-inline-grid place-items-center mb-2" style="width:96px;height:96px;font-size:2rem;"><i class="fas fa-user"></i></div><?php endif; ?><input type="file" name="profile_picture" class="form-control" accept="image/jpeg,image/png,image/webp"><div class="form-text">JPG, PNG or WEBP, maximum 2 MB.</div></div>
                    <div class="mb-3"><label class="form-label fw-bold">IC Number</label><input type="text" name="ic_number" class="form-control" value="<?= sanitize($user['ic_number'] ?? ''); ?>" required></div>
                    <div class="mb-3"><label class="form-label fw-bold">Full Name</label><input type="text" name="full_name" class="form-control" value="<?= sanitize($user['full_name'] ?? ''); ?>" readonly></div>
                    <div class="mb-3"><label class="form-label fw-bold">Email</label><input type="email" name="email" class="form-control" value="<?= sanitize($user['email'] ?? ''); ?>" required></div>
                    <div class="mb-3"><label class="form-label fw-bold">Department</label><input type="text" class="form-control" value="<?= sanitize($user['department'] ?? ''); ?>" disabled></div>
                    <div class="mb-4"><label class="form-label fw-bold">Change Password</label><input type="password" name="password" class="form-control" minlength="8" placeholder="Leave blank to keep current password"><div class="form-text">Password must contain at least 8 characters.</div></div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fas fa-save me-1"></i><span data-i18n="Save Profile">Save Profile</span></button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
