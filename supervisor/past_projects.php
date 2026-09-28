<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Supervisor']);

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// Tetapan carian & penapis (filters)
$keyword    = trim($_GET['keyword'] ?? '');
$category   = trim($_GET['category'] ?? '');
$session    = trim($_GET['session'] ?? '');
$department = trim($_GET['department'] ?? '');

// Bina asas query untuk arkib projek lepas (diselaraskan menggunakan jadual projects dan mengambil cover image daripada project_documents)
$sql = "
    SELECT 
        p.id, 
        p.title, 
        p.description, 
        p.category, 
        p.session, 
        COALESCE(p.department, u_leader.department, 'GENERAL') as department,
        u_leader.full_name as leader_name,
        s.full_name as supervisor_name,
        (SELECT file_path FROM project_documents pd WHERE pd.project_id = p.id AND pd.doc_type = 'Project Image' LIMIT 1) as cover_image
    FROM projects p
    LEFT JOIN users u_leader ON p.student_id = u_leader.id
    LEFT JOIN users s ON p.supervisor_id = s.id
    WHERE 1=1
";

$params = [];
$types = "";

if (!empty($keyword)) {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $kw = "%$keyword%";
    $params[] = $kw;
    $params[] = $kw;
    $types .= "ss";
}

if (!empty($category) && $category !== '-- All Categories --') {
    $sql .= " AND p.category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($session)) {
    $sql .= " AND p.session LIKE ?";
    $params[] = "%$session%";
    $types .= "s";
}

if (!empty($department) && $department !== '-- All Dept --') {
    $sql .= " AND (p.department = ? OR u_leader.department = ?)";
    $params[] = $department;
    $params[] = $department;
    $types .= "ss";
}

$sql .= " ORDER BY p.project_group_no ASC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = null;
}

// Ambil semua dokumen projek yang wujud untuk projek-projek ini bagi paparan modal butiran
$project_docs = [];
$doc_stmt = $conn->prepare("SELECT project_id, doc_type, file_path FROM project_documents");
if ($doc_stmt) {
    $doc_stmt->execute();
    $res_docs = $doc_stmt->get_result();
    while ($d = $res_docs->fetch_assoc()) {
        $project_docs[$d['project_id']][] = $d;
    }
}

include_once '../includes/header.php';
include_once '../includes/sidebar_supervisor.php';
?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <h2><i class="fas fa-archive text-primary me-2"></i> Past Project References</h2>
        <p class="text-muted">Gallery of documentation, final reports, and system previews from alumni and previous students' projects.</p>
    </div>

    <!-- Kotak Carian & Penapis (Search & Filter Form) -->
    <div class="card shadow-sm border-0 rounded-3 mb-4 p-3 bg-white">
        <form method="GET" action="" class="row g-3 align-items-center">
            <div class="col-md-4">
                <input type="text" class="form-control" name="keyword" placeholder="Keyword / title..." value="<?= sanitize($keyword); ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="category">
                    <option value="">-- All Categories --</option>
                    <option value="Proposal" <?= ($category == 'Proposal') ? 'selected' : ''; ?>>Proposal / Web</option>
                    <option value="Mobile App" <?= ($category == 'Mobile App') ? 'selected' : ''; ?>>Mobile App</option>
                    <option value="IoT / Hardware" <?= ($category == 'IoT / Hardware') ? 'selected' : ''; ?>>IoT / Hardware</option>
                    <option value="AI / Machine Learning" <?= ($category == 'AI / Machine Learning') ? 'selected' : ''; ?>>AI / Machine Learning</option>
                    <option value="Rekabentuk Kraf & Visual" <?= ($category == 'Rekabentuk Kraf & Visual') ? 'selected' : ''; ?>>Rekabentuk Kraf & Visual</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="session" placeholder="Session (e.g. 2024/2025)" value="<?= sanitize($session); ?>">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="department">
                    <option value="">-- All Dept --</option>
                    <option value="JTMK" <?= ($department == 'JTMK') ? 'selected' : ''; ?>>JTMK</option>
                    <option value="JRKV" <?= ($department == 'JRKV') ? 'selected' : ''; ?>>JRKV</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    <p class="text-muted small mb-3">Showing records: <strong><?= ($result) ? $result->num_rows : 0; ?></strong> project(s) found.</p>

    <!-- Senarai Kad Projek Lepas -->
    <div class="row g-4">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php 
                    $p_id = $row['id'];
                    $docs = $project_docs[$p_id] ?? [];
                ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden bg-white d-flex flex-column justify-content-between">
                        <div>
                            <!-- Paparan Gambar / Preview Projek -->
                            <div class="position-relative text-center border-bottom overflow-hidden" style="height: 180px;">
                                <?php if (!empty($row['cover_image']) && file_exists('../uploads/documents/' . $row['cover_image'])): ?>
                                    <img src="../uploads/documents/<?= sanitize($row['cover_image']); ?>" alt="Project Preview" class="img-fluid w-100 h-100" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-white" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);">
                                        <i class="fas fa-image fa-2x mb-2 opacity-75"></i>
                                        <small class="fw-semibold opacity-75" style="font-size: 12px; letter-spacing: 0.5px;">No Preview Available</small>
                                    </div>
                                <?php endif; ?>
                                <span class="badge bg-primary position-absolute top-0 start-0 m-2 shadow-sm">
                                    <?= sanitize($row['category'] ?? 'FYP Project'); ?>
                                </span>
                            </div>

                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge bg-light text-dark border me-1"><i class="fas fa-calendar-alt me-1 text-muted"></i> <?= sanitize($row['session'] ?? '-'); ?></span>
                                    <span class="badge bg-info text-dark"><?= sanitize($row['department'] ?? '-'); ?></span>
                                </div>

                                <h5 class="fw-bold text-dark mb-2" style="min-height: 48px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= sanitize($row['title']); ?></h5>
                                <p class="text-muted small mb-3" style="font-size: 12px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"><?= sanitize($row['description'] ?? 'No abstract description provided.'); ?></p>
                            </div>
                        </div>

                        <div class="card-footer bg-light border-top p-3 mt-auto">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted text-truncate me-2" style="font-size: 11px; max-width: 60%;">
                                    <i class="fas fa-user-tie me-1"></i> SV: <?= sanitize($row['supervisor_name'] ?? 'N/A'); ?>
                                </small>
                                <button type="button" class="btn btn-sm btn-primary fw-bold text-nowrap" data-bs-toggle="modal" data-bs-target="#projectModal<?= $p_id; ?>">
                                    <i class="fas fa-eye me-1"></i> View Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL POP-UP BUTIRAN PROJEK & SENARAI DOKUMEN -->
                <div class="modal fade" id="projectModal<?= $p_id; ?>" tabindex="-1" aria-labelledby="modalLabel<?= $p_id; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title fw-bold" id="modalLabel<?= $p_id; ?>">
                                    <i class="fas fa-project-diagram me-2"></i><?= sanitize($row['title']); ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <!-- Maklumat Ringkas Projek -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <p class="mb-1 small text-muted"><strong>Category:</strong> <span class="badge bg-secondary"><?= sanitize($row['category']); ?></span></p>
                                        <p class="mb-1 small text-muted"><strong>Department:</strong> <span class="badge bg-info text-dark"><?= sanitize($row['department'] ?? '-'); ?></span></p>
                                        <p class="mb-1 small text-muted"><strong>Activity Session:</strong> <?= sanitize($row['session']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 small text-muted"><strong>Supervisor:</strong> <?= sanitize($row['supervisor_name'] ?? '-'); ?></p>
                                        <p class="mb-1 small text-muted"><strong>Project Leader:</strong> <?= sanitize($row['leader_name'] ?? '-'); ?></p>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6 class="fw-bold text-dark"><i class="fas fa-align-left me-2 text-primary"></i>Project Description / Abstract:</h6>
                                    <p class="text-muted small bg-light p-3 rounded"><?= nl2br(sanitize($row['description'] ?? 'No description provided.')); ?></p>
                                </div>

                                <!-- Senarai Ahli Kumpulan Lengkap -->
                                <div class="mb-4">
                                    <h6 class="fw-bold text-dark"><i class="fas fa-users me-2 text-primary"></i>Project Team Members:</h6>
                                    <ul class="list-group list-group-flush bg-light rounded p-2">
                                        <?php
                                        $stmt_m_list = $conn->prepare("SELECT u.full_name, pm.role FROM project_members pm JOIN users u ON pm.student_id = u.id WHERE pm.project_id = ?");
                                        $stmt_m_list->bind_param("i", $p_id);
                                        $stmt_m_list->execute();
                                        $res_m_list = $stmt_m_list->get_result();
                                        if ($res_m_list->num_rows > 0):
                                            while ($member = $res_m_list->fetch_assoc()):
                                        ?>
                                            <li class="list-group-item bg-transparent py-1 border-0">
                                                <i class="fas fa-user-circle me-2 text-secondary"></i>
                                                <?= sanitize($member['full_name']); ?> 
                                                <span class="badge bg-<?= ($member['role'] == 'Leader') ? 'primary' : 'secondary'; ?> text-white" style="font-size: 10px;">
                                                    <?= sanitize($member['role']); ?>
                                                </span>
                                            </li>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <li class="list-group-item bg-transparent text-muted py-1 border-0">Leader: <?= sanitize($row['leader_name'] ?? 'N/A'); ?></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>

                                <!-- Senarai Dokumen Terlibat -->
                                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-folder-open me-2 text-primary"></i>Submitted Reference Documents:</h6>
                                <?php if (!empty($docs)): ?>
                                    <div class="list-group">
                                        <?php foreach ($docs as $doc): ?>
                                            <?php 
                                                $file_link = "../uploads/documents/" . ltrim($doc['file_path'], '/');
                                            ?>
                                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-file-pdf fa-2x text-danger me-3"></i>
                                                    <div>
                                                        <strong class="d-block text-dark small"><?= sanitize($doc['doc_type']); ?></strong>
                                                        <small class="text-muted" style="font-size: 11px;"><?= basename($doc['file_path']); ?></small>
                                                    </div>
                                                </div>
                                                <a href="<?= sanitize($file_link); ?>" target="_blank" class="btn btn-outline-primary btn-sm fw-bold">
                                                    <i class="fas fa-external-link-alt me-1"></i> Open File
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning small mb-0">
                                        <i class="fas fa-exclamation-triangle me-1"></i> No documents have been uploaded for this project yet.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning text-center p-5 shadow-sm rounded-3">
                    <i class="fas fa-folder-open fa-3x mb-3 text-warning"></i>
                    <h4>No Past Project Records Found</h4>
                    <p class="mb-0">No project archives were found based on your search or filter criteria.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>