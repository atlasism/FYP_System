<?php
require_once 'config/database.php';
require_once 'includes/ranking.php';
include_once 'includes/header.php';
include_once 'includes/navbar.php';

// Fungsi keselamatan terbina jika fungsi sanitize() tiada dalam projek
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Ambil pengumuman semasa dengan selamat
$announcement = 'No current announcements.';
$stmt_ann = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'announcement_text'");
if ($stmt_ann) {
    $stmt_ann->execute();
    $res_ann = $stmt_ann->get_result();
    if ($res_ann && $res_ann->num_rows > 0) {
        $row_ann = $res_ann->fetch_assoc();
        if (!empty($row_ann['setting_value'])) {
            $announcement = $row_ann['setting_value'];
        }
    }
}

// Ambil deadline rasmi yang paling hampir dahulu.
$deadlines = [];
$deadline_result = $conn->query("SELECT title, description, due_date FROM submission_deadlines ORDER BY CASE WHEN due_date IS NULL OR due_date = '0000-00-00 00:00:00' OR due_date = '0000-00-00' THEN 1 ELSE 0 END, due_date ASC, id ASC");
if ($deadline_result) {
    while ($deadline = $deadline_result->fetch_assoc()) {
        $deadlines[] = $deadline;
    }
}

$panel_choice_rows = $conn->query("SELECT p.id, p.project_group_no, p.title, p.category, p.session, leader.full_name AS leader_name, COUNT(DISTINCT pm.student_id) AS member_count FROM projects p LEFT JOIN users leader ON leader.id = p.student_id LEFT JOIN project_members pm ON pm.project_id = p.id WHERE p.department = 'JTMK' AND p.course_code = 'DFT50114' AND p.is_panel_choice = 1 GROUP BY p.id, p.project_group_no, p.title, p.category, p.session, leader.full_name ORDER BY p.project_group_no ASC");

$ranking_data = get_project_rankings($conn, 5);
$top_rankings = $ranking_data['rows'];
$ranking_available = $ranking_data['available'];
$podium_order = [2, 1, 3];


// Paparkan projek semasa yang belum lengkap untuk penilaian.
$stmt_projects = $conn->prepare("SELECT p.*, u.full_name FROM projects p JOIN users u ON p.student_id = u.id WHERE COALESCE(p.is_complete_for_evaluation, 0) = 0 AND (p.status IS NULL OR p.status <> 'Completed') ORDER BY p.project_group_no ASC LIMIT 6");
$top_projects = null;
if ($stmt_projects) {
    $stmt_projects->execute();
    $top_projects = $stmt_projects->get_result();
}
?>

<div class="container py-4">
    <!-- Hero / Banner Utama -->
    <div class="hero-banner text-center mb-4 p-4 rounded shadow-sm bg-primary text-white">
        <h1 class="fw-bold">SPInE Student Project System</h1>
        <p class="lead">JTMK | DFT50114 Integrated Project</p>
        <span class="badge bg-warning text-dark px-3 py-2 fs-6">Politeknik Besut</span>
    </div>

    <div class="card public-glass card-custom p-4 mb-4 shadow-sm border-0">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="fw-bold text-primary mb-0"><i class="fas fa-trophy text-warning me-2"></i>Top 5 Project Ranking</h4>
            <span class="badge bg-primary">Demo 3 Panel Marks</span>
        </div>
        <?php if ($ranking_available && $top_rankings): ?>
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
                        <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                            <div><span class="badge bg-secondary me-2">#<?= (int) $ranking['rank']; ?></span><strong><?= sanitize($ranking['title']); ?></strong><small class="text-muted d-block ms-4">Group <?= (int) $ranking['project_group_no']; ?> · <?= sanitize($ranking['leader_name'] ?? '-'); ?></small></div>
                            <span class="badge bg-primary"><?= number_format($ranking['avg_total_score'], 2); ?> pts</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-muted mb-0">Project ranking will appear here once the external panel submits Demo 3 marks.</p>
        <?php endif; ?>
    </div>

    <div class="card public-glass card-custom p-4 mb-4 shadow-sm border-0">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="fw-bold text-primary mb-0"><i class="fas fa-star text-warning me-2"></i>Panel's Choices</h4>
            <span class="badge bg-warning text-dark">Featured Projects</span>
        </div>
        <?php if ($panel_choice_rows && $panel_choice_rows->num_rows > 0): ?>
            <div class="row g-3">
                <?php while ($choice = $panel_choice_rows->fetch_assoc()): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <div class="d-flex justify-content-between gap-2"><span class="badge bg-primary">Group <?= (int) $choice['project_group_no']; ?></span><i class="fas fa-star text-warning"></i></div>
                            <h5 class="fw-bold mt-3 mb-2"><?= sanitize($choice['title']); ?></h5>
                            <p class="small text-muted mb-0"><?= sanitize($choice['leader_name'] ?? '-'); ?> · <?= (int) $choice['member_count']; ?> students · <?= sanitize($choice['session'] ?? '-'); ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">Panel's Choices will appear here after the panel selects the featured groups.</p>
        <?php endif; ?>
    </div>

    <!-- Pengumuman Semasa -->
    <div class="alert alert-info public-glass border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
        <i class="fas fa-bullhorn fa-2x me-3"></i>
        <div>
            <strong>Current Announcement:</strong> <?= sanitize($announcement); ?>
        </div>
    </div>

    <!-- Deadline Semasa -->
    <div class="card public-glass card-custom p-4 mb-4 shadow-sm border-0">
        <h4 class="fw-bold text-primary mb-3"><i class="fas fa-calendar-check me-2"></i>Project Deadlines</h4>
        <div class="row g-3">
            <?php if ($deadlines): ?>
                <?php foreach ($deadlines as $deadline):
                    $has_due_date = !empty($deadline['due_date']) && !in_array($deadline['due_date'], ['0000-00-00 00:00:00', '0000-00-00'], true);
                    $due_timestamp = $has_due_date ? strtotime($deadline['due_date']) : null;
                    $is_overdue = $due_timestamp && $due_timestamp < time();
                ?>
                    <div class="col-md-6 col-xl-3">
                        <div class="p-3 bg-light rounded border h-100">
                            <h6 class="fw-bold text-secondary mb-2"><?= sanitize($deadline['title']); ?></h6>
                            <?php if ($due_timestamp): ?>
                                <span class="badge <?= $is_overdue ? 'bg-secondary' : 'bg-warning text-dark'; ?> mb-2"><i class="fas fa-clock me-1"></i><?= date('d/m/Y, h:i A', $due_timestamp); ?></span>
                            <?php else: ?>
                                <span class="badge bg-info mb-2">To Be Announced</span>
                            <?php endif; ?>
                            <small class="text-muted d-block"><?= sanitize($deadline['description'] ?? ''); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12"><p class="text-muted mb-0">No project deadlines have been published yet.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Projek Semasa -->
    <h4 class="fw-bold text-white mb-3"><i class="fas fa-spinner text-warning me-2"></i>Current Projects In Progress</h4>
    <div class="row g-4">
        <?php if ($top_projects && $top_projects->num_rows > 0): ?>
            <?php while ($proj = $top_projects->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card public-glass card-custom h-100 shadow-sm border-0">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-secondary mb-2 w-auto align-self-start">
                                <?= sanitize($proj['department'] ?? 'N/A'); ?> - <?= sanitize($proj['category'] ?? 'N/A'); ?>
                            </span>
                            <h5 class="card-title fw-bold text-dark"><?= sanitize($proj['title']); ?></h5>
                            <p class="card-text text-muted small flex-grow-1">
                                <?= sanitize(mb_substr($proj['description'] ?? '', 0, 120)) . (mb_strlen($proj['description'] ?? '') > 120 ? '...' : ''); ?>
                            </p>
                            <hr>
                            <small class="text-secondary"><i class="fas fa-user me-1"></i> Student: <?= sanitize($proj['full_name']); ?></small>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-light text-center py-4 border shadow-sm">
                    <i class="fas fa-folder-open fa-2x mb-2 text-muted"></i>
                    <p class="mb-0 text-muted">Current session project materials are being updated by students.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>