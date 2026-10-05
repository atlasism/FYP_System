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

$project_result = $conn->query("SELECT p.id, p.project_group_no, p.title FROM projects p WHERE p.department = 'JTMK' AND p.course_code = 'DFT50114' AND p.project_group_no IS NOT NULL AND p.session = (SELECT latest.session FROM projects latest WHERE latest.department = 'JTMK' AND latest.course_code = 'DFT50114' AND latest.project_group_no IS NOT NULL ORDER BY latest.id DESC LIMIT 1) ORDER BY p.project_group_no ASC, p.id ASC");
if (!$project_result) {
    $error = 'QR setup is not available yet. Please contact the system administrator.';
    $projects = [];
} else {
    $projects = [];
    while ($row = $project_result->fetch_assoc()) {
        $projects[] = ['id' => (int) $row['id'], 'group_no' => (int) $row['project_group_no'], 'title' => $row['title']];
    }
}

$total_panels = max(2, min(255, (int) ($_POST['total_panels'] ?? 6)));
$qr_mode = isset($_POST['generate_panel_qr']) ? 'split' : 'shared';
$qr_count = max(1, min(count($projects), (int) ($_POST['qr_count'] ?? 2)));
$group_sizes = is_array($_POST['group_sizes'] ?? null) ? array_values($_POST['group_sizes']) : [];
$panel_counts = is_array($_POST['panel_counts'] ?? null) ? array_values($_POST['panel_counts']) : [];

if (!$group_sizes) {
    $base_size = intdiv(count($projects), $qr_count);
    $group_remainder = count($projects) % $qr_count;
    for ($index = 0; $index < $qr_count; $index++) {
        $group_sizes[$index] = $base_size + ($index < $group_remainder ? 1 : 0);
    }
}
if (!$panel_counts) {
    $base_count = intdiv($total_panels, $qr_count);
    $panel_remainder = $total_panels % $qr_count;
    for ($index = 0; $index < $qr_count; $index++) {
        $panel_counts[$index] = max(2, $base_count + ($index < $panel_remainder ? 1 : 0));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['generate_panel_qr']) || isset($_POST['generate_shared_qr']))) {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form session. Please try again.';
    } elseif ($error !== '') {
        // Keep the database/setup error visible.
    } elseif (!$projects) {
        $error = 'No DFT50114 project groups are available.';
    } else {
        $is_shared_request = isset($_POST['generate_shared_qr']);
        $raw_total_panels = filter_var($_POST['total_panels'] ?? null, FILTER_VALIDATE_INT);
        $raw_qr_count = $is_shared_request ? 1 : filter_var($_POST['qr_count'] ?? null, FILTER_VALIDATE_INT);
        if ($raw_total_panels === false || $raw_total_panels < 2 || $raw_total_panels > 255 || $raw_qr_count === false || $raw_qr_count < 1 || $raw_qr_count > count($projects)) {
            $error = 'Enter valid panel and QR batch counts. Each batch needs at least 2 panel members.';
        } else {
            $generation_total_panels = $raw_total_panels;
            $generation_qr_count = $raw_qr_count;
            $generation_group_sizes = !$is_shared_request && is_array($_POST['group_sizes'] ?? null) ? array_values($_POST['group_sizes']) : [count($projects)];
            $generation_panel_counts = $is_shared_request
                ? [$generation_total_panels]
                : (is_array($_POST['panel_counts'] ?? null) ? array_values($_POST['panel_counts']) : []);
            $groups_by_batch = array_fill(0, $generation_qr_count, []);
            $allocated_panels = 0;
            $allocated_groups = 0;

            if (count($generation_panel_counts) !== $generation_qr_count || count($generation_group_sizes) !== $generation_qr_count) {
                $error = 'Set group and panel counts for every QR batch.';
            } else {
                foreach ($generation_panel_counts as $index => $panel_count) {
                    $validated_count = filter_var($panel_count, FILTER_VALIDATE_INT);
                    if ($validated_count === false || $validated_count < 2 || $validated_count > $generation_total_panels) {
                        $error = 'Each QR batch must have at least 2 panel members and cannot exceed the total number of panel members.';
                        break;
                    }
                    $allocated_panels += $validated_count;

                    $validated_size = filter_var($generation_group_sizes[$index], FILTER_VALIDATE_INT);
                    if ($validated_size === false || $validated_size < 1) {
                        $error = 'Each QR batch must contain at least one group.';
                        break;
                    }
                    $allocated_groups += $validated_size;
                }
                if (!$error && $allocated_panels < $generation_total_panels) {
                    $error = 'Assign all panel members to at least one QR batch. A panel member can be assigned to more than one batch.';
                }
                if (!$error && $allocated_groups !== count($projects)) {
                    $error = 'The group counts across QR batches must add up to the total number of groups.';
                }
            }

            if (!$error) {
                $project_offset = 0;
                foreach ($generation_group_sizes as $batch_index => $group_size) {
                    $groups_by_batch[$batch_index] = array_slice($projects, $project_offset, (int) $group_size);
                    $project_offset += (int) $group_size;
                }
            }

            if (!$error) {
            $admin_id = (int) ($_SESSION['user_id'] ?? 0);
            $conn->begin_transaction();
            try {
                $session_stmt = $conn->prepare("INSERT INTO panel_sessions (project_id, token, created_by, expected_panel_count, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))");
                if (!$session_stmt) {
                    throw new RuntimeException('QR setup is not available yet. Please contact the system administrator.');
                }
                $mapping_stmt = $conn->prepare('INSERT INTO panel_session_projects (panel_session_id, project_id) VALUES (?, ?)');
                if (!$mapping_stmt) {
                    throw new RuntimeException('QR setup is not available yet. Please contact the system administrator.');
                }
                foreach ($groups_by_batch as $batch_index => $project_chunk) {
                    $token = bin2hex(random_bytes(32));
                    $first_project_id = $project_chunk[0]['id'];
                    $assigned_panel_count = (int) $generation_panel_counts[$batch_index];
                    $session_stmt->bind_param('isii', $first_project_id, $token, $admin_id, $assigned_panel_count);
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
                    $generated_ranges[] = [
                        'groups' => array_column($project_chunk, 'group_no'),
                        'count' => count($project_chunk),
                        'panel_count' => $assigned_panel_count,
                        'token' => $token
                    ];
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
                $message = $is_shared_request
                    ? 'QR generated for all ' . count($projects) . ' groups, with a limit of ' . $generation_total_panels . ' panel members for 7 days.'
                    : count($generated_ranges) . ' QR batches generated for ' . count($projects) . ' groups. Each QR can be shared by its assigned panel members and is valid for 7 days.';
            } catch (Throwable $exception) {
                $conn->rollback();
                error_log('Panel QR generation failed: ' . $exception->getMessage());
                $error = 'Could not generate the QR codes. Please check the setup and try again.';
            }
            }
        }
    }
}

include_once '../includes/admin_header.php';
?>

<style>
    .qr-batch-list { border-top: 1px solid #dce3eb; }
    .qr-batch-row { display: grid; grid-template-columns: minmax(135px, 1fr) 96px 132px minmax(170px, 1.35fr); align-items: center; gap: 16px; padding: 14px 12px; border-bottom: 1px solid #e4e9ef; }
    .qr-batch-identity { display: flex; align-items: center; gap: 11px; min-width: 0; }
    .qr-batch-index { display: grid; place-items: center; flex: 0 0 36px; height: 36px; border-radius: 8px; color: #1f63aa; background: #e9f1f9; font-size: .8rem; font-weight: 800; }
    .qr-batch-identity strong { display: block; color: #10243d; }
    .qr-batch-field label, .qr-batch-preview-label { display: block; margin-bottom: 5px; color: #667588; font-size: .72rem; font-weight: 700; }
    .qr-batch-field input { text-align: center; font-weight: 700; }
    .qr-batch-preview { min-width: 0; }
    .batch-group-list { color: #34465c; font-size: .84rem; line-height: 1.45; overflow-wrap: anywhere; }
    .qr-batch-summary { display: flex; justify-content: space-between; gap: 12px; padding: 10px 12px; color: #667588; font-size: .82rem; }
    .qr-batch-summary .text-danger { color: #b42318 !important; }
    @media (max-width: 767.98px) {
        .qr-batch-row { grid-template-columns: minmax(0, 1fr) 92px 112px; gap: 10px; padding: 13px 8px; }
        .qr-batch-identity { grid-column: 1 / -1; }
        .qr-batch-preview { grid-column: 1 / -1; padding-left: 47px; }
        .qr-batch-summary { flex-direction: column; gap: 3px; }
    }
</style>

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
                <p class="text-muted small mb-3">Generate one QR for all groups and set the maximum number of panel members who can register with it.</p>
                <?php if ($projects): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token); ?>">
                        <div class="row g-3 align-items-end mb-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-bold" for="shared_total_panels">Maximum panel members</label>
                                <input type="number" class="form-control" id="shared_total_panels" name="total_panels" min="2" max="255" value="<?= $total_panels; ?>" required>
                            </div>
                        </div>
                        <button type="submit" name="generate_shared_qr" value="1" class="btn btn-primary fw-bold"><i class="bi bi-qr-code me-2"></i>Generate QR</button>
                    </form>
                    <details class="mt-4" <?= $qr_mode === 'split' && $error ? 'open' : ''; ?>>
                        <summary class="fw-bold">Set up split QR batches</summary>
                        <form method="POST" class="pt-3">
                            <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token); ?>">
                            <input type="hidden" name="qr_mode" value="split">
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6"><label class="form-label fw-bold" for="total_panels">Total panel members</label><input type="number" class="form-control" id="total_panels" name="total_panels" min="2" max="255" value="<?= $total_panels; ?>" required></div>
                                <div class="col-sm-6"><label class="form-label fw-bold" for="qr_count">Number of QR batches</label><input type="number" class="form-control" id="qr_count" name="qr_count" min="1" max="<?= max(1, count($projects)); ?>" value="<?= $qr_count; ?>" required></div>
                                <div class="col-12"><div class="form-text" id="qrCountHint">Up to <?= count($projects); ?> QR batches. Each QR needs at least 2 panel members; a panel member can use more than one QR.</div></div>
                            </div>
                            <div class="qr-batch-list mb-2" id="batchAssignmentList">
                                <?php $group_offset = 0; for ($index = 0; $index < $qr_count; $index++):
                                    $group_size = (int) ($group_sizes[$index] ?? 0);
                                    $batch_projects = array_slice($projects, $group_offset, $group_size);
                                    $group_offset += $group_size;
                                ?>
                                    <div class="qr-batch-row">
                                        <div class="qr-batch-identity"><span class="qr-batch-index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT); ?></span><strong>QR batch <?= $index + 1; ?></strong></div>
                                        <div class="qr-batch-field"><label for="group_count_<?= $index; ?>">Groups</label><input type="number" class="form-control form-control-sm batch-group-count" id="group_count_<?= $index; ?>" name="group_sizes[<?= $index; ?>]" min="1" max="<?= count($projects); ?>" value="<?= $group_size; ?>" required></div>
                                        <div class="qr-batch-field"><label for="panel_count_<?= $index; ?>">Panel members</label><input type="number" class="form-control form-control-sm batch-panel-count" id="panel_count_<?= $index; ?>" name="panel_counts[<?= $index; ?>]" min="2" max="<?= $total_panels; ?>" value="<?= (int) ($panel_counts[$index] ?? 2); ?>" required></div>
                                        <div class="qr-batch-preview"><span class="qr-batch-preview-label">Included groups</span><div class="batch-group-list"><?= $batch_projects ? 'Groups ' . sanitize(implode(', ', array_column($batch_projects, 'group_no'))) : 'No groups'; ?></div></div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <div class="qr-batch-summary mb-3"><span id="groupCountSummary"></span><span id="panelCountSummary"></span></div>
                            <p class="small text-muted">Set any group split you need. For 18 groups, you can assign 10 to one QR and 8 to another. Panel members can be assigned to multiple QR batches; every QR needs at least 2 panels.</p>
                            <div><button type="submit" name="generate_panel_qr" value="1" class="btn btn-primary fw-bold"><i class="bi bi-qr-code me-2"></i>Generate Split QRs</button></div>
                        </form>
                    </details>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">No current DFT50114 groups are available.</div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($generated_ranges): ?>
            <div class="col-12">
                <div class="row g-4">
                    <?php foreach ($generated_ranges as $index => $range): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="card admin-card p-4 text-center h-100">
                                <h5 class="fw-bold mb-1"><?= count($generated_ranges) === 1 ? 'QR Code' : 'QR Batch ' . ($index + 1); ?></h5>
                                <p class="text-muted mb-2"><?= $range['panel_count'] ? (int) $range['panel_count'] . ' panel members · ' : 'All panel members · '; ?><?= (int) $range['count']; ?> groups</p>
                                <p class="small mb-3">Groups <?= sanitize(implode(', ', $range['groups'])); ?></p>
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=<?= urlencode($range['url']); ?>" alt="QR batch <?= $index + 1; ?> code" class="img-fluid mx-auto mb-3" style="max-width:260px;">
                                <div class="input-group input-group-sm mb-2">
                                    <input type="text" class="form-control" value="<?= sanitize($range['url']); ?>" readonly>
                                    <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(this.previousElementSibling.value)"><i class="bi bi-copy"></i></button>
                                </div>
                                <small class="text-muted">Share this QR with the panel members. Each panel enters their name and submits separate scores.</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const qrCountInput = document.getElementById('qr_count');
    const totalPanelsInput = document.getElementById('total_panels');
    const batchList = document.getElementById('batchAssignmentList');
    const form = batchList && batchList.closest('form');
    const projects = <?= json_encode($projects, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    if (!qrCountInput || !totalPanelsInput || !batchList || !form || !projects.length) return;

    function evenCounts(total, count) {
        const base = Math.floor(total / count);
        const remainder = total % count;
        return Array.from({ length: count }, function (_, index) { return base + (index < remainder ? 1 : 0); });
    }

    function evenPanelCounts(total, count) {
        return evenCounts(total, count).map(function (panelCount) { return Math.max(2, panelCount); });
    }

    function updateSummaries() {
        const groupInputs = Array.from(batchList.querySelectorAll('.batch-group-count'));
        const panelInputs = Array.from(batchList.querySelectorAll('.batch-panel-count'));
        const groupTotal = groupInputs.reduce(function (sum, input) { return sum + Number(input.value || 0); }, 0);
        const panelTotal = panelInputs.reduce(function (sum, input) { return sum + Number(input.value || 0); }, 0);
        let groupOffset = 0;
        groupInputs.forEach(function (input, index) {
            const groupCount = Math.max(0, Number(input.value || 0));
            const groupNumbers = projects.slice(groupOffset, groupOffset + groupCount).map(function (project) { return project.group_no; });
            const list = batchList.querySelectorAll('.batch-group-list')[index];
            list.textContent = groupNumbers.length ? 'Groups ' + groupNumbers.join(', ') : 'No groups';
            groupOffset += groupCount;
        });
        const groupSummary = document.getElementById('groupCountSummary');
        const panelSummary = document.getElementById('panelCountSummary');
        groupSummary.textContent = groupTotal + ' / ' + projects.length + ' groups';
        panelSummary.textContent = Number(totalPanelsInput.value || 0) + ' panel members · ' + panelTotal + ' QR assignments';
        groupSummary.classList.toggle('text-danger', groupTotal !== projects.length);
        panelSummary.classList.toggle('text-danger', panelTotal < Number(totalPanelsInput.value || 0));
    }

    function bindBatchInputs() {
        batchList.querySelectorAll('.batch-group-count, .batch-panel-count').forEach(function (input) {
            input.addEventListener('input', updateSummaries);
        });
        updateSummaries();
    }

    function renderBatches() {
        const maximum = Math.max(1, projects.length);
        const count = Math.max(1, Math.min(Number(qrCountInput.value) || 1, maximum));
        qrCountInput.value = String(count);
        qrCountInput.max = String(maximum);
        document.getElementById('qrCountHint').textContent = 'Up to ' + maximum + ' QR batches. Each QR needs at least 2 panel members; a panel can use multiple QR batches.';
        const groupCounts = evenCounts(projects.length, count);
        const panelCounts = evenPanelCounts(Number(totalPanelsInput.value), count);
        batchList.replaceChildren();
        for (let index = 0; index < count; index++) {
            const row = document.createElement('div');
            row.className = 'qr-batch-row';
            const identity = document.createElement('div');
            identity.className = 'qr-batch-identity';
            const badge = document.createElement('span');
            badge.className = 'qr-batch-index';
            badge.textContent = String(index + 1).padStart(2, '0');
            const title = document.createElement('strong');
            title.textContent = 'QR batch ' + (index + 1);
            identity.append(badge, title);
            row.appendChild(identity);
            [[groupCounts[index], 'group_sizes', 'batch-group-count', 'Groups', 1, projects.length, 'group_count_'], [panelCounts[index], 'panel_counts', 'batch-panel-count', 'Panel members', 2, Number(totalPanelsInput.value), 'panel_count_']].forEach(function (config) {
                const field = document.createElement('div');
                field.className = 'qr-batch-field';
                const label = document.createElement('label');
                label.htmlFor = config[6] + index;
                label.textContent = config[3];
                const input = document.createElement('input');
                input.type = 'number';
                input.id = config[6] + index;
                input.className = 'form-control form-control-sm ' + config[2];
                input.name = config[1] + '[' + index + ']';
                input.min = String(config[4]);
                input.max = String(config[5]);
                input.value = String(config[0]);
                input.required = true;
                field.append(label, input);
                row.appendChild(field);
            });
            const preview = document.createElement('div');
            preview.className = 'qr-batch-preview';
            const previewLabel = document.createElement('span');
            previewLabel.className = 'qr-batch-preview-label';
            previewLabel.textContent = 'Included groups';
            const groupList = document.createElement('div');
            groupList.className = 'batch-group-list';
            preview.append(previewLabel, groupList);
            row.appendChild(preview);
            batchList.appendChild(row);
        }
        bindBatchInputs();
    }

    qrCountInput.addEventListener('change', renderBatches);
    totalPanelsInput.addEventListener('change', function () {
        const maximum = Math.max(1, projects.length);
        qrCountInput.max = String(maximum);
        document.getElementById('qrCountHint').textContent = 'Up to ' + maximum + ' QR batches. Each QR needs at least 2 panel members; a panel can use multiple QR batches.';
        if (Number(qrCountInput.value) > maximum) {
            qrCountInput.value = String(maximum);
            renderBatches();
            return;
        }
        const panelCounts = evenPanelCounts(Number(totalPanelsInput.value), Number(qrCountInput.value));
        batchList.querySelectorAll('.batch-panel-count').forEach(function (input, index) {
            input.max = totalPanelsInput.value;
            input.value = String(panelCounts[index]);
        });
        updateSummaries();
    });
    form.addEventListener('submit', function (event) {
        const groupTotal = Array.from(batchList.querySelectorAll('.batch-group-count')).reduce(function (sum, input) { return sum + Number(input.value || 0); }, 0);
        const panelTotal = Array.from(batchList.querySelectorAll('.batch-panel-count')).reduce(function (sum, input) { return sum + Number(input.value || 0); }, 0);
        if (groupTotal !== projects.length || panelTotal < Number(totalPanelsInput.value)) {
            event.preventDefault();
            alert('Make sure group counts add up to all groups and every panel is assigned to at least one QR.');
        }
    });
    bindBatchInputs();
})();
</script>

<?php include_once '../includes/admin_footer.php'; ?>
