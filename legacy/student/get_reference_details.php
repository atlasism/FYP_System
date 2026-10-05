<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Student']);

// Ambil ID projek daripada request AJAX
$project_id = intval($_GET['id'] ?? 0);

if ($project_id <= 0) {
    echo '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Invalid project ID.</div>';
    exit;
}

// 1. Dapatkan Maklumat Terperinci Projek
$stmt = $conn->prepare("SELECT p.*, u.full_name as student_name, u.department, sup.full_name as supervisor_name 
                        FROM projects p 
                        JOIN users u ON p.student_id = u.id 
                        LEFT JOIN users sup ON p.supervisor_id = sup.id 
                        WHERE p.id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    echo '<div class="alert alert-warning mb-0"><i class="fas fa-info-circle me-2"></i>Project details were not found.</div>';
    exit;
}

// 2. Dapatkan Dokumen & Gambar Projek
$stmt_docs = $conn->prepare("SELECT * FROM project_documents WHERE project_id = ? ORDER BY id DESC");
$stmt_docs->bind_param("i", $project_id);
$stmt_docs->execute();
$documents = $stmt_docs->get_result();
?>

<div>
    <!-- Tajuk Utama -->
    <h4 class="fw-bold text-primary mb-3"><?= htmlspecialchars($project['title']); ?></h4>
    
    <!-- Student and supervisor summary -->
    <div class="row bg-light p-3 rounded mb-4 border">
        <div class="col-md-6 mb-2 mb-md-0">
            <strong><i class="fas fa-user text-secondary me-2"></i>Student:</strong> 
            <?= htmlspecialchars($project['student_name']); ?> (<?= htmlspecialchars($project['department'] ?? 'N/A'); ?>)
        </div>
        <div class="col-md-6">
            <strong><i class="fas fa-user-tie text-secondary me-2"></i>Supervisor:</strong> 
            <?= htmlspecialchars($project['supervisor_name'] ?? 'No supervisor assigned'); ?>
        </div>
    </div>

    <!-- Description / abstract -->
    <div class="mb-4">
        <h6 class="fw-bold text-dark"><i class="fas fa-align-left text-primary me-2"></i>Project Description / Abstract:</h6>
        <div class="p-3 bg-white rounded border text-secondary" style="white-space: pre-line; line-height: 1.6;">
            <?= htmlspecialchars($project['description'] ?? 'No description provided.'); ?>
        </div>
    </div>

    <hr class="my-4">

    <!-- Reference images and files -->
    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-images text-warning me-2"></i>Project Reference Images &amp; Documents</h6>

    <div class="row g-3">
        <?php if ($documents && $documents->num_rows > 0): ?>
            <?php while ($doc = $documents->fetch_assoc()): ?>
                <?php 
                    $file_path = "../uploads/documents/" . $doc['file_path'];
                    $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                    
                    // Semak sama ada fail adalah GAMBAR
                    $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                    $doc_title = !empty($doc['doc_type']) ? $doc['doc_type'] : 'Reference Document';
                ?>
                
                <div class="col-md-6">
                    <div class="card h-100 border shadow-sm p-2">
                        <?php if ($is_image): ?>
                            <!-- Image preview -->
                            <a href="<?= $file_path; ?>" target="_blank" title="Click to view the full-size image">
                                <img src="<?= $file_path; ?>" class="card-img-top rounded mb-2" style="height: 180px; object-fit: cover;" alt="Reference image">
                            </a>
                        <?php else: ?>
                            <!-- Document icon -->
                            <div class="text-center py-4 bg-light rounded mb-2">
                                <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                                <p class="text-muted small mb-0"><?= strtoupper($ext); ?> Fail</p>
                            </div>
                        <?php endif; ?>

                        <div class="card-body p-2 text-center d-flex flex-column justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark small"><?= htmlspecialchars($doc_title); ?></h6>
                                <p class="text-muted mb-2" style="font-size: 0.75rem;">
                                    Uploaded: <?= !empty($doc['uploaded_at']) ? date('d/m/Y', strtotime($doc['uploaded_at'])) : '-'; ?>
                                </p>
                            </div>
                            
                            <a href="<?= $file_path; ?>" class="btn btn-sm btn-outline-primary w-100 fw-bold mt-2" target="_blank" download>
                                <i class="fas fa-download me-1"></i> Open / Download
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-secondary text-center mb-0">
                    <i class="fas fa-folder-open me-2"></i>No files or images have been uploaded for this reference project.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>