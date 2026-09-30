<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/student_import.php';
check_access(['Admin']);

$message = '';
$error = '';
$import_summary = '';
$import_errors = [];
$ignored_department_count = 0;
$csrf_token = $_SESSION['csrf_admin_users'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_admin_users'] = $csrf_token;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form session.';
    } elseif (($_POST['action'] ?? '') === 'import_students') {
        $upload = $_FILES['student_file'] ?? null;
        $extension = strtolower(pathinfo($upload['name'] ?? '', PATHINFO_EXTENSION));
        if (!$upload || $upload['error'] !== UPLOAD_ERR_OK) {
            $error = 'Choose a CSV or XLSX file and try again.';
        } elseif (!in_array($extension, ['csv', 'xlsx'], true) || $upload['size'] > 5 * 1024 * 1024) {
            $error = 'Only CSV or XLSX files up to 5 MB are accepted.';
        } else {
            try {
                $records = student_import_parse_file($upload['tmp_name'], $extension);
                if (!$records) {
                    throw new RuntimeException('No student rows were found below the header.');
                }

                $program_name = 'JTMK - Information Technology';
                $course_code = 'DFT50114';
                $role = 'Student';
                $insert = $conn->prepare('INSERT INTO users (username, ic_number, matric_no, full_name, email, password, role, department, program_name, course_code, academic_session) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $duplicate_check = $conn->prepare('SELECT id FROM users WHERE ic_number = ? OR matric_no = ? OR email = ? LIMIT 1');
                $seen_ic = [];
                $seen_matric = [];
                $department = 'JTMK';
                $imported_count = 0;

                $conn->begin_transaction();
                foreach ($records as $record) {
                    $student = $record['values'];
                    $full_name = trim($student['full_name']);
                    $ic_number = trim($student['ic_number']);
                    $matric_no = trim($student['matric_no']);
                    $academic_session = trim($student['session']);
                    $row_department = strtoupper(trim($student['department']));

                    if ($full_name === '' || $ic_number === '' || $matric_no === '' || $academic_session === '') {
                        $import_errors[] = 'Row ' . $record['row'] . ': name, IC, matric number and session are required.';
                        continue;
                    }
                    if ($row_department !== 'JTMK') {
                        $ignored_department_count++;
                        continue;
                    }
                    if (!preg_match('/^[A-Za-z0-9]+$/', $matric_no)) {
                        $import_errors[] = 'Row ' . $record['row'] . ': matric number must contain letters and numbers only.';
                        continue;
                    }

                    $email = strtolower($matric_no) . '@jtmk.local';
                    $ic_key = strtolower($ic_number);
                    $matric_key = strtolower($matric_no);
                    if (isset($seen_ic[$ic_key]) || isset($seen_matric[$matric_key])) {
                        $import_errors[] = 'Row ' . $record['row'] . ': IC or matric number is duplicated in this file.';
                        continue;
                    }

                    $duplicate_check->bind_param('sss', $ic_number, $matric_no, $email);
                    $duplicate_check->execute();
                    if ($duplicate_check->get_result()->num_rows > 0) {
                        $import_errors[] = 'Row ' . $record['row'] . ': IC, matric number or generated email already exists.';
                        continue;
                    }

                    $username = $ic_number;
                    $hashed_password = password_hash($ic_number, PASSWORD_DEFAULT);
                    $insert->bind_param('sssssssssss', $username, $ic_number, $matric_no, $full_name, $email, $hashed_password, $role, $department, $program_name, $course_code, $academic_session);
                    $insert->execute();
                    $seen_ic[$ic_key] = true;
                    $seen_matric[$matric_key] = true;
                    $imported_count++;
                }
                $conn->commit();

                $import_summary = $imported_count . ' Student JTMK account' . ($imported_count === 1 ? '' : 's') . ' imported; ' . $ignored_department_count . ' non-JTMK row' . ($ignored_department_count === 1 ? '' : 's') . ' ignored; ' . count($import_errors) . ' invalid or duplicate row' . (count($import_errors) === 1 ? '' : 's') . ' skipped.';
            } catch (Throwable $exception) {
                $conn->rollback();
                $import_errors = [];
                $error = 'Import failed. Confirm that student_import_migration.sql has been run, then try again.';
            }
        }
    } elseif (($_POST['action'] ?? '') === 'delete_student') {
        $student_id = (int) ($_POST['student_id'] ?? 0);
        $dependency = $conn->prepare("SELECT (SELECT COUNT(*) FROM projects WHERE student_id = ?) + (SELECT COUNT(*) FROM project_members WHERE student_id = ?) + (SELECT COUNT(*) FROM supervisor_students WHERE student_id = ?) AS total");
        $dependency->bind_param('iii', $student_id, $student_id, $student_id);
        $dependency->execute();
        $dependency_count = (int) $dependency->get_result()->fetch_assoc()['total'];
        if ($dependency_count > 0) {
            $error = 'This student cannot be deleted because the account is linked to a project or supervisor assignment.';
        } else {
            $delete = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'Student' AND department = 'JTMK'");
            $delete->bind_param('i', $student_id);
            if ($delete->execute() && $delete->affected_rows === 1) {
                $message = 'Student account deleted successfully.';
            } else {
                $error = 'Unable to delete the selected student account.';
            }
        }
    } else {
        $error = 'Invalid user management action.';
    }
}

$student_count = (int) $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'Student' AND department = 'JTMK'")->fetch_assoc()['total'];
$student_result = $conn->query("SELECT id, full_name, ic_number, matric_no, email, academic_session FROM users WHERE role = 'Student' AND department = 'JTMK' ORDER BY full_name ASC");
$students_by_session = [];
while ($student = $student_result->fetch_assoc()) {
    $session = trim($student['academic_session'] ?? '') ?: 'Sesi tidak dinyatakan';
    $students_by_session[$session][] = $student;
}

foreach ($students_by_session as &$session_students) {
    usort($session_students, static function ($left, $right) {
        return strnatcasecmp((string) ($left['matric_no'] ?? ''), (string) ($right['matric_no'] ?? ''));
    });
}
unset($session_students);

$session_sort_key = static function ($session) {
    $year = preg_match('/(\d{4})\s*\/\s*\d{4}/', $session, $year_match) ? (int) $year_match[1] : 0;
    $term = 0;
    if (preg_match('/\bsession\s*(\d+)/i', $session, $term_match)) {
        $term = (int) $term_match[1];
    } elseif (preg_match('/^\s*(I{1,3}|IV|V)\s*:/i', $session, $term_match)) {
        $term = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5][strtoupper($term_match[1])] ?? 0;
    }
    return [$year, $term ?: ($year ? 1 : 0)];
};
uksort($students_by_session, static function ($left, $right) use ($session_sort_key) {
    [$left_year, $left_term] = $session_sort_key($left);
    [$right_year, $right_term] = $session_sort_key($right);
    return ($right_year <=> $left_year) ?: ($right_term <=> $left_term) ?: strnatcasecmp($left, $right);
});
$supervisors = $conn->query("SELECT full_name, ic_number, email FROM users WHERE role = 'Supervisor' AND department = 'JTMK' ORDER BY full_name");

include_once '../includes/admin_header.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div><h3 class="fw-bold text-primary mb-1"><i class="bi bi-people-fill me-2"></i>Manage JTMK Users</h3><p class="text-muted mb-0">Admin-only account management. Student imports are limited to the JTMK department.</p></div>
        <a href="dashboard.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= sanitize($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error); ?></div><?php endif; ?>
    <?php if ($import_summary): ?><div class="alert alert-info"><?= sanitize($import_summary); ?></div><?php endif; ?>
    <?php if ($import_errors): ?><div id="skipped-rows-alert" class="alert alert-warning"><strong>Skipped rows</strong><ul class="mb-0 mt-2"><?php foreach (array_slice($import_errors, 0, 30) as $import_error): ?><li><?= sanitize($import_error); ?></li><?php endforeach; ?></ul><?php if (count($import_errors) > 30): ?><div class="mt-2">And <?= count($import_errors) - 30; ?> more skipped rows.</div><?php endif; ?></div><?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-4 col-xl-3">
            <div class="card admin-stat admin-stat-blue h-100 p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div><small class="text-white-50 fw-bold d-block">JTMK Students</small><h2 class="fw-bold mb-0"><?= $student_count; ?></h2></div>
                    <span class="admin-stat-icon"><i class="bi bi-mortarboard-fill"></i></span>
                </div>
            </div>
        </div>
        <div class="col-md-8 col-xl-9">
            <div class="card admin-card p-4 h-100">
                <h5 class="fw-bold mb-2"><i class="bi bi-search text-primary me-2"></i>Find Student</h5>
                <label class="form-label fw-bold" for="student_search">Search by student name, IC number, or matric number</label>
                <input id="student_search" type="search" class="form-control" autocomplete="off" placeholder="Type at least 2 characters..." aria-describedby="student_search_status">
                <div id="student_search_status" class="form-text mt-2" aria-live="polite">Search results will appear here.</div>
                <div id="student_search_results" class="table-responsive mt-3" hidden></div>
            </div>
        </div>
    </div>

    <div class="card admin-card p-4 mb-4">
        <h5 class="fw-bold mb-2"><i class="fas fa-file-import text-primary me-2"></i>Import Student JTMK</h5>
        <p class="text-muted">Upload CSV or XLSX with Name, IC No, Matric No, Session and Department columns. Only rows marked JTMK are imported; other departments are ignored.</p>
        <div class="alert alert-warning py-2"><strong>Account setup:</strong> imported students use their IC number as their initial password. Passwords are stored as secure hashes; students should change the initial password after signing in.</div>
        <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
            <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token); ?>">
            <input type="hidden" name="action" value="import_students">
            <div class="col-md-9"><label class="form-label fw-bold" for="student_file">Student data file</label><input id="student_file" type="file" name="student_file" class="form-control" accept=".csv,.xlsx" required></div>
            <div class="col-md-3"><button class="btn btn-primary w-100" type="submit"><i class="fas fa-upload me-1"></i> Import Students</button></div>
        </form>
    </div>

    <div class="card admin-card p-4 mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-mortarboard-fill text-primary me-2"></i>Student JTMK</h5>
        <div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Name</th><th>I/C No</th><th>Matrix No</th><th>Email</th><th>Academic Session</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($students_by_session as $session => $session_students): ?>
            <tr class="table-primary"><th colspan="6" scope="rowgroup"><?= sanitize($session); ?><span class="badge bg-primary ms-2"><?= count($session_students); ?></span></th></tr>
            <?php foreach ($session_students as $student): ?><tr><td class="fw-bold"><?= sanitize($student['full_name']); ?></td><td><?= sanitize($student['ic_number']); ?></td><td><?= sanitize($student['matric_no'] ?: '-'); ?></td><td><?= sanitize($student['email']); ?></td><td><?= sanitize($session === 'Sesi tidak dinyatakan' ? '-' : $session); ?></td><td class="text-nowrap"><a href="edit_student.php?id=<?= (int) $student['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i> Edit</a> <form method="POST" class="d-inline" onsubmit="return confirm('Delete this student account?');"><input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token); ?>"><input type="hidden" name="action" value="delete_student"><input type="hidden" name="student_id" value="<?= (int) $student['id']; ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> Delete</button></form></td></tr><?php endforeach; ?>
        <?php endforeach; ?>
        </tbody></table></div>
    </div>

    <div class="card admin-card p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-person-workspace text-success me-2"></i>Lecturers / Supervisors</h5>
        <div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Name</th><th>I/C No</th><th>Email</th></tr></thead><tbody>
        <?php while ($supervisor = $supervisors->fetch_assoc()): ?><tr><td class="fw-bold"><?= sanitize($supervisor['full_name']); ?></td><td><?= sanitize($supervisor['ic_number']); ?></td><td><?= sanitize($supervisor['email']); ?></td></tr><?php endwhile; ?>
        </tbody></table></div>
    </div>
</div>

<script>
const studentSearch = document.getElementById('student_search');
const searchStatus = document.getElementById('student_search_status');
const searchResults = document.getElementById('student_search_results');
let searchTimer;
let activeSearch;

studentSearch.addEventListener('input', function () {
    clearTimeout(searchTimer);
    if (activeSearch) activeSearch.abort();

    const query = studentSearch.value.trim();
    searchResults.hidden = true;
    searchResults.replaceChildren();
    if (query.length < 2) {
        searchStatus.textContent = 'Type at least 2 characters to find a JTMK student.';
        return;
    }

    searchStatus.textContent = 'Searching students...';
    searchTimer = setTimeout(async function () {
        activeSearch = new AbortController();
        try {
            const response = await fetch('search_students.php?q=' + encodeURIComponent(query), {
                headers: { 'Accept': 'application/json' },
                signal: activeSearch.signal
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Student search failed.');

            if (!data.results.length) {
                searchStatus.textContent = 'No JTMK students match that search.';
                return;
            }

            const table = document.createElement('table');
            table.className = 'table table-sm table-hover align-middle mb-0';
            const head = table.createTHead().insertRow();
            ['Name', 'Matrix No', 'I/C No', 'Session', 'Email'].forEach(function (label) {
                const cell = document.createElement('th');
                cell.textContent = label;
                head.appendChild(cell);
            });
            const body = table.createTBody();
            data.results.forEach(function (student) {
                const row = body.insertRow();
                [student.full_name, student.matric_no, student.ic_number, student.academic_session || '-', student.email].forEach(function (value) {
                    row.insertCell().textContent = value || '-';
                });
            });
            searchResults.appendChild(table);
            searchResults.hidden = false;
            searchStatus.textContent = data.results.length === 20
                ? 'Showing the first 20 matches. Refine the search for more specific results.'
                : data.results.length + ' matching JTMK student' + (data.results.length === 1 ? '' : 's') + '.';
        } catch (error) {
            if (error.name !== 'AbortError') searchStatus.textContent = error.message;
        }
    }, 250);
});
</script>
<?php if ($import_errors): ?>
<script>
setTimeout(function () {
    document.getElementById('skipped-rows-alert')?.remove();
}, 10000);
</script>
<?php endif; ?>

<?php include_once '../includes/admin_footer.php'; ?>
