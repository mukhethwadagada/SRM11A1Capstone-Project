<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Only allow admins
require_admin();

// Handle reward approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_reward'])) {
    $rewardId = filter_input(INPUT_POST, 'reward_id', FILTER_SANITIZE_NUMBER_INT);
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
        // Update reward status
        $stmt = $pdo->prepare("UPDATE user_rewards SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $rewardId]);
        
        // Add points to user
        $stmt = $pdo->prepare("UPDATE users SET reward_points = reward_points + 100 WHERE id = ?");
        $stmt->execute([$userId]);
        
        $_SESSION['success_message'] = "Reward approved successfully!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error approving reward: " . $e->getMessage();
    }
    
    header("Location: rewards.php");
    exit();
}

// Get pending rewards
$rewards = [];
try {
    $stmt = $pdo->prepare("
    SELECT ur.id, ur.user_id, u.name, u.email, ur.reward_type, ur.reward_date as created_at
    FROM user_rewards ur
    JOIN users u ON ur.user_id = u.id
    WHERE ur.status = 'pending'
    ORDER BY ur.reward_date ASC
");
    $stmt->execute();
    $rewards = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching rewards: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2><i class="fas fa-award me-2"></i> Approve Rewards</h2>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($rewards)): ?>
                <div class="alert alert-info">No pending rewards to approve</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Reward Type</th>
                                <th>Date Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rewards as $reward): ?>
                                <tr>
                                    <td><?= htmlspecialchars($reward['name']) ?></td>
                                    <td><?= htmlspecialchars($reward['email']) ?></td>
                                    <td><?= htmlspecialchars($reward['reward_type']) ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($reward['created_at'])) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="reward_id" value="<?= $reward['id'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $reward['user_id'] ?>">
                                            <button type="submit" name="approve_reward" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <button class="btn btn-danger btn-sm" onclick="rejectReward(<?= $reward['id'] ?>)">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function rejectReward(rewardId) {
    if (confirm('Are you sure you want to reject this reward?')) {
        fetch('reject_reward.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `reward_id=${rewardId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>