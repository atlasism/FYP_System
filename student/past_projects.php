<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Student']);

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// Parameter Carian & Filter
$search     = $_GET['search'] ?? '';
$session    = $_GET['session'] ?? '';

$session_options = [];
$session_result = $conn->query("SELECT DISTINCT session FROM projects WHERE session IS NOT NULL AND session <> '' ORDER BY session DESC");
if ($session_result) {
    while ($session_row = $session_result->fetch_assoc()) {
        $session_options[] = $session_row['session'];
    }
}

// Query untuk mengambil projek-projek lepas
$query = "
    SELECT 
        p.id, 
        p.title, 
        p.description, 
        p.category, 
        p.session, 
        u_leader.department,
        u_leader.full_name as leader_name,
        s.full_name as supervisor_name,
        (SELECT file_path FROM project_documents pd WHERE pd.project_id = p.id AND pd.doc_type = 'Project Image' LIMIT 1) as cover_image
    FROM projects p
    LEFT JOIN users u_leader ON p.student_id = u_leader.id
    LEFT JOIN users s ON p.supervisor_id = s.id
    WHERE 1=1
";

$params = [];
$types  = "";

if (!empty($search)) {
    $query .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $s_param = "%$search%";
    $params[] = $s_param; $params[] = $s_param;
    $types .= "ss";
}

if (!empty($session)) {
    $query .= " AND p.session = ?";
    $params[] = $session;
    $types .= "s";
}

$query .= " ORDER BY p.project_group_no ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$projects_res = $stmt->get_result();

// Ambil semua dokumen projek yang wujud untuk projek-projek ini
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
include_once '../includes/sidebar_student.php';
?>

<div class="container-fluid p-4">

    <!-- Kad Header & Form Carian -->
    <div class="card card-custom p-4 mb-4 shadow-sm border-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold text-primary mb-1">
                    <i class="fas fa-archive me-2"></i>Past Project References
                </h4>
                <p class="text-muted mb-0">Gallery of documentation, final reports, and system previews from alumni and previous students' projects.</p>
            </div>
            <?php if (!empty($search) || !empty($session)): ?>
                <div>
                    <a href="<?= htmlspecialchars(basename($_SERVER['PHP_SELF'])); ?>" class="btn btn-outline-secondary btn-sm fw-bold">
                        <i class="fas fa-redo me-1"></i> Reset Filter
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <form method="GET" action="<?= htmlspecialchars(basename($_SERVER['PHP_SELF'])); ?>" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Keyword / title..." value="<?= sanitize($search); ?>">
            </div>
            <div class="col-md-2">
                <select name="session" class="form-select">
                    <option value="">-- Select Session --</option>
                    <?php foreach ($session_options as $session_option): ?>
                        <option value="<?= sanitize($session_option); ?>" <?= $session === $session_option ? 'selected' : ''; ?>><?= sanitize($session_option); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="fas fa-search me-1"></i> Search
                </button>
            </div>
        </form>

        <?php if ($projects_res): ?>
            <div class="mt-3 text-muted small">
                <i class="fas fa-info-circle me-1"></i> Records shown: <strong><?= $projects_res->num_rows; ?></strong> projects found.
            </div>
        <?php endif; ?>
    </div>

    <!-- Senarai Projek Rujukan (Grid Card System) -->
    <?php if ($projects_res && $projects_res->num_rows > 0): ?>
        <div class="row g-4">
            <?php while ($p = $projects_res->fetch_assoc()): ?>
                <?php 
                    $p_id = $p['id'];
                    $docs = $project_docs[$p_id] ?? [];
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 card-custom border-0 shadow-sm d-flex flex-column justify-content-between">
                        <div>
                            <!-- Cover Image Projek -->
                            <div class="position-relative rounded-top text-center border-bottom overflow-hidden" style="height: 160px;">
                                <?php if (!empty($p['cover_image']) && file_exists('../uploads/documents/' . $p['cover_image'])): ?>
                                    <img src="../uploads/documents/<?= sanitize($p['cover_image']); ?>" alt="Project Cover" class="img-fluid project-cover-img" style="object-fit: cover; height: 100%; width: 100%;">
                                <?php else: ?>
                                    <div class="text-white text-center d-flex flex-column justify-content-center align-items-center h-100" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);">
                                        <i class="fas fa-image fa-2x mb-2 opacity-75"></i>
                                        <small class="fw-semibold opacity-75" style="font-size: 12px; letter-spacing: 0.5px;">No Preview Available</small>
                                    </div>
                                <?php endif; ?>
                                
                                <span class="badge bg-primary position-absolute top-0 start-0 m-2 shadow-sm">
                                    <?= sanitize($p['category'] ?? 'FYP Project'); ?>
                                </span>
                            </div>

                            <!-- Kandungan Kad -->
                            <div class="p-3">
                                <small class="text-muted fw-bold d-block mb-1">
                                    <i class="fas fa-calendar-alt me-1"></i>Project Session: <?= sanitize($p['session'] ?? '-'); ?>
                                    <?php if (!empty($p['department'])): ?>
                                        | <span class="badge bg-info text-dark"><?= sanitize($p['department']); ?></span>
                                    <?php endif; ?>
                                </small>
                                <h6 class="fw-bold text-dark mb-2" style="min-height: 42px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= sanitize($p['title']); ?>
                                </h6>
                                <p class="text-muted small mb-3" style="font-size: 12px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= sanitize($p['description'] ?? 'No abstract description provided.'); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Footer Kad -> Butang View More -->
                        <div class="p-3 bg-light border-top rounded-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted text-truncate me-2" style="font-size: 11px; max-width: 60%;">
                                    <i class="fas fa-user-tie me-1"></i><?= sanitize($p['supervisor_name'] ?? 'Supervisor N/A'); ?>
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
                                    <i class="fas fa-project-diagram me-2"></i><?= sanitize($p['title']); ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <!-- Maklumat Ringkas Projek -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <p class="mb-1 small text-muted"><strong>Category:</strong> <span class="badge bg-secondary"><?= sanitize($p['category']); ?></span></p>
                                        <p class="mb-1 small text-muted"><strong>Department:</strong> <span class="badge bg-info text-dark"><?= sanitize($p['department'] ?? '-'); ?></span></p>
                                        <p class="mb-1 small text-muted"><strong>Project Session:</strong> <?= sanitize($p['session']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 small text-muted"><strong>Supervisor:</strong> <?= sanitize($p['supervisor_name'] ?? '-'); ?></p>
                                        <p class="mb-1 small text-muted"><strong>Project Leader:</strong> <?= sanitize($p['leader_name'] ?? '-'); ?></p>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6 class="fw-bold text-dark"><i class="fas fa-align-left me-2 text-primary"></i>Project Description / Abstract:</h6>
                                    <p class="text-muted small bg-light p-3 rounded"><?= nl2br(sanitize($p['description'] ?? 'No description provided.')); ?></p>
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
                                            <li class="list-group-item bg-transparent text-muted py-1 border-0">Leader: <?= sanitize($p['leader_name'] ?? 'N/A'); ?></li>
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
        </div>
    <?php else: ?>
        <!-- Paparan Kosong Jika Tiada Projek -->
        <div class="card card-custom p-5 text-center shadow-sm border-0">
            <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
            <h5 class="fw-bold text-dark">No reference projects found</h5>
            <p class="text-muted mb-0">No reference project records match your search criteria or the database does not contain past projects yet.</p>
        </div>
    <?php endif; ?>

</div>

<?php include_once '../includes/footer.php'; ?>