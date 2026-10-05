<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Supervisor']);

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$supervisor_id = $_SESSION['user_id'] ?? 0;
$search = trim($_GET['search'] ?? '');

$query = "
    SELECT 
        p.id as project_id,
        p.title, 
        p.category, 
        p.session, 
        p.status,
        COALESCE(p.department, u_leader.department, 'GENERAL') as department,
        u_leader.full_name as leader_name,
        u_leader.matric_no as leader_matric,
        GROUP_CONCAT(DISTINCT CONCAT(u_member.full_name, ' (', u_member.matric_no, ')') SEPARATOR '||') as members_list
    FROM projects p 
    LEFT JOIN users u_leader ON p.student_id = u_leader.id 
    LEFT JOIN project_members pm ON p.id = pm.project_id
    LEFT JOIN users u_member ON pm.student_id = u_member.id AND u_member.id <> p.student_id
    JOIN supervisor_students ss ON u_leader.id = ss.student_id
    WHERE ss.supervisor_id = ?
";

$params = [$supervisor_id];
$types = "i";

if (!empty($search)) {
    $query .= " AND (p.title LIKE ? OR u_leader.full_name LIKE ? OR u_leader.matric_no LIKE ? OR u_member.full_name LIKE ? OR u_member.matric_no LIKE ?)";
    $s_param = "%$search%";
    array_push($params, $s_param, $s_param, $s_param, $s_param, $s_param);
    $types .= "sssss";
}

$query .= " GROUP BY p.id ORDER BY p.project_group_no ASC";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $groups_res = $stmt->get_result();
} else {
    $groups_res = null;
}

include_once '../includes/header.php';
include_once '../includes/sidebar_supervisor.php';
?>

<div class="container-fluid py-4">
    <div class="card card-custom p-4 mb-4 bg-white border-0 shadow-sm">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="fw-bold text-primary mb-1"><i class="fas fa-project-diagram me-2"></i>Supervised Projects Monitoring</h4>
                <p class="text-muted mb-0">List of project groups and registered members under your supervision.</p>
            </div>
        </div>
        
        <form method="GET" action="" class="row g-2 mt-3">
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search student name, matric number, or project title..." value="<?= sanitize($search); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    Search Project
                </button>
            </div>
        </form>
    </div>

    <div class="card card-custom p-4 shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="5%">#</th>
                        <th width="30%">Project Title / System</th>
                        <th width="35%">Group Members (Name & Matric No.)</th>
                        <th width="15%">Category / Session</th>
                        <th width="15%">Department</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($groups_res && $groups_res->num_rows > 0): $i=1; while ($g = $groups_res->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td>
                                <strong class="text-dark d-block mb-1"><?= sanitize($g['title'] ?? 'Title Not Provided'); ?></strong>
                                <small class="text-muted"><i class="fas fa-layer-group me-1"></i>Session: <?= sanitize($g['session'] ?? '-'); ?></small>
                            </td>
                            <td>
                                <?php if (!empty($g['leader_name'])): ?>
                                    <div class="mb-1">
                                        <span class="badge bg-primary text-white p-2 m-1 d-inline-block">
                                            <i class="fas fa-user-shield me-1"></i><?= sanitize($g['leader_name']); ?> (<?= sanitize($g['leader_matric']); ?>) [Leader]
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <?php 
                                    if (!empty($g['members_list'])) {
                                        $members = explode('||', $g['members_list']);
                                        foreach ($members as $m) {
                                            echo '<div class="mb-1">';
                                            echo '<span class="badge bg-light text-dark border p-2 m-1 d-inline-block">';
                                            echo '<i class="fas fa-user me-1"></i>' . sanitize($m);
                                            echo '</span>';
                                            echo '</div>';
                                        }
                                    }
                                ?>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark"><?= sanitize($g['category'] ?? 'N/A'); ?></span>
                            </td>
                            <td>
                                <span class="fw-bold text-secondary">
                                    <?= sanitize($g['department'] ?? 'GENERAL'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-search fa-3x mb-3 d-block text-secondary"></i>
                                <h6>No projects found under your supervision.</h6>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>