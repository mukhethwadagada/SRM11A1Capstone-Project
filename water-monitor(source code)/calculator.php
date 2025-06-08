<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$page_title = "Water Usage Calculator";
include 'includes/header.php';

// Initialize variables
$meter_no = $current_reading = $prev_reading = $num_people = '';
$usage = $per_person_usage = $tips = '';
$show_results = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $meter_no = trim($_POST['meter_no']);
    $current_reading = trim($_POST['current_reading']);
    $prev_reading = trim($_POST['prev_reading']);
    $num_people = trim($_POST['num_people']);
    
    // Validate inputs
    if (is_numeric($current_reading) && is_numeric($prev_reading) && is_numeric($num_people)) {
        $usage = $current_reading - $prev_reading;
        $per_person_usage = ($num_people > 0) ? $usage / $num_people : 0;
        $show_results = true;
        
        // Generate tips based on usage
        $tips = generate_water_tips($usage, $per_person_usage, $num_people);
    }
}

function generate_water_tips($usage, $per_person_usage, $num_people) {
    $tips = [];
    
    // General tips based on total usage
    if ($usage > 20000) {
        $tips[] = "Your household is using <strong>".number_format($usage)." liters</strong> which is very high. Consider installing a rainwater harvesting system.";
    } elseif ($usage > 15000) {
        $tips[] = "At <strong>".number_format($usage)." liters</strong>, your usage is above average. Check for leaks and reduce irrigation water.";
    } else {
        $tips[] = "Good job! Your household uses <strong>".number_format($usage)." liters</strong>, which is below the national average.";
    }
    
    // Per person tips
    if ($per_person_usage > 200) {
        $tips[] = "Each person uses <strong>".number_format($per_person_usage, 1)." liters</strong> daily - try shorter showers and turning off taps when brushing.";
    } elseif ($per_person_usage > 150) {
        $tips[] = "Your per person usage of <strong>".number_format($per_person_usage, 1)." liters</strong> is moderate. Fix any dripping taps to save more.";
    } else {
        $tips[] = "Excellent! Only <strong>".number_format($per_person_usage, 1)." liters</strong> per person - you're a water conservation champion!";
    }
    
    // Household size specific tips
    if ($num_people > 4) {
        $tips[] = "For your large household, consider installing a <strong>JoJo tank</strong> to supplement your water supply.";
    }
    
    // Random bonus tip
    $bonus_tips = [
        "Install aerators on faucets to reduce flow by 30% without noticing a difference.",
        "Water plants early morning or evening to reduce evaporation.",
        "Collect shower warm-up water in a bucket for plant watering.",
        "Only run full loads in your washing machine and dishwasher.",
        "Consider replacing old toilets with low-flow models (6 liters/flush vs 13 liters)."
    ];
    $tips[] = "Pro Tip: " . $bonus_tips[array_rand($bonus_tips)];
    
    return $tips;
}
?>

<!-- Blue-themed Calculator -->
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-calculator mr-2"></i> Water Usage Calculator</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="meter_no" class="form-label">Meter Number</label>
                                <input type="text" class="form-control" id="meter_no" name="meter_no" value="<?php echo htmlspecialchars($meter_no); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="num_people" class="form-label">Number of People in Household</label>
                                <input type="number" class="form-control" id="num_people" name="num_people" min="1" value="<?php echo htmlspecialchars($num_people); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="prev_reading" class="form-label">Previous Meter Reading (liters)</label>
                                <input type="number" class="form-control" id="prev_reading" name="prev_reading" min="0" step="100" value="<?php echo htmlspecialchars($prev_reading); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="current_reading" class="form-label">Current Meter Reading (liters)</label>
                                <input type="number" class="form-control" id="current_reading" name="current_reading" min="<?php echo htmlspecialchars($prev_reading); ?>" step="100" value="<?php echo htmlspecialchars($current_reading); ?>" required>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-tint mr-2"></i> Calculate Usage
                            </button>
                        </div>
                    </form>
                    
                    <?php if ($show_results): ?>
                    <div class="results-section mt-5">
                        <div class="alert alert-info">
                            <h4 class="alert-heading"><i class="fas fa-info-circle mr-2"></i> Meter <?php echo htmlspecialchars($meter_no); ?> Results</h4>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Total Water Used:</strong> <?php echo number_format($usage); ?> liters</p>
                                    <p><strong>Usage per Person:</strong> <?php echo number_format($per_person_usage, 1); ?> liters</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Household Size:</strong> <?php echo htmlspecialchars($num_people); ?> people</p>
                                    <p><strong>Billing Period:</strong> <?php echo date('F Y'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tips-section mt-4">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h4 class="mb-0"><i class="fas fa-lightbulb mr-2"></i> Personalized Water Saving Tips</h4>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($tips as $tip): ?>
                                        <li class="list-group-item">
                                            <i class="fas fa-check-circle text-success mr-2"></i> <?php echo $tip; ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="submit-reading.php" class="btn btn-outline-primary">
                                    <i class="fas fa-cloud-upload-alt mr-2"></i> Submit This Reading
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        border-radius: 10px;
        overflow: hidden;
    }
    
    .card-header {
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .form-control {
        border-radius: 8px;
        padding: 10px 15px;
    }
    
    .btn-primary {
        background-color: #0077b6;
        border-color: #0077b6;
        border-radius: 8px;
        padding: 10px 25px;
    }
    
    .btn-primary:hover {
        background-color: #025b8c;
        border-color: #025b8c;
    }
    
    .list-group-item {
        padding: 15px;
        border-left: 0;
        border-right: 0;
    }
    
    .list-group-item:first-child {
        border-top: 0;
    }
    
    .list-group-item:last-child {
        border-bottom: 0;
    }
</style>

<?php include 'includes/footer.php'; ?>