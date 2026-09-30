<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | SPInE Politeknik Besut</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/user-preferences.css?v=20">
    <style>
        :root { --admin-sidebar: 272px; --admin-blue: #1f63aa; --admin-ink: #10243d; --admin-bg: #e9eef4; }
        body { margin: 0; background: var(--admin-bg); color: var(--admin-ink); font-family: "Segoe UI", sans-serif; }
        .admin-shell { min-height: 100vh; }
        .admin-sidebar { position: fixed; inset: 0 auto 0 0; z-index: 1040; width: var(--admin-sidebar); padding: 0 16px 22px; background: linear-gradient(180deg, #10243d 0%, #183b63 100%); color: #fff; box-shadow: 8px 0 26px rgba(20, 35, 65, .12); transition: transform .25s ease, width .25s ease; }
        .admin-brand { height: 82px; box-sizing: border-box; display: flex; align-items: center; gap: 14px; padding: 0 10px; color: #fff; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,.12); }
        .admin-brand-icon { width: 82px; height: 52px; flex: 0 0 82px; display: grid; place-items: center; font-size: 1.35rem; }
        .admin-brand-icon img { width: 100%; height: 100%; object-fit: contain; }
        .admin-brand strong { display: block; font-size: 1.3rem; line-height: 1.1; }
        .admin-brand small { display: block; margin-top: 5px; color: #a9bcda; font-size: .72rem; letter-spacing: .08em; text-transform: uppercase; }
        .admin-department { margin: 20px 8px 18px; padding: 10px 12px; border-radius: 10px; color: #f0d0a8; background: rgba(180,119,67,.2); font-size: .78rem; font-weight: 700; }
        .admin-nav .nav-link { display: flex; align-items: center; gap: 13px; margin: 5px 0; padding: 12px 13px; color: #bdcbe1; border-radius: 11px; font-weight: 600; transition: .2s ease; }
        .admin-nav .nav-link i { width: 22px; text-align: center; font-size: 1.15rem; }
        .admin-nav .nav-link > span,
        .admin-logout > span,
        .admin-department > span { transition: width .28s ease, max-width .28s ease, opacity .18s ease, visibility .18s ease; }
        .admin-nav .nav-link:hover, .admin-nav .nav-link.active { color: #fff; background: #2563eb; box-shadow: 0 8px 18px rgba(37,99,235,.28); }
        .admin-logout { position: absolute; right: 16px; bottom: 20px; left: 16px; }
        .admin-main { min-height: 100vh; margin-left: var(--admin-sidebar); }
        .admin-sidebar.collapsed { width: 82px; }
        .admin-sidebar.collapsed .admin-brand-icon { width: 48px; height: 48px; flex-basis: 48px; }
        .admin-sidebar.collapsed .admin-brand { justify-content: center; padding-left: 0; padding-right: 0; }
        .admin-sidebar.collapsed .admin-brand > span:last-child, .admin-sidebar.collapsed .admin-department > span, .admin-sidebar.collapsed .admin-nav .nav-link > span, .admin-sidebar.collapsed .admin-logout > span { display: block; width: 0; max-width: 0; overflow: hidden; opacity: 0; visibility: hidden; transition: width .28s ease, max-width .28s ease, opacity .18s ease, visibility .18s ease; }
        .admin-sidebar.collapsed .admin-department { text-align: center; padding-left: 0; padding-right: 0; }
        .admin-sidebar.collapsed .admin-nav .nav-link, .admin-sidebar.collapsed .admin-logout { justify-content: center; gap: 0; }
        .admin-main.sidebar-collapsed { margin-left: 82px; }
        .admin-topbar { position: sticky; top: 0; z-index: 1030; height: 82px; min-height: 82px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; color: #fff; background: #10243d; border-bottom: 1px solid rgba(255,255,255,.12); backdrop-filter: blur(12px); }
        .admin-content { min-height: calc(100vh - 82px); padding: 28px 30px 46px; }
        .admin-toggle { border: 0; background: transparent; color: #fff; font-size: 1.45rem; }
        .admin-topbar .text-muted { color: #c8d5e5 !important; }
        .admin-profile { display: flex; align-items: center; gap: 10px; }
        .admin-avatar { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 50%; color: #fff; background: var(--admin-blue); }
        .admin-card { border: 0; border-radius: 18px; box-shadow: 0 10px 28px rgba(31, 55, 92, .08); }
        .admin-stat { position: relative; overflow: hidden; min-height: 145px; color: #fff; border: 0; border-radius: 18px; box-shadow: 0 12px 24px rgba(31,55,92,.13); }
        .admin-stat::after { content: ""; position: absolute; width: 125px; height: 125px; right: -34px; bottom: -50px; border: 18px solid rgba(255,255,255,.12); border-radius: 50%; }
        .admin-stat-icon { width: 52px; height: 52px; display: grid; place-items: center; border-radius: 15px; color: #fff; background: rgba(255,255,255,.18); font-size: 1.55rem; }
        .admin-stat-blue { background: linear-gradient(135deg, #2563eb, #4f46e5); }
        .admin-stat-green { background: linear-gradient(135deg, #059669, #0f766e); }
        .admin-stat-cyan { background: linear-gradient(135deg, #0891b2, #2563eb); }
        @media (max-width: 991.98px) { .admin-sidebar { transform: translateX(-100%); } .admin-sidebar.open { transform: translateX(0); } .admin-main { margin-left: 0; } .admin-topbar, .admin-content { padding-left: 18px; padding-right: 18px; } }
        .ranking-podium { display: flex; align-items: flex-end; justify-content: center; gap: 14px; flex-wrap: wrap; }
        .podium-step { display: flex; flex-direction: column; align-items: center; width: 190px; }
        .podium-card { width: 100%; padding: 14px; border-radius: 14px; background: #fff; border: 1px solid #e5e9f0; box-shadow: 0 8px 18px rgba(31,55,92,.1); text-align: center; }
        .podium-medal { display: inline-block; padding: 3px 12px; border-radius: 20px; font-weight: 700; color: #fff; background: var(--admin-blue); margin-bottom: 6px; }
        .podium-step-1 .podium-medal { background: linear-gradient(135deg, #f5b301, #d69100); }
        .podium-step-2 .podium-medal { background: linear-gradient(135deg, #9aa5b1, #6b7684); }
        .podium-step-3 .podium-medal { background: linear-gradient(135deg, #c9782f, #a5601f); }
        .podium-bar { width: 100%; margin-top: 8px; border-radius: 10px 10px 0 0; background: linear-gradient(180deg, #2563eb, #10243d); }
        .podium-bar-1 { height: 90px; }
        .podium-bar-2 { height: 62px; }
        .podium-bar-3 { height: 40px; }
    </style>
</head>
<body class="user-portal">
<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
        <a href="dashboard.php" class="admin-brand">
            <span class="admin-brand-icon"><img src="../assets/image/logo.png" alt="SPInE logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';"><i class="bi bi-grid-1x2-fill" style="display:none;"></i></span>
            <span><strong>SPInE</strong><small>Politeknik Besut</small></span>
        </a>
        <div class="admin-department"><i class="bi bi-building me-2"></i><span>JTMK | DFT50114</span></div>
        <nav class="admin-nav nav flex-column">
            <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php"><i class="bi bi-speedometer2"></i><span data-i18n="Dashboard">Dashboard</span></a>
            <a class="nav-link <?= $current_page === 'manage_users.php' ? 'active' : ''; ?>" href="manage_users.php"><i class="bi bi-people"></i><span data-i18n="Manage Users">Manage Users</span></a>
            <a class="nav-link <?= $current_page === 'manage_projects.php' ? 'active' : ''; ?>" href="manage_projects.php"><i class="bi bi-kanban"></i><span data-i18n="Manage Projects">Manage Projects</span></a>
            <a class="nav-link <?= $current_page === 'panel_qr.php' ? 'active' : ''; ?>" href="panel_qr.php"><i class="bi bi-qr-code"></i><span data-i18n="External Panel QR">External Panel QR</span></a>
            <a class="nav-link <?= $current_page === 'reports.php' ? 'active' : ''; ?>" href="reports.php"><i class="bi bi-bar-chart-line"></i><span data-i18n="Reports">Reports</span></a>
            <a class="nav-link <?= $current_page === 'settings.php' ? 'active' : ''; ?>" href="settings.php"><i class="bi bi-gear"></i><span data-i18n="Settings">Settings</span></a>
        </nav>
        <a href="../logout.php" class="admin-logout nav-link text-warning" onclick="return confirm('Log out from the Admin Panel?');"><i class="bi bi-box-arrow-right me-2"></i><span data-i18n="Logout">Logout</span></a>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar">
            <button class="admin-toggle" id="adminSidebarToggle" type="button" aria-label="Toggle sidebar"><i class="bi bi-list"></i></button>
            <div class="admin-profile"><div class="admin-theme-toggle appearance-controls"><button type="button" class="btn btn-outline-light" data-theme-toggle aria-label="Toggle dark mode" title="Light/Dark"><i data-theme-icon class="fas fa-moon"></i></button><div class="language-switch" data-language-switch aria-label="Language"><button type="button" data-language-option="en">EN</button><button type="button" data-language-option="ms">BM</button></div></div><a class="btn btn-outline-light btn-sm" href="../index.php"><i class="bi bi-house-door me-1"></i><span data-i18n="Home">Home</span></a><a href="profile.php" class="text-end text-white text-decoration-none"><small class="text-muted d-block" data-i18n="Signed in as">Signed in as</small><strong><?= sanitize($_SESSION['full_name'] ?? 'Admin'); ?></strong></a><a href="profile.php" class="admin-avatar text-decoration-none"><?php if (!empty($_SESSION['profile_picture'])): ?><img src="../uploads/profile/<?= rawurlencode($_SESSION['profile_picture']); ?>" alt="Profile picture" class="profile-avatar rounded-circle"><?php else: ?><i class="bi bi-person-fill"></i><?php endif; ?></a></div>
        </header>
        <section class="admin-content">
