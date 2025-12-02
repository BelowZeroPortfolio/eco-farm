<?php
/**
 * Verify Sensors Display
 * Checks if sensors.php will have data to display
 */

require_once 'config/database.php';

echo "=== Sensors Display Verification ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $pdo = getDatabaseConnection();
    
    // Check if sensors table has records
    echo "1. Checking sensors table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sensors");
    $sensorCount = $stmt->fetch()['count'];
    echo "   Sensors registered: {$sensorCount}\n";
    
    if ($sensorCount > 0) {
        $stmt = $pdo->query("SELECT sensor_name, sensor_type, status FROM sensors LIMIT 5");
        $sensors = $stmt->fetchAll();
        foreach ($sensors as $sensor) {
            echo "   - {$sensor['sensor_name']} ({$sensor['sensor_type']}) - {$sensor['status']}\n";
        }
    }
    echo "\n";
    
    // Check sensorreadings table
    echo "2. Checking sensorreadings table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sensorreadings");
    $readingCount = $stmt->fetch()['count'];
    echo "   Total readings: {$readingCount}\n";
    
    // Check recent readings (last hour)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM sensorreadings 
        WHERE ReadingTime >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $recentCount = $stmt->fetch()['count'];
    echo "   Recent readings (1 hour): {$recentCount}\n";
    
    // Check very recent readings (last 5 minutes)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM sensorreadings 
        WHERE ReadingTime >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $veryRecentCount = $stmt->fetch()['count'];
    echo "   Very recent readings (5 min): {$veryRecentCount}\n";
    echo "\n";
    
    // Show latest readings by type
    echo "3. Latest readings by sensor type...\n";
    $stmt = $pdo->query("
        SELECT 
            'temperature' as sensor_type,
            Temperature as value,
            '°C' as unit,
            ReadingTime as recorded_at,
            TIMESTAMPDIFF(SECOND, ReadingTime, NOW()) as seconds_ago
        FROM sensorreadings
        ORDER BY ReadingTime DESC
        LIMIT 1
    ");
    $tempReading = $stmt->fetch();
    
    $stmt = $pdo->query("
        SELECT 
            'humidity' as sensor_type,
            Humidity as value,
            '%' as unit,
            ReadingTime as recorded_at,
            TIMESTAMPDIFF(SECOND, ReadingTime, NOW()) as seconds_ago
        FROM sensorreadings
        ORDER BY ReadingTime DESC
        LIMIT 1
    ");
    $humidityReading = $stmt->fetch();
    
    $stmt = $pdo->query("
        SELECT 
            'soil_moisture' as sensor_type,
            SoilMoisture as value,
            '%' as unit,
            ReadingTime as recorded_at,
            TIMESTAMPDIFF(SECOND, ReadingTime, NOW()) as seconds_ago
        FROM sensorreadings
        ORDER BY ReadingTime DESC
        LIMIT 1
    ");
    $soilReading = $stmt->fetch();
    
    $latestReadings = array_filter([$tempReading, $humidityReading, $soilReading]);
    
    if ($latestReadings) {
        foreach ($latestReadings as $reading) {
            echo sprintf("   %s: %s%s (%d seconds ago)\n",
                $reading['sensor_type'],
                $reading['value'],
                $reading['unit'],
                $reading['seconds_ago']
            );
        }
    } else {
        echo "   No readings found\n";
    }
    echo "\n";
    
    // Check what sensors.php will display (simulate the query)
    echo "4. Simulating sensors.php data query...\n";
    $startDate = date('Y-m-d H:i:s', strtotime('-24 hours'));
    
    $stmt = $pdo->prepare("
        SELECT * FROM (
            SELECT 
                30 as sensor_id,
                'Arduino Temperature Sensor' as sensor_name,
                'temperature' as sensor_type,
                'Farm Field' as location,
                Temperature as value,
                '°C' as unit,
                ReadingTime as recorded_at
            FROM sensorreadings
            WHERE ReadingTime >= ?
            UNION ALL
            SELECT 
                31 as sensor_id,
                'Arduino Humidity Sensor' as sensor_name,
                'humidity' as sensor_type,
                'Farm Field' as location,
                Humidity as value,
                '%' as unit,
                ReadingTime as recorded_at
            FROM sensorreadings
            WHERE ReadingTime >= ?
            UNION ALL
            SELECT 
                32 as sensor_id,
                'Arduino Soil Moisture Sensor' as sensor_name,
                'soil_moisture' as sensor_type,
                'Farm Field' as location,
                SoilMoisture as value,
                '%' as unit,
                ReadingTime as recorded_at
            FROM sensorreadings
            WHERE ReadingTime >= ?
        ) combined
        ORDER BY recorded_at DESC
        LIMIT 10
    ");
    $stmt->execute([$startDate, $startDate, $startDate]);
    $displayData = $stmt->fetchAll();
    
    echo "   Data for sensors.php (last 24h, limit 10):\n";
    if ($displayData) {
        foreach ($displayData as $i => $record) {
            echo sprintf("   %d. %s: %s%s at %s\n",
                $i + 1,
                $record['sensor_type'],
                $record['value'],
                $record['unit'],
                date('M j, g:i A', strtotime($record['recorded_at']))
            );
        }
    } else {
        echo "   ❌ No data available for display\n";
    }
    echo "\n";
    
    // Summary
    echo "=== Summary ===\n";
    echo "Sensors registered: " . ($sensorCount > 0 ? "✅ {$sensorCount}" : "❌ None") . "\n";
    echo "Total readings: " . ($readingCount > 0 ? "✅ {$readingCount}" : "❌ None") . "\n";
    echo "Recent data: " . ($recentCount > 0 ? "✅ {$recentCount}" : "❌ None") . "\n";
    echo "Display ready: " . (count($displayData) > 0 ? "✅ Yes" : "❌ No data") . "\n";
    
    if (count($displayData) == 0) {
        echo "\n🔧 To fix:\n";
        echo "1. Run: php test_5sec_system.php\n";
        echo "2. Run: php sync_5sec.php (in background)\n";
        echo "3. Wait 30 seconds, then check sensors.php\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>