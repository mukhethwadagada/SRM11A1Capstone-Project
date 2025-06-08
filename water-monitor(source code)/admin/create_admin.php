<?php
require_once 'includes/config.php';

$admin_email = "admin@watersaver.joburg";
$admin_password = "password"; // Change this after setup!

// Check if admin exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$admin_email]);

if (!$stmt->fetch()) {
    $stmt = $pdo->prepare("
        INSERT INTO users 
        (name, email, password_hash, is_admin, created_at) 
        VALUES (?, ?, ?, 1, NOW())
    ");
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    $stmt->execute(['System Admin', $admin_email, $hashed_password]);
    
    echo "Admin account created successfully!<br>";
    echo "Email: $admin_email<br>";
    echo "Password: $admin_password<br>";
    echo "<strong>Change this password immediately after login!</strong>";
} else {
    echo "Admin account already exists";
}
?>