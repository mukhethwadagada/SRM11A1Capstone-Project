<?php
// Ensure no direct access to this file
defined('BASE_PATH') or die('No direct script access allowed');

// Check if required files exist before including
if (!file_exists('config.php') || !file_exists('auth.php')) {
    die('Required system files are missing. Please contact administrator.');
}

require_once 'config.php';
require_once 'auth.php';

function redirect_if_not_logged_in() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    if (!is_logged_in()) {
        // Store current URL for redirect back after login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        $_SESSION['redirect_message'] = "Please login to access this page";
        
        // Prevent session fixation attacks
        session_regenerate_id(true);
        
        header('Location: login.php');
        exit();
    }
}

function redirect_if_not_admin() {
    redirect_if_not_logged_in();
    
    if (!is_admin()) {
        // Log unauthorized access attempt
        error_log("Unauthorized admin access attempt by user ID: " . $_SESSION['user_id']);
        
        $_SESSION['redirect_message'] = "Admin access required";
        
        // Prevent session fixation attacks
        session_regenerate_id(true);
        
        header('Location: ../login.php');
        exit();
    }
}

// Add CSRF protection function
function validate_csrf_token() {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("CSRF token validation failed for IP: " . $_SERVER['REMOTE_ADDR']);
        die("Security error: Invalid CSRF token");
    }
}

// Generate and store CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}