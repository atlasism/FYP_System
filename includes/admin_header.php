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
    <style>
        :root { --admin-sidebar: 272px; --admin-blue: #1f63aa; --admin-ink: #10243d; --admin-bg: #e9eef4; }
        body { margin: 0; background: var(--admin-bg); color: var(--admin-ink); font-family: "Segoe UI", sans-serif; }
        .admin-shell { min-height: 100vh; }
        .admin-sidebar { position: fixed; inset: 0 auto 0 0; z-index: 1040; width: var(--admin-sidebar); padding: 22px 16px; background: linear-gradient(180deg, #10243d 0%, #183b63 100%); color: #fff; box-shadow: 8px 0 26px rgba(20, 35, 65, .12); transition: transform .25s ease, width .25s ease; }
        .admin-brand { display: flex; align-items: center; gap: 12px; padding: 2px 10px 22px; color: #fff; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,.12); }
        .admin-brand-icon { width: 82px; height: 52px; flex: 0 0 82px; display: grid; place-items: center; font-size: 1.35rem; }
        .admin-brand-icon img { width: 100%; height: 100%; object-fit: contain; }
        .admin-brand strong { display: block; font-size: 1.3rem; line-height: 1.1; }
        .admin-brand small { display: block; margin-top: 5px; color: #a9bcda; font-size: .72rem; letter-spacing: .08em; text-transform: uppercase; }
        .admin-department { margin: 20px 8px 18px; padding: 10px 12px; border-radius: 10px; color: #f0d0a8; background: rgba(180,119,67,.2); font-size: .78rem; font-weight: 700; }
        .admin-nav .nav-link { display: flex; align-items: center; gap: 13px; margin: 5px 0; padding: 12px 13px; color: #bdcbe1; border-radius: 11px; font-weight: 600; transition: .2s ease; }
        .admin-nav .nav-link i { width: 22px; text-align: center; font-size: 1.15rem; }
        .admin-nav .nav-link:hover, .admin-nav .nav-link.active { color: #fff; background: #2563eb; box-shadow: 0 8px 18px rgba(37,99,235,.28); }
        .admin-logout { position: absolute; right: 16px; bottom: 20px; left: 16px; }
        .admin-main { min-height: 100vh; margin-left: var(--admin-sidebar); }
        .admin-sidebar.collapsed { width: 82px; }
        .admin-sidebar.collapsed .admin-brand-icon { width: 48px; height: 48px; flex-basis: 48px; }
        .admin-sidebar.collapsed .admin-brand { justify-content: center; padding-left: 0; padding-right: 0; }
        .admin-sidebar.collapsed .admin-brand > span:last-child, .admin-sidebar.collapsed .admin-department > span, .admin-sidebar.collapsed .admin-nav .nav-link > span, .admin-sidebar.collapsed .admin-logout > span { display: none; }
        .admin-sidebar.collapsed .admin-department { text-align: center; padding-left: 0; padding-right: 0; }
        .admin-sidebar.collapsed .admin-nav .nav-link, .admin-sidebar.collapsed .admin-logout { justify-content: center; gap: 0; }
        .admin-main.sidebar-collapsed { margin-left: 82px; }
        .admin-topbar { position: sticky; top: 0; z-index: 1030; min-height: 72px; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; color: #fff; background: #10243d; border-bottom: 1px solid rgba(255,255,255,.12); backdrop-filter: blur(12px); }
        .admin-content { min-height: calc(100vh - 72px); padding: 28px 30px 46px; }
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
    </style>
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
        <a href="dashboard.php" class="admin-brand">
            <span class="admin-brand-icon"><img src="../assets/image/logo.png" alt="SPInE logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';"><i class="bi bi-grid-1x2-fill" style="display:none;"></i></span>
            <span><strong>SPInE</strong><small>Politeknik Besut</small></span>
        </a>
        <div class="admin-department"><i class="bi bi-building me-2"></i><span>JTMK | DFT50114</span></div>
        <nav class="admin-nav nav flex-column">
            <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
            <a class="nav-link <?= $current_page === 'manage_users.php' ? 'active' : ''; ?>" href="manage_users.php"><i class="bi bi-people"></i><span>Manage Users</span></a>
            <a class="nav-link <?= $current_page === 'manage_projects.php' ? 'active' : ''; ?>" href="manage_projects.php"><i class="bi bi-kanban"></i><span>Manage Projects</span></a>
            <a class="nav-link <?= $current_page === 'panel_qr.php' ? 'active' : ''; ?>" href="panel_qr.php"><i class="bi bi-qr-code"></i><span>External Panel QR</span></a>
            <a class="nav-link <?= $current_page === 'reports.php' ? 'active' : ''; ?>" href="reports.php"><i class="bi bi-bar-chart-line"></i><span>Reports</span></a>
            <a class="nav-link <?= $current_page === 'settings.php' ? 'active' : ''; ?>" href="settings.php"><i class="bi bi-gear"></i><span>Settings</span></a>
        </nav>
        <a href="../logout.php" class="admin-logout nav-link text-warning" onclick="return confirm('Log out from the Admin Panel?');"><i class="bi bi-box-arrow-right me-2"></i><span>Logout</span></a>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar">
            <button class="admin-toggle" id="adminSidebarToggle" type="button" aria-label="Toggle sidebar"><i class="bi bi-list"></i></button>
            <div class="admin-profile"><a class="btn btn-outline-light btn-sm" href="../index.php"><i class="bi bi-house-door me-1"></i>Main Page</a><div class="text-end"><small class="text-muted d-block">Signed in as</small><strong><?= sanitize($_SESSION['full_name'] ?? 'Admin'); ?></strong></div><span class="admin-avatar"><i class="bi bi-person-fill"></i></span></div>
        </header>
        <section class="admin-content">
