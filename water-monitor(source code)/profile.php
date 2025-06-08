<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_login();

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get rewards
$rewards = $pdo->prepare("SELECT * FROM user_rewards WHERE user_id = ? ORDER BY reward_date DESC");
$rewards->execute([$user_id]);

// Get penalties
$penalties = $pdo->prepare("SELECT * FROM penalties WHERE user_id = ? ORDER BY penalty_date DESC");
$penalties->execute([$user_id]);

$page_title = "My Profile - " . SITE_NAME;
include 'includes/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h2>My Profile</h2>
            <p class="text-muted">Manage your account and view your rewards</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Account Details</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar-circle bg-primary text-white mb-2">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </div>
                        <h4><?= htmlspecialchars($user['name']) ?></h4>
                        <p class="text-muted">Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>
                    </div>
                    
                    <dl class="row">
                        <dt class="col-sm-5">Email</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($user['email']) ?></dd>
                        
                        <dt class="col-sm-5">Household Size</dt>
                        <dd class="col-sm-7"><?= $user['household_size'] ?> person<?= $user['household_size'] > 1 ? 's' : '' ?></dd>
                        
                        <dt class="col-sm-5">Account Number</dt>
                        <dd class="col-sm-7"><?= $user['account_number'] ?: 'Not provided' ?></dd>
                        
                        <dt class="col-sm-5">Address</dt>
                        <dd class="col-sm-7"><?= $user['address'] ? nl2br(htmlspecialchars($user['address'])) : 'Not provided' ?></dd>
                    </dl>
                    
                    <a href="#" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        Edit Profile
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">My Rewards</h5>
                </div>
                <div class="card-body">
                    <?php if ($rewards->rowCount() > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reward</th>
                                        <th>Value</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($reward = $rewards->fetch()): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($reward['reward_date'])) ?></td>
                                        <td>
                                            <?php 
                                                $reward_names = [
                                                    'jojo_tank' => 'JoJo Tank',
                                                    'tax_rebate' => 'Tax Rebate',
                                                    'badge' => 'Water Hero Badge'
                                                ];
                                                echo $reward_names[$reward['reward_type']];
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($reward['reward_value']): ?>
                                                R<?= number_format($reward['reward_value'], 2) ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $reward['status'] == 'awarded' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($reward['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No rewards yet. Save more water to qualify!</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Penalties & Warnings</h5>
                </div>
                <div class="card-body">
                    <?php if ($penalties->rowCount() > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($penalty = $penalties->fetch()): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($penalty['penalty_date'])) ?></td>
                                        <td><?= ucfirst($penalty['penalty_type']) ?></td>
                                        <td>
                                            <?php if ($penalty['amount']): ?>
                                                R<?= number_format($penalty['amount'], 2) ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $penalty['status'] == 'applied' ? 'danger' : 'warning' ?>">
                                                <?= ucfirst($penalty['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No penalties recorded. Keep up the good work!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="update-profile.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" 
                               value="<?= htmlspecialchars($user['name']) ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_household" class="form-label">Household Size</label>
                        <select class="form-select" id="edit_household" name="household_size">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>" <?= $user['household_size'] == $i ? 'selected' : '' ?>>
                                    <?= $i ?> person<?= $i > 1 ? 's' : '' ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_account" class="form-label">Municipal Account No.</label>
                        <input type="text" class="form-control" id="edit_account" name="account_number"
                               value="<?= htmlspecialchars($user['account_number']) ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Address</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="3"><?= 
                            htmlspecialchars($user['address']) ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>