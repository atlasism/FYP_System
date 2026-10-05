<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Student']);

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$search = $_GET['search'] ?? '';

// Query Asas
$query = "
    SELECT 
        p.id as project_id,
        p.title, 
        p.category, 
        p.session, 
        p.status,
        COALESCE(u_leader.department, 'No Department') as department,
        u_leader.full_name as leader_name,
        u_leader.matric_no as leader_matric,
        GROUP_CONCAT(DISTINCT CONCAT(u_member.full_name, ' (', u_member.matric_no, ')') SEPARATOR '||') as members_list
    FROM projects p 
    LEFT JOIN users u_leader ON p.student_id = u_leader.id 
    LEFT JOIN project_members pm ON p.id = pm.project_id
    LEFT JOIN users u_member ON pm.student_id = u_member.id AND u_member.id <> p.student_id
    WHERE 1=1
";

$params = [];
$types = "";

// Carian Keyword (Tajuk atau Nama/No Matrik Pelajar)
if (!empty($search)) {
    $query .= " AND (p.title LIKE ? OR u_leader.full_name LIKE ? OR u_leader.matric_no LIKE ? OR u_member.full_name LIKE ? OR u_member.matric_no LIKE ?)";
    $s_param = "%$search%";
    $params = array_merge($params, [$s_param, $s_param, $s_param, $s_param, $s_param]);
    $types .= "sssss";
}

$query .= " GROUP BY p.id ORDER BY p.project_group_no ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$groups_res = $stmt->get_result();

include_once '../includes/sidebar_student.php';
?>

<!-- Kad Carian & Penapisan -->
<div class="card card-custom p-4 mb-4 border-0 shadow-sm bg-light">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h4 class="fw-bold text-primary mb-1"><i class="fas fa-layer-group me-2"></i>FYP Group & Project Monitoring</h4>
            <p class="text-muted mb-0 small">Search and filter project groups and members across the system.</p>
        </div>
    </div>
    
    <!-- Borang Carian & Filter -->
    <form method="GET" action="groups.php" class="row g-3">
        <div class="col-md-8">
            <label class="form-label small fw-bold text-muted">Keyword / Title / Name</label>
            <div class="input-group">
                <span class="input-group-text bg-white text-primary"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search title, name..." value="<?= sanitize($search); ?>">
            </div>
        </div>
        <div class="col-md-4 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-primary w-50 fw-bold shadow-sm">
                <i class="fas fa-filter me-1"></i> Search
            </button>
            <a href="groups.php" class="btn btn-outline-secondary w-50 fw-bold shadow-sm" title="Reset all">
                <i class="fas fa-redo me-1"></i> Reset
            </a>
        </div>
    </form>
</div>

<!-- Jadual Senarai Projek -->
<div class="card card-custom p-4 shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light text-uppercase fs-7 text-secondary">
                <tr>
                    <th width="5%">#</th>
                    <th width="30%">Project Title / System</th>
                    <th width="35%">Group Members (Name & Matric No.)</th>
                    <th width="20%">Session</th>
                    <th width="10%">Department</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($groups_res && $groups_res->num_rows > 0): $i=1; while ($g = $groups_res->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-bold text-muted"><?= $i++; ?></td>
                        <td>
                            <strong class="text-dark d-block mb-1"><?= sanitize($g['title'] ?? 'Title Not Provided'); ?></strong>
                        </td>
                        <td>
                            <!-- Papar Ketua -->
                            <?php if (!empty($g['leader_name'])): ?>
                                <div class="mb-1">
                                    <span class="badge bg-primary text-white px-2 py-1 shadow-sm">
                                        <i class="fas fa-user-shield me-1"></i><?= sanitize($g['leader_name']); ?> (<?= sanitize($g['leader_matric']); ?>) [Leader]
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- Papar Ahli-Ahli -->
                            <?php 
                                if (!empty($g['members_list'])) {
                                    $members = explode('||', $g['members_list']);
                                    foreach ($members as $m) {
                                        echo '<div class="d-inline-block m-1">';
                                        echo '<span class="badge bg-white text-dark border px-2 py-1 shadow-sm">';
                                        echo '<i class="fas fa-user text-muted me-1"></i>' . sanitize($m);
                                        echo '</span>';
                                        echo '</div>';
                                    }
                                }
                            ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border fw-normal">
                                <i class="fas fa-calendar-alt me-1 text-primary"></i><?= sanitize($g['session'] ?? '-'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-dark fw-bold px-2 py-1">
                                <?= sanitize($g['department']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <div class="mb-3">
                                <i class="fas fa-folder-open fa-3x text-secondary opacity-50"></i>
                            </div>
                            <h6 class="fw-bold">No records found</h6>
                            <p class="small text-muted mb-0">Try adjusting your filter options or click Reset to view all.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>