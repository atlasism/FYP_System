<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Supervisor']);

$supervisor_id = (int) ($_SESSION['user_id'] ?? 0);

$stmt = $conn->prepare("
    SELECT
        u.id,
        u.full_name,
        u.matric_no,
        u.ic_number,
        u.department,
        u.track,
        u.class_name,
        ss.session,
        (
            SELECT COUNT(*)
            FROM supervisor_logbook sl
            WHERE sl.supervisor_id = ss.supervisor_id
              AND sl.student_id = u.id
              AND sl.is_verified = 1
        ) AS verified_weeks,
        (
            SELECT status
            FROM student_demo_status ds
            WHERE ds.supervisor_id = ss.supervisor_id
              AND ds.student_id = u.id
              AND ds.demo_type = 'Demo 1'
        ) AS demo_1_status,
        (
            SELECT status
            FROM student_demo_status ds
            WHERE ds.supervisor_id = ss.supervisor_id
              AND ds.student_id = u.id
              AND ds.demo_type = 'Demo 2'
        ) AS demo_2_status
    FROM supervisor_students ss
    INNER JOIN users u ON u.id = ss.student_id AND u.role = 'Student'
    WHERE ss.supervisor_id = ?
    GROUP BY u.id, u.full_name, u.matric_no, u.ic_number, u.department, u.track, u.class_name, ss.session, ss.supervisor_id
    ORDER BY u.full_name ASC
");
$stmt->bind_param('i', $supervisor_id);
$stmt->execute();
$students = $stmt->get_result();

include_once '../includes/header.php';
include_once '../includes/sidebar_supervisor.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1"><i class="fas fa-user-graduate me-2"></i>Assigned Students</h3>
            <p class="text-muted mb-0">Verify log book activities and Demo 1/Demo 2 milestone status.</p>
        </div>
    </div>

    <div class="alert alert-info border-0 shadow-sm">
        <i class="fas fa-info-circle me-2"></i>
        Supervisor actions are verification only. No numerical marks or grades are entered here.
    </div>

    <div class="card card-custom border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Student</th>
                            <th>Matrix No</th>
                            <th>Session</th>
                            <th>Log Book</th>
                            <th>Demo 1</th>
                            <th>Demo 2</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students->num_rows === 0): ?>
                            <tr><td colspan="7" class="text-center text-muted py-5">No assigned students found.</td></tr>
                        <?php else: ?>
                            <?php while ($student = $students->fetch_assoc()): ?>
                                <?php
                                $demo_1 = $student['demo_1_status'] ?: 'Pending';
                                $demo_2 = $student['demo_2_status'] ?: 'Pending';
                                $status_class = static function ($status) {
                                    return $status === 'Passed' ? 'bg-success' : ($status === 'Not Passed' ? 'bg-danger' : 'bg-secondary');
                                };
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold d-block"><?= sanitize($student['full_name']); ?></span>
                                        <small class="text-muted"><?= sanitize($student['department'] ?? ''); ?><?= !empty($student['class_name']) ? ' | ' . sanitize($student['class_name']) : ''; ?></small>
                                    </td>
                                    <td><?= sanitize($student['matric_no'] ?: 'Not Set'); ?></td>
                                    <td><?= sanitize($student['session'] ?? '-'); ?></td>
                                    <td><span class="badge <?= (int) $student['verified_weeks'] === 14 ? 'bg-success' : 'bg-warning text-dark'; ?>"><?= (int) $student['verified_weeks']; ?>/14 weeks</span></td>
                                    <td><span class="badge <?= $status_class($demo_1); ?>"><?= sanitize($demo_1); ?></span></td>
                                    <td><span class="badge <?= $status_class($demo_2); ?>"><?= sanitize($demo_2); ?></span></td>
                                    <td class="text-end pe-4">
                                        <a href="verify_logbook.php?student_id=<?= (int) $student['id']; ?>" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-book me-1"></i> Log Book</a>
                                        <a href="update_demo_status.php?student_id=<?= (int) $student['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-flag-checkered me-1"></i> Milestones</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
