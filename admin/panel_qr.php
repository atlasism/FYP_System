<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Admin']);

$message = '';
$error = '';
$panel_urls = [];
$generated_ranges = [];
$csrf_token = $_SESSION['csrf_panel_qr'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_panel_qr'] = $csrf_token;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_panel_qr'])) {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form session. Please try again.';
    } else {
        $requested_panel_count = max(1, (int) ($_POST['panel_count'] ?? 1));
        $project_result = $conn->query("SELECT p.id, p.project_group_no FROM projects p WHERE p.department = 'JTMK' AND p.course_code = 'DFT50114' AND p.project_group_no IS NOT NULL AND p.session = (SELECT latest.session FROM projects latest WHERE latest.department = 'JTMK' AND latest.course_code = 'DFT50114' AND latest.project_group_no IS NOT NULL ORDER BY latest.id DESC LIMIT 1) ORDER BY p.project_group_no ASC, p.id ASC");
        if (!$project_result) {
            $error = 'Panel database tables are missing. Please run panel_module_migration.sql first.';
        } elseif ($project_result->num_rows === 0) {
            $error = 'No DFT50114 project groups are available.';
        } else {
            $projects = [];
            while ($row = $project_result->fetch_assoc()) {
                $projects[] = ['id' => (int) $row['id'], 'group_no' => (int) $row['project_group_no']];
            }
            $panel_count = min($requested_panel_count, count($projects));
            $projects_per_panel = (int) ceil(count($projects) / $panel_count);
            $project_chunks = array_chunk($projects, $projects_per_panel);
            $admin_id = (int) ($_SESSION['user_id'] ?? 0);
            $conn->begin_transaction();
            try {
                $session_stmt = $conn->prepare("INSERT INTO panel_sessions (project_id, token, created_by, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))");
                if (!$session_stmt) {
                    throw new RuntimeException('Panel database tables are missing. Please run panel_module_migration.sql first.');
                }
                $mapping_stmt = $conn->prepare('INSERT INTO panel_session_projects (panel_session_id, project_id) VALUES (?, ?)');
                if (!$mapping_stmt) {
                    throw new RuntimeException('Panel database tables are missing. Please run panel_module_migration.sql first.');
                }
                foreach ($project_chunks as $project_chunk) {
                    $token = bin2hex(random_bytes(32));
                    $first_project_id = $project_chunk[0]['id'];
                    $session_stmt->bind_param('isi', $first_project_id, $token, $admin_id);
                    if (!$session_stmt->execute()) {
                        throw new RuntimeException('Unable to create the panel QR session.');
                    }
                    $panel_session_id = (int) $conn->insert_id;
                    foreach ($project_chunk as $project) {
                        $mapping_stmt->bind_param('ii', $panel_session_id, $project['id']);
                        if (!$mapping_stmt->execute()) {
                            throw new RuntimeException('Unable to add all groups to the panel QR session.');
                        }
                    }
                    $generated_ranges[] = ['from' => $project_chunk[0]['group_no'], 'to' => $project_chunk[count($project_chunk) - 1]['group_no'], 'count' => count($project_chunk), 'token' => $token];
                }
                $conn->commit();
                $base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
                $qr_host = $_SERVER['HTTP_HOST'];
                if (preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $qr_host)) {
                    $detected_host = gethostbyname(gethostname());
                    if (filter_var($detected_host, FILTER_VALIDATE_IP) && !str_starts_with($detected_host, '127.')) {
                        $port = strpos($qr_host, ':') !== false ? substr($qr_host, strpos($qr_host, ':')) : '';
                        $qr_host = $detected_host . $port;
                    }
                }
                foreach ($generated_ranges as &$range) {
                    $range['url'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $qr_host . $base_path . '/panel.php?token=' . urlencode($range['token']);
                    $panel_urls[] = $range['url'];
                }
                unset($range);
                $message = count($generated_ranges) === 1
                    ? 'One panel QR generated for ' . count($projects) . ' project groups. This link is valid for 7 days.'
                    : count($generated_ranges) . ' panel QRs generated for ' . count($projects) . ' project groups. Each QR contains its assigned group range.';
            } catch (Throwable $exception) {
                $conn->rollback();
                $error = $exception->getMessage();
            }
        }
    }
}

include_once '../includes/admin_header.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1"><i class="bi bi-qr-code me-2"></i>External Panel QR</h3>
            <p class="text-muted mb-0">Generate a secure link for an external panel to evaluate Project Demonstration 3.</p>
        </div>
        <span class="badge bg-primary px-3 py-2">Demo 3 | 15%</span>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= sanitize($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= sanitize($error); ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card admin-card p-4">
                <h5 class="fw-bold mb-1">Generate Panel Access</h5>
                <p class="text-muted small mb-4">Use 1 QR for all groups, or split the current project groups across several panel QR codes.</p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token); ?>">
                    <label class="form-label fw-bold" for="panel_count">Number of panel QR codes</label>
                    <input type="number" class="form-control" id="panel_count" name="panel_count" min="1" value="<?= (int) ($_POST['panel_count'] ?? 1); ?>" required>
                    <div class="form-text">Choose 1 if every panel member should evaluate all groups. If there are 25 groups and you choose 5, the system creates ranges 1–5, 6–10, and so on.</div>
                    <button type="submit" name="generate_panel_qr" class="btn btn-primary fw-bold mt-3"><i class="bi bi-qr-code me-2"></i>Generate QR Code</button>
                </form>
            </div>
        </div>

        <?php if ($generated_ranges): ?>
            <div class="col-12">
                <div class="row g-4">
                    <?php foreach ($generated_ranges as $index => $range): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="card admin-card p-4 text-center h-100">
                                <h5 class="fw-bold mb-1">Panel <?= $index + 1; ?></h5>
                                <p class="text-muted mb-3">Groups <?= (int) $range['from']; ?>–<?= (int) $range['to']; ?> · <?= (int) $range['count']; ?> groups</p>
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=<?= urlencode($range['url']); ?>" alt="Panel <?= $index + 1; ?> QR code" class="img-fluid mx-auto mb-3" style="max-width:260px;">
                                <div class="input-group input-group-sm mb-2">
                                    <input type="text" class="form-control" value="<?= sanitize($range['url']); ?>" readonly>
                                    <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(this.previousElementSibling.value)"><i class="bi bi-copy"></i></button>
                                </div>
                                <small class="text-muted">This QR only shows its assigned group range.</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
