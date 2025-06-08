<?php
// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'name' => 'SecureSession',
        'cookie_lifetime' => 86400,
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'use_only_cookies' => 1,
        'gc_maxlifetime' => 1440
    ]);
}

require_once 'config.php';

/**
 * Authentication Functions
 */

function is_logged_in() {
    return isset($_SESSION['user_id'], $_SESSION['ip_address'], $_SESSION['user_agent']) && 
           $_SESSION['ip_address'] === $_SERVER['REMOTE_ADDR'] && 
           $_SESSION['user_agent'] === $_SERVER['HTTP_USER_AGENT'];
}

function is_admin() {
    return is_logged_in() && $_SESSION['is_admin'];
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        $_SESSION['redirect_message'] = "Please login to access this page";
        header('Location: login.php');
        exit();
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        $_SESSION['redirect_message'] = "Administrator privileges required";
        header('Location: dashboard.php');
        exit();
    }
}

/**
 * Session Security Functions
 */

function validate_session() {
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 900) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
    
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        session_destroy();
        header('Location: login.php?message=session_error');
        exit();
    }
    
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_destroy();
        header('Location: login.php?message=session_error');
        exit();
    }
}

/**
 * Login/Logout Functions
 */

function handle_login_attempt($email, $pdo) {
    try {
        // Check if table exists
        $tableExists = $pdo->query("SHOW TABLES LIKE 'login_attempts'")->rowCount() > 0;
        
        if ($tableExists) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts 
                                  WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $stmt->execute([$_SERVER['REMOTE_ADDR']]);
            $attempts = $stmt->fetchColumn();
            
            if ($attempts > 5) {
                error_log("Brute force attempt detected from IP: {$_SERVER['REMOTE_ADDR']}");
                return ["error" => "Too many login attempts. Please try again later."];
            }
            
            $pdo->prepare("INSERT INTO login_attempts (ip_address, email, attempt_time) 
                          VALUES (?, ?, NOW())")->execute([$_SERVER['REMOTE_ADDR'], $email]);
        }
        return null;
    } catch (PDOException $e) {
        error_log("Login attempt tracking error: " . $e->getMessage());
        return null; // Continue with login even if tracking fails
    }
}

function handle_successful_login($user, $pdo) {
    session_regenerate_id(true);
    
    $_SESSION = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'is_admin' => (bool)$user['is_admin'],
        'name' => $user['name'] ?? 'User',
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'last_activity' => time(),
        'created' => time()
    ];
    
    try {
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        
        // Clear failed attempts if table exists
        if ($pdo->query("SHOW TABLES LIKE 'login_attempts'")->rowCount() > 0) {
            $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")
                ->execute([$_SERVER['REMOTE_ADDR']]);
        }
    } catch (PDOException $e) {
        error_log("Login update error: " . $e->getMessage());
    }
}

function handle_logout() {
    // Mark that we're processing logout to prevent loops
    $_SESSION['logout_in_progress'] = true;
    
    // Clear all session data
    $_SESSION = [];
    
    // Destroy the session cookie
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
    
    // Clear remember token cookie if it exists
    if (isset($_COOKIE['remember_token'])) {
        setcookie(
            'remember_token',
            '',
            time() - 42000,
            '/',
            '',
            true,
            true
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page with success message (not logout parameter)
    header('Location: login.php?message=logged_out');
    exit();
}

/**
 * Main Authentication Handler
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $login_error = "Valid email is required";
    } elseif (empty($password)) {
        $login_error = "Password is required";
    } else {
        $brute_check = handle_login_attempt($email, $pdo);
        if (($brute_check)) {
            $login_error = $brute_check['error'];
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                // Default admin credentials
                $default_admin_email = "admin@joburgwatersaver.co.za";
                $default_admin_password = "Admin@1234";
                
                // Check for default admin login
                if ($email === $default_admin_email && $password === $default_admin_password) {
                    if (!$user) {
                        // Create default admin if doesn't exist
                        $hashed_password = password_hash($default_admin_password, PASSWORD_DEFAULT);
                        $pdo->prepare("INSERT INTO users (email, password_hash, name, is_admin) VALUES (?, ?, 'System Admin', 1)")
                           ->execute([$default_admin_email, $hashed_password]);
                        $user = [
                            'id' => $pdo->lastInsertId(), 
                            'email' => $default_admin_email, 
                            'is_admin' => 1,
                            'name' => 'System Admin'
                        ];
                    }
                    $valid_login = true;
                } else {
                    $valid_login = $user && password_verify($password, $user['password_hash']);
                }
                
                if ($valid_login) {
                    if (password_needs_rehash($user['password_hash'] ?? null, PASSWORD_ARGON2ID)) {
                        $new_hash = password_hash($password, PASSWORD_ARGON2ID);
                        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
                            ->execute([$new_hash, $user['id']]);
                    }
                    
                    handle_successful_login($user, $pdo);
                    
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expires = time() + 60*60*24*30;
                        
                        setcookie(
                            'remember_token',
                            $token,
                            $expires,
                            '/',
                            '',
                            true,
                            true
                        );
                        
                        $pdo->prepare("UPDATE users SET 
                                      remember_token = ?,
                                      token_expires = ?
                                      WHERE id = ?")
                            ->execute([$token, date('Y-m-d H:i:s', $expires), $user['id']]);
                    }
                    
                    $redirect = $user['is_admin'] ? 'admin/dashboard.php' : 'dashboard.php';
                    if (isset($_SESSION['redirect_url'])) {
                        $redirect = $_SESSION['redirect_url'];
                        unset($_SESSION['redirect_url']);
                    }
                    header("Location: $redirect");
                    exit();
                } else {
                    $login_error = "Invalid email or password";
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $login_error = "System error. Please try again later.";
            }
        }
    }
}

// Auto-login from remember token
if (!is_logged_in() && isset($_COOKIE['remember_token'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users 
                              WHERE remember_token = ? 
                              AND token_expires > NOW()");
        $stmt->execute([$_COOKIE['remember_token']]);
        $user = $stmt->fetch();
        
        if ($user) {
            handle_successful_login($user, $pdo);
            header("Refresh:0");
            exit();
        } else {
            // Invalid remember token - clear it
            setcookie(
                'remember_token',
                '',
                time() - 42000,
                '/',
                '',
                true,
                true
            );
        }
    } catch (PDOException $e) {
        error_log("Remember token error: " . $e->getMessage());
    }
}

// Handle logout request
if (isset($_GET['logout']) && !isset($_SESSION['logout_in_progress'])) {
    handle_logout();
}

if (is_logged_in()) {
    validate_session();
    $_SESSION['last_activity'] = time();
    
    // Check for idle timeout (30 minutes)
    if (time() - $_SESSION['last_activity'] > 1800) {
        handle_logout();
    }
}