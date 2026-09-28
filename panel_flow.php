<?php
require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$selected_project_id = (int) ($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
$error = '';
$saved = isset($_GET['saved']) && $_GET['saved'] === '1';
$finished = isset($_GET['done']) && $_GET['done'] === '1';
$choice_saved = isset($_GET['choice_saved']) && $_GET['choice_saved'] === '1';
$panel_session = null;
$groups = [];
$selected_group = null;
$members = [];
$score_values = $_POST['scores'] ?? [];
$active_aspect = max(0, min(7, (int) ($_POST['active_aspect'] ?? 0)));
$assessor_type = 'EXTERNAL ASSESSOR';
$choice_csrf = '';
$rubric = [
    ['title' => 'Project achievement and objective', 'weight' => 12.5, 'levels' => [4 => 'Project is 100% complete and achieve all objectives', 3 => 'Project is more than 80% complete and achieve all objectives', 2 => 'Project is more than 50% complete and achieve a few objectives', 1 => 'Project is less than 50% complete and achieve only one objective']],
    ['title' => 'User Requirements', 'weight' => 12.5, 'levels' => [4 => 'All requirements were met.', 3 => 'Several requirements were met', 2 => 'Only one requirement was met', 1 => 'No requirement was met']],
    ['title' => 'Construction and functionality', 'weight' => 12.5, 'levels' => [4 => 'Excellently describes how the system was constructed and how it functions', 3 => 'Clearly describes how the system was constructed and how it functions', 2 => 'Moderately describes how the system was constructed and how it functions', 1 => 'Generally, not clearly describes how the system was constructed and how it functions']],
    ['title' => 'Feasibility', 'weight' => 12.5, 'levels' => [4 => 'Excellently communicated feasibility of construction and implementation most of the time', 3 => 'Clearly communicated feasibility of construction and implementation most of the time with minor error', 2 => 'Moderately communicated feasibility of construction and implementation most of the time with minor error', 1 => 'Generally, not clear communicated feasibility of construction and implementation most of the time with minor error']],
    ['title' => 'Originality', 'weight' => 12.5, 'levels' => [4 => 'Product shows an excellent genuine idea', 3 => 'Product shows a good genuine idea', 2 => 'Product shows moderate amount of genuine idea', 1 => 'Copied product']],
    ['title' => 'Marketability', 'weight' => 12.5, 'levels' => [4 => 'Provide strong evidence through market surveys AND have a client (with proof) OR recognition from outside parties (lab tested, MyIPO, testimonial)', 3 => 'Provide strong evidence such as market surveys OR have a client (with proof) OR recognition from outside parties', 2 => 'Provide a few evidence from both sources of newspapers, blogs, social media or etc.', 1 => 'Least attempt is made for a market potential']],
    ['title' => 'Creativity', 'weight' => 12.5, 'levels' => [4 => 'Excellent ideas, creative and inventive.', 3 => 'Good ideas, creative and inventive.', 2 => 'Moderate ideas, creative and inventive.', 1 => 'Least creative ideas and inventive.']],
    ['title' => 'System Security, Features and Testing', 'weight' => 12.5, 'levels' => [4 => 'Excellent implementation of user controls and validation controls', 3 => 'Good implementation of user controls and validation controls', 2 => 'Moderate implementation of user controls and validation controls', 1 => 'Least implementation of user controls and validation controls']]
];

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error = 'This panel evaluation link is invalid.';
} else {
    $stmt = $conn->prepare('SELECT * FROM panel_sessions WHERE token = ? LIMIT 1');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $panel_session = $stmt->get_result()->fetch_assoc();

    if (!$panel_session) {
        $error = 'This panel evaluation link could not be found.';
    } elseif ($panel_session['status'] === 'Expired' || ($panel_session['expires_at'] && strtotime($panel_session['expires_at']) < time())) {
        $error = 'This panel evaluation link has expired.';
    } else {
        $choice_csrf_key = 'panel_choice_csrf_' . $panel_session['id'];
        $_SESSION[$choice_csrf_key] = $_SESSION[$choice_csrf_key] ?? bin2hex(random_bytes(32));
        $choice_csrf = $_SESSION[$choice_csrf_key];
        $ids_stmt = $conn->prepare('SELECT psp.project_id FROM panel_session_projects psp JOIN projects p ON p.id = psp.project_id WHERE psp.panel_session_id = ? ORDER BY COALESCE(p.project_group_no, 255), p.id');
        $ids_stmt->bind_param('i', $panel_session['id']);
        $ids_stmt->execute();
        $project_ids = array_map('intval', array_column($ids_stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'project_id'));
        if (!$project_ids) {
            $project_ids = [(int) $panel_session['project_id']];
        }

        $project_stmt = $conn->prepare("SELECT p.id, p.title, p.session, p.course_code, p.student_id, p.is_panel_choice, sv.full_name AS supervisor_name, leader.full_name AS leader_name FROM projects p LEFT JOIN users sv ON sv.id = p.supervisor_id LEFT JOIN users leader ON leader.id = p.student_id WHERE p.id = ? AND p.department = 'JTMK' AND p.course_code = 'DFT50114' LIMIT 1");
        $member_stmt = $conn->prepare("SELECT u.id, u.full_name, u.matric_no, u.class_name FROM project_members pm JOIN users u ON u.id = pm.student_id WHERE pm.project_id = ? ORDER BY CASE WHEN pm.member_order = 0 THEN 255 ELSE pm.member_order END, pm.id ASC");
        $evaluation_stmt = $conn->prepare('SELECT id, student_scores_json FROM panel_evaluations WHERE panel_session_id = ? AND project_id = ? LIMIT 1');

        foreach ($project_ids as $project_id) {
            $project_stmt->bind_param('i', $project_id);
            $project_stmt->execute();
            $group = $project_stmt->get_result()->fetch_assoc();
            if (!$group) {
                continue;
            }
            $group['members'] = [];
            $member_stmt->bind_param('i', $project_id);
            $member_stmt->execute();
            $member_result = $member_stmt->get_result();
            while ($member = $member_result->fetch_assoc()) {
                $group['members'][] = $member;
            }
            $evaluation_stmt->bind_param('ii', $panel_session['id'], $project_id);
            $evaluation_stmt->execute();
            $group['evaluation'] = $evaluation_stmt->get_result()->fetch_assoc();
            $groups[$project_id] = $group;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_panel_choice'])) {
            $choice_project_id = (int) ($_POST['choice_project_id'] ?? 0);
            if (!hash_equals($choice_csrf, $_POST['choice_csrf'] ?? '')) {
                $error = 'Your panel choice request is invalid. Please reload the page.';
            } elseif (!isset($groups[$choice_project_id])) {
                $error = 'The selected group is not included in this panel link.';
            } else {
                $choice_stmt = $conn->prepare('UPDATE projects SET panel_choice_at = IF(is_panel_choice = 1, NULL, NOW()), is_panel_choice = IF(is_panel_choice = 1, 0, 1) WHERE id = ?');
                $choice_stmt->bind_param('i', $choice_project_id);
                if ($choice_stmt->execute()) {
                    header('Location: panel.php?token=' . urlencode($token) . '&choice_saved=1');
                    exit();
                }
                $error = 'Unable to update Panel\'s Choice.';
            }
        }

        if (!$groups) {
            $error = 'There are no groups available for this panel link.';
        } elseif ($selected_project_id && !isset($groups[$selected_project_id])) {
            $error = 'The selected group is not included in this panel link.';
        } elseif ($selected_project_id) {
            $selected_group = $groups[$selected_project_id];
            $members = $selected_group['members'];
            $evaluation = $selected_group['evaluation'];
            $csrf_key = 'panel_csrf_' . $panel_session['id'];
            $_SESSION[$csrf_key] = $_SESSION[$csrf_key] ?? bin2hex(random_bytes(32));
            $panel_csrf = $_SESSION[$csrf_key];

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_evaluation'])) {
                $panel_name = trim($_POST['panel_name'] ?? $panel_session['panel_name'] ?? '');
                $panel_email = trim($_POST['panel_email'] ?? $panel_session['panel_email'] ?? '');
                $comments = trim($_POST['comments'] ?? '');
                $aspect_scores = [];
                $student_totals = array_fill_keys(array_column($members, 'id'), 0.0);
                $score_sum = 0;
                $score_count = 0;

                if (!hash_equals($panel_csrf, $_POST['panel_csrf'] ?? '')) {
                    $error = 'Your form session is invalid. Please reload the page.';
                } elseif ($evaluation) {
                    $error = 'This group has already been assessed in this panel session.';
                } elseif ($panel_name === '') {
                    $error = 'Please enter the panel name.';
                } elseif ($panel_email !== '' && !filter_var($panel_email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address.';
                } else {
                    foreach ($rubric as $aspect_index => $aspect) {
                        $aspect_scores[$aspect_index] = [];
                        foreach ($members as $member_index => $member) {
                            $raw = $score_values[$aspect_index][$member_index] ?? '';
                            if (!in_array((string) $raw, ['1', '2', '3', '4'], true)) {
                                $error = 'Please select a score from 1 to 4 for every criteria and student.';
                                $active_aspect = $aspect_index;
                                break 2;
                            }
                            $score = (int) $raw;
                            $aspect_scores[$aspect_index][(int) $member['id']] = $score;
                            $student_totals[$member['id']] += ($score / 4) * $aspect['weight'];
                            $score_sum += $score;
                            $score_count++;
                        }
                    }
                }

                if (!$error) {
                    $results = [];
                    foreach ($members as $member) {
                        $student_id = (int) $member['id'];
                        $total = round($student_totals[$student_id], 2);
                        $results[$student_id] = ['total_score' => $total, 'demo3_score' => round($total / 100 * 15, 2)];
                    }
                    $score_json = json_encode(['members' => $members, 'aspects' => $aspect_scores, 'results' => $results], JSON_UNESCAPED_UNICODE);
                    $average_score = $score_count ? round($score_sum / $score_count, 2) : 0;
                    $assessor_string = $assessor_type;
                    $conn->begin_transaction();
                    try {
                        $save_stmt = $conn->prepare('INSERT INTO panel_evaluations (panel_session_id, project_id, panel_name, panel_email, assessor_types, student_scores_json, comments, average_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                        $save_stmt->bind_param('iisssssd', $panel_session['id'], $selected_project_id, $panel_name, $panel_email, $assessor_string, $score_json, $comments, $average_score);
                        if (!$save_stmt->execute()) {
                            throw new RuntimeException('Evaluation insert failed.');
                        }
                        $evaluation_id = (int) $conn->insert_id;
                        $student_mark_stmt = $conn->prepare('INSERT INTO panel_student_marks (panel_evaluation_id, project_id, student_id, total_score, demo3_score) VALUES (?, ?, ?, ?, ?)');
                        foreach ($members as $member) {
                            $student_id = (int) $member['id'];
                            $total = $results[$student_id]['total_score'];
                            $demo3 = $results[$student_id]['demo3_score'];
                            $student_mark_stmt->bind_param('iiidd', $evaluation_id, $selected_project_id, $student_id, $total, $demo3);
                            if (!$student_mark_stmt->execute()) {
                                throw new RuntimeException('Student mark insert failed.');
                            }
                        }

                        $group_demo3 = count($results) ? round(array_sum(array_column($results, 'demo3_score')) / count($results), 2) : 0;
                        $old_marks_stmt = $conn->prepare('SELECT demonstration_3, total_score FROM project_marks WHERE project_id = ? FOR UPDATE');
                        $old_marks_stmt->bind_param('i', $selected_project_id);
                        $old_marks_stmt->execute();
                        $old_marks = $old_marks_stmt->get_result()->fetch_assoc();
                        if ($old_marks) {
                            $new_total = round((float) $old_marks['total_score'] - (float) $old_marks['demonstration_3'] + $group_demo3, 2);
                            $update_marks = $conn->prepare('UPDATE project_marks SET demonstration_3 = ?, total_score = ? WHERE project_id = ?');
                            $update_marks->bind_param('ddi', $group_demo3, $new_total, $selected_project_id);
                        } else {
                            $new_total = $group_demo3;
                            $update_marks = $conn->prepare('INSERT INTO project_marks (project_id, demonstration_3, total_score) VALUES (?, ?, ?)');
                            $update_marks->bind_param('idd', $selected_project_id, $group_demo3, $new_total);
                        }
                        if (!$update_marks->execute()) {
                            throw new RuntimeException('Project marks update failed.');
                        }

                        $identity_stmt = $conn->prepare('UPDATE panel_sessions SET panel_name = ?, panel_email = ? WHERE id = ? AND status = \'Active\'');
                        $identity_stmt->bind_param('ssi', $panel_name, $panel_email, $panel_session['id']);
                        if (!$identity_stmt->execute()) {
                            throw new RuntimeException('Panel identity update failed.');
                        }

                        $count_stmt = $conn->prepare('SELECT COUNT(*) AS total FROM panel_session_projects WHERE panel_session_id = ?');
                        $count_stmt->bind_param('i', $panel_session['id']);
                        $count_stmt->execute();
                        $required_count = (int) $count_stmt->get_result()->fetch_assoc()['total'];
                        if (!$required_count) {
                            $required_count = 1;
                        }
                        $count_stmt = $conn->prepare('SELECT COUNT(*) AS total FROM panel_evaluations WHERE panel_session_id = ?');
                        $count_stmt->bind_param('i', $panel_session['id']);
                        $count_stmt->execute();
                        $completed_count = (int) $count_stmt->get_result()->fetch_assoc()['total'];
                        if ($completed_count >= $required_count) {
                            $done_stmt = $conn->prepare("UPDATE panel_sessions SET status = 'Submitted', submitted_at = NOW() WHERE id = ? AND status = 'Active'");
                            $done_stmt->bind_param('i', $panel_session['id']);
                            $done_stmt->execute();
                        }
                        $conn->commit();
                        header('Location: panel.php?token=' . urlencode($token) . '&saved=1');
                        exit();
                    } catch (Throwable $exception) {
                        $conn->rollback();
                        $error = 'Unable to save the evaluation. Please contact the administrator.';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Demo 3 | Politeknik Besut</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --panel-blue: #1f63aa; --panel-navy: #10243d; --panel-ice: #e9eef4; --panel-line: #d6dee8; }
        body { margin: 0; color: var(--panel-navy); background: var(--panel-ice); font-family: Arial, sans-serif; }
        .panel-header { background: var(--panel-navy); color: #fff; }
        .panel-logo { width: 118px; height: 58px; object-fit: contain; }
        .panel-card { background: #fff; border: 1px solid var(--panel-line); border-radius: 4px; box-shadow: 0 8px 24px rgba(16,36,61,.08); }
        .panel-section-bar { color: #fff; background: var(--panel-blue); }
        .info-label { background: var(--panel-ice); font-weight: 700; }
        .group-row { border-bottom: 1px solid var(--panel-line); }
        .group-row:last-child { border-bottom: 0; }
        .group-link { color: var(--panel-navy); text-decoration: none; }
        .group-link:hover { color: var(--panel-blue); }
        .aspect-tabs { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
        .aspect-tab { min-height: 44px; border: 1px solid var(--panel-line); color: var(--panel-navy); background: #fff; }
        .aspect-tab.active { color: #fff; border-color: var(--panel-blue); background: var(--panel-blue); }
        .aspect-tab.complete::after { content: ' ✓'; }
        .level-card { height: 100%; border: 1px solid var(--panel-line); border-top: 3px solid var(--panel-blue); background: #fff; }
        .level-score { color: var(--panel-blue); font-size: 1.15rem; font-weight: 800; }
        .score-choice { min-width: 48px; min-height: 44px; color: var(--panel-blue); border: 1px solid var(--panel-blue); background: #fff; font-weight: 700; }
        .score-choice:hover, .score-choice.selected { color: #fff; background: var(--panel-blue); }
        .panel-choice-button { color: #b5bfcc; border: 1px solid var(--panel-line); background: #fff; min-width: 38px; }
        .panel-choice-button:hover, .panel-choice-button.is-selected { color: #f2b01e; border-color: #f2b01e; background: #fffaf0; }
        .aspect-panel[hidden] { display: none !important; }
        .panel-progress { height: 6px; background: #dce5ef; }
        .panel-progress-bar { height: 100%; width: 0; background: var(--panel-blue); transition: width .2s ease; }
        @media (max-width: 640px) { .aspect-tabs { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>
</head>
<body>
<header class="panel-header py-3 mb-4"><div class="container d-flex align-items-center justify-content-between gap-3"><div><strong class="d-block fs-4">Panel Evaluation</strong><small>Project Demonstration 3 | DFT50114</small></div><img src="assets/image/logo.png" class="panel-logo" alt="Politeknik Besut"></div></header>
<main class="container pb-5">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= sanitize($error); ?></div>
    <?php elseif ($panel_session && $selected_group): ?>
        <?php if ($selected_group['evaluation']): ?>
            <section class="panel-card p-4"><h1 class="h4 fw-bold">Group already assessed</h1><p><?= sanitize($selected_group['title']); ?></p><a class="btn btn-primary" href="panel.php?token=<?= urlencode($token); ?>">Back to Group List</a></section>
        <?php else: ?>
            <div class="d-flex justify-content-between align-items-center mb-3"><a class="btn btn-outline-primary" href="panel.php?token=<?= urlencode($token); ?>"><i class="fas fa-arrow-left me-2"></i>Group List</a><span>Demo 3 · 15%</span></div>
            <section class="panel-card p-4 mb-4">
                <div class="text-center border-bottom pb-3 mb-3"><h1 class="h3 fw-bold">PROJECT DEMONSTRATION 3</h1><strong>(15%)</strong></div>
                <div class="text-center fw-bold py-2 panel-section-bar">STUDENT INFORMATION</div>
                <div class="row g-0 border">
                    <div class="col-md-3 p-2 info-label">COURSE NAME</div><div class="col-md-3 p-2 border-start">INTEGRATED PROJECT</div><div class="col-md-2 p-2 info-label border-start">COURSE CODE</div><div class="col-md-4 p-2 border-start"><?= sanitize($selected_group['course_code']); ?></div>
                    <div class="col-md-3 p-2 info-label border-top">PROJECT TITLE</div><div class="col-md-9 p-2 border-start border-top"><?= sanitize($selected_group['title']); ?></div>
                    <div class="col-md-3 p-2 info-label border-top">SUPERVISOR NAME</div><div class="col-md-5 p-2 border-start border-top"><?= sanitize($selected_group['supervisor_name'] ?? '-'); ?></div><div class="col-md-2 p-2 info-label border-start border-top">DATE</div><div class="col-md-2 p-2 border-start border-top"><?= date('d/m/Y'); ?></div>
                    <div class="col-md-3 p-2 info-label border-top">STUDENT NAME</div><div class="col-md-9 p-2 border-start border-top"><?php foreach ($members as $i => $member): ?><div>S<?= $i + 1; ?>: <?= sanitize($member['full_name']); ?></div><?php endforeach; ?></div>
                    <div class="col-md-3 p-2 info-label border-top">REGISTRATION NUMBER</div><div class="col-md-9 p-2 border-start border-top"><?php foreach ($members as $i => $member): ?><div>S<?= $i + 1; ?>: <?= sanitize($member['matric_no'] ?? '-'); ?></div><?php endforeach; ?></div>
                    <div class="col-md-3 p-2 info-label border-top">CLASS</div><div class="col-md-9 p-2 border-start border-top"><?= sanitize(implode(', ', array_unique(array_filter(array_column($members, 'class_name')))) ?: '-'); ?></div>
                </div>
            </section>
            <form method="POST" id="panelEvaluationForm" class="panel-card p-4">
                <input type="hidden" name="token" value="<?= sanitize($token); ?>"><input type="hidden" name="project_id" value="<?= $selected_project_id; ?>"><input type="hidden" name="panel_csrf" value="<?= sanitize($panel_csrf); ?>"><input type="hidden" name="active_aspect" id="activeAspectInput" value="<?= $active_aspect; ?>"><input type="hidden" name="save_evaluation" value="1">
                <h2 class="h5 fw-bold mb-3">Panel Information</h2>
                <div class="row g-3 mb-4">
                    <?php if ($panel_session['panel_name']): ?>
                        <div class="col-12"><div class="alert alert-primary mb-0"><strong><?= sanitize($panel_session['panel_name']); ?></strong><?php if ($panel_session['panel_email']): ?> · <?= sanitize($panel_session['panel_email']); ?><?php endif; ?><span class="d-block small mt-1">External assessor · saved for this QR session</span></div></div>
                    <?php else: ?>
                        <div class="col-12"><p class="small text-muted mb-0">Assessor type: External Assessor. Your name and email will be saved for this QR session and reused for every group.</p></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Panel Name *</label><input class="form-control" name="panel_name" required value="<?= sanitize($_POST['panel_name'] ?? ''); ?>"></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Email Address</label><input class="form-control" type="email" name="panel_email" value="<?= sanitize($_POST['panel_email'] ?? ''); ?>"></div>
                    <?php endif; ?>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h5 fw-bold mb-0">Select a Criteria</h2><span class="small text-muted"><span id="completedCount">0</span>/8 completed</span></div><div class="panel-progress mb-3"><div id="progressBar" class="panel-progress-bar"></div></div>
                <nav class="aspect-tabs mb-4" aria-label="Rubric criteria"><?php foreach ($rubric as $i => $aspect): ?><button type="button" class="btn aspect-tab <?= $i === $active_aspect ? 'active' : ''; ?>" data-aspect-tab="<?= $i; ?>">Criteria <?= $i + 1; ?></button><?php endforeach; ?></nav>
                <?php foreach ($rubric as $aspect_index => $aspect): ?>
                    <section class="aspect-panel" data-aspect-panel="<?= $aspect_index; ?>" <?= $aspect_index !== $active_aspect ? 'hidden' : ''; ?>>
                        <span class="small text-uppercase text-primary fw-bold">Criteria <?= $aspect_index + 1; ?></span><h3 class="h4 fw-bold mb-3"><?= sanitize($aspect['title']); ?></h3>
                        <div class="row g-2 mb-4"><?php foreach ([4 => 'Very Good', 3 => 'Good', 2 => 'Fair', 1 => 'Weak'] as $level => $label): ?><div class="col-md-6"><div class="level-card p-3"><div class="level-score"><?= $level; ?> <span class="fs-6 text-dark"><?= $label; ?></span></div><div><?= sanitize($aspect['levels'][$level]); ?></div></div></div><?php endforeach; ?></div>
                        <h4 class="h6 fw-bold">Score each student</h4>
                        <?php foreach ($members as $member_index => $member): $selected = (string) ($score_values[$aspect_index][$member_index] ?? ''); ?>
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 border-top py-3"><div><strong>S<?= $member_index + 1; ?> · <?= sanitize($member['full_name']); ?></strong><div class="small text-muted"><?= sanitize($member['matric_no'] ?? '-'); ?></div></div><input type="hidden" name="scores[<?= $aspect_index; ?>][<?= $member_index; ?>]" value="<?= sanitize($selected); ?>" data-score-input data-aspect="<?= $aspect_index; ?>" data-member="<?= $member_index; ?>"><div class="d-flex gap-2" role="group" aria-label="Score for S<?= $member_index + 1; ?>"><?php foreach ([4, 3, 2, 1] as $score): ?><button type="button" class="btn score-choice <?= $selected === (string) $score ? 'selected' : ''; ?>" data-score-choice data-score="<?= $score; ?>" data-aspect="<?= $aspect_index; ?>" data-member="<?= $member_index; ?>" aria-pressed="<?= $selected === (string) $score ? 'true' : 'false'; ?>"><?= $score; ?></button><?php endforeach; ?></div></div>
                        <?php endforeach; ?>
                        <div class="d-flex justify-content-between mt-3"><button type="button" class="btn btn-outline-primary" data-previous <?= $aspect_index === 0 ? 'disabled' : ''; ?>>Previous</button><button type="button" class="btn btn-outline-primary" data-next <?= $aspect_index === 7 ? 'disabled' : ''; ?>>Next Criteria</button></div>
                    </section>
                <?php endforeach; ?>
                <div class="mt-4"><label class="form-label fw-bold" for="comments">Comments / Feedback</label><textarea id="comments" class="form-control" name="comments" rows="3"><?= sanitize($_POST['comments'] ?? ''); ?></textarea></div>
                <div class="d-flex justify-content-between mt-4"><a class="btn btn-outline-secondary" href="panel.php?token=<?= urlencode($token); ?>">Cancel</a><button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-check me-2"></i>Done · Save Scores</button></div>
                <p class="small text-muted mt-3 mb-0">Demo 3 marks are calculated automatically: weighted total / 100 × 15.</p>
            </form>
        <?php endif; ?>
    <?php elseif ($panel_session): ?>
                <?php if ($saved): ?><div class="alert alert-primary">Scores saved. Select the next group or press Done when finished.</div><?php endif; ?>
                <?php if ($choice_saved): ?><div class="alert alert-success">Panel\'s Choice updated.</div><?php endif; ?>
        <?php if ($finished && $panel_session['status'] === 'Submitted'): ?>
            <section class="panel-card p-5 text-center"><i class="fas fa-check-circle text-primary fa-3x mb-3"></i><h1 class="h3 fw-bold">Evaluation Complete</h1><p class="text-muted mb-0">Thank you, <?= sanitize($panel_session['panel_name'] ?? 'panel'); ?>. All groups have been assessed.</p></section>
        <?php else: ?>
            <section class="panel-card p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3"><div><h1 class="h3 fw-bold mb-1"><?= $panel_session['status'] === 'Submitted' ? 'Evaluation Results' : 'Group List'; ?></h1><p class="text-muted mb-0"><?= $panel_session['status'] === 'Submitted' ? 'Review each group assessment status.' : 'Select a group to assess Project Demonstration 3.'; ?></p></div><span class="badge text-bg-primary">Demo 3 · 15%</span></div>
                <?php if ($panel_session['panel_name']): ?><div class="alert alert-primary py-2"><strong><?= sanitize($panel_session['panel_name']); ?></strong><?php if ($panel_session['panel_email']): ?> · <?= sanitize($panel_session['panel_email']); ?><?php endif; ?> · External assessor</div><?php endif; ?>
                <?php $group_number = 0; foreach ($groups as $group): $group_number++; $group_done = !empty($group['evaluation']); ?>
                    <div class="group-row py-3"><div class="d-flex align-items-center justify-content-between gap-3"><div class="d-flex align-items-start gap-3"><span class="badge rounded-pill text-bg-light border text-dark mt-1" style="min-width: 32px;"> <?= $group_number; ?> </span><div><strong><?= sanitize($group['title']); ?></strong><div class="small text-muted"><?= sanitize($group['leader_name'] ?? ''); ?> · <?= count($group['members']); ?> <?= count($group['members']) === 1 ? 'student' : 'students'; ?> · <?= sanitize($group['session'] ?? ''); ?></div></div></div><div class="d-flex align-items-center gap-2"><form method="POST" class="m-0"><input type="hidden" name="token" value="<?= sanitize($token); ?>"><input type="hidden" name="choice_project_id" value="<?= (int) $group['id']; ?>"><input type="hidden" name="choice_csrf" value="<?= sanitize($choice_csrf); ?>"><button type="submit" name="toggle_panel_choice" value="1" class="btn btn-sm panel-choice-button <?= !empty($group['is_panel_choice']) ? 'is-selected' : ''; ?>" title="<?= !empty($group['is_panel_choice']) ? 'Remove from Panel\'s Choices' : 'Add to Panel\'s Choices'; ?>" aria-label="<?= !empty($group['is_panel_choice']) ? 'Remove from Panel\'s Choices' : 'Add to Panel\'s Choices'; ?>"><i class="fas fa-star"></i></button></form><?php if ($group_done): ?><span class="badge text-bg-primary">Complete</span><?php else: ?><a class="btn btn-sm btn-outline-primary" href="panel.php?token=<?= urlencode($token); ?>&amp;project_id=<?= (int) $group['id']; ?>">Assess Group</a><?php endif; ?></div></div></div>
                <?php endforeach; ?>
                <?php if ($panel_session['status'] === 'Submitted'): ?><div class="alert alert-primary mt-4 mb-0">Evaluation complete. All groups in this QR session have been assessed.</div><?php endif; ?>
            </section>
            <?php if ($panel_session['status'] === 'Submitted'): ?><div class="text-end mt-3"><a class="btn btn-primary" href="panel.php?token=<?= urlencode($token); ?>&amp;done=1">Done</a></div><?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</main>
<script>
(function () {
    const tabs = Array.from(document.querySelectorAll('[data-aspect-tab]'));
    const panels = Array.from(document.querySelectorAll('[data-aspect-panel]'));
    const activeInput = document.getElementById('activeAspectInput');
    function activate(index) {
        tabs.forEach(function (tab) { tab.classList.toggle('active', Number(tab.dataset.aspectTab) === index); });
        panels.forEach(function (panel) { panel.hidden = Number(panel.dataset.aspectPanel) !== index; });
        if (activeInput) activeInput.value = String(index);
    }
    function progress() {
        const members = new Set(Array.from(document.querySelectorAll('[data-score-input]')).map(function (input) { return input.dataset.member; })).size;
        let completed = 0;
        tabs.forEach(function (tab) {
            const inputs = Array.from(document.querySelectorAll('[data-score-input][data-aspect="' + tab.dataset.aspectTab + '"]'));
            const done = inputs.length === members && inputs.every(function (input) { return input.value !== ''; });
            tab.classList.toggle('complete', done);
            if (done) completed++;
        });
        const count = document.getElementById('completedCount');
        const bar = document.getElementById('progressBar');
        if (count) count.textContent = String(completed);
        if (bar) bar.style.width = (completed / 8 * 100) + '%';
    }
    tabs.forEach(function (tab) { tab.addEventListener('click', function () { activate(Number(tab.dataset.aspectTab)); }); });
    document.querySelectorAll('[data-score-choice]').forEach(function (button) {
        button.addEventListener('click', function () {
            const selector = '[data-aspect="' + button.dataset.aspect + '"][data-member="' + button.dataset.member + '"]';
            const input = document.querySelector('[data-score-input]' + selector);
            if (input) input.value = button.dataset.score;
            document.querySelectorAll('[data-score-choice]' + selector).forEach(function (option) {
                const active = option === button;
                option.classList.toggle('selected', active);
                option.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            progress();
        });
    });
    document.querySelectorAll('[data-next]').forEach(function (button) { button.addEventListener('click', function () { if (!button.disabled) activate(Number(button.closest('[data-aspect-panel]').dataset.aspectPanel) + 1); }); });
    document.querySelectorAll('[data-previous]').forEach(function (button) { button.addEventListener('click', function () { if (!button.disabled) activate(Number(button.closest('[data-aspect-panel]').dataset.aspectPanel) - 1); }); });
    const form = document.getElementById('panelEvaluationForm');
    if (form) form.addEventListener('submit', function (event) {
        const missing = Array.from(document.querySelectorAll('[data-score-input]')).find(function (input) { return input.value === ''; });
        if (missing) {
            event.preventDefault();
            activate(Number(missing.dataset.aspect));
            alert('Select a score from 1 to 4 for every student in every criteria.');
        }
    });
    progress();
})();
</script>
</body>
</html>