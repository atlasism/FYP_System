<?php
header('Location: login.php');
exit();

require_once 'config/database.php';

// Maklumat akaun yang nak dimasukkan
$username = 'IzzahAthirah';
$raw_password = 'Izzah@Athirah11';
$full_name = 'NUR IZZAH ATHIRAH BINTI ABDULLAH';
$email = 'izzahatyrah06@gmail.com';
$matric_ic = '34DIT24F1032';
$role = 'Student';
$department = 'JTMK';

// Generate hash secara tepat menggunakan PHP
$hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

// Padam user lama jika wujud untuk elak duplicate
$stmt_del = $conn->prepare("DELETE FROM users WHERE username = ?");
$stmt_del->bind_param("s", $username);
$stmt_del->execute();

// Masukkan akaun baru dengan hash yang sah
$stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, ic_number, role, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $username, $hashed_password, $full_name, $email, $matric_ic, $role, $department);

if ($stmt->execute()) {
    echo "<h2 style='color:green;'>Account created successfully with a valid password hash.</h2>";
    echo "<p><a href='login.php'>Click here to sign in</a></p>";
    echo "<p><strong>Username:</strong> " . $username . "</p>";
} else {
    echo "<h2 style='color:red;'>Error: " . $conn->error . "</h2>";
}
?>