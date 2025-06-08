<?php
// Start output buffering to prevent header errors
ob_start();

require_once 'includes/config.php';
require_once 'includes/auth.php';

// Ensure we have a session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear session data
$_SESSION = [];

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Clear output buffer
ob_end_clean();

// Redirect with success message
header('Location: login.php?logout=success');
exit();
?>