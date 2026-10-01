<?php
require_once 'panel_flow.php';
exit;

require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = '';
$panel_session = null;
$project = null;
$members = [];
$aspects = [
    'Project achievement and objective' => 'Project is complete and achieves the stated objectives.',
    'User Requirements' => 'The project meets the required user needs.',
    'Construction and functionality' => 'The system is constructed well and functions as demonstrated.',
    'Feasibility' => 'The project is feasible to implement and operate.',
    'Originality' => 'The product demonstrates a genuine and original idea.',
    'Marketability' => 'The project shows evidence of market potential.',
    'Creativity' => 'The ideas are creative and inventive.',
    'System Security, Features and Testing' => 'The project includes suitable controls, features and validation testing.'
];

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error = 'This panel evaluation link is invalid.';
} else {
    $session_stmt = $conn->prepare("SELECT ps.*, p.title, p.category, p.session, p.course_code, p.program_name, u.full_name AS leader_name, u.matric_no AS leader_matric, u.class_name FROM panel_sessions ps JOIN projects p ON p.id = ps.project_id LEFT JOIN users u ON u.id = p.student_id WHERE ps.token = ? LIMIT 1");
    $session_stmt->bind_param('s', $token);
    $session_stmt->execute();
    $panel_session = $session_stmt->get_result()->fetch_assoc();

    if (!$panel_session) {
        $error = 'This panel evaluation link could not be found.';
    } elseif ($panel_session['status'] !== 'Active' || ($panel_session['expires_at'] && strtotime($panel_session['expires_at']) < time())) {
        $error = 'This panel evaluation link has expired or has already been submitted.';
    } else {
        $member_stmt = $conn->prepare("SELECT u.full_name, u.matric_no, u.class_name FROM project_members pm JOIN users u ON u.id = pm.student_id WHERE pm.project_id = ? ORDER BY pm.role = 'Leader' DESC, pm.id ASC");
        $member_stmt->bind_param('i', $panel_session['project_id']);
        $member_stmt->execute();
        $member_result = $member_stmt->get_result();
        while ($member = $member_result->fetch_assoc()) {
            $members[] = $member;
        }
        if (!$members && $panel_session['leader_name']) {
            $members[] = ['full_name' => $panel_session['leader_name'], 'matric_no' => $panel_session['leader_matric'], 'class_name' => $panel_session['class_name']];
        }
        $group_classes = implode(', ', array_unique(array_filter(array_column($members, 'class_name'))));

        $csrf_key = 'panel_csrf_' . $panel_session['id'];
        $_SESSION[$csrf_key] = $_SESSION[$csrf_key] ?? bin2hex(random_bytes(32));
        $panel_csrf = $_SESSION[$csrf_key];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $panel_name = trim($_POST['panel_name'] ?? '');
            $panel_email = trim($_POST['panel_email'] ?? '');
            $assessor_types = array_values(array_intersect(['SUPERVISOR', 'EXTERNAL ASSESSOR', 'INTERNAL ASSESSOR'], $_POST['assessor_types'] ?? []));
            $submitted_scores = $_POST['scores'] ?? [];
            $comments = trim($_POST['comments'] ?? '');
            $scores = [];
            $score_total = 0;
            $score_count = 0;

            if (!hash_equals($panel_csrf, $_POST['panel_csrf'] ?? '')) {
                $error = 'Your form session is invalid. Please reload the page.';
            } elseif ($panel_name === '') {
                $error = 'Please enter the panel member name.';
            } elseif ($panel_email !== '' && !filter_var($panel_email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif (count($assessor_types) === 0) {
                $error = 'Please select at least one assessor type.';
            } else {
                foreach ($aspects as $aspect => $description) {
                    $scores[$aspect] = [];
                    foreach ($members as $member_index => $member) {
                        $raw_score = $submitted_scores[$aspect][$member_index] ?? '';
                        if ($raw_score === '' || !is_numeric($raw_score) || (float) $raw_score < 1 || (float) $raw_score > 4) {
                            $error = 'Please enter a score from 1 to 4 for every student and aspect.';
                            break 2;
                        }
                        $score = (float) $raw_score;
                        $scores[$aspect][$member_index] = $score;
                        $score_total += $score;
                        $score_count++;
                    }
                }
            }

            if ($error === '') {
                $average_score = $score_count > 0 ? round($score_total / $score_count, 2) : 0;
                $score_json = json_encode(['members' => $members, 'aspects' => $scores], JSON_UNESCAPED_UNICODE);
                $conn->begin_transaction();
                try {
                    $evaluation_stmt = $conn->prepare("INSERT INTO panel_evaluations (panel_session_id, project_id, panel_name, panel_email, assessor_types, student_scores_json, comments, average_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $assessor_string = implode(', ', $assessor_types);
                    $evaluation_stmt->bind_param('iisssssd', $panel_session['id'], $panel_session['project_id'], $panel_name, $panel_email, $assessor_string, $score_json, $comments, $average_score);
                    if (!$evaluation_stmt->execute()) {
                        throw new RuntimeException('Evaluation could not be saved.');
                    }

                    $status_stmt = $conn->prepare("UPDATE panel_sessions SET panel_name = ?, panel_email = ?, status = 'Submitted', submitted_at = NOW() WHERE id = ? AND status = 'Active'");
                    $status_stmt->bind_param('ssi', $panel_name, $panel_email, $panel_session['id']);
                    if (!$status_stmt->execute() || $status_stmt->affected_rows !== 1) {
                        throw new RuntimeException('Panel link was already submitted.');
                    }

                    $new_demo_3 = round(($average_score / 4) * 15, 2);
                    $marks_stmt = $conn->prepare("SELECT demonstration_3, total_score FROM project_marks WHERE project_id = ? FOR UPDATE");
                    $marks_stmt->bind_param('i', $panel_session['project_id']);
                    $marks_stmt->execute();
                    $existing_marks = $marks_stmt->get_result()->fetch_assoc();
                    if ($existing_marks) {
                        $new_total = round((float) $existing_marks['total_score'] - (float) $existing_marks['demonstration_3'] + $new_demo_3, 2);
                        $update_marks = $conn->prepare("UPDATE project_marks SET demonstration_3 = ?, total_score = ? WHERE project_id = ?");
                        $update_marks->bind_param('ddi', $new_demo_3, $new_total, $panel_session['project_id']);
                    } else {
                        $new_total = $new_demo_3;
                        $update_marks = $conn->prepare("INSERT INTO project_marks (project_id, demonstration_3, total_score) VALUES (?, ?, ?)");
                        $update_marks->bind_param('idd', $panel_session['project_id'], $new_demo_3, $new_total);
                    }
                    if (!$update_marks->execute()) {
                        throw new RuntimeException('Project marks could not be updated.');
                    }
                    $conn->commit();
                    $success = 'Thank you. Your Project Demonstration 3 evaluation has been submitted.';
                } catch (Throwable $exception) {
                    $conn->rollback();
                    $error = 'Unable to submit this evaluation. Please contact the administrator.';
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
    <title>Panel Evaluation | Politeknik Besut</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --panel-blue: #1f63aa; --panel-navy: #10243d; --panel-ice: #e9eef4; }
        body { margin: 0; color: #18212b; background: #f5f7f9; font-family: Arial, sans-serif; }
        .panel-header { background: var(--panel-navy); color: #fff; }
        .panel-logo { width: 118px; height: 58px; object-fit: contain; }
        .panel-card { border: 1px solid #d7dce1; border-radius: 4px; box-shadow: 0 10px 26px rgba(16,36,61,.08); }
        .rubric thead th { color: #fff; background: var(--panel-blue); vertical-align: middle; }
        .rubric tbody th { background: var(--panel-ice); vertical-align: middle; }
        .rubric td { min-width: 105px; vertical-align: middle; }
        .score-input { width: 72px; margin: auto; text-align: center; font-weight: 700; }
        .info-label { background: var(--panel-ice); font-weight: 700; }
        .panel-section-bar { color: #fff; background: var(--panel-blue); }
        @media (max-width: 768px) { .rubric { min-width: 920px; } .panel-card { border-radius: 0; } }
    </style>
</head>
<body>
<header class="panel-header py-3 mb-4">
    <div class="container d-flex align-items-center justify-content-between gap-3">
        <div><strong class="d-block fs-4">External Panel Evaluation</strong><small>Project Demonstration 3 | DFT50114</small></div>
        <img src="assets/image/logosistem.png" class="panel-logo" alt="Politeknik Besut logo">
    </div>
</header>
<main class="container pb-5">
    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= sanitize($error); ?></div>
    <?php elseif ($success): ?>
        <div class="panel-card bg-white p-5 text-center"><i class="fas fa-circle-check text-success fa-3x mb-3"></i><h2 class="fw-bold">Evaluation Submitted</h2><p class="text-muted mb-0"><?= sanitize($success); ?></p></div>
    <?php elseif ($panel_session): ?>
        <div class="panel-card bg-white p-4 mb-4">
            <div class="text-center border-bottom pb-3 mb-3"><h1 class="fw-bold h3 mb-1">PROJECT DEMONSTRATION 3</h1><strong>(15%)</strong></div>
            <div class="text-center text-white fw-bold py-2 mb-0 panel-section-bar">STUDENT INFORMATION</div>
            <div class="row g-0 border">
                <div class="col-md-3 p-2 info-label">COURSE NAME</div><div class="col-md-3 p-2 border-start">INTEGRATED PROJECT</div>
                <div class="col-md-2 p-2 info-label border-start">COURSE CODE</div><div class="col-md-4 p-2 border-start"><?= sanitize($panel_session['course_code'] ?? 'DFT50114'); ?></div>
                <div class="col-md-3 p-2 info-label border-top">PROJECT TITLE</div><div class="col-md-9 p-2 border-start border-top"><?= sanitize($panel_session['title']); ?></div>
                <div class="col-md-3 p-2 info-label border-top">CLASS</div><div class="col-md-3 p-2 border-start border-top"><?= sanitize($group_classes ?: '-'); ?></div>
                <div class="col-md-2 p-2 info-label border-start border-top">SESSION</div><div class="col-md-4 p-2 border-start border-top"><?= sanitize($panel_session['session'] ?? '-'); ?></div>
                <div class="col-md-3 p-2 info-label border-top">STUDENT NAME</div><div class="col-md-9 p-2 border-start border-top"><?php foreach ($members as $index => $member): ?><div>S<?= $index + 1; ?>: <?= sanitize($member['full_name']); ?> (<?= sanitize($member['matric_no'] ?? '-'); ?>) - <?= sanitize($member['class_name'] ?? '-'); ?></div><?php endforeach; ?></div>
            </div>
        </div>

        <form method="POST" class="panel-card bg-white p-4">
            <input type="hidden" name="token" value="<?= sanitize($token); ?>">
            <input type="hidden" name="panel_csrf" value="<?= sanitize($panel_csrf); ?>">
            <h2 class="h5 fw-bold mb-3">Panel Information</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-6"><label class="form-label fw-bold">Panel Name *</label><input type="text" name="panel_name" class="form-control" required value="<?= sanitize($_POST['panel_name'] ?? ''); ?>"></div>
                <div class="col-md-6"><label class="form-label fw-bold">Email Address</label><input type="email" name="panel_email" class="form-control" value="<?= sanitize($_POST['panel_email'] ?? ''); ?>"></div>
                <div class="col-12"><label class="form-label fw-bold d-block">Panel Type *</label><?php foreach (['SUPERVISOR', 'EXTERNAL ASSESSOR', 'INTERNAL ASSESSOR'] as $type): ?><label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="assessor_types[]" value="<?= $type; ?>" <?= in_array($type, $_POST['assessor_types'] ?? [], true) ? 'checked' : ''; ?>><span class="form-check-label"><?= $type; ?></span></label><?php endforeach; ?></div>
            </div>

            <h2 class="h5 fw-bold mb-3">Project Demonstration 3 Score</h2>
            <div class="table-responsive">
                <table class="table table-bordered rubric text-center align-middle">
                    <thead><tr><th rowspan="2">Aspects</th><th colspan="4">Performance Level</th><th rowspan="2">Weightage<br>(%)</th><th rowspan="2">Standard</th><?php foreach ($members as $index => $member): ?><th rowspan="2">S<?= $index + 1; ?><br>Score</th><?php endforeach; ?></tr><tr><th>Very Good (4)</th><th>Good (3)</th><th>Fair (2)</th><th>Weak (1)</th></tr></thead>
                    <tbody>
                        <?php foreach ($aspects as $aspect => $description): ?><tr><th><?= sanitize($aspect); ?><small class="d-block fw-normal mt-2"><?= sanitize($description); ?></small></th><td>Excellent performance</td><td>Good performance</td><td>Acceptable performance</td><td>Needs improvement</td><td>12.5</td><td>( / 4) * 12.5</td><?php foreach ($members as $index => $member): ?><td><input class="form-control score-input" type="number" min="1" max="4" step="1" required name="scores[<?= sanitize($aspect); ?>][<?= $index; ?>]" value="<?= sanitize($_POST['scores'][$aspect][$index] ?? ''); ?>"></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
                </table>
            </div>
            <div class="row g-3 mt-3"><div class="col-12"><label class="form-label fw-bold">Comments / Feedback</label><textarea name="comments" class="form-control" rows="4" placeholder="Optional feedback for the project team..."><?= sanitize($_POST['comments'] ?? ''); ?></textarea></div></div>
            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold mt-4"><i class="fas fa-paper-plane me-2"></i>Submit Panel Evaluation</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
