<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_admin();

// Handle form submissions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['toggle_admin'])) {
            $stmt = $pdo->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
            $message = "User privileges updated";
        }
        elseif (isset($_POST['reset_password'])) {
            $new_password = password_hash('temp123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$new_password, $_POST['user_id']]);
            $message = "Password reset to 'temp123' (user should change immediately)";
        }
        elseif (isset($_POST['deactivate'])) {
            $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
            $message = "User deactivated";
        }
        elseif (isset($_POST['activate'])) {
            $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
            $message = "User activated";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Get all users with their usage stats
$users = $pdo->query("
    SELECT u.id, u.name, u.email, u.created_at, u.is_admin, u.is_active,
           COUNT(w.id) as reading_count,
           COALESCE(SUM(w.liters_used), 0) as total_usage,
           SUM(CASE WHEN w.is_leak THEN 1 ELSE 0 END) as leak_count
    FROM users u
    LEFT JOIN water_usage w ON u.id = w.user_id
    GROUP BY u.id, u.name, u.email, u.created_at, u.is_admin, u.is_active
    ORDER BY u.created_at DESC
")->fetchAll();


$page_title = "User Management - " . SITE_NAME;
include '../includes/admin-header.php';
?>

<div class="container mt-4">
    <?php if ($message): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">User Management</h5>
                <span class="badge bg-light text-dark">
                    Total Users: <?= count($users) ?>
                </span>
            </div>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Stats</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($user['name']) ?></strong>
                                <div class="text-muted small"><?= htmlspecialchars($user['email']) ?></div>
                                <div class="small">
                                    Joined: <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="badge bg-info">Readings: <?= $user['reading_count'] ?></span>
                                    <span class="badge bg-success">Usage: <?= round($user['total_usage']) ?>L</span>
                                    <span class="badge bg-danger">Leaks: <?= $user['leak_count'] ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">User</span>
                                <?php endif; ?>
                                
                                <?php if ($user['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="toggle_admin" class="btn btn-outline-primary" 
                                                title="<?= $user['is_admin'] ? 'Revoke admin' : 'Make admin' ?>">
                                            <i class="fas fa-user-shield"></i>
                                        </button>
                                        
                                        <button type="submit" name="reset_password" class="btn btn-outline-warning"
                                                title="Reset password">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        
                                        <?php if ($user['is_active']): ?>
                                            <button type="submit" name="deactivate" class="btn btn-outline-danger"
                                                    title="Deactivate">
                                                <i class="fas fa-user-slash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="activate" class="btn btn-outline-success"
                                                    title="Activate">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>