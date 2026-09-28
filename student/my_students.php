<?php

require_once '../config/database.php';

require_once '../includes/auth_check.php';

check_access(['Supervisor']);



$supervisor_id = $_SESSION['user_id'] ?? 0;



// Query 1: Jumlah keseluruhan pelajar seliaan

$q_count = $conn->prepare("SELECT COUNT(DISTINCT u.id) as total FROM users u JOIN supervisor_students ss ON u.id = ss.student_id WHERE ss.supervisor_id = ?");

$total_students = 0;

if ($q_count) {

    $q_count->bind_param("i", $supervisor_id);

    $q_count->execute();

    $total_students = $q_count->get_result()->fetch_assoc()['total'] ?? 0;

}



// Query 2: Jumlah pelajar JTMK

$q_jtmk = $conn->prepare("SELECT COUNT(DISTINCT u.id) as total FROM users u JOIN supervisor_students ss ON u.id = ss.student_id WHERE ss.supervisor_id = ? AND u.department LIKE '%JTMK%'");

$total_jtmk = 0;

if ($q_jtmk) {

    $q_jtmk->bind_param("i", $supervisor_id);

    $q_jtmk->execute();

    $total_jtmk = $q_jtmk->get_result()->fetch_assoc()['total'] ?? 0;

}



// Query 3: Jumlah pelajar JRKV

$q_jrkv = $conn->prepare("SELECT COUNT(DISTINCT u.id) as total FROM users u JOIN supervisor_students ss ON u.id = ss.student_id WHERE ss.supervisor_id = ? AND u.department LIKE '%JRKV%'");

$total_jrkv = 0;

if ($q_jrkv) {

    $q_jrkv->bind_param("i", $supervisor_id);

    $q_jrkv->execute();

    $total_jrkv = $q_jrkv->get_result()->fetch_assoc()['total'] ?? 0;

}



// Query utama untuk menyusun data mengikut Kumpulan Projek / Tajuk Projek

$query = "SELECT p.id as project_id, COALESCE(p.title, 'Individual / No Project Title') as group_name, p.department, p.status as project_status,

                 GROUP_CONCAT(u.full_name SEPARATOR '___') as student_names,

                 GROUP_CONCAT(u.matric_no SEPARATOR '___') as student_matrics

          FROM supervisor_students ss

          JOIN users u ON ss.student_id = u.id

          LEFT JOIN projects p ON u.id = p.student_id

          WHERE ss.supervisor_id = ?

          GROUP BY p.id, p.title, p.department, p.status";



$stmt = $conn->prepare($query);

if ($stmt) {

    $stmt->bind_param("i", $supervisor_id);

    $stmt->execute();

    $groups_result = $stmt->get_result();

} else {

    $db_error = $conn->error;

}



include_once '../includes/header.php';

include_once '../includes/sidebar_supervisor.php';

?>



<div class="container-fluid p-4">

    <!-- Banner Tajuk Utama -->

    <div class="card card-custom p-4 mb-4 shadow-sm border-0 bg-primary text-white">

        <h3 class="fw-bold mb-1"><i class="fas fa-users-cog me-2"></i>My Supervised Students & Groups</h3>

        <p class="mb-0 text-white-50">Manage and monitor student groups and their project details efficiently.</p>

    </div>



    <!-- Tiga Kotak Kad Statistik Disusun Sebelah-Sebelah (Side-by-Side) -->

    <div class="row g-3 mb-4">

        <!-- Kad 1: Total Students -->

        <div class="col-xl-4 col-md-4">

            <div class="card border-0 shadow-sm p-3 border-start border-primary border-4 text-dark h-100">

                <div class="d-flex align-items-center">

                    <div class="flex-shrink-0 bg-primary-subtle text-primary p-3 rounded fs-3">

                        <i class="fas fa-user-graduate"></i>

                    </div>

                    <div class="ms-3">

                        <span class="text-muted small fw-bold d-block">TOTAL SUPERVISED STUDENTS</span>

                        <h3 class="fw-bold mb-0 text-dark"><?= $total_students; ?></h3>

                    </div>

                </div>

            </div>

        </div>



        <!-- Kad 2: Total JTMK -->

        <div class="col-xl-4 col-md-4">

            <div class="card border-0 shadow-sm p-3 border-start border-info border-4 text-dark h-100">

                <div class="d-flex align-items-center">

                    <div class="flex-shrink-0 bg-info-subtle text-info p-3 rounded fs-3">

                        <i class="fas fa-laptop-code"></i>

                    </div>

                    <div class="ms-3">

                        <span class="text-muted small fw-bold d-block">TOTAL JTMK STUDENTS</span>

                        <h3 class="fw-bold mb-0 text-dark"><?= $total_jtmk; ?></h3>

                    </div>

                </div>

            </div>

        </div>



        <!-- Kad 3: Total JRKV -->

        <div class="col-xl-4 col-md-4">

            <div class="card border-0 shadow-sm p-3 border-start border-secondary border-4 text-dark h-100">

                <div class="d-flex align-items-center">

                    <div class="flex-shrink-0 bg-secondary-subtle text-secondary p-3 rounded fs-3">

                        <i class="fas fa-tools"></i>

                    </div>

                    <div class="ms-3">

                        <span class="text-muted small fw-bold d-block">TOTAL JRKV STUDENTS</span>

                        <h3 class="fw-bold mb-0 text-dark"><?= $total_jrkv; ?></h3>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <?php if (isset($db_error)): ?>

        <div class="alert alert-danger shadow-sm" role="alert">

            <i class="fas fa-exclamation-triangle me-2"></i> Database Error: <?= htmlspecialchars($db_error); ?>

        </div>

    <?php endif; ?>



    <!-- Senarai Kad Kumpulan Pelajar -->

    <div class="row g-4">

        <?php if ($groups_result && $groups_result->num_rows > 0): $no = 1; ?>

            <?php while($row = $groups_result->fetch_assoc()): ?>

                <?php

                    $names = explode('___', $row['student_names']);

                    $matrics = explode('___', $row['student_matrics']);

                   

                    $dept = strtoupper(trim($row['department'] ?? 'General'));

                    $badge_bg = 'bg-secondary';

                    if (str_contains($dept, 'JTMK')) {

                        $badge_bg = 'bg-primary';

                    } elseif (str_contains($dept, 'JRKV')) {

                        $badge_bg = 'bg-info text-dark';

                    }



                    $status = $row['project_status'] ?? 'Pending';

                ?>

                <div class="col-xl-4 col-md-6">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">

                            <span class="fw-bold text-muted">Group #<?= $no++; ?></span>

                            <span class="badge <?= $badge_bg; ?> px-2 py-1">

                                <i class="fas fa-building me-1"></i> <?= htmlspecialchars($row['department'] ?? 'Unassigned'); ?>

                            </span>

                        </div>



                        <div class="card-body">

                            <div class="mb-3">

                                <span class="text-muted small fw-bold d-block mb-1">GROUP / PROJECT TITLE</span>

                                <h5 class="fw-bold text-primary mb-0">

                                    <?= htmlspecialchars($row['group_name']); ?>

                                </h5>

                            </div>



                            <hr class="text-muted opacity-25">



                            <div class="mb-3">

                                <span class="text-muted small fw-bold d-block mb-2">

                                    <i class="fas fa-users me-1"></i> STUDENT MEMBERS (<?= count($names); ?>)

                                </span>

                                <div class="list-group list-group-flush border rounded overflow-hidden">

                                    <?php for($i = 0; $i < count($names); $i++): ?>

                                        <div class="list-group-item bg-light py-2 px-3 d-flex justify-content-between align-items-center">

                                            <span class="fw-semibold text-dark small"><?= htmlspecialchars($names[$i]); ?></span>

                                            <span class="badge bg-white text-muted border small"><?= htmlspecialchars($matrics[$i] ?? 'No Matric'); ?></span>

                                        </div>

                                    <?php endfor; ?>

                                </div>

                            </div>



                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">

                                <span class="text-muted small">Project Status:</span>

                                <?php if ($status == 'Verified' || $status == 'Approved'): ?>

                                    <span class="badge bg-success">Verified</span>

                                <?php elseif ($status == 'Rejected'): ?>

                                    <span class="badge bg-danger">Rejected</span>

                                <?php else: ?>

                                    <span class="badge bg-warning text-dark">Pending</span>

                                <?php endif; ?>

                            </div>

                        </div>



                        <div class="card-footer bg-white border-0 pb-3 pt-0">

                            <a href="verify_projects.php" class="btn btn-sm btn-outline-primary w-100 fw-semibold">

                                <i class="fas fa-eye me-1"></i> Review Group Project

                            </a>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="col-12">

                <div class="card border-0 shadow-sm text-center py-5">

                    <div class="card-body">

                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>

                        <h5 class="fw-bold text-dark">No Students Assigned</h5>

                        <p class="text-muted mb-0">There are no student groups assigned under your supervision yet.</p>

                    </div>

                </div>

            </div>

        <?php endif; ?>

    </div>

</div>



<?php include_once '../includes/footer.php'; ?> 

