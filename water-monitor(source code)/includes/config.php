<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'water_monitor');

// Application settings
define('BASE_URL', 'http://localhost/water-monitor');
define('SITE_NAME', 'Joburg Water Saver');
define('ADMIN_EMAIL', 'admin@watersaver.joburg');

// Water usage thresholds (liters per person per day)
define('BASELINE_USAGE', 100); // 100L/person/day
define('WASTAGE_THRESHOLD', 150); // 150% of baseline triggers penalty
define('SAVINGS_THRESHOLD', 70); // 70% of baseline qualifies for rewards

// Reward system
define('TOKENS_PER_100L', 1); // 1 token per 100L saved
define('JOJO_TANK_TOKENS', 50); // Tokens needed for JoJo tank
define('TAX_REBATE_TOKENS', 30); // Tokens needed for 5% tax rebate

// Create database connection
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Fixed this line
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session
session_start();

// Include other dependencies
require_once 'functions.php';
?>