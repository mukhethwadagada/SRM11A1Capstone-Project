<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $name = trim($_POST['name']);
    $household_size = (int)$_POST['household_size'];
    $account_number = trim($_POST['account_number']);
    $address = trim($_POST['address']);
    
    $stmt = $pdo->prepare("UPDATE users 
                          SET name = ?, household_size = ?, account_number = ?, address = ?
                          WHERE id = ?");
    $stmt->execute([$name, $household_size, $account_number, $address, $user_id]);
    
    $_SESSION['name'] = $name;
    $_SESSION['success'] = "Profile updated successfully!";
}

header('Location: profile.php');
exit();
?>
