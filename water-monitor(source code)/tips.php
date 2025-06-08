<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Initialize variables with default values
$user_id = $_SESSION['user_id'] ?? 0;
$tips = [];
$stats = [
    'current' => 0,
    'avg' => 1, // Prevent division by zero
    'tokens' => 0,
    'savings' => 0
];

// Define constants if not already defined in config.php
defined('WATER_COST_PER_LITER') or define('WATER_COST_PER_LITER', 0.015); // Example: R0.015 per liter
defined('TOKENS_PER_100L') or define('TOKENS_PER_100L', 5);
defined('JOJO_TANK_TOKENS') or define('JOJO_TANK_TOKENS', 50);
defined('TAX_REBATE_TOKENS') or define('TAX_REBATE_TOKENS', 30);

// Only try to get data if user is logged in
if ($user_id > 0) {
    $tips = get_personalized_tips($user_id) ?? [];
    $stats = get_user_stats($user_id) ?? $stats;
}

// Calculate total potential savings
$total_savings = array_sum(array_column($tips, 'savings_liters')) ?? 0;
$potential_usage = max(0, $stats['current'] - $total_savings);

$page_title = "Conservation Tips - " . SITE_NAME;
include 'includes/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col text-center">
            <h1 class="display-4 text-primary">Your Personalized Water Conservation Plan</h1>
            <p class="lead">Smart recommendations to help you save water and earn rewards</p>
            
            <!-- Hero image from placeholder service -->
            <img src="https://www.ugallery.com/cdn/shop/products/orig_kira-yustak-acrylic-painting-water-is-life.jpg?v=1692697616" 
                 alt="Water conservation" class="img-fluid rounded mt-3 mb-4" style="max-height: 300px;">
        </div>
    </div>
    
    <div class="row">
        <!-- Main Tips Section -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Your Custom Water Saving Recommendations</h3>
                    <span class="badge bg-light text-primary"><?= count($tips) ?> Active Tips</span>
                </div>
                <div class="card-body">
                    <?php if (!empty($tips)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="45%">Recommendation</th>
                                        <th width="25%" class="text-center">Potential Savings</th>
                                        <th width="30%" class="text-center">Impact Level</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tips as $tip): ?>
                                    <tr>
                                        <td>
                                            <h6 class="mb-1"><?= htmlspecialchars($tip['tip_title'] ?? 'Water saving tip') ?></h6>
                                            <p class="mb-0 text-muted small"><?= htmlspecialchars($tip['tip_text'] ?? 'Implement this to save water') ?></p>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="fw-bold text-success"><?= number_format($tip['savings_liters'] ?? 0, 1) ?>L/day</span>
                                            <div class="progress mt-1" style="height: 5px;">
                                                <div class="progress-bar bg-success" 
                                                     style="width: <?= min(100, (($tip['savings_liters'] ?? 0) / 50) * 100) ?>%">
                                                </div>
                                            </div>
                                            <small class="text-muted">≈ R<?= number_format(($tip['savings_liters'] ?? 0) * 30 * WATER_COST_PER_LITER, 2) ?>/month</small>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php 
                                                $priority = (($tip['savings_liters'] ?? 0) / max(1, $stats['avg'])) * 100;
                                                $priority_class = $priority > 20 ? 'danger' : ($priority > 10 ? 'warning' : 'success');
                                            ?>
                                            <span class="badge bg-<?= $priority_class ?> rounded-pill py-2 px-3">
                                                <?php if ($priority > 20): ?>
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                <?php elseif ($priority > 10): ?>
                                                    <i class="fas fa-info-circle me-1"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-check-circle me-1"></i>
                                                <?php endif; ?>
                                                <?= number_format($priority, 0) ?>% Impact
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <img src="https://images-cdn.ubuy.ae/6502ede3e48d7e1ba25edb15-mr-pen-mechanical-switch-calculator.jpg" 
                                 alt="No tips available" class="img-fluid rounded mt-3 mb-4" style="max-height: 300px;">
                            <h4>Calculate household Water Reading to view water saving tips available</h4>
                            <p>Submit more water usage readings to get customized recommendations</p>
                            <a href="calculator.php" class="btn btn-primary">Submit Water Reading</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Implementation Guide Section -->
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h3 class="mb-0"><i class="fas fa-book me-2"></i>Water Saving Implementation Guide</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" 
                                 alt="Shorter showers" class="img-fluid rounded mb-2">
                            <h5>Shorter Showers</h5>
                            <p>Reducing your shower time by just 2 minutes can save approximately 20 liters of water.</p>
                            <div class="alert alert-light">
                                <i class="fas fa-stopwatch me-2"></i> Use a 5-minute shower timer to track your time.
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <img src="https://images.unsplash.com/photo-1600566752355-35792bedcfea?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" 
                                 alt="Fix leaks" class="img-fluid rounded mb-2">
                            <h5>Fix Leaks</h5>
                            <p>A dripping tap can waste up to 30 liters per day. Most leaks are easy to fix with basic tools.</p>
                            <div class="alert alert-light">
                                <i class="fas fa-tools me-2"></i> Check for leaks monthly and replace washers promptly.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Section -->
        <div class="col-lg-4">
            <!-- Savings Summary -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0"><i class="fas fa-chart-line me-2"></i>Your Savings Potential</h3>
                </div>
                <div class="card-body text-center">
                    <img src="https://previews.123rf.com/images/iqoncept/iqoncept0907/iqoncept090700046/5263227-a-green-button-with-the-word-save-on-it.jpg" 
                         alt="Water savings chart" class="img-fluid mb-3">
                    
                    <div class="d-flex justify-content-between mb-3">
                       
                        <div>
                            <h6 class="text-muted">Potential Usage</h6>
                            <h4 class="text-success"><?= number_format($potential_usage, 1) ?>L</h4>
                        </div>
                    </div>
                    
                    <div class="alert alert-success">
                        <h5><i class="fas fa-coins me-2"></i>Reward Potential</h5>
                        <p>Implementing all recommendations could earn you:</p>
                        <ul class="text-start">
                            <li><strong><?= floor($total_savings / 100 * TOKENS_PER_100L) ?></strong> tokens per day</li>
                            <li>≈ <strong>R<?= number_format($total_savings * 30 * WATER_COST_PER_LITER, 2) ?></strong> monthly savings</li>
                            <li>Faster qualification for municipal rebates</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Rewards Progress -->
            <div class="card shadow mb-4">
                <div class="card-header bg-warning text-dark">
                    <h3 class="mb-0"><i class="fas fa-award me-2"></i>Your Rewards Progress</h3>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <h5 class="mb-0">JoJo Tank (50 tokens)</h5>
                            <span><?= $stats['tokens'] ?>/50</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped bg-success" role="progressbar" 
                                 style="width: <?= min(100, ($stats['tokens'] / JOJO_TANK_TOKENS) * 100) ?>%">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <h5 class="mb-0">5% Tax Rebate (30 tokens)</h5>
                            <span><?= $stats['tokens'] ?>/30</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped bg-info" role="progressbar" 
                                 style="width: <?= min(100, ($stats['tokens'] / TAX_REBATE_TOKENS) * 100) ?>%">
                            </div>
                        </div>
                    </div>
                    
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>