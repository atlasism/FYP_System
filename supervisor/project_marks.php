<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Supervisor']);

header('Location: my_students.php');
exit();

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
        GROUP_CONCAT(DISTINCT CONCAT(u_member.full_name, ' (', u_member.matric_no, ')') SEPARATOR '||') as members_list,
        m.total_score
    FROM projects p 
    LEFT JOIN users u_leader ON p.student_id = u_leader.id 
    LEFT JOIN project_members pm ON p.id = pm.project_id
    LEFT JOIN users u_member ON pm.student_id = u_member.id AND u_member.id <> p.student_id
    JOIN supervisor_students ss ON u_leader.id = ss.student_id
    LEFT JOIN project_marks m ON p.id = m.project_id
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
                <h4 class="fw-bold text-primary mb-1"><i class="fas fa-clipboard-check me-2"></i>Project Marks Evaluation</h4>
                <p class="text-muted mb-0">Select a project group below to evaluate or update their marks.</p>
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

    <div class="row">
        <?php if ($groups_res && $groups_res->num_rows > 0): while ($g = $groups_res->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card shadow-sm border-0 rounded-3 h-100 bg-white d-flex flex-column">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="fw-bold text-primary mb-0">
                                    <i class="fas fa-project-diagram me-1"></i> <?= sanitize($g['title'] ?? 'Title Not Provided'); ?>
                                </h5>
                            </div>
                            <small class="text-muted d-block mb-3">
                                <i class="fas fa-layer-group me-1"></i> Session: <?= sanitize($g['session'] ?? '-'); ?> | 
                                <span class="fw-bold text-secondary"><?= sanitize($g['department'] ?? 'GENERAL'); ?></span>
                            </small>

                            <!-- Group Members List -->
                            <div class="mb-3">
                                <strong class="text-dark small d-block mb-2">Group Members:</strong>
                                
                                <!-- Leader Badge -->
                                <?php if (!empty($g['leader_name'])): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-primary text-white p-2 d-block text-start w-100">
                                            <i class="fas fa-user-shield me-1"></i> <?= sanitize($g['leader_name']); ?> (<?= sanitize($g['leader_matric']); ?>) <span class="float-end">[Leader]</span>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <!-- Other Members in Gray Box -->
                                <?php 
                                    $has_members = false;
                                    $members_output = '';
                                    if (!empty($g['members_list'])) {
                                        $members = explode('||', $g['members_list']);
                                        foreach ($members as $m) {
                                            // Do not display leader twice if included in members_list
                                            if (!empty(trim($m)) && strpos($m, $g['leader_matric']) === false) {
                                                $has_members = true;
                                                $members_output .= '<div class="small text-dark mb-1"><i class="fas fa-user text-muted me-1"></i> ' . sanitize($m) . '</div>';
                                            }
                                        }
                                    }
                                    
                                    if ($has_members):
                                ?>
                                    <div class="bg-light border p-2 rounded-3">
                                        <?= $members_output; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Bottom Section: Total Marks & Evaluate Button Side-by-Side -->
                        <div class="pt-3 border-top mt-3">
                            <div class="row g-2 align-items-center">
                                <div class="col-6">
                                    <small class="text-muted d-block" style="font-size: 11px;">Total Marks</small>
                                    <span class="badge bg-info text-dark px-3 py-2 fs-6 w-100 text-center">
                                        <?= number_format($g['total_score'] ?? 0, 0); ?> / 100%
                                    </span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block opacity-0" style="font-size: 11px;">Action</small>
                                    <a href="evaluate_project.php?id=<?= $g['project_id']; ?>" class="btn btn-primary btn-sm py-2 fw-bold w-100">
                                        <i class="fas fa-edit me-1"></i> Evaluate
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; else: ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm p-5 text-center bg-white">
                    <div class="card-body">
                        <i class="fas fa-search fa-3x mb-3 text-secondary"></i>
                        <h6 class="text-muted">No projects found under your supervision.</h6>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>