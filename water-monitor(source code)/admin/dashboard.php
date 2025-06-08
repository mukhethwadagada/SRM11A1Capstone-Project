<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_admin();

// Get system statistics
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active_users' => $pdo->query("SELECT COUNT(DISTINCT user_id) FROM water_usage 
                                  WHERE reading_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn(),
    'total_readings' => $pdo->query("SELECT COUNT(*) FROM water_usage")->fetchColumn(),
    'pending_rewards' => $pdo->query("SELECT COUNT(*) FROM user_rewards WHERE status = 'pending'")->fetchColumn(),
    'active_leaks' => $pdo->query("SELECT COUNT(*) FROM water_usage WHERE is_leak = 1")->fetchColumn(),
    'total_savings' => 0 // Will calculate below
];

// Calculate water savings
$usage_data = $pdo->query("
    SELECT u.household_size, w.liters_used 
    FROM water_usage w 
    JOIN users u ON w.user_id = u.id
    WHERE MONTH(reading_date) = MONTH(CURDATE())
")->fetchAll();

foreach ($usage_data as $row) {
    $stats['total_savings'] += (BASELINE_USAGE * $row['household_size']) - $row['liters_used'];
}

// Get recent activity
$recent_activity = $pdo->query("
    SELECT u.name, u.email, w.reading_date, w.liters_used, w.is_leak
    FROM water_usage w
    JOIN users u ON w.user_id = u.id
    ORDER BY w.reading_date DESC
    LIMIT 10
")->fetchAll();

$page_title = "Admin Dashboard - " . SITE_NAME;
include '../includes/admin-header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-tachometer-alt me-2"></i> Admin Dashboard</h2>
            <p class="text-muted">System overview and quick actions</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="card-title"><?= $stats['total_users'] ?></h3>
                    <p class="card-text small mb-0">Total Users</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="card-title"><?= $stats['active_users'] ?></h3>
                    <p class="card-text small mb-0">Active Users</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body text-center">
                    <h3 class="card-title"><?= $stats['total_readings'] ?></h3>
                    <p class="card-text small mb-0">Total Readings</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="card stat-card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3 class="card-title"><?= $stats['pending_rewards'] ?></h3>
                    <p class="card-text small mb-0">Pending Rewards</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="card stat-card bg-danger text-white">
                <div class="card-body text-center">
                    <h3 class="card-title"><?= $stats['active_leaks'] ?></h3>
                    <p class="card-text small mb-0">Active Leaks</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="card stat-card bg-secondary text-white">
                <div class="card-body text-center">
                    <h3 class="card-title"><?= round($stats['total_savings']) ?>L</h3>
                    <p class="card-text small mb-0">Water Saved</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="users.php" class="btn btn-outline-primary text-start">
                            <i class="fas fa-users me-2"></i> Manage Users
                        </a>
                        <a href="rewards.php" class="btn btn-outline-success text-start">
                            <i class="fas fa-award me-2"></i> Approve Rewards
                        </a>
                        <a href="leaks.php" class="btn btn-outline-danger text-start">
                            <i class="fas fa-tint me-2"></i> View Active Leaks
                        </a>
                        <a href="settings.php" class="btn btn-outline-secondary text-start">
                            <i class="fas fa-cog me-2"></i> System Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-3">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($recent_activity as $activity): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?= htmlspecialchars($activity['name']) ?></h6>
                                <small><?= date('M j, g:i a', strtotime($activity['reading_date'])) ?></small>
                            </div>
                            <p class="mb-1">
                                <?= $activity['liters_used'] ?> liters
                                <?php if ($activity['is_leak']): ?>
                                    <span class="badge bg-danger float-end">LEAK</span>
                                <?php endif; ?>
                            </p>
                            <small class="text-muted"><?= htmlspecialchars($activity['email']) ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Water Savings Chart -->
    <div class="row">
        <div class="col">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Monthly Water Savings</h5>
                </div>
                <div class="card-body">
                    <canvas id="savingsChart" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize savings chart
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('savingsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Water Saved (Liters)',
                data: [1200, 1900, 1500, 2000, 1800, <?= round($stats['total_savings']/6) ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Liters Saved'
                    }
                }
            }
        }
    });
});
</script>

<?php include '../includes/admin-footer.php'; ?>