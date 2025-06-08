<?php
// Calculate user's water usage statistics
function get_user_stats($user_id) {
    global $pdo;
    
    $stats = [
        'today' => 0,
        'week' => 0,
        'month' => 0,
        'avg' => 0,
        'leaks' => 0,
        'savings' => 0,
        'tokens' => 0
    ];
    
    // Get household size
    $stmt = $pdo->prepare("SELECT household_size FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $household = $stmt->fetchColumn() ?: 1;
    
    // Today's usage
    $stmt = $pdo->prepare("SELECT SUM(liters_used) FROM water_usage 
                          WHERE user_id = ? AND reading_date = CURDATE()");
    $stmt->execute([$user_id]);
    $stats['today'] = $stmt->fetchColumn() ?: 0;
    
    // Weekly usage
    $stmt = $pdo->prepare("SELECT SUM(liters_used) FROM water_usage 
                          WHERE user_id = ? AND reading_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stmt->execute([$user_id]);
    $stats['week'] = $stmt->fetchColumn() ?: 0;
    
    // Monthly usage
    $stmt = $pdo->prepare("SELECT SUM(liters_used) FROM water_usage 
                          WHERE user_id = ? AND MONTH(reading_date) = MONTH(CURDATE())");
    $stmt->execute([$user_id]);
    $stats['month'] = $stmt->fetchColumn() ?: 0;
    
    // Average daily usage
    $stmt = $pdo->prepare("SELECT AVG(liters_used) FROM water_usage 
                          WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['avg'] = $stmt->fetchColumn() ?: 0;
    
    // Leak count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM water_usage 
                          WHERE user_id = ? AND is_leak = TRUE");
    $stmt->execute([$user_id]);
    $stats['leaks'] = $stmt->fetchColumn() ?: 0;
    
    // Calculate savings and tokens
    $baseline = BASELINE_USAGE * $household;
    $stats['savings'] = max(0, $baseline - $stats['avg']);
    $stats['tokens'] = floor($stats['savings'] / 100 * TOKENS_PER_100L);
    
    return $stats;
}

// Check for leaks and apply penalties/rewards
function check_water_usage($user_id, $liters) {
    global $pdo;
    
    // Get user info
    $stmt = $pdo->prepare("SELECT household_size, account_number FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $household = $user['household_size'] ?: 1;
    
    // Calculate baseline and thresholds
    $baseline = BASELINE_USAGE * $household;
    $wastage_threshold = $baseline * (WASTAGE_THRESHOLD / 100);
    $savings_threshold = $baseline * (SAVINGS_THRESHOLD / 100);
    
    // Check for leak (spike detection)
    $stmt = $pdo->prepare("SELECT AVG(liters_used) FROM water_usage 
                          WHERE user_id = ? AND reading_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stmt->execute([$user_id]);
    $avg_weekly = $stmt->fetchColumn() ?: $baseline;
    
    $is_leak = ($liters > $avg_weekly * 1.5);
    
    // Apply penalty if wasting water
    if ($liters > $wastage_threshold) {
        $surcharge = ($liters - $wastage_threshold) * 0.01; // R1 per liter over
        
        $stmt = $pdo->prepare("INSERT INTO penalties 
                              (user_id, penalty_type, amount, penalty_date) 
                              VALUES (?, 'surcharge', ?, CURDATE())");
        $stmt->execute([$user_id, $surcharge]);
        
        // In real system, would call city API to apply to bill
    }
    
    // Grant tokens if saving water
    if ($liters < $savings_threshold) {
        $savings = $baseline - $liters;
        $tokens = floor($savings / 100 * TOKENS_PER_100L);
        
        // Check if enough tokens for rewards
        $stmt = $pdo->prepare("SELECT SUM(reward_value) FROM user_rewards 
                              WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$user_id]);
        $pending_tokens = $stmt->fetchColumn() ?: 0;
        
        $total_tokens = $tokens + $pending_tokens;
        
        // Award JoJo tank if enough tokens
        if ($total_tokens >= JOJO_TANK_TOKENS) {
            $stmt = $pdo->prepare("INSERT INTO user_rewards 
                                  (user_id, reward_type, reward_value, reward_date) 
                                  VALUES (?, 'jojo_tank', ?, CURDATE())");
            $stmt->execute([$user_id, JOJO_TANK_TOKENS]);
            $total_tokens -= JOJO_TANK_TOKENS;
        }
        
        // Award tax rebate if enough tokens
        if ($total_tokens >= TAX_REBATE_TOKENS) {
            $stmt = $pdo->prepare("INSERT INTO user_rewards 
                                  (user_id, reward_type, reward_value, reward_date) 
                                  VALUES (?, 'tax_rebate', ?, CURDATE())");
            $stmt->execute([$user_id, TAX_REBATE_TOKENS]);
            $total_tokens -= TAX_REBATE_TOKENS;
        }
    }
    
    return $is_leak;
}

function get_personalized_tips($user_id) {
    global $pdo;
    
    // Get user's usage patterns (modified to use reading_date instead of reading_time)
    $stmt = $pdo->prepare("
        SELECT 
            AVG(liters_used) as avg_usage,
            SUM(is_leak) as leak_count
        FROM water_usage 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $usage = $stmt->fetch();
    
    // Determine which tips are most relevant
    $conditions = [];
    if ($usage['leak_count'] > 0) $conditions[] = 'leak';
    
    // Since we removed time-based analysis, we'll add a general condition
    if ($usage['avg_usage'] > BASELINE_USAGE) {
        $conditions[] = 'general';
    }
    
    // Get matching tips
    if (empty($conditions)) {
        $stmt = $pdo->prepare("SELECT * FROM conservation_tips WHERE condition_type IS NULL");
        $stmt->execute();
    } else {
        $placeholders = implode(',', array_fill(0, count($conditions), '?'));
        $stmt = $pdo->prepare("SELECT * FROM conservation_tips 
                              WHERE condition_type IN ($placeholders) 
                              ORDER BY savings_liters DESC");
        $stmt->execute($conditions);
    }
    
    return $stmt->fetchAll();
}
// Update neighborhood statistics
function update_neighborhood_stats() {
    global $pdo;
    
    // Get all neighborhoods (simplified - in real app would geocode addresses)
    $neighborhoods = ['North', 'South', 'East', 'West', 'Central'];
    
    foreach ($neighborhoods as $neighborhood) {
        // Calculate average usage for this neighborhood
        $stmt = $pdo->prepare("
            SELECT AVG(w.liters_used/u.household_size) as avg_usage
            FROM water_usage w
            JOIN users u ON w.user_id = u.id
            WHERE w.reading_date = CURDATE()
              AND u.address LIKE ?
        ");
        $stmt->execute(["%$neighborhood%"]);
        $avg_usage = $stmt->fetchColumn();
        
        if ($avg_usage) {
            // Insert or update neighborhood stats
            $stmt = $pdo->prepare("
                INSERT INTO neighborhood_stats (neighborhood, avg_usage, stat_date)
                VALUES (?, ?, CURDATE())
                ON DUPLICATE KEY UPDATE avg_usage = ?
            ");
            $stmt->execute([$neighborhood, $avg_usage, $avg_usage]);
        }
    }
}
// Get neighborhood leaderboard
function get_leaderboard() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT u.id, u.name, 
               SUM(w.liters_used) as total_usage,
               u.household_size,
               SUM(w.liters_used)/u.household_size as per_person_usage
        FROM users u
        JOIN water_usage w ON u.id = w.user_id
        WHERE MONTH(w.reading_date) = MONTH(CURDATE())
        GROUP BY u.id
        ORDER BY per_person_usage ASC
        LIMIT 10
    ");
    
    return $stmt->fetchAll();
}
?>
