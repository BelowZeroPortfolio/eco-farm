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
    
    $sensorTypes = ['temperature', 'humidity', 'soil_moisture'];
    foreach ($sensorTypes as $type) {
        // Get sensor ID
        $stmt = $pdo->prepare("SELECT id FROM sensors WHERE sensor_type = ? AND sensor_name LIKE 'Arduino%'");
        $stmt->execute([$type]);
        $sensor = $stmt->fetch();
        
        if ($sensor) {
            // Delete all but the last 5 readings for this sensor
            $stmt = $pdo->prepare("
                DELETE FROM sensor_readings 
                WHERE sensor_id = ? 
                AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM sensor_readings 
                        WHERE sensor_id = ? 
                        ORDER BY recorded_at DESC 
                        LIMIT 5
                    ) as keep_readings
                )
            ");
            $stmt->execute([$sensor['id'], $sensor['id']]);
            $deleted = $stmt->rowCount();
            echo "   {$type}: Deleted {$deleted} old readings\n";
        }
    }
    echo "\n";
    
    // Step 2: Set 5-second interval
    echo "2. Setting 5-second interval...\n";
    $result = $arduino->setLoggingInterval(0.0833, 1);
    echo "   " . ($result['success'] ? "✅ " . $result['message'] : "❌ " . $result['message']) . "\n\n";
    
    // Step 3: Force immediate readings by updating last reading times
    echo "3. Resetting last reading timestamps...\n";
    foreach ($sensorTypes as $type) {
        $stmt = $pdo->prepare("SELECT id FROM sensors WHERE sensor_type = ? AND sensor_name LIKE 'Arduino%'");
        $stmt->execute([$type]);
        $sensor = $stmt->fetch();
        
        if ($sensor) {
            // Update the most recent reading to be 10 seconds ago
            $stmt = $pdo->prepare("
                UPDATE sensor_readings 
                SET recorded_at = DATE_SUB(NOW(), INTERVAL 10 SECOND)
                WHERE sensor_id = ? 
                ORDER BY recorded_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$sensor['id']]);
            echo "   {$type}: Reset last reading timestamp\n";
        }
    }
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
            foreach ($sensorTypes as $type) {
                $stmt = $pdo->prepare("SELECT id FROM sensors WHERE sensor_type = ? AND sensor_name LIKE 'Arduino%'");
                $stmt->execute([$type]);
                $sensor = $stmt->fetch();
                
                if ($sensor) {
                    $stmt = $pdo->prepare("SELECT MAX(recorded_at) as last_reading FROM sensor_readings WHERE sensor_id = ?");
                    $stmt->execute([$sensor['id']]);
                    $result = $stmt->fetch();
                    
                    if ($result && $result['last_reading']) {
                        $lastReading = strtotime($result['last_reading']);
                        $now = time();
                        $secondsPassed = $now - $lastReading;
                        echo "   {$type}: {$secondsPassed} seconds since last reading\n";
                    }
                }
            }
        }
    }
    echo "\n";
    
    // Step 5: Verify results
    echo "5. Verifying results...\n";
    $stmt = $pdo->query("
        SELECT s.sensor_type, sr.value, sr.unit, sr.recorded_at,
               TIMESTAMPDIFF(SECOND, sr.recorded_at, NOW()) as seconds_ago
        FROM sensors s
        JOIN sensor_readings sr ON s.id = sr.sensor_id
        WHERE s.sensor_name LIKE 'Arduino%'
        ORDER BY sr.recorded_at DESC
        LIMIT 5
    ");
    $recent = $stmt->fetchAll();
    
    if ($recent) {
        echo "   Recent readings:\n";
        foreach ($recent as $reading) {
            echo sprintf("   - %s: %s%s (%d seconds ago)\n",
                $reading['sensor_type'],
                $reading['value'],
                $reading['unit'],
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