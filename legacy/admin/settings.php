<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Admin']);

$settings = [
    'System Name' => 'SPInE Politeknik Besut',
    'Department' => 'JTMK - Information Technology',
    'Course Code' => 'DFT50114',
    'Course Name' => 'Integrated Project',
    'Project Scope' => 'JTMK IT projects only',
    'Assessment Model' => 'Supervisor verification only: Passed / Not Passed'
];

include_once '../includes/admin_header.php';
?>

<div class="container-fluid px-0">
    <div class="mb-4"><h3 class="fw-bold text-primary mb-1"><i class="bi bi-gear-fill me-2"></i>System Settings</h3><p class="text-muted mb-0">View the fixed configuration for this JTMK project system.</p></div>
    <div class="card admin-card p-4"><div class="d-flex align-items-center gap-3 mb-4"><span class="admin-stat-icon bg-primary text-white"><i class="bi bi-sliders"></i></span><div><h5 class="fw-bold mb-1">System Configuration</h5><p class="text-muted mb-0">These values are fixed to maintain the DFT50114 JTMK scope.</p></div></div><div class="row g-3"><?php foreach ($settings as $label => $value): ?><div class="col-md-6"><label class="form-label small text-muted fw-bold"><?= sanitize($label); ?></label><input class="form-control bg-light" value="<?= sanitize($value); ?>" readonly></div><?php endforeach; ?></div><div class="alert alert-info mt-4 mb-0"><i class="bi bi-info-circle me-2"></i>Account, project and supervisor data are managed through the Admin pages. This system does not use numerical supervisor marks.</div></div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
