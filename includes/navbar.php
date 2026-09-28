<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="/fyp_system/index.php">
      <img src="/fyp_system/assets/image/logo.png" alt="SPInE Politeknik Besut logo" height="40" class="me-2" onerror="this.style.display='none'">
      <span class="fw-bold text-wrap">SPInE Politeknik Besut</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item">
          <a class="nav-link" href="/fyp_system/index.php"><i class="fas fa-home me-1"></i> Home</a>
        </li>
        <?php if (isset($_SESSION['user_id'])): ?>
          <?php if ($_SESSION['role'] === 'Student'): ?>
            <li class="nav-item"><a class="nav-link" href="/fyp_system/student/dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/fyp_system/student/my_project.php">My Project</a></li>
            <li class="nav-item"><a class="nav-link" href="/fyp_system/student/upload_doc.php">Upload Documents</a></li>
          <?php elseif ($_SESSION['role'] === 'Admin'): ?>
            <li class="nav-item"><a class="nav-link" href="/fyp_system/admin/dashboard.php">Admin Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/fyp_system/admin/manage_users.php">Users</a></li>
            <li class="nav-item"><a class="nav-link" href="/fyp_system/admin/manage_projects.php">Projects</a></li>
          <?php elseif ($_SESSION['role'] === 'Supervisor'): ?>
            <li class="nav-item"><a class="nav-link" href="/fyp_system/supervisor/dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/fyp_system/supervisor/supervised_projects.php">My Projects</a></li>
          <?php endif; ?>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-outline-light btn-sm px-3" href="/fyp_system/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout (<?= sanitize($_SESSION['username']); ?>)</a>
          </li>
        <?php else: ?>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-primary btn-sm px-4 fw-semibold" href="/fyp_system/login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>