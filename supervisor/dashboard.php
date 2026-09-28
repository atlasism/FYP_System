<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Supervisor']);

$supervisor_id = $_SESSION['user_id'] ?? 0;

// 1. Kira jumlah keseluruhan pelajar seliaan (Gabungan 3 kemungkinan struktur pangkalan data)
$total_students = 0;
$q_students = $conn->prepare("
    SELECT COUNT(DISTINCT student_id) as cnt FROM (
        -- Kemungkinan A: Melalui supervisor_students (Leader)
        SELECT ss.student_id 
        FROM supervisor_students ss 
        WHERE ss.supervisor_id = ?
        
        UNION
        
        -- Kemungkinan B: Melalui projects.supervisor_id (Leader)
        SELECT p.student_id 
        FROM projects p 
        WHERE p.supervisor_id = ?
        
        UNION
        
        -- Kemungkinan C: Melalui project_members (Ahli kumpulan)
        SELECT pm.student_id 
        FROM project_members pm
        JOIN projects p ON pm.project_id = p.id
        LEFT JOIN supervisor_students ss ON p.student_id = ss.student_id
        WHERE ss.supervisor_id = ? OR p.supervisor_id = ?
    ) as all_supervised_students
");
if ($q_students) {
    $q_students->bind_param("iiii", $supervisor_id, $supervisor_id, $supervisor_id, $supervisor_id);
    $q_students->execute();
    $total_students = $q_students->get_result()->fetch_assoc()['cnt'] ?? 0;
}

// 2. Kira jumlah kumpulan / projek yang diselia
$total_projects = 0;
$q_proj_cnt = $conn->prepare("
    SELECT COUNT(DISTINCT p.id) as cnt 
    FROM projects p 
    LEFT JOIN supervisor_students ss ON p.student_id = ss.student_id 
    WHERE ss.supervisor_id = ? OR p.supervisor_id = ?
");
if ($q_proj_cnt) {
    $q_proj_cnt->bind_param("ii", $supervisor_id, $supervisor_id);
    $q_proj_cnt->execute();
    $total_projects = $q_proj_cnt->get_result()->fetch_assoc()['cnt'] ?? 0;
}

// 3. Ambil senarai projek seliaan
$recent_submissions = null;
$q_recent = $conn->prepare("
    SELECT 
        p.*, 
        COALESCE(p.department, u_leader.department, 'GENERAL') as department,
        u_leader.full_name as leader_name,
        u_leader.matric_no as leader_matric,
        GROUP_CONCAT(DISTINCT CONCAT(u_member.full_name, ' (', u_member.matric_no, ')') SEPARATOR '||') as members_list
    FROM projects p
    LEFT JOIN users u_leader ON p.student_id = u_leader.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    LEFT JOIN users u_member ON pm.student_id = u_member.id AND u_member.id <> p.student_id
    LEFT JOIN supervisor_students ss ON u_leader.id = ss.student_id
    WHERE ss.supervisor_id = ? OR p.supervisor_id = ?
    GROUP BY p.id
    ORDER BY p.project_group_no ASC LIMIT 5
");
if ($q_recent) {
    $q_recent->bind_param("ii", $supervisor_id, $supervisor_id);
    $q_recent->execute();
    $recent_submissions = $q_recent->get_result();
}

include_once '../includes/header.php';
include_once '../includes/sidebar_supervisor.php';
?>

<div class="container-fluid p-4">
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
            <div>
                <span class="text-uppercase text-muted small fw-bold tracking-wider"><i class="fas fa-user-shield me-1 text-primary"></i> Supervisor Portal</span>
                <h2 class="fw-bold text-dark mb-1">Welcome back, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Supervisor'); ?>! 👋</h2>
                <p class="text-muted mb-0">Here is the overview of your supervision activities, student progress, and final year project updates for this session.</p>
            </div>
            <div>
                <span class="badge bg-white text-dark border px-3 py-2 shadow-sm fs-6">
                    <i class="fas fa-calendar-alt me-2 text-primary"></i> <?= date('F d, Y'); ?>
                </span>
            </div>
        </div>
        <hr class="mt-3 text-muted-subtle">
    </div>

    <div class="card card-custom p-4 mb-4 shadow-sm border-0 bg-primary text-white">
        <div class="row align-items-center">
            <div class="col-md-12">
                <h3 class="fw-bold mb-1"><i class="fas fa-chalkboard-teacher me-2"></i>Supervisor Dashboard Control Center</h3>
                <p class="mb-0 text-white-50">Manage your supervised students, review pending project submissions, and evaluate academic milestones efficiently in one centralized place.</p>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6 col-md-6">
            <div class="card border-0 shadow-sm p-3 h-100 border-start border-primary border-4 text-dark bg-white">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary-subtle text-primary p-3 rounded fs-3">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="ms-3">
                        <span class="text-muted small fw-bold d-block">TOTAL STUDENTS</span>
                        <h3 class="fw-bold mb-0 text-dark"><?= $total_students; ?></h3>
                        <small class="text-muted">Total individual students across all groups</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6 col-md-6">
            <div class="card border-0 shadow-sm p-3 h-100 border-start border-success border-4 text-dark bg-white">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-success-subtle text-success p-3 rounded fs-3">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="ms-3">
                        <span class="text-muted small fw-bold d-block">SUPERVISED GROUPS</span>
                        <h3 class="fw-bold mb-0 text-dark"><?= $total_projects; ?></h3>
                        <small class="text-muted">Total groups/projects under your supervision</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-dark"><i class="fas fa-list-alt me-2 text-primary"></i> Recent Student Submissions & Groups</h5>
                    <a href="supervised_projects.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45%;">Project Title & Group Members</th>
                                    <th style="width: 20%;">Submission Date</th>
                                    <th style="width: 15%;">Status</th>
                                    <th style="width: 20%;">Department</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($recent_submissions) && $recent_submissions->num_rows > 0): ?>
                                    <?php while($row = $recent_submissions->fetch_assoc()): ?>
                                        <?php 
                                            $status = $row['status'] ?? 'Pending';
                                            $badge_bg = 'bg-warning text-dark';
                                            if ($status == 'Verified' || $status == 'Approved') $badge_bg = 'bg-success';
                                            elseif ($status == 'Rejected') $badge_bg = 'bg-danger';
                                        ?>
                                        <tr>
                                            <td class="py-3">
                                                <div class="fw-bold text-primary mb-1">
                                                    <i class="fas fa-project-diagram me-1"></i> <?= htmlspecialchars($row['title'] ?? 'Title Not Provided'); ?>
                                                </div>
                                                <div class="text-muted small ps-3 border-start border-2 border-primary-subtle">
                                                    <span class="fw-semibold text-dark d-block mb-1">Group Members:</span>
                                                    
                                                    <?php if (!empty($row['leader_name'])): ?>
                                                        <div class="mb-1">
                                                            <span class="badge bg-primary text-white px-2 py-1">
                                                                <i class="fas fa-user-shield me-1"></i> <?= htmlspecialchars($row['leader_name']); ?> (<?= htmlspecialchars($row['leader_matric']); ?>) [Leader]
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php 
                                                        if (!empty($row['members_list'])) {
                                                            $members = explode('||', $row['members_list']);
                                                            foreach ($members as $m) {
                                                                if (!empty(trim($m))) {
                                                                    echo '<div class="mb-1">';
                                                                    echo '<span class="badge bg-light text-dark border px-2 py-1">';
                                                                    echo '<i class="fas fa-user me-1 text-muted"></i>' . htmlspecialchars($m);
                                                                    echo '</span>';
                                                                    echo '</div>';
                                                                }
                                                            }
                                                        }
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-secondary small">
                                                    <i class="fas fa-calendar-alt me-1"></i> 
                                                    <?= !empty($row['created_at']) ? date('d M Y, h:i A', strtotime($row['created_at'])) : 'N/A'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $badge_bg; ?> px-2 py-1"><?= htmlspecialchars($status); ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-secondary">
                                                    <?= htmlspecialchars($row['department'] ?? 'GENERAL'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No recent student submissions found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>