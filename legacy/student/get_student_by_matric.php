<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Student']);

header('Content-Type: application/json; charset=utf-8');

$matric_no = trim($_GET['matric_no'] ?? '');
if ($matric_no === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Matrix No is required.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, full_name, matric_no, ic_number, track, class_name, phone_no, email FROM users WHERE matric_no = ? AND role = 'Student' AND department = 'JTMK' LIMIT 1");
$stmt->bind_param('s', $matric_no);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No registered student was found for this Matrix No.']);
    exit;
}

echo json_encode([
    'success' => true,
    'student' => [
        'id' => (int) $student['id'],
        'full_name' => $student['full_name'],
        'matric_no' => $student['matric_no'],
        'ic_number' => $student['ic_number'],
        'track' => $student['track'],
        'class_name' => $student['class_name'],
        'phone_no' => $student['phone_no'],
        'email' => $student['email']
    ]
]);
