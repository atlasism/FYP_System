<?php
// config/database.php

$env = @parse_ini_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');
$env = is_array($env) ? $env : [];

define('APP_NAME', $env['APP_NAME'] ?? 'FYP System');
define('APP_ENV', $env['APP_ENV'] ?? 'production');
define('APP_API_KEY', $env['APP_API_KEY'] ?? '');
define('DB_HOST', ($env['DB_HOST'] ?? getenv('DB_HOST')) ?: 'localhost');
define('DB_PORT', (int) (($env['DB_PORT'] ?? getenv('DB_PORT')) ?: 3306));
define('DB_USER', ($env['DB_USER'] ?? getenv('DB_USER')) ?: (getenv('DB_USERNAME') ?: 'root'));
define('DB_PASS', ($env['DB_PASS'] ?? getenv('DB_PASS')) ?: (getenv('DB_PASSWORD') ?: ''));
define('DB_NAME', ($env['DB_NAME'] ?? getenv('DB_NAME')) ?: (getenv('DB_DATABASE') ?: 'fyp_inventory_db'));

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die("Unable to connect to the database: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Fungsi pembantu global untuk elak XSS
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>
