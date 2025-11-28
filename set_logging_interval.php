<?php
/**
 * Set Sensor Logging Interval
 * Quick script to change how often data is saved to database
 * 
 * USAGE: php set_logging_interval.php [interval_in_minutes]
 * Example: php set_logging_interval.php 1
 */

// Check command line argument
if ($argc < 2) {
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║        SET SENSOR LOGGING INTERVAL                         ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "USAGE: php set_logging_interval.php [minutes]\n";
    echo "\n";
    echo "EXAMPLES:\n";
    echo "  php set_logging_interval.php 1    → 1 minute (defense)\n";
    echo "  php set_logging_interval.php 5    → 5 minutes (normal)\n";
    echo "  php set_logging_interval.php 30   → 30 minutes (production)\n";
    echo "\n";
    echo "CURRENT INTERVAL:\n";
    
    try {
        require_once 'config/database.php';
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            SELECT setting_value 
            FROM user_settings 
            WHERE setting_key = 'sensor_logging_interval' 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result) {
            $minutes = floatval($result['setting_value']);
            echo "  → {$minutes} minute(s)\n";
        } else {
            echo "  → Not set (using default: 30 minutes)\n";
        }
    } catch (Exception $e) {
        echo "  → Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    exit(1);
}

$intervalMinutes = floatval($argv[1]);

// Validate interval
if ($intervalMinutes <= 0) {
    echo "ERROR: Interval must be greater than 0\n";
    exit(1);
}

if ($intervalMinutes < 0.1) {
    echo "WARNING: Interval less than 6 seconds (0.1 minutes) may cause issues\n";
}

if ($intervalMinutes > 1440) {
    echo "WARNING: Interval greater than 24 hours (1440 minutes) is unusual\n";
}

try {
    require_once 'config/database.php';
    $pdo = getDatabaseConnection();
    
    // Check if setting exists
    $stmt = $pdo->prepare("
        SELECT id 
        FROM user_settings 
        WHERE setting_key = 'sensor_logging_interval' 
        LIMIT 1
    ");
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Update existing setting
        $stmt = $pdo->prepare("
            UPDATE user_settings 
            SET setting_value = ?, updated_at = NOW() 
            WHERE setting_key = 'sensor_logging_interval'
        ");
        $stmt->execute([$intervalMinutes]);
    } else {
        // Insert new setting
        $stmt = $pdo->prepare("
            INSERT INTO user_settings (setting_key, setting_value, created_at, updated_at) 
            VALUES ('sensor_logging_interval', ?, NOW(), NOW())
        ");
        $stmt->execute([$intervalMinutes]);
    }
    
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║              LOGGING INTERVAL UPDATED                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "New Interval: {$intervalMinutes} minute(s)\n";
    
    // Convert to seconds for display
    $seconds = intval($intervalMinutes * 60);
    if ($seconds >= 60) {
        $display = round($seconds / 60) . ' minute(s)';
    } else {
        $display = $seconds . ' second(s)';
    }
    echo "Equivalent:   {$display}\n";
    
    // Show use case
    if ($intervalMinutes <= 1) {
        echo "Use Case:     Defense / Live Demo\n";
    } elseif ($intervalMinutes <= 10) {
        echo "Use Case:     Normal Monitoring\n";
    } else {
        echo "Use Case:     Production / Long-term Tracking\n";
    }
    
    echo "\n";
    echo "IMPORTANT:\n";
    echo "• Restart sync_sensors_online.php for changes to take effect\n";
    echo "• Arduino still reads every 3 seconds (unchanged)\n";
    echo "• This only controls how often data is SAVED to database\n";
    echo "\n";
    
} catch (Exception $e) {
    echo "\n";
    echo "ERROR: Failed to update interval\n";
    echo "Details: " . $e->getMessage() . "\n";
    echo "\n";
    exit(1);
}
