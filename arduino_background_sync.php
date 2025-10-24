<?php

/**
 * Arduino Background Sync Service
 * Continuously syncs Arduino sensor data to database based on logging interval
 * Run this script in the background to maintain database logging
 */

require_once 'config/database.php';
require_once 'includes/arduino-api.php';

// Prevent script timeout
set_time_limit(0);
ini_set('memory_limit', '128M');

// Initialize Arduino bridge
$arduino = new ArduinoBridge();

echo "Arduino Background Sync Service Started\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 60) . "\n";

// Main sync loop
$loopCount = 0;
$lastSyncTime = 0;

while (true) {
    $loopCount++;
    $currentTime = time();

    try {
        // Check if Arduino service is healthy
        if (!$arduino->isHealthy()) {
            echo "[" . date('H:i:s') . "] ❌ Arduino bridge service not healthy, retrying in 30s...\n";
            sleep(30);
            continue;
        }

        // Get current logging interval (in minutes)
        $intervalInfo = $arduino->getLoggingIntervalSetting();
        $intervalMinutes = $intervalInfo['interval_minutes'] ?? 30;
        $intervalSeconds = $intervalMinutes * 60;

        // Check if it's time to sync based on interval
        if (($currentTime - $lastSyncTime) >= $intervalSeconds) {
            echo "[" . date('H:i:s') . "] 🔄 Syncing sensor data (interval: {$intervalInfo['formatted']})...\n";

            // Get current sensor data
            $sensorData = $arduino->getAllSensorData();

            if ($sensorData) {
                echo "[" . date('H:i:s') . "] 📊 Current readings:\n";
                foreach ($sensorData as $sensorType => $data) {
                    $value = $data['value'] ?? 'N/A';
                    $unit = getUnit($sensorType);
                    echo "   - {$sensorType}: {$value}{$unit}\n";
                }

                // Sync to database
                $synced = $arduino->syncToDatabase();

                if ($synced > 0) {
                    echo "[" . date('H:i:s') . "] ✅ Synced {$synced} readings to database\n";
                    $lastSyncTime = $currentTime;
                } else {
                    echo "[" . date('H:i:s') . "] ⚠️ No data synced (interval not reached or no new data)\n";
                }
            } else {
                echo "[" . date('H:i:s') . "] ❌ Failed to get sensor data\n";
            }

            echo str_repeat('-', 40) . "\n";
        }

        // Status update every 10 loops (approximately every 30 seconds)
        if ($loopCount % 10 == 0) {
            echo "[" . date('H:i:s') . "] 💓 Service running (loop #{$loopCount}, next sync in " .
                max(0, $intervalSeconds - ($currentTime - $lastSyncTime)) . "s)\n";
        }

        // Sleep for 3 seconds before next check
        sleep(3);
    } catch (Exception $e) {
        echo "[" . date('H:i:s') . "] ❌ Error: " . $e->getMessage() . "\n";
        echo "[" . date('H:i:s') . "] 🔄 Retrying in 10 seconds...\n";
        sleep(10);
    }
}

/**
 * Get unit for sensor type
 */
function getUnit($sensorType)
{
    $units = [
        'temperature' => '°C',
        'humidity' => '%',
        'soil_moisture' => '%'
    ];

    return $units[$sensorType] ?? '';
}
