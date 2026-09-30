<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Semak authentication student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$message = '';
$error = '';
$jtmk_categories = [
    'Multimedia and animation', 'Internet of Things (IOT)', 'Artificial Intelligent (AI)',
    'Software application', 'Web application', 'Mobile application', 'Networking system',
    'Hardware design', 'Robotic programming', 'Information system', 'Security system',
    'Data management & visualization', 'Data analysis'
];

// 1. Semak samada pelajar ni dah ada projek atau belum
$stmt_check_my_proj = $conn->prepare("
    SELECT p.*, pm.role 
    FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.student_id = ?
    WHERE p.student_id = ? OR pm.student_id = ?
    LIMIT 1
");
$stmt_check_my_proj->bind_param("iii", $student_id, $student_id, $student_id);
$stmt_check_my_proj->execute();
$my_project = $stmt_check_my_proj->get_result()->fetch_assoc();

// Process Submit Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$my_project) {
    $option_type = $_POST['option_type'] ?? 'new';

    if ($option_type === 'existing') {
        // --- PROSES SERTAI PROJEK ---
        $selected_project_id = intval($_POST['existing_project_id'] ?? 0);

        if ($selected_project_id > 0) {
            $stmt_proj_check = $conn->prepare("SELECT id FROM projects WHERE id = ? AND department = 'JTMK'");
            $stmt_proj_check->bind_param("i", $selected_project_id);
            $stmt_proj_check->execute();
            $proj_data = $stmt_proj_check->get_result()->fetch_assoc();

            if (!$proj_data) {
                $error = "Please select a valid JTMK project.";
            } else {
                // Semak bilangan ahli semasa (< 3 orang)
                $stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM project_members WHERE project_id = ?");
                $stmt_count->bind_param("i", $selected_project_id);
                $stmt_count->execute();
                $count_result = $stmt_count->get_result()->fetch_assoc();

                if ($count_result['total'] < 3) {
                    // Masukkan pelajar sebagai Member
                    $stmt_join = $conn->prepare("INSERT INTO project_members (project_id, student_id, role) VALUES (?, ?, 'Member')");
                    $stmt_join->bind_param("ii", $selected_project_id, $student_id);
                    if ($stmt_join->execute()) {
                        $message = "Successfully joined the project group!";
                        header("Refresh:1");
                    } else {
                        $error = "Error while joining the project.";
                    }
                } else {
                    $error = "Sorry, this project group already has the maximum of 3 members!";
                }

            }
        } else {
            $error = "Please select a project.";
        }

    } else {
        // --- PROSES CIPTA PROJEK BAHARU ---
        $title          = trim($_POST['title']);
        $category       = trim($_POST['category']);
        $session_val    = trim($_POST['session']);
        $description    = trim($_POST['description']);
        $declaration    = isset($_POST['declaration']) ? true : false;

        if (!empty($title) && in_array($category, $jtmk_categories, true)) {
            if (!$declaration) {
                $error = "You must agree to the project originality declaration checkbox.";
            } else {
                $conn->begin_transaction();

                try {
                    // 1. Insert ke jadual projects
                    $department = 'JTMK';
                    $program_name = 'JTMK - Information Technology';
                    $course_code = 'DFT50114';
                    $stmt_proj = $conn->prepare("INSERT INTO projects (student_id, created_by, supervisor_id, title, department, program_name, course_code, category, session, description) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_proj->bind_param("iisssssss", $student_id, $student_id, $title, $department, $program_name, $course_code, $category, $session_val, $description);
                    $stmt_proj->execute();
                    $new_project_id = $conn->insert_id;

                    // 2. Insert ke jadual project_members sebagai Leader
                    $stmt_mem = $conn->prepare("INSERT INTO project_members (project_id, student_id, role) VALUES (?, ?, 'Leader')");
                    $stmt_mem->bind_param("ii", $new_project_id, $student_id);
                    $stmt_mem->execute();

                    $conn->commit();
                    $message = "New project successfully registered!";
                    header("Refresh:1");
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Project registration error: " . $e->getMessage();
                }
            }
        } else {
            $error = "Please fill in all required project fields.";
        }
    }
}

// 2. Ambil senarai projek sedia ada yang mempunyai AHLI KURANG DARI 3 ORANG
$query_available_projects = "
    SELECT p.id, p.title, p.category, COUNT(pm.id) as current_members 
    FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    GROUP BY p.id
    HAVING current_members < 3
";
$available_projects = $conn->query($query_available_projects);

include_once '../includes/header.php';
include_once '../includes/sidebar_student.php';
?>

<!-- KANDUNGAN UTAMA -->
<div class="container-fluid p-4">

    <!-- Header Tajuk Halaman -->
    <div class="d-flex align-items-center mb-1">
        <i class="fas fa-project-diagram fa-2x text-primary me-2"></i>
        <h2 class="fw-bold m-0">My Project Information</h2>
    </div>
    <p class="text-muted mb-4">Manage your Final Year Project / SPInE details.</p>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success fw-bold"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger fw-bold"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- JIKA USER DAH ADA PROJEK -->
    <?php if ($my_project): ?>
        <div class="card card-custom p-4 shadow-sm">
            <span class="badge bg-success w-auto align-self-start mb-2">You Are Already in a Group</span>
            <h4 class="fw-bold text-primary"><?= htmlspecialchars($my_project['title']); ?></h4>
            <p class="text-muted mb-2">
                <strong>Category:</strong> <?= htmlspecialchars($my_project['category']); ?> | 
                <strong>Your Role:</strong> <span class="badge bg-info text-dark"><?= htmlspecialchars($my_project['role'] ?? 'Project Leader'); ?></span>
            </p>
            <p><?= nl2br(htmlspecialchars($my_project['description'])); ?></p>
            <hr>
            <h6 class="fw-bold">Project Group Members (Max 3 Members):</h6>
            <ul class="list-group list-group-flush mb-3">
                <?php
                $stmt_members = $conn->prepare("SELECT u.full_name, u.ic_number, pm.role FROM project_members pm JOIN users u ON pm.student_id = u.id WHERE pm.project_id = ?");
                $stmt_members->bind_param("i", $my_project['id']);
                $stmt_members->execute();
                $res_members = $stmt_members->get_result();
                if ($res_members->num_rows > 0):
                    while ($m = $res_members->fetch_assoc()):
                ?>
                        <li class="list-group-item bg-transparent">
                            <i class="fas fa-user-circle me-2 text-secondary"></i>
                            <strong><?= htmlspecialchars($m['full_name']); ?></strong> (<?= htmlspecialchars($m['ic_number']); ?>)
                            - <span class="small text-muted"><?= htmlspecialchars($m['role']); ?></span>
                        </li>
                    <?php 
                    endwhile;
                else: 
                ?>
                    <li class="list-group-item bg-transparent text-muted">
                        <i class="fas fa-user-circle me-2"></i> You (Project Leader)
                    </li>
                <?php endif; ?>
            </ul>
        </div>

    <!-- JIKA USER BELUM ADA PROJEK (PAPAR BORANG) -->
    <?php else: ?>
        <div class="card card-custom p-4 shadow-sm">
            
            <!-- Pilihan Kad Bentuk Moden (Toggle Tabs) -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card p-3 border-2 shadow-sm option-card bg-light" id="card_create" onclick="switchMode('new')" style="cursor: pointer; border-color: var(--bs-primary) !important;">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3">
                                <i class="fas fa-rocket fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Create New Project (Leader)</h6>
                                <small class="text-muted">Register a new project and become the group leader.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3 border-2 shadow-sm option-card" id="card_join" onclick="switchMode('existing')" style="cursor: pointer;">
                        <div class="d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 text-success p-3 rounded-3 me-3">
                                <i class="fas fa-users fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Join Existing Project (Member)</h6>
                                <small class="text-muted">Join an existing project group.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORM 1: CIPTA PROJEK BAHARU -->
            <form method="POST" action="" id="form_new_project">
                <input type="hidden" name="option_type" value="new">
                
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fas fa-plus-circle text-primary me-2"></i>Register New Project Details</h5>

                <div class="mb-3">
                    <label class="form-label fw-bold">Project Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="Example: Polytechnic FYP Management System" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Project Category *</label>
                        <select name="category" class="form-select" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($jtmk_categories as $category_option): ?>
                                <option value="<?= htmlspecialchars($category_option); ?>"><?= htmlspecialchars($category_option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Academic Session</label>
                        <input type="text" name="session" class="form-control bg-light" value="I : 2024/2025" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Project Description / Abstract</label>
                    <textarea name="description" id="abstractText" class="form-control" rows="4" maxlength="500" placeholder="Summary of project objectives and solutions..." onkeyup="countChar(this)"></textarea>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">Briefly describe the project objectives and scope.</small>
                        <small class="text-muted" id="charCount">0 / 500 characters</small>
                    </div>
                </div>

                <!-- Kotak Pengesahan Asal Usul Projek -->
                <div class="mb-4 form-check bg-light p-3 rounded border">
                    <input type="checkbox" class="form-check-input ms-1 me-2" id="declaration" name="declaration" required style="transform: scale(1.2);">
                    <label class="form-check-label fw-semibold text-dark" for="declaration" style="font-size: 0.9rem;">
                        I confirm that this project title is original, has not been registered by another group, and complies with department guidelines.
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm"><i class="fas fa-rocket me-1"></i> Register Project</button>
            </form>

            <!-- FORM 2: SERTAI PROJEK SEDIA ADA -->
            <form method="POST" action="" id="form_existing_project" style="display: none;">
                <input type="hidden" name="option_type" value="existing">

                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fas fa-users text-success me-2"></i>Join Current Project Group</h5>

                <div class="mb-3">
                    <label class="form-label fw-bold">Select Current Project (Available Projects with &lt; 3 members): *</label>
                    <select name="existing_project_id" class="form-select form-select-lg" required>
                        <option value="">-- Select Project --</option>
                        <?php if ($available_projects && $available_projects->num_rows > 0): ?>
                            <?php while ($proj = $available_projects->fetch_assoc()): ?>
                                <option value="<?= $proj['id']; ?>">
                                    <?= htmlspecialchars($proj['title']); ?> (<?= htmlspecialchars($proj['category']); ?>) - [Members: <?= $proj['current_members']; ?>/3]
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>No current projects have available slots.</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-4">
                </div>

                <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm"><i class="fas fa-user-plus me-1"></i> Join This Group</button>
            </form>

        </div>
    <?php endif; ?>

</div>

<script>
function switchMode(type) {
    const cardCreate = document.getElementById('card_create');
    const cardJoin = document.getElementById('card_join');
    const formNew = document.getElementById('form_new_project');
    const formExisting = document.getElementById('form_existing_project');

    if (type === 'new') {
        formNew.style.display = 'block';
        formExisting.style.display = 'none';
        cardCreate.style.borderColor = 'var(--bs-primary)';
        cardCreate.classList.add('bg-light');
        cardJoin.style.borderColor = '#dee2e6';
        cardJoin.classList.remove('bg-light');
    } else {
        formNew.style.display = 'none';
        formExisting.style.display = 'block';
        cardJoin.style.borderColor = 'var(--bs-success)';
        cardJoin.classList.add('bg-light');
        cardCreate.style.borderColor = '#dee2e6';
        cardCreate.classList.remove('bg-light');
    }
}

function countChar(val) {
    let len = val.value.length;
    document.getElementById('charCount').innerText = len + ' / 500 characters';
}
</script>

<?php include_once '../includes/footer.php'; ?>