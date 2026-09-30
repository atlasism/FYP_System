<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Admin']);

$message = '';
$error = '';
$csrf_token = $_SESSION['csrf_admin_projects'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_admin_projects'] = $csrf_token;
$categories = [
    'Multimedia and animation', 'Internet of Things (IOT)', 'Artificial Intelligent (AI)',
    'Software application', 'Web application', 'Mobile application', 'Networking system',
    'Hardware design', 'Robotic programming', 'Information system', 'Security system',
    'Data management & visualization', 'Data analysis'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_supervisor'])) {
    $project_id = (int) ($_POST['project_id'] ?? 0);
    $supervisor_id = (int) ($_POST['supervisor_id'] ?? 0);

    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form session.';
    } else {
        $project_data = null;
        $validation = $conn->prepare("SELECT p.id AS project_id, p.session, s.id AS supervisor_id FROM projects p CROSS JOIN users s WHERE p.id = ? AND p.department = 'JTMK' AND p.course_code = 'DFT50114' AND s.id = ? AND s.role = 'Supervisor' AND s.department = 'JTMK'");
        if (!$validation) {
            $error = 'Unable to validate project assignment: ' . $conn->error;
        } else {
            $validation->bind_param('ii', $project_id, $supervisor_id);
            $validation->execute();
            $project_data = $validation->get_result()->fetch_assoc();
        }

        if (!$project_data) {
            $error = 'Invalid project or JTMK supervisor.';
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare('UPDATE projects SET supervisor_id = ? WHERE id = ?');
                if (!$stmt) {
                    throw new RuntimeException('Project update could not be prepared.');
                }
                $stmt->bind_param('ii', $supervisor_id, $project_id);
                if (!$stmt->execute()) {
                    throw new RuntimeException('Project supervisor update failed.');
                }

                $remove_assignments = $conn->prepare('DELETE FROM supervisor_students WHERE student_id IN (SELECT student_id FROM project_members WHERE project_id = ?)');
                if (!$remove_assignments) {
                    throw new RuntimeException('Existing assignment cleanup could not be prepared.');
                }
                $remove_assignments->bind_param('i', $project_id);
                $remove_assignments->execute();

                $members_stmt = $conn->prepare('SELECT student_id FROM project_members WHERE project_id = ?');
                if (!$members_stmt) {
                    throw new RuntimeException('Project members query could not be prepared.');
                }
                $members_stmt->bind_param('i', $project_id);
                $members_stmt->execute();
                $members = $members_stmt->get_result();
                $assignment_stmt = $conn->prepare('INSERT INTO supervisor_students (supervisor_id, student_id, session) VALUES (?, ?, ?)');
                if (!$assignment_stmt) {
                    throw new RuntimeException('Supervisor assignment insert could not be prepared.');
                }
                while ($member = $members->fetch_assoc()) {
                    $assignment_stmt->bind_param('iis', $supervisor_id, $member['student_id'], $project_data['session']);
                    $assignment_stmt->execute();
                }

                $conn->commit();
                $message = 'Supervisor assignment updated successfully for the whole group.';
            } catch (Throwable $exception) {
                $conn->rollback();
                $error = 'Unable to update supervisor assignment: ' . $exception->getMessage();
            }
        }
    }
}

$supervisors = $conn->query("SELECT id, full_name FROM users WHERE role = 'Supervisor' AND department = 'JTMK' ORDER BY full_name");
$projects = $conn->query("SELECT p.id, p.title, p.category, p.session, p.status, p.supervisor_id, s.full_name AS supervisor_name, GROUP_CONCAT(DISTINCT CONCAT(u.full_name, ' (', COALESCE(u.matric_no, u.ic_number), ')') ORDER BY pm.role DESC SEPARATOR '||') AS members, GROUP_CONCAT(DISTINCT CONCAT(pm.student_id, ':', COALESCE(d1.status, 'Pending'), ':', COALESCE(d2.status, 'Pending')) SEPARATOR '||') AS member_statuses FROM projects p LEFT JOIN users s ON s.id = p.supervisor_id LEFT JOIN project_members pm ON pm.project_id = p.id LEFT JOIN users u ON u.id = pm.student_id LEFT JOIN student_demo_status d1 ON d1.student_id = pm.student_id AND d1.demo_type = 'Demo 1' LEFT JOIN student_demo_status d2 ON d2.student_id = pm.student_id AND d2.demo_type = 'Demo 2' WHERE p.department = 'JTMK' AND p.course_code = 'DFT50114' GROUP BY p.id, p.title, p.category, p.session, p.status, p.supervisor_id, s.full_name ORDER BY p.project_group_no ASC");

include_once '../includes/admin_header.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div><h3 class="fw-bold text-primary mb-1"><i class="bi bi-kanban-fill me-2"></i>Manage JTMK Projects</h3><p class="text-muted mb-0">Assign supervisors and monitor Demo 1/Demo 2 verification statuses.</p></div>
        <a href="dashboard.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= sanitize($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error); ?></div><?php endif; ?>

    <?php if ($projects && $projects->num_rows > 0): while ($project = $projects->fetch_assoc()): ?>
            <div class="card admin-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div><h5 class="fw-bold mb-1"><?= sanitize($project['title']); ?></h5><span class="badge bg-info text-dark me-1"><?= sanitize($project['category']); ?></span><span class="text-muted small">Session: <?= sanitize($project['session']); ?></span></div>
                <span class="badge <?= $project['status'] === 'Approved' ? 'bg-success' : ($project['status'] === 'Draft' ? 'bg-secondary' : 'bg-warning text-dark'); ?>"><?= sanitize($project['status'] ?? 'Submitted'); ?></span>
            </div>
            <div class="row g-4">
                <div class="col-lg-5"><h6 class="fw-bold">Group Members</h6><ul class="list-group list-group-flush mb-3"><?php foreach (explode('||', $project['members'] ?? '') as $member): if ($member !== ''): ?><li class="list-group-item px-0"><?= sanitize($member); ?></li><?php endif; endforeach; ?></ul></div>
                <div class="col-lg-7"><h6 class="fw-bold">Supervisor Assignment</h6><form method="POST" class="row g-2 mb-3"><input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token); ?>"><input type="hidden" name="project_id" value="<?= (int) $project['id']; ?>"><div class="col-md-8"><select name="supervisor_id" class="form-select" required><option value="">-- Select JTMK Supervisor --</option><?php $supervisors->data_seek(0); while ($supervisor = $supervisors->fetch_assoc()): ?><option value="<?= (int) $supervisor['id']; ?>" <?= ((int) $project['supervisor_id'] === (int) $supervisor['id']) ? 'selected' : ''; ?>><?= sanitize($supervisor['full_name']); ?></option><?php endwhile; ?></select></div><div class="col-md-4"><button name="assign_supervisor" class="btn btn-primary w-100"><i class="fas fa-user-check me-1"></i> Assign SV</button></div></form><div class="small text-muted mb-3">Current Supervisor: <strong><?= sanitize($project['supervisor_name'] ?: 'Not Assigned Yet'); ?></strong></div>
                    <h6 class="fw-bold">Individual Milestone Verification</h6><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Student</th><th>Demo 1</th><th>Demo 2</th></tr></thead><tbody><?php foreach (explode('||', $project['member_statuses'] ?? '') as $status_row): $parts = explode(':', $status_row, 3); if (count($parts) === 3): $member_id = (int) $parts[0]; $member_query = $conn->prepare('SELECT full_name FROM users WHERE id = ?'); $member_query->bind_param('i', $member_id); $member_query->execute(); $member_name = $member_query->get_result()->fetch_assoc()['full_name'] ?? 'Student'; ?><tr><td><?= sanitize($member_name); ?></td><td><span class="badge <?= $parts[1] === 'Passed' ? 'bg-success' : ($parts[1] === 'Not Passed' ? 'bg-danger' : 'bg-secondary'); ?>"><?= sanitize($parts[1]); ?></span></td><td><span class="badge <?= $parts[2] === 'Passed' ? 'bg-success' : ($parts[2] === 'Not Passed' ? 'bg-danger' : 'bg-secondary'); ?>"><?= sanitize($parts[2]); ?></span></td></tr><?php endif; endforeach; ?></tbody></table></div>
                </div>
            </div>
        </div>
    <?php endwhile; else: ?><div class="alert alert-info">No JTMK projects found.</div><?php endif; ?>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
