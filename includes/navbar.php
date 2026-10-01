<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="/fyp_system/index.php">
      <?php if ($current_page === 'index.php' && isset($_SESSION['user_id'])): ?><img src="/fyp_system/assets/image/logo.png" alt="Politeknik Besut logo" height="40" class="me-2" onerror="this.style.display='none'"><?php endif; ?>
      <span class="brand-logosistem-badge me-2"><img src="/fyp_system/assets/image/logosistem.png" alt="SPInE Politeknik Besut logo" class="brand-logosistem" onerror="this.style.display='none'"></span>
      <span class="fw-bold text-wrap">SPInE Politeknik Besut</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <?php if ($current_page === 'index.php'): ?><li class="nav-item home-language-item"><div class="language-switch" data-language-switch aria-label="Language"><button type="button" data-language-option="en">EN</button><button type="button" data-language-option="ms">BM</button></div></li><?php else: ?><li class="nav-item"><a class="nav-link" href="/fyp_system/index.php"><i class="fas fa-home me-1"></i> <span data-i18n="Home">Home</span></a></li><?php endif; ?>
        <?php if (isset($_SESSION['user_id'])): ?>
          <?php if ($_SESSION['role'] === 'Student'): ?>
            <li class="nav-item"><a class="nav-link" href="/fyp_system/student/dashboard.php" data-i18n="Dashboard">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/fyp_system/student/upload_doc.php" data-i18n="Upload Documents">Upload Documents</a></li>
          <?php elseif ($_SESSION['role'] === 'Admin'): ?>
            <li class="nav-item"><a class="nav-link" href="/fyp_system/admin/dashboard.php">Admin Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/fyp_system/admin/manage_users.php">Users</a></li>
            <li class="nav-item"><a class="nav-link" href="/fyp_system/admin/manage_projects.php">Projects</a></li>
          <?php elseif ($_SESSION['role'] === 'Supervisor'): ?>
            <li class="nav-item"><a class="nav-link" href="/fyp_system/supervisor/dashboard.php" data-i18n="Dashboard">Dashboard</a></li>
          <?php endif; ?>
          <li class="nav-item ms-lg-2 d-flex align-items-center">
            <?php if ($current_page !== 'index.php'): ?><div class="appearance-controls">
              <button type="button" class="btn btn-outline-light" data-theme-toggle aria-label="Toggle dark mode" title="Light/Dark"><i data-theme-icon class="fas fa-moon"></i></button>
              <div class="language-switch" data-language-switch aria-label="Language"><button type="button" data-language-option="en">EN</button><button type="button" data-language-option="ms">BM</button></div>
            </div><?php endif; ?>
            <a class="btn btn-outline-light btn-sm px-3" href="/fyp_system/logout.php"><i class="fas fa-sign-out-alt me-1"></i> <span data-i18n="Logout">Logout</span> (<?= sanitize($_SESSION['username']); ?>)</a>
          </li>
        <?php else: ?>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-primary btn-sm px-4 fw-semibold" href="/fyp_system/login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
          </li>
          <?php if ($current_page === 'index.php'): ?><li class="nav-item ms-lg-2 d-flex align-items-center"><img src="/fyp_system/assets/image/logo.png" alt="Politeknik Besut logo" class="home-nav-logo" onerror="this.style.display='none'"></li><?php endif; ?>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>