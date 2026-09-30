<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FYP Inventory & Evaluation System | Politeknik Besut</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/fyp_system/assets/style.css">
    <?php if (isset($_SESSION['user_id']) && !in_array($current_page, ['index.php', 'login.php'], true)): ?><link rel="stylesheet" href="/fyp_system/assets/user-preferences.css?v=9"><?php endif; ?>
</head>
<body class="d-flex flex-column min-vh-100 <?= $current_page === 'login.php' ? 'login-page' : ($current_page === 'index.php' ? 'home-page' : 'site-page'); ?><?= isset($_SESSION['user_id']) && !in_array($current_page, ['index.php', 'login.php'], true) ? ' user-portal' : ''; ?>">