-- Create database
CREATE DATABASE IF NOT EXISTS water_monitor;
USE water_monitor;

-- Users table (updated with username field)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    address TEXT,
    household_size INT DEFAULT 1,
    account_number VARCHAR(50),
    reward_points INT DEFAULT 0,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- System settings table (missing from your schema)
CREATE TABLE system_settings (
    name VARCHAR(50) PRIMARY KEY,
    value VARCHAR(255) NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Water leaks table (missing from your schema)
CREATE TABLE water_leaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    location VARCHAR(100) NOT NULL,
    severity ENUM('low', 'medium', 'high') NOT NULL,
    water_loss DECIMAL(10,2) NOT NULL,
    description TEXT,
    status ENUM('active', 'resolved') DEFAULT 'active',
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    resolved_by INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (resolved_by) REFERENCES users(id)
);

-- Water usage records
CREATE TABLE water_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reading_date DATE NOT NULL,
    liters_used DECIMAL(10,2) NOT NULL,
    meter_photo VARCHAR(255),
    is_leak BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Conservation tips
CREATE TABLE conservation_tips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tip_text TEXT NOT NULL,
    condition_type VARCHAR(50),
    savings_liters DECIMAL(10,2),
    icon_class VARCHAR(50) DEFAULT 'fas fa-tint'
);

-- Penalties and rewards (updated schema)
CREATE TABLE user_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_type ENUM('points', 'jojo_tank', 'tax_rebate', 'badge') NOT NULL,
    reward_value DECIMAL(10,2),
    reward_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

CREATE TABLE penalties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    penalty_type ENUM('surcharge', 'warning') NOT NULL,
    amount DECIMAL(10,2),
    penalty_date DATE NOT NULL,
    status ENUM('pending', 'applied') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Neighborhood averages
CREATE TABLE neighborhood_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    neighborhood VARCHAR(100) NOT NULL,
    avg_usage DECIMAL(10,2) NOT NULL,
    stat_date DATE NOT NULL
);

-- Initial system settings
INSERT INTO system_settings (name, value, description) VALUES
('points_per_leak', '10', 'Reward points for reporting a leak'),
('points_per_report', '5', 'Reward points for submitting a water reading'),
('leak_threshold', '50', 'Minimum liters/hour to be considered a leak (for alerts)'),
('notification_email', 'admin@water-monitor.com', 'Email for system notifications'),
('reward_approval', '1', 'Whether rewards require manual approval');

-- Pre-populate tips
INSERT INTO conservation_tips (tip_text, condition_type, savings_liters, icon_class) VALUES
('Fix dripping faucet', 'leak', 20.00, 'fas fa-tint'),
('Take 5-minute showers instead of 10', 'shower', 75.00, 'fas fa-shower'),
('Install low-flow showerhead', 'shower', 45.00, 'fas fa-shower-head'),
('Turn off tap while brushing teeth', 'general', 15.00, 'fas fa-tooth'),
('Only run full loads in washing machine', 'laundry', 50.00, 'fas fa-tshirt'),
('Water plants in early morning', 'garden', 30.00, 'fas fa-seedling'),
('Use a broom instead of hose to clean driveway', 'garden', 100.00, 'fas fa-broom'),
('Install dual-flush toilet', 'toilet', 40.00, 'fas fa-toilet'),
('Collect rainwater for gardening', 'garden', 80.00, 'fas fa-cloud-rain'),
('Check for silent toilet leaks', 'toilet', 200.00, 'fas fa-toilet-paper');

-- Create initial admin user (password: Admin@123)
INSERT INTO users (username, name, email, password_hash, is_admin) VALUES
('admin', 'System Admin', 'admin@water-monitor.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE);