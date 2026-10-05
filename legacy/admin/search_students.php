<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_access(['Admin']);

header('Content-Type: application/json; charset=utf-8');

$query = trim($_GET['q'] ?? '');
if (mb_strlen($query, 'UTF-8') < 2) {
    echo json_encode(['results' => []]);
    exit();
}

$search_term = '%' . addcslashes($query, '\\%_') . '%';
$stmt = $conn->prepare("SELECT full_name, ic_number, matric_no, email, academic_session FROM users WHERE role = 'Student' AND department = 'JTMK' AND (full_name LIKE ? OR ic_number LIKE ? OR matric_no LIKE ?) ORDER BY full_name LIMIT 20");
$stmt->bind_param('sss', $search_term, $search_term, $search_term);
$stmt->execute();

echo json_encode(['results' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);