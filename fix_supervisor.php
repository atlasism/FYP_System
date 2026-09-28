<?php
require_once 'config/database.php';

// Data akaun supervisor yang dikehendaki
$username = 'Supervisor1';
$raw_password = 'Password123';
$full_name = 'SUPERVISOR';
$email = 'supervisor@gmail.com';
$matric_ic = 'SV4556';
$role = 'Supervisor';
$department = 'JTMK';

// Hash kata laluan terus melalui enjin PHP
$hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

// 1. Padam rekod Supervisor1 lama yang rosak/plain text
$stmt_del = $conn->prepare("DELETE FROM users WHERE username = ?");
$stmt_del->bind_param("s", $username);
$stmt_del->execute();

// 2. Masukkan akaun Supervisor1 baharu dengan hash PHP yang sah
$stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, ic_number, role, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $username, $hashed_password, $full_name, $email, $matric_ic, $role, $department);

if ($stmt->execute()) {
    echo "<div style='font-family: Arial; padding: 20px; background: #e8f5e9; border: 1px solid #4caf50; border-radius: 8px;'>";
    echo "<h2 style='color: #2e7d32; margin-top:0;'>Supervisor password hash updated successfully.</h2>";
    echo "<p>The supervisor account now has a valid PHP password hash in the database.</p>";
    echo "<p><strong>Username:</strong> " . $username . "</p>";
    echo "<hr><a href='login.php' style='display:inline-block; padding: 10px 20px; background: #2e7d32; color: white; text-decoration: none; border-radius: 4px;'>Click here to sign in</a>";
    echo "</div>";
} else {
    echo "<h2 style='color:red;'>Error: " . $conn->error . "</h2>";
}
?>