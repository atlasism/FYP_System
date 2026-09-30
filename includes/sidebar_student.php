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
    <title>Student Portal - SPInE FYP | Politeknik Besut</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/user-preferences.css?v=13">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 80px;
            --primary-color: #1f63aa;
            --bg-color: #e9eef4;
            --footer-height: 50px;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: var(--footer-height);
        }

        #wrapper {
            display: flex;
            min-height: calc(100vh - var(--footer-height));
            transition: all 0.3s ease;
        }

        /* Sidebar Styling */
        #sidebar {
            width: var(--sidebar-width);
            position: sticky;
            top: 0;
            align-self: flex-start;
            min-height: calc(100vh - var(--footer-height));
            height: calc(100vh - var(--footer-height));
            background: #10243d;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        #sidebar::before {
            content: "";
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar-width);
            background: #10243d;
            z-index: -1;
        }

        /* Kelakuan apabila Sidebar Dikecilkan (Collapsed) */
        #sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        #sidebar.collapsed::before {
            width: var(--sidebar-collapsed-width);
        }

        #sidebar.collapsed .link-text,
        #sidebar.collapsed .brand-text {
            display: block !important;
            width: 0;
            max-width: 0;
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transition: width .28s ease, max-width .28s ease, opacity .18s ease, visibility .18s ease;
        }

        #sidebar.collapsed .nav-link {
            text-align: center;
            padding: 12px 0;
            margin: 4px 10px;
        }

        #sidebar.collapsed .nav-link i {
            margin-right: 0 !important;
            font-size: 1.3rem;
        }

        #sidebar.collapsed .portal-brand {
            justify-content: center !important;
            gap: 0;
        }

        #sidebar .nav-link {
            color: #c8d5e5;
            padding: 12px 20px;
            margin: 4px 15px;
            border-radius: 10px;
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        #sidebar .nav-link:hover, 
        #sidebar .nav-link.active {
            color: #ffffff !important;
            background-color: var(--primary-color);
            box-shadow: 0 4px 10px rgba(31, 99, 170, 0.3);
        }

        #sidebar .nav-link i {
            width: 25px;
            font-size: 1.1rem;
        }

        #sidebar .link-text,
        #sidebar .brand-text {
            transition: width .28s ease, max-width .28s ease, opacity .18s ease, visibility .18s ease;
        }

        .portal-brand {
            height: 82px;
            min-height: 82px;
            box-sizing: border-box;
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255,255,255,.12);
            justify-content: flex-start !important;
            gap: 14px;
        }

        .portal-brand img {
            width: 82px;
            height: 52px;
            object-fit: contain;
        }

        .portal-brand .brand-text {
            color: #ffffff;
            line-height: 1.1;
        }

        .portal-brand .brand-text strong {
            display: block;
            font-size: 1.3rem;
        }

        .portal-brand .brand-text small {
            display: block;
            margin-top: 5px;
            color: #a9bcda;
            font-size: .72rem;
            letter-spacing: .08em;
        }

        /* Styling Butang Log Keluar */
        .logout-link {
            color: #d63031 !important;
            transition: all 0.2s ease;
        }

        .logout-link:hover {
            background-color: #d63031 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(214, 48, 49, 0.3) !important;
        }

        #page-content {
            flex: 1;
            width: 100%;
            overflow-x: clip;
            min-height: calc(100vh - var(--footer-height));
            transition: all 0.3s ease;
        }

        .toggle-btn {
            cursor: pointer;
            border: none;
            background: #ffffff;
            padding: 8px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: background 0.2s;
        }

        .toggle-btn:hover {
            background: #f1f2f6;
        }

        .card-custom {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            background: #ffffff;
        }

        /* Profile Link Hover Effect */
        .profile-link {
            transition: opacity 0.2s ease;
        }
        .profile-link:hover {
            opacity: 0.8;
        }
        .profile-link:hover strong {
            text-decoration: underline;
        }

        #page-content > .navbar {
            position: sticky;
            top: 0;
            z-index: 1030;
            height: 82px;
            box-sizing: border-box;
            background: #10243d !important;
            border-color: rgba(255,255,255,.12) !important;
        }

        #page-content > .navbar .profile-link,
        #page-content > .navbar .profile-link .text-muted,
        #page-content > .navbar .profile-link .text-primary,
        #page-content > .navbar .profile-link i {
            color: #ffffff !important;
        }
        @media (min-width: 992px) {
            #wrapper { display: block; }
            #sidebar { position: fixed; top: 0; bottom: var(--footer-height); left: 0; height: calc(100vh - var(--footer-height)); min-height: 0; }
            #page-content { width: calc(100% - var(--sidebar-width)); margin-left: var(--sidebar-width); }
            #sidebar.collapsed ~ #page-content { width: calc(100% - var(--sidebar-collapsed-width)); margin-left: var(--sidebar-collapsed-width); }
        }
    </style>
</head>
<body class="user-portal">

<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar">
        <!-- Bahagian Atas: Logo & Menu Navigasi -->
        <div>
            <div class="portal-brand d-flex align-items-center justify-content-center">
                <img src="../assets/image/logo.png" alt="Politeknik Besut logo">
                <span class="fw-bold brand-text"><strong>SPInE</strong><small>POLITEKNIK BESUT</small></span>
            </div>
            
            <ul class="nav nav-pills flex-column mt-3">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                        <i class="fas fa-home me-2"></i> <span class="link-text" data-i18n="Dashboard">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="register_project.php" class="nav-link <?= ($current_page == 'register_project.php') ? 'active' : ''; ?>">
                        <i class="fas fa-project-diagram me-2"></i> <span class="link-text" data-i18n="My Project">My Project</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="upload_doc.php" class="nav-link <?= ($current_page == 'upload_doc.php') ? 'active' : ''; ?>">
                        <i class="fas fa-file-upload me-2"></i> <span class="link-text" data-i18n="Upload Documents">Upload Documents</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="marks.php" class="nav-link <?= ($current_page == 'marks.php') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar me-2"></i> <span class="link-text" data-i18n="Evaluation Marks">Evaluation Marks</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reminders.php" class="nav-link <?= ($current_page == 'reminders.php') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt me-2 text-warning"></i> <span class="link-text" data-i18n="Deadline Reminders">Deadline Reminders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="groups.php" class="nav-link <?= ($current_page == 'groups.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users me-2"></i> <span class="link-text" data-i18n="Group List">Group List</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="past_projects.php" class="nav-link <?= ($current_page == 'past_projects.php') ? 'active' : ''; ?>">
                        <i class="fas fa-archive me-2"></i> <span class="link-text" data-i18n="Past Projects Archive">Past Projects Archive</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Bahagian Bawah Sidebar (Terletak di Atas Footer) -->
        <div class="pb-3 border-top pt-2">
            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a href="../logout.php" class="nav-link logout-link" onclick="return confirm('Are you sure you want to log out?');">
                        <i class="fas fa-sign-out-alt me-2"></i> <span class="link-text" data-i18n="Logout">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Container Kandungan Halaman -->
    <div id="page-content">
        <!-- Top Navigation Bar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-3">
            <button class="toggle-btn" id="sidebarToggle" type="button">
                <i class="fas fa-bars"></i>
            </button>
            <div class="ms-auto d-flex align-items-center">
                <div class="appearance-controls">
                    <button type="button" class="btn btn-outline-light" data-theme-toggle aria-label="Toggle dark mode" title="Light/Dark"><i data-theme-icon class="fas fa-moon"></i></button>
                    <div class="language-switch" data-language-switch aria-label="Language"><button type="button" data-language-option="en">EN</button><button type="button" data-language-option="ms">BM</button></div>
                </div>
                <a href="../index.php" class="btn btn-outline-primary btn-sm me-3"><i class="fas fa-home me-1"></i><span data-i18n="Home">Home</span></a>
                <a href="profile.php" class="text-decoration-none d-flex align-items-center profile-link">
                    <span class="me-3 fw-semibold text-muted"><span data-i18n="Welcome">Welcome</span>, <strong class="text-primary"><?= sanitize($_SESSION['full_name'] ?? 'Student'); ?></strong></span>
                    <?php if (!empty($_SESSION['profile_picture'])): ?><img src="../uploads/profile/<?= rawurlencode($_SESSION['profile_picture']); ?>" alt="Profile picture" class="profile-avatar rounded-circle"><?php else: ?><i class="fas fa-user-circle fa-2x text-primary"></i><?php endif; ?>
                </a>
            </div>
        </nav>

        <div class="p-4">