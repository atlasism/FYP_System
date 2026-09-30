<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Student']);

$student_id = (int) $_SESSION['user_id'];
$error = '';
$jtmk_department = 'JTMK';
$it_program = 'JTMK - Information Technology';
$course_code = 'DFT50114';
$project_categories = [
    'Multimedia and animation',
    'Internet of Things (IOT)',
    'Artificial Intelligent (AI)',
    'Software application',
    'Web application',
    'Mobile application',
    'Networking system',
    'Hardware design',
    'Robotic programming',
    'Information system',
    'Security system',
    'Data management & visualization',
    'Data analysis'
];
$csrf_token = $_SESSION['csrf_register_project'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_register_project'] = $csrf_token;

$student_stmt = $conn->prepare("SELECT id, full_name, matric_no, ic_number, email, department, program_name, course_code, track, class_name, phone_no FROM users WHERE id = ? AND role = 'Student' AND department = 'JTMK'");
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$current_student = $student_stmt->get_result()->fetch_assoc();

$existing_stmt = $conn->prepare("SELECT p.id FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id WHERE p.student_id = ? OR pm.student_id = ? LIMIT 1");
$existing_stmt->bind_param("ii", $student_id, $student_id);
$existing_stmt->execute();
if ($existing_stmt->get_result()->num_rows > 0) {
    header("Location: my_project.php");
    exit();
}

$supervisors = [];
$supervisor_result = $conn->query("SELECT id, full_name, department FROM users WHERE role = 'Supervisor' AND department = 'JTMK' ORDER BY full_name");
if ($supervisor_result) {
    while ($supervisor = $supervisor_result->fetch_assoc()) {
        $supervisors[] = $supervisor;
    }
}

$member_defaults = [1 => $current_student ?: [], 2 => [], 3 => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $session = trim($_POST['session'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $supervisor_id = (int) ($_POST['supervisor_id'] ?? 0);

    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $error = 'This form session is invalid. Please try again.';
    } elseif (!$current_student || empty($title) || !in_array($category, $project_categories, true) || empty($session) || empty($description) || !$supervisor_id) {
        $error = 'Please complete all required fields, including title, category, session, description, and supervisor.';
    } else {
        $member_ids = [$student_id];
        for ($member_number = 2; $member_number <= 3; $member_number++) {
            $matric_no = trim($_POST["member_{$member_number}_matric"] ?? '');
            if ($matric_no === '') {
                continue;
            }

            $member_stmt = $conn->prepare("SELECT id FROM users WHERE matric_no = ? AND role = 'Student' AND department = 'JTMK'");
            $member_stmt->bind_param("s", $matric_no);
            $member_stmt->execute();
            $member = $member_stmt->get_result()->fetch_assoc();
            if (!$member || in_array((int) $member['id'], $member_ids, true)) {
                $error = "Student #{$member_number} was not found or has already been selected.";
                break;
            }
            $member_ids[] = (int) $member['id'];
        }

        if ($error === '') {
            $supervisor_stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'Supervisor' AND department = 'JTMK'");
            $supervisor_stmt->bind_param("i", $supervisor_id);
            $supervisor_stmt->execute();
            if (!$supervisor_stmt->get_result()->fetch_assoc()) {
                $error = 'The selected supervisor is invalid.';
            }
        }

        if ($error === '') {
            $conn->begin_transaction();
            try {
                    $department = $jtmk_department;
                    $project_stmt = $conn->prepare("INSERT INTO projects (student_id, created_by, supervisor_id, title, department, program_name, course_code, category, session, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $project_stmt->bind_param("iiisssssss", $student_id, $student_id, $supervisor_id, $title, $department, $it_program, $course_code, $category, $session, $description);
                $project_stmt->execute();
                $project_id = $conn->insert_id;

                $member_insert = $conn->prepare("INSERT INTO project_members (project_id, student_id, role) VALUES (?, ?, ?)");
                foreach ($member_ids as $index => $member_id) {
                    $role = $index === 0 ? 'Leader' : 'Member';
                    $member_insert->bind_param("iis", $project_id, $member_id, $role);
                    $member_insert->execute();
                }
                $conn->commit();
                header('Location: my_project.php?registered=1');
                exit();
            } catch (Throwable $exception) {
                $conn->rollback();
                $error = 'Unable to save the project registration. Please check that the database migrations have been run.';
            }
        }
    }
}

for ($member_number = 2; $member_number <= 3; $member_number++) {
    $member_defaults[$member_number]['matric_no'] = $_POST["member_{$member_number}_matric"] ?? '';
    $member_defaults[$member_number]['full_name'] = $_POST["member_{$member_number}_name"] ?? '';
    $member_defaults[$member_number]['ic_number'] = $_POST["member_{$member_number}_ic"] ?? '';
    $member_defaults[$member_number]['track'] = $_POST["member_{$member_number}_track"] ?? '';
    $member_defaults[$member_number]['class_name'] = $_POST["member_{$member_number}_class"] ?? '';
    $member_defaults[$member_number]['phone_no'] = $_POST["member_{$member_number}_phone"] ?? '';
    $member_defaults[$member_number]['email'] = $_POST["member_{$member_number}_email"] ?? '';
}

include_once '../includes/header.php';
include_once '../includes/navbar.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card card-custom p-4">
            <h4 class="fw-bold mb-1"><i class="fas fa-plus-circle me-2 text-primary"></i>DFT50114 Project Registration</h4>
            <p class="text-muted mb-4">Project group registration for one to three students.</p>
                
                <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error); ?></div><?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token); ?>">
                    <h5 class="fw-bold border-bottom pb-2">SECTION A: PROJECT TEAM</h5>
                    <p class="small text-muted">Student #1 is the logged-in project leader. Enter the registered Matrix No. for optional members.</p>
                    <?php for ($member_number = 1; $member_number <= 3; $member_number++): $member = $member_defaults[$member_number]; ?>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <h6 class="fw-bold text-primary">Student #<?= $member_number; ?> <?= $member_number === 1 ? '(Project Leader)' : '(Optional Member)'; ?></h6>
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Name</label><input id="member_<?= $member_number; ?>_name" name="member_<?= $member_number; ?>_name" class="form-control" value="<?= sanitize($member['full_name'] ?? ''); ?>" <?= $member_number === 1 ? 'readonly required' : ''; ?>></div>
                                <div class="col-md-6"><label class="form-label">Matrix No</label><input id="member_<?= $member_number; ?>_matric" name="member_<?= $member_number; ?>_matric" class="form-control" value="<?= sanitize($member['matric_no'] ?? ''); ?>" <?= $member_number === 1 ? 'readonly' : ''; ?> <?= $member_number > 1 ? 'placeholder="Example: 34DIT2xFxxx" data-student-lookup' : ''; ?>></div>
                                <div class="col-md-4"><label class="form-label">I/C No</label><input id="member_<?= $member_number; ?>_ic" name="member_<?= $member_number; ?>_ic" class="form-control" value="<?= sanitize($member['ic_number'] ?? ''); ?>" <?= $member_number === 1 ? 'readonly required' : ''; ?>></div>
                                <div class="col-md-4"><label class="form-label">Track</label><input id="member_<?= $member_number; ?>_track" name="member_<?= $member_number; ?>_track" class="form-control" value="<?= sanitize($member['track'] ?? ''); ?>" <?= $member_number === 1 ? 'readonly required' : ''; ?>></div>
                                <div class="col-md-4"><label class="form-label">Class</label><input id="member_<?= $member_number; ?>_class" name="member_<?= $member_number; ?>_class" class="form-control" value="<?= sanitize($member['class_name'] ?? ''); ?>" <?= $member_number === 1 ? 'readonly required' : ''; ?>></div>
                                <div class="col-md-6"><label class="form-label">Phone No</label><input id="member_<?= $member_number; ?>_phone" name="member_<?= $member_number; ?>_phone" class="form-control" value="<?= sanitize($member['phone_no'] ?? ''); ?>" <?= $member_number === 1 ? 'readonly required' : ''; ?>></div>
                                <div class="col-md-6"><label class="form-label">Email</label><input id="member_<?= $member_number; ?>_email" type="email" name="member_<?= $member_number; ?>_email" class="form-control" value="<?= sanitize($member['email'] ?? ''); ?>" <?= $member_number === 1 ? 'readonly required' : ''; ?>></div>
                                <?php if ($member_number > 1): ?><div id="member_<?= $member_number; ?>_lookup_message" class="form-text">Enter a registered Matrix No to load the student's details.</div><?php endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                    <h5 class="fw-bold border-bottom pb-2 mt-4">SECTION B: PROJECT INFORMATION</h5>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label fw-bold">Project Title *</label><input type="text" name="title" class="form-control" required value="<?= sanitize($_POST['title'] ?? ''); ?>"></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Project Category *</label><select name="category" class="form-select" required><option value="">-- Select JTMK IT Category --</option><?php foreach ($project_categories as $category_option): ?><option value="<?= sanitize($category_option); ?>" <?= (($_POST['category'] ?? '') === $category_option) ? 'selected' : ''; ?>><?= sanitize($category_option); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label fw-bold">Department</label><input type="text" class="form-control" value="JTMK" readonly></div>
                        <div class="col-md-5"><label class="form-label fw-bold">Programme</label><input type="text" class="form-control" value="<?= sanitize($it_program); ?>" readonly></div>
                        <div class="col-md-4"><label class="form-label fw-bold">Course Code</label><input type="text" class="form-control" value="<?= sanitize($course_code . ' - Integrated Project'); ?>" readonly></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Academic Session *</label><input type="text" name="session" class="form-control" value="<?= sanitize($_POST['session'] ?? 'I : 2026/2027'); ?>" required></div>
                        <div class="col-12"><label class="form-label fw-bold">Project Description *</label><textarea name="description" rows="5" class="form-control" required><?= sanitize($_POST['description'] ?? ''); ?></textarea></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Supervisor's Name *</label><select name="supervisor_id" class="form-select" required><option value="">-- Select JTMK Supervisor --</option><?php foreach ($supervisors as $supervisor): ?><option value="<?= (int) $supervisor['id']; ?>" <?= ((int) ($_POST['supervisor_id'] ?? 0) === (int) $supervisor['id']) ? 'selected' : ''; ?>><?= sanitize($supervisor['full_name'] . ' (JTMK)'); ?></option><?php endforeach; ?></select></div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mt-4"><i class="fas fa-paper-plane me-1"></i> Submit Project Registration</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('[data-student-lookup]').forEach(function (input) {
    input.addEventListener('blur', function () {
        const matricNo = input.value.trim();
        const memberNumber = input.id.split('_')[1];
        const message = document.getElementById('member_' + memberNumber + '_lookup_message');

        if (!matricNo) {
            message.textContent = 'Enter a registered Matrix No to load the student\'s details.';
            message.className = 'form-text';
            return;
        }

        message.textContent = 'Searching student...';
        message.className = 'form-text text-muted';

        fetch('get_student_by_matric.php?matric_no=' + encodeURIComponent(matricNo), { headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok || !result.data.success) {
                    throw new Error(result.data.message || 'Student not found.');
                }

                const student = result.data.student;
                document.getElementById('member_' + memberNumber + '_name').value = student.full_name || '';
                document.getElementById('member_' + memberNumber + '_ic').value = student.ic_number || '';
                document.getElementById('member_' + memberNumber + '_track').value = student.track || '';
                document.getElementById('member_' + memberNumber + '_class').value = student.class_name || '';
                document.getElementById('member_' + memberNumber + '_phone').value = student.phone_no || '';
                document.getElementById('member_' + memberNumber + '_email').value = student.email || '';
                message.textContent = 'Student found and details loaded.';
                message.className = 'form-text text-success';
            })
            .catch(function (error) {
                message.textContent = error.message;
                message.className = 'form-text text-danger';
            });
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>