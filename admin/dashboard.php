<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/ranking.php';
check_access(['Admin']);

$ranking_data = get_project_rankings($conn, 5);
$top_rankings = $ranking_data['rows'];
$ranking_available = $ranking_data['available'];
$podium_order = [2, 1, 3];

$student_count = 0;
$supervisor_count = 0;
$project_count = 0;
$submitted_count = 0;
$approved_count = 0;
$draft_count = 0;
$panel_choice_count = 0;

$count_queries = [
    'student_count' => "SELECT COUNT(*) AS total FROM users WHERE role = 'Student' AND department = 'JTMK'",
    'supervisor_count' => "SELECT COUNT(*) AS total FROM users WHERE role = 'Supervisor' AND department = 'JTMK'",
    'project_count' => "SELECT COUNT(*) AS total FROM projects WHERE department = 'JTMK' AND course_code = 'DFT50114'",
    'submitted_count' => "SELECT COUNT(*) AS total FROM projects WHERE department = 'JTMK' AND status = 'Submitted'",
    'approved_count' => "SELECT COUNT(*) AS total FROM projects WHERE department = 'JTMK' AND status = 'Approved'",
    'draft_count' => "SELECT COUNT(*) AS total FROM projects WHERE department = 'JTMK' AND status = 'Draft'",
    'panel_choice_count' => "SELECT COUNT(*) AS total FROM projects WHERE department = 'JTMK' AND course_code = 'DFT50114' AND is_panel_choice = 1"
];

foreach ($count_queries as $variable => $query) {
    $result = $conn->query($query);
    $$variable = (int) ($result->fetch_assoc()['total'] ?? 0);
}

include_once '../includes/admin_header.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1"><i class="bi bi-shield-check me-2"></i>Admin Dashboard</h3>
            <p class="text-muted mb-0">JTMK - DFT50114 Integrated Project administration.</p>
        </div>
        <span class="badge bg-primary px-3 py-2">JTMK | DFT50114</span>
    </div>

    <div class="row g-4 mb-4">
        <?php foreach ([
            ['label' => 'JTMK Students', 'value' => $student_count, 'icon' => 'bi-mortarboard-fill', 'class' => 'blue'],
            ['label' => 'Supervisors / Lecturers', 'value' => $supervisor_count, 'icon' => 'bi-person-workspace', 'class' => 'green'],
            ['label' => 'Registered Projects', 'value' => $project_count, 'icon' => 'bi-kanban-fill', 'class' => 'cyan']
        ] as $stat): ?>
            <div class="col-md-4">
                <div class="card admin-stat admin-stat-<?= $stat['class']; ?> h-100 p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div><small class="text-white-50 fw-bold d-block"><?= sanitize($stat['label']); ?></small><h2 class="fw-bold mb-0"><?= $stat['value']; ?></h2></div>
                        <span class="admin-stat-icon"><i class="bi <?= $stat['icon']; ?>"></i></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card admin-card p-4" id="reports">
        <h5 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Overall Project Status</h5>
        <div class="row g-3">
            <div class="col-md-4"><div class="p-3 bg-light rounded border"><span class="text-muted d-block">Draft</span><strong class="fs-3 text-secondary"><?= $draft_count; ?></strong></div></div>
            <div class="col-md-4"><div class="p-3 bg-light rounded border"><span class="text-muted d-block">Submitted</span><strong class="fs-3 text-warning"><?= $submitted_count; ?></strong></div></div>
            <div class="col-md-4"><div class="p-3 bg-light rounded border"><span class="text-muted d-block">Approved</span><strong class="fs-3 text-success"><?= $approved_count; ?></strong></div></div>
        </div>
    </div>

    <div class="card admin-card p-4 mt-4" id="rankings">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div><h5 class="fw-bold mb-1"><i class="bi bi-trophy-fill text-warning me-2"></i>Top 5 Project Ranking</h5><p class="text-muted mb-0">Ranked by average panel marks from Demo 3 evaluation.</p></div>
            <a class="btn btn-outline-primary" href="reports.php#rankings"><i class="bi bi-list-ol me-2"></i>View Full Ranking</a>
        </div>
        <?php if (!$ranking_available): ?>
            <div class="alert alert-warning mb-0">Ranking data is unavailable. Run <code>panel_module_migration.sql</code>.</div>
        <?php elseif (!$top_rankings): ?>
            <p class="text-muted mb-0">Ranking will appear once the external panel submits marks.</p>
        <?php else: ?>
            <div class="ranking-podium">
                <?php foreach ($podium_order as $podium_position): $ranking = $top_rankings[$podium_position - 1] ?? null; if (!$ranking) continue; ?>
                    <div class="podium-step podium-step-<?= $podium_position; ?>">
                        <div class="podium-card">
                            <span class="podium-medal">#<?= (int) $ranking['rank']; ?></span>
                            <strong class="d-block text-truncate" title="<?= sanitize($ranking['title']); ?>"><?= sanitize($ranking['title']); ?></strong>
                            <small class="d-block text-muted">Group <?= (int) $ranking['project_group_no']; ?> · <?= sanitize($ranking['leader_name'] ?? '-'); ?></small>
                            <span class="badge bg-primary mt-2"><?= number_format($ranking['avg_total_score'], 2); ?> pts</span>
                        </div>
                        <div class="podium-bar podium-bar-<?= $podium_position; ?>"></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($top_rankings) > 3): ?>
                <div class="list-group mt-3">
                    <?php foreach (array_slice($top_rankings, 3) as $ranking): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div><span class="badge bg-secondary me-2">#<?= (int) $ranking['rank']; ?></span><strong><?= sanitize($ranking['title']); ?></strong><small class="text-muted d-block ms-4">Group <?= (int) $ranking['project_group_no']; ?> · <?= sanitize($ranking['leader_name'] ?? '-'); ?></small></div>
                            <span class="badge bg-primary"><?= number_format($ranking['avg_total_score'], 2); ?> pts</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card admin-card p-4 mt-4" id="panel-choices">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div><h5 class="fw-bold mb-1"><i class="bi bi-star-fill text-warning me-2"></i>Panel's Choices</h5><p class="text-muted mb-0">Groups selected by the external panel.</p></div>
            <a class="btn btn-outline-primary" href="reports.php#panel-choices"><i class="bi bi-bar-chart-line me-2"></i>Open Panel's Choice Report</a>
        </div>
        <div class="mt-3"><span class="display-6 fw-bold text-primary"><?= $panel_choice_count; ?></span><span class="text-muted ms-2">selected group<?= $panel_choice_count === 1 ? '' : 's'; ?></span></div>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
