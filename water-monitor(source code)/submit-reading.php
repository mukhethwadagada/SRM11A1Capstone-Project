<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';// Instead of auth_redirect.php
require_login();

$user_id = $_SESSION['user_id'];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $liters = (float)$_POST['liters'];
    $photo = null;
    
    // Handle photo upload
    if (isset($_FILES['meter_photo']) && $_FILES['meter_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/meter_photos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $ext = pathinfo($_FILES['meter_photo']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['meter_photo']['tmp_name'], $target_path)) {
            $photo = $target_path;
        }
    }
    
    // Check for leaks
    $is_leak = check_water_usage($user_id, $liters);
    
    // Save reading
    $stmt = $pdo->prepare("INSERT INTO water_usage 
                          (user_id, reading_date, liters_used, meter_photo, is_leak) 
                          VALUES (?, CURDATE(), ?, ?, ?)");
    $stmt->execute([$user_id, $liters, $photo, $is_leak]);
    
    $success = true;
    
    // Update neighborhood stats
    update_neighborhood_stats();
}

$page_title = "Submit Reading - " . SITE_NAME;
include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Submit Water Meter Reading</h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <h5>Reading submitted successfully!</h5>
                            <p class="mb-0">Thank you for helping Johannesburg conserve water.</p>
                        </div>
                        <a href="dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
                    <?php else: ?>
                        <form method="POST" action="submit-reading.php" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="liters" class="form-label">Current Meter Reading (Liters)</label>
                                <input type="number" step="0.01" class="form-control" id="liters" name="liters" required>
                                <div class="form-text">Enter the total liters shown on your water meter</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="meter_photo" class="form-label">Meter Photo (Optional)</label>
                                <input type="file" class="form-control" id="meter_photo" name="meter_photo" accept="image/*">
                                <div class="form-text">Upload a clear photo of your water meter for verification</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Submit Reading</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>