<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Only allow admins
require_admin();

// Get active leaks
$leaks = [];
try {
    $stmt = $pdo->prepare("
        SELECT l.*, u.name, u.email 
        FROM water_leaks l
        JOIN users u ON l.user_id = u.id
        WHERE l.status = 'active'
        ORDER BY l.detected_at DESC
    ");
    $stmt->execute();
    $leaks = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching leaks: " . $e->getMessage();
}

// Internal CSS styles
$internalCSS = "
<style>
    .leak-container {
        margin-top: 2rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .leak-header {
        color: #0077b6;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #00b4d8;
    }
    .leak-card {
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-top: 5px solid #00b4d8;
        margin-bottom: 2rem;
    }
    .leak-card-header {
        background: linear-gradient(to right, #0077b6, #00b4d8);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px 10px 0 0 !important;
    }
    .leak-table {
        width: 100%;
        border-collapse: collapse;
    }
    .leak-table th {
        background-color: #f8f9fa;
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
    }
    .leak-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }
    .leak-table tr:hover {
        background-color: #f8f9fa;
    }
    .badge-high {
        background-color: #dc3545;
    }
    .badge-medium {
        background-color: #ffc107;
        color: #212529;
    }
    .badge-low {
        background-color: #17a2b8;
    }
    .action-btn {
        margin-right: 5px;
        padding: 5px 10px;
        font-size: 0.875rem;
    }
    .no-leaks-alert {
        margin: 1rem;
        border-left: 4px solid #28a745;
    }
    .error-alert {
        margin-bottom: 1.5rem;
        border-left: 4px solid #dc3545;
    }
</style>
";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Water Leaks - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?= $internalCSS ?>
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>

    <div class="leak-container">
        <h2 class="leak-header"><i class="fas fa-tint me-2"></i> Active Water Leaks</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger error-alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="card leak-card">
            <div class="card-header leak-card-header">
                <h5 class="mb-0">Current Active Leaks</h5>
            </div>
            <div class="card-body">
                <?php if (empty($leaks)): ?>
                    <div class="alert alert-success no-leaks-alert">
                        <i class="fas fa-check-circle me-2"></i> No active leaks detected
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table leak-table">
                            <thead>
                                <tr>
                                    <th>Location</th>
                                    <th>Severity</th>
                                    <th>Reported By</th>
                                    <th>Date Detected</th>
                                    <th>Water Loss (L)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaks as $leak): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($leak['location']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= 
                                                $leak['severity'] === 'high' ? 'high' : 
                                                ($leak['severity'] === 'medium' ? 'medium' : 'low') 
                                            ?>">
                                                <?= ucfirst($leak['severity']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($leak['name']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($leak['email']) ?></small>
                                        </td>
                                        <td><?= date('M j, Y g:i A', strtotime($leak['detected_at'])) ?></td>
                                        <td><?= number_format($leak['water_loss']) ?></td>
                                        <td>
                                            <a href="resolve_leak.php?id=<?= $leak['id'] ?>" class="btn btn-success btn-sm action-btn">
                                                <i class="fas fa-check"></i> Resolve
                                            </a>
                                            <a href="leak_details.php?id=<?= $leak['id'] ?>" class="btn btn-info btn-sm action-btn">
                                                <i class="fas fa-info-circle"></i> Details
                                            </a>
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

    <?php include '../includes/admin-footer.php'; ?>
</body>
</html>