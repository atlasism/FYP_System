<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Supervisor']);

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// Proses kemas kini tarikh dan masa deadline
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_deadline'])) {
    $deadline_id = intval($_POST['deadline_id']);
    $new_datetime = $conn->real_escape_string($_POST['due_date']); // format YYYY-MM-DDTHH:MM

    // Tukar format dari HTML datetime-local (YYYY-MM-DDTHH:MM) kepada format SQL (YYYY-MM-DD HH:MM:SS)
    if (!empty($new_datetime)) {
        $new_datetime = str_replace('T', ' ', $new_datetime) . ':00';
    } else {
        $new_datetime = null;
    }

    $stmt = $conn->prepare("UPDATE submission_deadlines SET due_date = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $new_datetime, $deadline_id);
        $stmt->execute();
    }
}

// Tarik data dari database
$result = $conn->query("SELECT * FROM submission_deadlines WHERE title <> 'Log Book' ORDER BY id ASC");

include_once '../includes/header.php';
include_once '../includes/sidebar_supervisor.php';
?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <h3 class="fw-bold text-primary"><i class="fas fa-calendar-check me-2"></i>Document Submission Deadline Reminders</h3>
        <p class="text-muted">Manage submission dates for the official DFT50114 document categories. This page does not record marks or grades.</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card card-custom border-0 shadow-sm p-4 bg-white rounded-3">
                <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-clock text-danger me-2"></i>Schedule by Document Type</h5>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="22%">Document Type</th>
                                <th width="28%">Description / Instructions</th>
                                <th width="20%">Deadline Date & Time</th>
                                <th width="12%" class="text-center">Status</th>
                                <th width="13%" class="text-center">Action (SV)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php $i = 1; while ($row = $result->fetch_assoc()): 
                                    $due_date_raw = $row['due_date'] ?? '';
                                    $has_date = !empty($due_date_raw) && $due_date_raw != '0000-00-00 00:00:00' && $due_date_raw != '0000-00-00';
                                    $due_timestamp = $has_date ? strtotime($due_date_raw) : null;
                                    $today = time();
                                    $is_overdue = $due_timestamp ? ($today > $due_timestamp) : false;
                                    
                                    // Untuk nilai input datetime-local HTML (Format: YYYY-MM-DDTHH:MM)
                                    $input_value = $has_date ? date('Y-m-d\TH:i', $due_timestamp) : '';
                                ?>
                                    <tr>
                                        <td><?= $i++; ?></td>
                                        <td class="fw-bold text-dark">
                                            <i class="fas fa-file-alt text-primary me-2"></i><?= sanitize($row['title']); ?>
                                        </td>
                                        <td class="text-secondary small"><?= sanitize($row['description']); ?></td>
                                        <td>
                                            <?php if ($has_date): ?>
                                                <span class="badge <?= $is_overdue ? 'bg-danger' : 'bg-warning text-dark'; ?> fs-6">
                                                    <i class="fas fa-calendar-alt me-1"></i><?= date('d/m/Y, h:i A', $due_timestamp); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark fs-6">
                                                    <i class="fas fa-calendar-day me-1"></i>To Be Announced
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!$has_date): ?>
                                                <span class="badge bg-info">Waiting for Admin</span>
                                            <?php elseif ($is_overdue): ?>
                                                <span class="badge bg-secondary">Expired</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id']; ?>">
                                                <i class="fas fa-edit me-1"></i> Set Date
                                            </button>

                                            <!-- Modal Ubah Tarikh & Masa -->
                                            <div class="modal fade" id="editModal<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fw-bold">Set Deadline: <?= sanitize($row['title']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                <input type="hidden" name="deadline_id" value="<?= $row['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Select Due Date & Time:</label>
                                                                    <input type="datetime-local" name="due_date" class="form-control" value="<?= $input_value; ?>" required>
                                                                    <div class="form-text">Please select the due date and time (12-hour/24-hour format depending on your device).</div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="update_deadline" class="btn btn-primary btn-sm">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No submission deadlines found in database.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info border-0 mt-3 mb-0 d-flex align-items-center">
                    <i class="fas fa-info-circle fa-2x me-3"></i>
                    <div>
                        <strong>Supervisor Control Notice:</strong> Set the submission date and exact time for students. Verification outcomes are managed separately as Passed or Not Passed.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>