<?php
/**
 * Force Fresh Readings
 * Clears old readings and forces new ones for 5-second interval testing
 */

require_once 'config/database.php';
require_once 'includes/arduino-api.php';

echo "=== Force Fresh Readings ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $pdo = getDatabaseConnection();
    $arduino = new ArduinoBridge();
    
    // Step 1: Clear old readings (keep last 5 for each sensor type)
    echo "1. Cleaning old readings...\n";
    
    // Delete old readings from sensorreadings table, keeping last 5
    $stmt = $pdo->prepare("
        DELETE FROM sensorreadings 
        WHERE ReadingID NOT IN (
            SELECT ReadingID FROM (
                SELECT ReadingID FROM sensorreadings 
                ORDER BY ReadingTime DESC 
                LIMIT 5
            ) as keep_readings
        )
    ");
    $stmt->execute();
    $deleted = $stmt->rowCount();
    echo "   Deleted {$deleted} old readings from sensorreadings table\n";
    echo "\n";
    
    // Step 2: Set 5-second interval
    echo "2. Setting 5-second interval...\n";
    $result = $arduino->setLoggingInterval(0.0833, 1);
    echo "   " . ($result['success'] ? "✅ " . $result['message'] : "❌ " . $result['message']) . "\n\n";
    
    // Step 3: Force immediate readings by updating last reading times
    echo "3. Resetting last reading timestamps...\n";
    // Update the most recent reading in sensorreadings to be 10 seconds ago
    $stmt = $pdo->prepare("
        UPDATE sensorreadings 
        SET ReadingTime = DATE_SUB(NOW(), INTERVAL 10 SECOND)
        ORDER BY ReadingTime DESC 
        LIMIT 1
    ");
    $stmt->execute();
    echo "   Reset last reading timestamp in sensorreadings table\n";
    echo "\n";
    
    // Step 4: Test immediate sync
    echo "4. Testing immediate sync...\n";
    $sensorData = $arduino->getAllSensorData();
    if ($sensorData) {
        echo "   Current readings:\n";
        foreach ($sensorData as $type => $data) {
            $value = $data['value'] ?? 'N/A';
            $unit = ($type === 'temperature') ? '°C' : '%';
            echo "   - {$type}: {$value}{$unit}\n";
        }
        
        echo "\n   Attempting sync...\n";
        $synced = $arduino->syncToDatabase();
        
        if ($synced > 0) {
            echo "   ✅ Successfully synced {$synced} readings!\n";
        } else {
            echo "   ⚠️ No readings synced - checking why...\n";
            
            // Debug: Check what shouldLogReading is doing
            $stmt = $pdo->prepare("SELECT MAX(ReadingTime) as last_reading FROM sensorreadings");
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result && $result['last_reading']) {
                $lastReading = strtotime($result['last_reading']);
                $now = time();
                $secondsPassed = $now - $lastReading;
                echo "   sensorreadings: {$secondsPassed} seconds since last reading\n";
            }
        }
    }
    echo "\n";
    
    // Step 5: Verify results
    echo "5. Verifying results...\n";
    $stmt = $pdo->query("
        SELECT 
            ReadingID,
            Temperature,
            Humidity,
            SoilMoisture,
            ReadingTime,
            TIMESTAMPDIFF(SECOND, ReadingTime, NOW()) as seconds_ago
        FROM sensorreadings
        ORDER BY ReadingTime DESC
        LIMIT 5
    ");
    $recent = $stmt->fetchAll();
    
    if ($recent) {
        echo "   Recent readings from sensorreadings:\n";
        foreach ($recent as $reading) {
            echo sprintf("   - Temp: %s°C, Humidity: %s%%, Soil: %s%% (%d seconds ago)\n",
                $reading['Temperature'],
                $reading['Humidity'],
                $reading['SoilMoisture'],
                $reading['seconds_ago']
            );
        }
    } else {
        echo "   No recent readings found\n";
    }
    
    echo "\n✅ Fresh readings setup complete!\n";
    echo "Now run: php sync_5sec.php\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>