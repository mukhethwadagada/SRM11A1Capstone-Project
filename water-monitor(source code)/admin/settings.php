<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Only allow admins
require_admin();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update reward settings
        if (isset($_POST['update_rewards'])) {
            $pointsPerLeak = filter_input(INPUT_POST, 'points_per_leak', FILTER_SANITIZE_NUMBER_INT);
            $pointsPerReport = filter_input(INPUT_POST, 'points_per_report', FILTER_SANITIZE_NUMBER_INT);
            
            $stmt = $pdo->prepare("UPDATE system_settings SET value = ? WHERE name = 'points_per_leak'");
            $stmt->execute([$pointsPerLeak]);
            
            $stmt = $pdo->prepare("UPDATE system_settings SET value = ? WHERE name = 'points_per_report'");
            $stmt->execute([$pointsPerReport]);
            
            $_SESSION['success_message'] = "Reward settings updated successfully!";
        }
        
        // Update system settings
        if (isset($_POST['update_system'])) {
            $leakThreshold = filter_input(INPUT_POST, 'leak_threshold', FILTER_SANITIZE_NUMBER_INT);
            $notificationEmail = filter_input(INPUT_POST, 'notification_email', FILTER_SANITIZE_EMAIL);
            
            $stmt = $pdo->prepare("UPDATE system_settings SET value = ? WHERE name = 'leak_threshold'");
            $stmt->execute([$leakThreshold]);
            
            $stmt = $pdo->prepare("UPDATE system_settings SET value = ? WHERE name = 'notification_email'");
            $stmt->execute([$notificationEmail]);
            
            $_SESSION['success_message'] = "System settings updated successfully!";
        }
        
        header("Location: settings.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Get current settings
$settings = [];
try {
    $stmt = $pdo->prepare("SELECT name, value FROM system_settings");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $error = "Error fetching settings: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2><i class="fas fa-cog me-2"></i> System Settings</h2>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Reward Settings</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="points_per_leak" class="form-label">Points per leak detected</label>
                            <input type="number" class="form-control" id="points_per_leak" name="points_per_leak" 
                                   value="<?= htmlspecialchars($settings['points_per_leak'] ?? 10) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="points_per_report" class="form-label">Points per report submitted</label>
                            <input type="number" class="form-control" id="points_per_report" name="points_per_report" 
                                   value="<?= htmlspecialchars($settings['points_per_report'] ?? 5) ?>" required>
                        </div>
                        <button type="submit" name="update_rewards" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Reward Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">System Configuration</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="leak_threshold" class="form-label">Leak Threshold (liters/hour)</label>
                            <input type="number" class="form-control" id="leak_threshold" name="leak_threshold" 
                                   value="<?= htmlspecialchars($settings['leak_threshold'] ?? 50) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="notification_email" class="form-label">Notification Email</label>
                            <input type="email" class="form-control" id="notification_email" name="notification_email" 
                                   value="<?= htmlspecialchars($settings['notification_email'] ?? '') ?>" required>
                        </div>
                        <button type="submit" name="update_system" class="btn btn-info text-white">
                            <i class="fas fa-save me-1"></i> Save System Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>