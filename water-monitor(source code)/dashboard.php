<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_login();

// Get user data safely
$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$stats = get_user_stats($user_id);
$tips = get_personalized_tips($user_id);
$leaderboard = get_leaderboard();

$page_title = "Dashboard - " . SITE_NAME;
include 'includes/header.php';
?>

<div class="container dashboard-container">
    <!-- Welcome Header with Water Animation -->
    <div class="row mb-4">
        <div class="col">
            <div class="welcome-header">
                <h2>Welcome, <?= htmlspecialchars($user_name) ?></h2>
                <p class="text-muted">Track and optimize your water usage</p>
                <div class="water-animation">
                    <div class="water-drop" style="--delay: 0s"></div>
                    <div class="water-drop" style="--delay: 0.5s"></div>
                    <div class="water-drop" style="--delay: 1s"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards with Icons -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card today-usage">
                <div class="card-body">
                    <div class="stat-icon">
                        <i class="fas fa-tint"></i>
                    </div>
                    <h6 class="card-subtitle mb-2 text-muted">Today's Usage</h6>
                    <h3 class="card-title"><?= number_format($stats['today']) ?>L</h3>
                    <div class="progress">
                        <div class="progress-bar bg-<?= ($stats['today'] > BASELINE_USAGE ? 'danger' : 'success') ?>" 
                             style="width: <?= min(100, ($stats['today'] / BASELINE_USAGE) * 100) ?>%"></div>
                    </div>
                    <p class="card-text small mt-2">
                        <?= ($stats['today'] > BASELINE_USAGE ? 'Above' : 'Below') ?> daily target
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card monthly-usage">
                <div class="card-body">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h6 class="card-subtitle mb-2 text-muted">Monthly Usage</h6>
                    <h3 class="card-title"><?= number_format($stats['month']) ?>L</h3>
                    <p class="card-text small">
                        <?= number_format($stats['avg'], 1) ?>L daily average
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card tokens">
                <div class="card-body">
                    <div class="stat-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <h6 class="card-subtitle mb-2 text-muted">Water Tokens</h6>
                    <h3 class="card-title"><?= $stats['tokens'] ?></h3>
                    <div class="token-progress">
                        <div class="progress-bar" 
                             style="width: <?= ($stats['tokens'] / JOJO_TANK_TOKENS) * 100 ?>%"></div>
                    </div>
                    <p class="card-text small mt-2">
                        <?= JOJO_TANK_TOKENS - $stats['tokens'] ?> more for JoJo tank
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card leaks">
                <div class="card-body">
                    <div class="stat-icon">
                        <i class="fas fa-toolbox"></i>
                    </div>
                    <h6 class="card-subtitle mb-2 text-muted">Leaks Detected</h6>
                    <h3 class="card-title"><?= $stats['leaks'] ?></h3>
                    <p class="card-text small text-<?= $stats['leaks'] > 0 ? 'danger' : 'success' ?>">
                        <i class="fas fa-<?= $stats['leaks'] > 0 ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                        <?= $stats['leaks'] > 0 ? 'Check your pipes!' : 'No leaks found' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content Row -->
    <div class="row mb-4">
        <!-- Usage Chart -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-chart-line mr-2"></i> Water Usage History</h5>
                </div>
                <div class="card-body">
                    <canvas id="usageChart" height="250"></canvas>
                    <div class="chart-legend mt-3">
                        <span class="legend-item"><span class="color-indicator usage"></span> Your Usage</span>
                        <span class="legend-item"><span class="color-indicator baseline"></span> Recommended Baseline</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Sidebar -->
        <div class="col-md-4">
            <!-- Quick Actions -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-info text-white">
                    <h5><i class="fas fa-bolt mr-2"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="submit-reading.php" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-cloud-upload-alt mr-2"></i> Submit Meter Reading
                    </a>
                    <a href="tips.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-lightbulb mr-2"></i> Conservation Tips
                    </a>
                    <a href="profile.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-user mr-2"></i> My Profile
                    </a>
                </div>
            </div>
            
            <!-- Personalized Tips -->
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5><i class="fas fa-magic mr-2"></i> Personalized Tip</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($tips)): ?>
                        <div class="tip-card">
                            <div class="tip-icon">
                                <i class="fas fa-<?= $tips[0]['icon'] ?>"></i>
                            </div>
                            <div class="tip-content">
                                <h6><?= htmlspecialchars($tips[0]['tip_text']) ?></h6>
                                <div class="savings-badge">
                                    <i class="fas fa-tint mr-1"></i>
                                    Saves ~<?= $tips[0]['savings_liters'] ?>L/day
                                </div>
                            </div>
                        </div>
                        <a href="tips.php" class="btn btn-sm btn-outline-success w-100 mt-2">
                            More Tips
                        </a>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-info-circle fa-2x mb-2 text-muted"></i>
                            <p class="text-muted">No tips available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Leaderboard -->
    <div class="row">
        <div class="col">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-trophy mr-2"></i> Neighborhood Leaderboard</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th width="15%">Rank</th>
                                    <th width="30%">Name</th>
                                    <th width="25%">Usage</th>
                                    <th width="30%">Savings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaderboard as $index => $user): ?>
                                <tr class="<?= $user['id'] == $user_id ? 'highlight-row' : '' ?>">
                                    <td>
                                        <?php if ($index < 3): ?>
                                            <span class="rank-badge rank-<?= $index + 1 ?>">
                                                <?= $index + 1 ?>
                                            </span>
                                        <?php else: ?>
                                            <?= $index + 1 ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($user['name']) ?>
                                        <?php if ($user['id'] == $user_id): ?>
                                            <span class="badge bg-primary">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="usage-bar-container">
                                            <div class="usage-bar" style="width: <?= min(100, ($user['per_person_usage'] / BASELINE_USAGE) * 100) ?>%"></div>
                                            <span><?= number_format($user['per_person_usage'], 1) ?>L</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="savings-amount">
                                            <?= number_format(BASELINE_USAGE - $user['per_person_usage'], 1) ?>L
                                        </span>
                                        <span class="savings-percent">
                                            (<?= number_format((1 - ($user['per_person_usage'] / BASELINE_USAGE)) * 100, 0) ?>%)
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Water Usage Modal -->
<div class="modal fade" id="usageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Detailed Usage Analysis</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="usageDetails"></div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
}

.welcome-header {
    position: relative;
    padding: 20px;
    background: linear-gradient(135deg, rgba(0,119,182,0.1) 0%, rgba(0,180,216,0.05) 100%);
    border-radius: 10px;
    overflow: hidden;
}

.water-animation {
    position: absolute;
    top: 0;
    right: 20px;
    height: 100%;
    width: 100px;
    overflow: hidden;
}

.water-drop {
    position: absolute;
    top: -20px;
    right: 30px;
    width: 8px;
    height: 8px;
    background: rgba(0, 180, 216, 0.5);
    border-radius: 50%;
    animation: drop 2s linear infinite;
    animation-delay: var(--delay);
}

@keyframes drop {
    0% { transform: translateY(0); opacity: 1; }
    80% { opacity: 1; }
    100% { transform: translateY(100px); opacity: 0; }
}

.stat-card {
    border-radius: 10px;
    border: none;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    color: white;
}

.today-usage .stat-icon { background-color: #0077b6; }
.monthly-usage .stat-icon { background-color: #00b4d8; }
.tokens .stat-icon { background-color: #ff9e00; }
.leaks .stat-icon { background-color: #dc3545; }

.token-progress {
    height: 6px;
    background-color: #f0f0f0;
    border-radius: 3px;
    margin-top: 8px;
}

.token-progress .progress-bar {
    background: linear-gradient(to right, #ff9e00, #ffcc00);
    border-radius: 3px;
}

.chart-legend {
    display: flex;
    justify-content: center;
    gap: 20px;
}

.legend-item {
    display: flex;
    align-items: center;
    font-size: 0.9rem;
    color: #666;
}

.color-indicator {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    margin-right: 5px;
}

.color-indicator.usage { background-color: #007bff; }
.color-indicator.baseline { background-color: #dc3545; }

.tip-card {
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.tip-icon {
    width: 40px;
    height: 40px;
    background-color: rgba(40, 167, 69, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #28a745;
    font-size: 1.2rem;
}

.savings-badge {
    display: inline-block;
    background-color: rgba(0, 180, 216, 0.1);
    color: #00b4d8;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 0.8rem;
    margin-top: 5px;
}

.highlight-row {
    background-color: rgba(0, 119, 182, 0.05) !important;
    font-weight: 500;
}

.rank-badge {
    display: inline-block;
    width: 25px;
    height: 25px;
    border-radius: 50%;
    text-align: center;
    line-height: 25px;
    color: white;
    font-weight: bold;
}

.rank-1 { background-color: #ffc107; }
.rank-2 { background-color: #6c757d; }
.rank-3 { background-color: #cd7f32; }

.usage-bar-container {
    width: 100%;
    height: 20px;
    background-color: #f0f0f0;
    border-radius: 10px;
    position: relative;
    overflow: hidden;
}

.usage-bar {
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    background: linear-gradient(to right, #00b4d8, #0077b6);
    border-radius: 10px;
}

.usage-bar-container span {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    text-align: center;
    line-height: 20px;
    font-size: 0.7rem;
    color: white;
    z-index: 1;
}

.savings-amount {
    font-weight: 500;
    color: #28a745;
}

.savings-percent {
    color: #6c757d;
    font-size: 0.9rem;
}
</style>

<script src="assets/js/chart.js"></script>
<script>
// Initialize usage chart with enhanced options
const ctx = document.getElementById('usageChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column(get_weekly_usage($user_id), 'date')) ?>,
        datasets: [{
            label: 'Your Usage',
            data: <?= json_encode(array_column(get_weekly_usage($user_id), 'liters')) ?>,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true,
            pointBackgroundColor: '#007bff',
            pointRadius: 4,
            pointHoverRadius: 6
        }, {
            label: 'Recommended Baseline',
            data: Array(7).fill(<?= BASELINE_USAGE ?>),
            borderColor: '#dc3545',
            borderDash: [5, 5],
            backgroundColor: 'transparent',
            borderWidth: 1,
            pointRadius: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.raw + ' liters';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Liters',
                    color: '#666'
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        },
        onClick: function(evt, elements) {
            if (elements.length > 0) {
                const index = elements[0].index;
                const date = this.data.labels[index];
                const usage = this.data.datasets[0].data[index];
                
                // Show modal with detailed info
                document.getElementById('usageDetails').innerHTML = `
                    <h6>Detailed Usage for ${date}</h6>
                    <p>Total Usage: <strong>${usage} liters</strong></p>
                    <p>Comparison to baseline: <strong class="${usage > <?= BASELINE_USAGE ?> ? 'text-danger' : 'text-success'}">
                        ${Math.abs(usage - <?= BASELINE_USAGE ?>)} liters ${usage > <?= BASELINE_USAGE ?> ? 'over' : 'under'} target
                    </strong></p>
                    <hr>
                    <h6>Recommendations</h6>
                    <ul>
                        <li>Check for leaks in high-usage areas</li>
                        <li>Review your water schedule</li>
                        <li>Compare with neighborhood average</li>
                    </ul>
                `;
                
                const modal = new bootstrap.Modal(document.getElementById('usageModal'));
                modal.show();
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>