<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Student']);

$student_id = $_SESSION['user_id'];
$message = '';
$message_type = '';
$max_file_size = 20 * 1024 * 1024;
$document_types = [
    'A' => ['label' => 'Proposal Presentation', 'weight' => '10%'],
    'B' => ['label' => 'Project Demonstration 1', 'weight' => '10%'],
    'C' => ['label' => 'Project Demonstration 2', 'weight' => '10%'],
    'D' => ['label' => 'Project Demonstration 3', 'weight' => '15%'],
    'E' => ['label' => 'Final Presentation - Poster', 'weight' => '15%'],
    'F' => ['label' => 'Final Presentation', 'weight' => '15%'],
    'LOG_BOOK' => ['label' => 'Log Book', 'weight' => '10%'],
    'TECHNICAL_REPORT' => ['label' => 'Technical Report', 'weight' => '15%']
];
$allowed_mimes = [
    'pdf' => ['application/pdf'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    'zip' => ['application/zip', 'application/x-zip-compressed']
];

// 1. Dapatkan ID Projek Pelajar (Ketua ATAU Ahli Kumpulan)
$stmt_proj = $conn->prepare("
    SELECT p.id 
    FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE p.student_id = ? OR pm.student_id = ?
    LIMIT 1
");
$stmt_proj->bind_param("ii", $student_id, $student_id);
$stmt_proj->execute();
$project = $stmt_proj->get_result()->fetch_assoc();

// 2. Proses Muat Naik Dokumen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document_file'])) {
    if (!$project) {
        $message = "Please register your project first before uploading documents!";
        $message_type = "danger";
    } else {
        // Tangkap jawapan daripada dropdown secara tepat
        $doc_type = trim($_POST['doc_type'] ?? '');
        $file = $_FILES['document_file'];

        if (!isset($document_types[$doc_type])) {
            $message = "Please select a Document Type from the list!";
            $message_type = "danger";
        } elseif ($file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

            if ($file['size'] > 0 && $file['size'] <= $max_file_size && isset($allowed_mimes[$ext]) && in_array($mime, $allowed_mimes[$ext], true)) {
                $upload_dir = '../uploads/documents/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $original_name = basename($file['name']);
                $filename = 'DOC_' . bin2hex(random_bytes(12)) . '.' . $ext;
                $target_path = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $stmt_ins = $conn->prepare("INSERT INTO project_documents (project_id, doc_type, file_path, original_name, status, uploaded_at) VALUES (?, ?, ?, ?, 'Pending', NOW())");
                    $stmt_ins->bind_param("isss", $project['id'], $doc_type, $filename, $original_name);
                    
                    if ($stmt_ins->execute()) {
                        $message = "Document <strong>" . htmlspecialchars($document_types[$doc_type]['label']) . "</strong> uploaded successfully and is pending review.";
                        $message_type = "success";
                    } else {
                        unlink($target_path);
                        $message = "Failed to save the record in the database.";
                        $message_type = "danger";
                    }
                } else {
                    $message = "Failed to move the uploaded file.";
                    $message_type = "danger";
                }
            } else {
                $message = "File must be a valid PDF, DOCX, or ZIP file not exceeding 20 MB.";
                $message_type = "danger";
            }
        } else {
            $message = "Error during file upload.";
            $message_type = "danger";
        }
    }
}

// 3. Ambil Senarai Dokumen dari Database
$documents = [];
if ($project) {
    $stmt_docs = $conn->prepare("SELECT * FROM project_documents WHERE project_id = ? ORDER BY uploaded_at DESC");
    $stmt_docs->bind_param("i", $project['id']);
    $stmt_docs->execute();
    $res = $stmt_docs->get_result();
    while ($row = $res->fetch_assoc()) {
        $documents[] = $row;
    }
}

include_once '../includes/sidebar_student.php';
?>

<!-- Tajuk Halaman -->
<div class="mb-4">
    <h3 class="fw-bold">Upload Project Documents</h3>
    <p class="text-muted">Submit each official DFT50114 rubric component as a PDF, DOCX, or ZIP file (maximum 20 MB).</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $message_type; ?> alert-dismissible fade show" role="alert">
        <?= $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Borang Muat Naik (Sebelah Kiri) -->
    <div class="col-md-5">
        <div class="card card-custom p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-upload text-primary me-2"></i>Upload Form</h5>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-bold">Document Type</label>
                    <select name="doc_type" class="form-select" required>
                        <option value="">-- Select Rubric Category --</option>
                        <?php foreach ($document_types as $type => $document): ?>
                            <option value="<?= sanitize($type); ?>" <?= (($_POST['doc_type'] ?? '') === $type) ? 'selected' : ''; ?>><?= sanitize($type . ': ' . $document['label'] . ' (' . $document['weight'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Choose File</label>
                    <input type="file" name="document_file" class="form-control" accept=".pdf,.docx,.zip" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="fas fa-cloud-upload-alt me-1"></i> Upload Now
                </button>
            </form>
        </div>
    </div>

    <!-- Kard Senarai Dokumen Dihantar (Sebelah Kanan) -->
    <div class="col-md-7">
        <div class="card card-custom p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-list text-primary me-2"></i>Submitted Documents List</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th>Rubric Category</th>
                            <th>Status</th>
                            <th>Upload Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documents)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    No documents uploaded yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($documents as $index => $doc): ?>
                                <?php 
                                    $raw_type = $doc['doc_type'] ?? '';
                                    
                                    // Tentukan nama jenis dokumen yang hendak dipaparkan
                                    if (!empty($raw_type) && isset($document_types[$raw_type])) {
                                        $papar_jenis = $raw_type . ': ' . $document_types[$raw_type]['label'];
                                    } elseif (!empty($raw_type)) {
                                        $papar_jenis = $raw_type;
                                    } else {
                                        $papar_jenis = basename($doc['file_path']);
                                    }
                                ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td>
                                        <span class="fw-bold text-dark">
                                            <?= htmlspecialchars($papar_jenis); ?>
                                        </span>
                                    </td>
                                    <td><span class="badge <?= ($doc['status'] ?? 'Pending') === 'Approved' ? 'bg-success' : (($doc['status'] ?? 'Pending') === 'Rejected' ? 'bg-danger' : 'bg-warning text-dark'); ?>"><?= sanitize($doc['status'] ?? 'Pending'); ?></span></td>
                                    <td>
                                        <small class="text-muted">
                                            <?= !empty($doc['uploaded_at']) ? date('d/m/Y h:i A', strtotime($doc['uploaded_at'])) : '-'; ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <a href="../uploads/documents/<?= htmlspecialchars($doc['file_path']); ?>" class="btn btn-sm btn-outline-primary" download target="_blank">
                                            <i class="fas fa-download me-1"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>