<?php
/**
 * 5-Second Background Sync Script
 * Simplified version for 5-second interval testing
 */

require_once 'config/database.php';
require_once 'includes/arduino-api.php';

// Prevent script timeout
set_time_limit(0);

echo "5-Second Arduino Sync Started at " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 50) . "\n";

$arduino = new ArduinoBridge();
$loopCount = 0;

// Set 5-second interval if not already set
$intervalResult = $arduino->setLoggingInterval(0.0833, 1);
echo "Interval setting: " . ($intervalResult['success'] ? "✅ 5 seconds" : "❌ Failed") . "\n";
echo str_repeat('-', 30) . "\n";

while (true) {
    $loopCount++;
    $currentTime = date('H:i:s');
    
    try {
        // Check Arduino health
        if (!$arduino->isHealthy()) {
            echo "[{$currentTime}] ❌ Arduino not healthy, retrying...\n";
            sleep(5);
            continue;
        }
        
        // Get current sensor data
        $sensorData = $arduino->getAllSensorData();
        
        if ($sensorData) {
            // Display current readings
            echo "[{$currentTime}] 📊 ";
            foreach ($sensorData as $type => $data) {
                $value = $data['value'] ?? 'N/A';
                $unit = ($type === 'temperature') ? '°C' : '%';
                echo "{$type}:{$value}{$unit} ";
            }
            
            // Try to sync to database
            $synced = $arduino->syncToDatabase();
            
            if ($synced > 0) {
                echo "→ ✅ Logged {$synced} readings\n";
            } else {
                echo "→ ⏳ Waiting for interval\n";
            }
        } else {
            echo "[{$currentTime}] ❌ No sensor data available\n";
        }
        
        // Status every 10 loops
        if ($loopCount % 10 == 0) {
            echo "[{$currentTime}] 💓 Loop #{$loopCount} - System running\n";
        }
        
        // Sleep for 3 seconds (check every 3 seconds, log every 5 seconds)
        sleep(3);
        
    } catch (Exception $e) {
        echo "[{$currentTime}] ❌ Error: " . $e->getMessage() . "\n";
        sleep(5);
    }
}
?>