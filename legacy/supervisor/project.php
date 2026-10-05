<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Supervisor']);

$supervisor_id = $_SESSION['user_id'] ?? 0;

// Mengambil senarai projek di bawah seliaan penyelia ini berserta nama pelajar ketua
$stmt = $conn->prepare("
    SELECT p.*, u.full_name as student_name, u.matric_no,
           (SELECT file_path FROM project_documents pd WHERE pd.project_id = p.id AND pd.doc_type = 'Project Image' LIMIT 1) as cover_image
    FROM projects p 
    LEFT JOIN users u ON p.student_id = u.id 
    WHERE p.supervisor_id = ? 
    ORDER BY p.project_group_no ASC
");
$stmt->bind_param("i", $supervisor_id);
$stmt->execute();
$projects = $stmt->get_result();

include_once '../includes/header.php';
include_once '../includes/sidebar_supervisor.php';
?>

<div class="container-fluid p-4">
    <!-- Kad Tajuk Halaman -->
    <div class="card card-custom p-4 mb-4 shadow-sm border-0 bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1"><i class="fas fa-folder-open me-2"></i>Supervised Projects</h3>
                <p class="mb-0 text-white-50">Manage, monitor, and review all Final Year Projects assigned under your supervision.</p>
            </div>
        </div>
    </div>

    <!-- Senarai Projek dalam Bentuk Grid Kad -->
    <div class="row g-4">
        <?php if ($projects && $projects->num_rows > 0): ?>
            <?php while($p = $projects->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-custom h-100 shadow-sm border-0 overflow-hidden d-flex flex-column">
                        
                        <!-- Bahagian Imej / Cover Projek -->
                        <div class="position-relative bg-primary" style="height: 180px;">
                            <?php if (!empty($p['cover_image']) && file_exists('../uploads/documents/' . $p['cover_image'])): ?>
                                <img src="../uploads/documents/<?= sanitize($p['cover_image']); ?>" alt="Project Cover" class="w-100 h-100" style="object-fit: cover;">
                            <?php elseif (!empty($p['cover_image']) && file_exists('../uploads/' . $p['cover_image'])): ?>
                                <img src="../uploads/<?= sanitize($p['cover_image']); ?>" alt="Project Cover" class="w-100 h-100" style="object-fit: cover;">
                            <?php else: ?>
                                <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white">
                                    <i class="fas fa-image fa-2x mb-2 opacity-50"></i>
                                    <span class="small opacity-75 fw-semibold">No Preview Available</span>
                                </div>
                            <?php endif; ?>

                            <!-- Badge Kategori Projek -->
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-dark bg-opacity-75 text-white px-2 py-1 backdrop-blur">
                                    <?= sanitize($p['category'] ?? 'General'); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Kandungan Maklumat Projek -->
                        <div class="card-body p-4 d-flex flex-column flex-grow-1">
                            <h5 class="fw-bold text-dark mb-2 text-truncate-2"><?= sanitize($p['title']); ?></h5>
                            
                            <p class="text-muted small mb-3 flex-grow-1">
                                <?= !empty($p['description']) ? sanitize(substr($p['description'], 0, 90)) . '...' : '<span class="fst-italic text-muted">No description provided yet.</span>'; ?>
                            </p>

                            <div class="border-top pt-3 mt-auto">
                                <div class="d-flex align-items-center text-muted small mb-2">
                                    <i class="fas fa-user-graduate text-primary me-2"></i>
                                    <span class="fw-semibold text-dark"><?= sanitize($p['student_name'] ?? 'Not Assigned'); ?></span>
                                </div>
                                <div class="d-flex align-items-center text-muted small">
                                    <i class="fas fa-calendar-alt text-secondary me-2"></i>
                                    <span>Session: <?= sanitize($p['session'] ?? '-'); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Bahagian Tindakan Bawah -->
                        <div class="card-footer bg-light border-0 px-4 py-3 d-flex justify-content-between align-items-center">
                            <span class="badge bg-success-subtle text-success px-2 py-1">Active Supervision</span>
                            <a href="view_project.php?id=<?= $p['id']; ?>" class="btn btn-sm btn-primary fw-semibold px-3">
                                <i class="fas fa-eye me-1"></i> Review Project
                            </a>
                        </div>

                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="card card-custom p-5 border-0 shadow-sm">
                    <div class="text-muted mb-3">
                        <i class="fas fa-folder-open fa-3x opacity-50"></i>
                    </div>
                    <h5 class="fw-bold text-dark">No Supervised Projects Found</h5>
                    <p class="text-muted small mb-0">There are currently no final year projects registered under your supervision list.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>