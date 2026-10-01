<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/ranking.php';
check_access(['Admin']);

$panel_batches = [];
$batch_result = $conn->query("SELECT ps.id, ps.created_at, MAX(pe.created_at) AS latest_evaluation_at, COUNT(DISTINCT psp.project_id) AS project_count FROM panel_sessions ps JOIN panel_evaluations pe ON pe.panel_session_id = ps.id JOIN panel_session_projects psp ON psp.panel_session_id = ps.id JOIN projects p ON p.id = psp.project_id WHERE p.department = 'JTMK' AND p.course_code = 'DFT50114' GROUP BY ps.id, ps.created_at ORDER BY latest_evaluation_at DESC, ps.id DESC");
if ($batch_result) {
    while ($batch = $batch_result->fetch_assoc()) {
        $panel_batches[] = $batch;
    }
}

$selected_batch_id = null;
if ($panel_batches) {
    $requested_batch_id = filter_input(INPUT_GET, 'panel_batch', FILTER_VALIDATE_INT);
    $selected_batch_id = (int) $panel_batches[0]['id'];
    foreach ($panel_batches as $batch) {
        if ($requested_batch_id !== false && $requested_batch_id !== null && (int) $batch['id'] === $requested_batch_id) {
            $selected_batch_id = $requested_batch_id;
            break;
        }
    }
}

$ranking_data = get_project_rankings($conn, null, $selected_batch_id);
$project_rankings = $ranking_data['rows'];
$ranking_available = $ranking_data['available'];
$panel_choice_batch_id = $panel_batches ? (int) $panel_batches[0]['id'] : 0;

$category_rows = $conn->query("SELECT category, COUNT(*) AS total FROM projects WHERE department = 'JTMK' AND course_code = 'DFT50114' GROUP BY category ORDER BY total DESC, category ASC");
$status_rows = $conn->query("SELECT status, COUNT(*) AS total FROM projects WHERE department = 'JTMK' AND course_code = 'DFT50114' GROUP BY status ORDER BY status ASC");
$demo_rows = $conn->query("SELECT demo_type, status, COUNT(*) AS total FROM student_demo_status WHERE supervisor_id IN (SELECT id FROM users WHERE role = 'Supervisor' AND department = 'JTMK') GROUP BY demo_type, status ORDER BY demo_type, status");
$panel_choice_rows = false;
if ($panel_choice_batch_id) {
    $panel_choice_stmt = $conn->prepare("SELECT p.id, p.project_group_no, p.title, p.category, p.session, psc.selected_at AS panel_choice_at, leader.full_name AS leader_name, COUNT(DISTINCT pm.student_id) AS member_count, sv.full_name AS supervisor_name FROM panel_session_choices psc JOIN projects p ON p.id = psc.project_id LEFT JOIN users leader ON leader.id = p.student_id LEFT JOIN project_members pm ON pm.project_id = p.id LEFT JOIN users sv ON sv.id = p.supervisor_id WHERE psc.panel_session_id = ? AND p.department = 'JTMK' AND p.course_code = 'DFT50114' GROUP BY p.id, p.project_group_no, p.title, p.category, p.session, psc.selected_at, leader.full_name, sv.full_name ORDER BY p.project_group_no ASC");
    if ($panel_choice_stmt) {
        $panel_choice_stmt->bind_param('i', $panel_choice_batch_id);
        $panel_choice_stmt->execute();
        $panel_choice_rows = $panel_choice_stmt->get_result();
    }
} else {
    $panel_choice_rows = $conn->query("SELECT p.id, p.project_group_no, p.title, p.category, p.session, p.panel_choice_at, leader.full_name AS leader_name, COUNT(DISTINCT pm.student_id) AS member_count, sv.full_name AS supervisor_name FROM projects p LEFT JOIN users leader ON leader.id = p.student_id LEFT JOIN project_members pm ON pm.project_id = p.id LEFT JOIN users sv ON sv.id = p.supervisor_id WHERE p.department = 'JTMK' AND p.course_code = 'DFT50114' AND p.is_panel_choice = 1 GROUP BY p.id, p.project_group_no, p.title, p.category, p.session, p.panel_choice_at, leader.full_name, sv.full_name ORDER BY p.project_group_no ASC");
}

$demo_summary = [];
while ($row = $demo_rows->fetch_assoc()) {
    $demo_summary[$row['demo_type']][$row['status']] = (int) $row['total'];
}

$panel_report_groups = [];
$panel_report_available = false;
$panel_submission_count = 0;
$panel_report_stmt = $conn->prepare("SELECT pe.id AS evaluation_id, pe.panel_name, pe.panel_email, pe.assessor_types, pe.student_scores_json, pe.comments, pe.average_score, pe.created_at, p.id AS project_id, p.project_group_no, p.title FROM panel_sessions ps JOIN panel_session_projects psp ON psp.panel_session_id = ps.id JOIN projects p ON p.id = psp.project_id LEFT JOIN panel_evaluations pe ON pe.panel_session_id = ps.id AND pe.project_id = p.id WHERE p.department = 'JTMK' AND p.course_code = 'DFT50114' AND ps.created_at = (SELECT MAX(created_at) FROM panel_sessions) ORDER BY p.project_group_no, pe.created_at DESC");
if ($panel_report_stmt) {
    $panel_report_available = true;
    $panel_report_stmt->execute();
    $panel_report_result = $panel_report_stmt->get_result();
    while ($evaluation = $panel_report_result->fetch_assoc()) {
        $project_id = (int) $evaluation['project_id'];
        if (!isset($panel_report_groups[$project_id])) {
            $panel_report_groups[$project_id] = [
                'group_no' => (int) $evaluation['project_group_no'],
                'title' => $evaluation['title'],
                'evaluations' => [],
                'rating_total' => 0,
                'demo3_total' => 0
            ];
        }

        if ($evaluation['evaluation_id'] !== null) {
            $score_data = json_decode($evaluation['student_scores_json'], true) ?: [];
            $evaluation['members'] = $score_data['members'] ?? [];
            $evaluation['aspects'] = $score_data['aspects'] ?? [];
            $evaluation['results'] = $score_data['results'] ?? [];
            $student_demo3_scores = array_column($evaluation['results'], 'demo3_score');
            $evaluation['group_demo3_score'] = $student_demo3_scores
                ? round(array_sum($student_demo3_scores) / count($student_demo3_scores), 2)
                : 0;

            $panel_report_groups[$project_id]['evaluations'][] = $evaluation;
            $panel_report_groups[$project_id]['rating_total'] += (float) $evaluation['average_score'];
            $panel_report_groups[$project_id]['demo3_total'] += $evaluation['group_demo3_score'];
            $panel_submission_count++;
        }
    }

    foreach ($panel_report_groups as &$group_report) {
        $evaluation_count = count($group_report['evaluations']);
        $group_report['average_rating'] = $evaluation_count ? round($group_report['rating_total'] / $evaluation_count, 2) : null;
        $group_report['average_demo3'] = $evaluation_count ? round($group_report['demo3_total'] / $evaluation_count, 2) : null;
    }
    unset($group_report);
}

include_once '../includes/admin_header.php';
?>

<div class="container-fluid px-0">
    <div class="mb-4"><h3 class="fw-bold text-primary mb-1"><i class="bi bi-bar-chart-line-fill me-2"></i>Reports</h3><p class="text-muted mb-0">JTMK and DFT50114 project administration reports.</p></div>
    <div class="row g-4">
        <div class="col-lg-6"><div class="card admin-card p-4 h-100"><h5 class="fw-bold mb-3">Projects by Category</h5><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Category</th><th class="text-end">Projects</th></tr></thead><tbody><?php if ($category_rows->num_rows === 0): ?><tr><td colspan="2" class="text-muted">No projects found.</td></tr><?php else: while ($row = $category_rows->fetch_assoc()): ?><tr><td><?= sanitize($row['category']); ?></td><td class="text-end"><span class="badge bg-primary rounded-pill"><?= $row['total']; ?></span></td></tr><?php endwhile; endif; ?></tbody></table></div></div></div>
        <div class="col-lg-6"><div class="card admin-card p-4 h-100"><h5 class="fw-bold mb-3">Project Registration Status</h5><?php while ($row = $status_rows->fetch_assoc()): ?><div class="d-flex justify-content-between align-items-center border-bottom py-3"><span><?= sanitize($row['status'] ?: 'Unknown'); ?></span><strong><?= $row['total']; ?></strong></div><?php endwhile; ?></div></div>
        <div class="col-12"><div class="card admin-card p-4"><h5 class="fw-bold mb-3">Demo Verification Summary</h5><div class="row g-3"><?php foreach (['Demo 1', 'Demo 2'] as $demo): ?><div class="col-md-6"><div class="p-3 rounded-3 bg-light border"><h6 class="fw-bold mb-3"><?= $demo; ?></h6><div class="d-flex gap-2 flex-wrap"><span class="badge bg-success">Passed: <?= $demo_summary[$demo]['Passed'] ?? 0; ?></span><span class="badge bg-danger">Not Passed: <?= $demo_summary[$demo]['Not Passed'] ?? 0; ?></span><span class="badge bg-secondary">Pending: <?= $demo_summary[$demo]['Pending'] ?? 0; ?></span></div></div></div><?php endforeach; ?></div><p class="text-muted small mt-3 mb-0">This summary covers Demo 1 and Demo 2 verification. Demo 3 panel scores are listed below.</p></div></div>
        <div class="col-12" id="rankings" style="scroll-margin-top: 96px;">
            <div class="card admin-card p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div><h5 class="fw-bold mb-1"><i class="bi bi-trophy-fill text-warning me-2"></i>Project Ranking by QR Batch</h5><p class="text-muted small mb-0">Groups ranked by average Demo 3 marks, starting with the most recently evaluated QR batch.</p></div>
                    <?php if ($ranking_available): ?><span class="badge text-bg-primary"><?= count($project_rankings); ?> ranked group<?= count($project_rankings) === 1 ? '' : 's'; ?></span><?php endif; ?>
                </div>
                <?php if ($panel_batches): ?>
                    <form method="GET" action="reports.php" class="row g-2 align-items-end mb-3">
                        <div class="col-md-8 col-lg-6">
                            <label for="panel_batch" class="form-label fw-semibold">QR batch</label>
                            <select class="form-select" id="panel_batch" name="panel_batch">
                                <?php foreach ($panel_batches as $batch): ?>
                                    <option value="<?= (int) $batch['id']; ?>" <?= $selected_batch_id === (int) $batch['id'] ? 'selected' : ''; ?>><?= sanitize(date('d M Y, h:i A', strtotime($batch['latest_evaluation_at']))); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto"><button type="submit" class="btn btn-primary"><i class="bi bi-filter me-1"></i>Show Ranking</button></div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">No evaluated QR batches are available yet.</div>
                <?php endif; ?>
                <?php if (!$panel_batches): ?>
                    <p class="text-muted mb-0">Ranking will appear after a panel evaluates a project through a QR batch.</p>
                <?php elseif (!$ranking_available): ?>
                    <div class="alert alert-warning mb-0">Ranking data is unavailable. Run <code>panel_module_migration.sql</code>.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>Rank</th><th>Group</th><th>Project</th><th>Leader</th><th>Members</th><th>Panel Submissions</th><th>Average Marks /100</th><th>Average Demo 3 /15</th></tr></thead>
                            <tbody>
                                <?php if (!$project_rankings): ?>
                                    <tr><td>-</td><td>-</td><td class="text-muted">No group evaluated</td><td colspan="5"></td></tr>
                                <?php endif; ?>
                                <?php foreach ($project_rankings as $ranking): ?>
                                    <tr>
                                        <td><span class="badge <?= $ranking['rank'] <= 3 ? 'bg-warning text-dark' : 'bg-secondary'; ?>">#<?= (int) $ranking['rank']; ?></span></td>
                                        <td><?= (int) $ranking['project_group_no']; ?></td>
                                        <td><strong><?= sanitize($ranking['title']); ?></strong><small class="d-block text-muted"><?= sanitize($ranking['category']); ?></small></td>
                                        <td><?= sanitize($ranking['leader_name'] ?? '-'); ?></td>
                                        <td><?= (int) $ranking['member_count']; ?></td>
                                        <td><?= (int) $ranking['evaluation_count']; ?></td>
                                        <td class="fw-bold"><?= number_format($ranking['avg_total_score'], 0); ?></td>
                                        <td><?= number_format($ranking['avg_demo3_score'], 0); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-12" id="panel-choices"><div class="card admin-card p-4"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div><h5 class="fw-bold mb-1"><i class="bi bi-star-fill text-warning me-2"></i>Panel's Choices</h5><p class="text-muted small mb-0">Groups marked as the panel's favourite projects.</p></div><span class="badge text-bg-warning"><?= $panel_choice_rows ? $panel_choice_rows->num_rows : 0; ?> selected</span></div><?php if (!$panel_choice_rows || $panel_choice_rows->num_rows === 0): ?><p class="text-muted mb-0">No groups have been selected yet.</p><?php else: ?><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Group</th><th>Project</th><th>Leader</th><th>Members</th><th>Supervisor</th><th>Selected At</th></tr></thead><tbody><?php while ($choice = $panel_choice_rows->fetch_assoc()): ?><tr><td><?= (int) $choice['project_group_no']; ?></td><td><strong><?= sanitize($choice['title']); ?></strong><small class="d-block text-muted"><?= sanitize($choice['category']); ?></small></td><td><?= sanitize($choice['leader_name'] ?? '-'); ?></td><td><?= (int) $choice['member_count']; ?></td><td><?= sanitize($choice['supervisor_name'] ?? '-'); ?></td><td><?= $choice['panel_choice_at'] ? sanitize(date('d M Y, h:i A', strtotime($choice['panel_choice_at']))) : '-'; ?></td></tr><?php endwhile; ?></tbody></table></div><?php endif; ?></div></div>

        <div class="col-12">
            <div class="card admin-card p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div><h5 class="fw-bold mb-1">External Panel Demo 3 Evaluations</h5><p class="text-muted small mb-0">One row per group from the latest generated QR batch.</p></div>
                    <?php if ($panel_report_available): ?><span class="badge text-bg-primary"><?= $panel_submission_count; ?> submissions · <?= count($panel_report_groups); ?> groups</span><?php endif; ?>
                </div>
                <?php if (!$panel_report_available): ?>
                    <div class="alert alert-warning mb-0">Panel report tables are unavailable. Run <code>panel_module_migration.sql</code>.</div>
                <?php elseif (!$panel_report_groups): ?>
                    <p class="text-muted mb-0">No projects are available in the latest QR batch.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light"><tr><th>Group</th><th>Project</th><th>Panel Submissions</th><th>Average Rating /4</th><th>Average Demo 3 /15</th><th>Details</th></tr></thead>
                            <tbody>
                                <?php foreach ($panel_report_groups as $group_report): ?>
                                    <tr>
                                        <td><?= $group_report['group_no']; ?></td>
                                        <td><?= sanitize($group_report['title']); ?></td>
                                        <td><?= count($group_report['evaluations']); ?></td>
                                        <td><?= $group_report['average_rating'] === null ? '<span class="text-muted">Not Evaluated</span>' : number_format($group_report['average_rating'], 2); ?></td>
                                        <td><?= $group_report['average_demo3'] === null ? '<span class="text-muted">Not Evaluated</span>' : number_format($group_report['average_demo3'], 2); ?></td>
                                        <td>
                                            <?php if ($group_report['evaluations']): ?><details>
                                                <summary class="text-primary fw-semibold">Panel breakdown</summary>
                                                <?php foreach ($group_report['evaluations'] as $evaluation): ?>
                                                    <div class="border rounded p-3 mt-3">
                                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2"><div><strong><?= sanitize($evaluation['panel_name']); ?></strong><?php if ($evaluation['panel_email']): ?><small class="d-block text-muted"><?= sanitize($evaluation['panel_email']); ?></small><?php endif; ?><small class="d-block text-muted"><?= sanitize($evaluation['assessor_types'] ?: 'EXTERNAL ASSESSOR'); ?> · <?= sanitize(date('d M Y, h:i A', strtotime($evaluation['created_at']))); ?></small></div><span class="badge text-bg-light border">Rating <?= number_format((float) $evaluation['average_score'], 2); ?>/4 · Demo 3 <?= number_format($evaluation['group_demo3_score'], 2); ?>/15</span></div>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-bordered align-middle mb-2">
                                                                <thead class="table-light"><tr><th>Student</th><th>Matric</th><?php foreach (range(1, 8) as $aspect_number): ?><th>Criteria <?= $aspect_number; ?></th><?php endforeach; ?><th>Total /100</th><th>Demo 3 /15</th></tr></thead>
                                                                <tbody>
                                                                    <?php foreach ($evaluation['members'] as $member): $member_id = (string) $member['id']; $result = $evaluation['results'][$member_id] ?? $evaluation['results'][(int) $member['id']] ?? []; ?>
                                                                        <tr>
                                                                            <td><?= sanitize($member['full_name'] ?? '-'); ?></td>
                                                                            <td><?= sanitize($member['matric_no'] ?? '-'); ?></td>
                                                                            <?php foreach (range(0, 7) as $aspect_index): ?><td class="text-center"><?= sanitize($evaluation['aspects'][$aspect_index][$member_id] ?? $evaluation['aspects'][$aspect_index][(int) $member['id']] ?? '-'); ?></td><?php endforeach; ?>
                                                                            <td class="text-center fw-bold"><?= number_format((float) ($result['total_score'] ?? 0), 0); ?></td>
                                                                            <td class="text-center fw-bold"><?= number_format((float) ($result['demo3_score'] ?? 0), 0); ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <?php if ($evaluation['comments']): ?><p class="small mb-0"><strong>Panel Comments:</strong> <?= nl2br(sanitize($evaluation['comments'])); ?></p><?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </details><?php else: ?><span class="text-muted">-</span><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (array_key_exists('panel_batch', $_GET)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rankingSection = document.getElementById('rankings');
    if (rankingSection) {
        rankingSection.scrollIntoView({ block: 'start' });
    }
});
</script>
<?php endif; ?>

<?php include_once '../includes/admin_footer.php'; ?>
