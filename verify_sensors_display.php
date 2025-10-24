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
    
    // Check sensor_readings table
    echo "2. Checking sensor_readings table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sensor_readings");
    $readingCount = $stmt->fetch()['count'];
    echo "   Total readings: {$readingCount}\n";
    
    // Check recent readings (last hour)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM sensor_readings 
        WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $recentCount = $stmt->fetch()['count'];
    echo "   Recent readings (1 hour): {$recentCount}\n";
    
    // Check very recent readings (last 5 minutes)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM sensor_readings 
        WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $veryRecentCount = $stmt->fetch()['count'];
    echo "   Very recent readings (5 min): {$veryRecentCount}\n";
    echo "\n";
    
    // Show latest readings by type
    echo "3. Latest readings by sensor type...\n";
    $stmt = $pdo->query("
        SELECT 
            s.sensor_type,
            sr.value,
            sr.unit,
            sr.recorded_at,
            TIMESTAMPDIFF(SECOND, sr.recorded_at, NOW()) as seconds_ago
        FROM sensors s
        JOIN sensor_readings sr ON s.id = sr.sensor_id
        WHERE sr.recorded_at = (
            SELECT MAX(sr2.recorded_at) 
            FROM sensor_readings sr2 
            JOIN sensors s2 ON sr2.sensor_id = s2.id 
            WHERE s2.sensor_type = s.sensor_type
        )
        ORDER BY s.sensor_type
    ");
    $latestReadings = $stmt->fetchAll();
    
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
        SELECT 
            s.id as sensor_id,
            s.sensor_name,
            s.sensor_type,
            s.location,
            sr.value,
            sr.unit,
            sr.recorded_at
        FROM sensors s
        JOIN sensor_readings sr ON s.id = sr.sensor_id
        WHERE sr.recorded_at >= ?
        ORDER BY sr.recorded_at DESC
        LIMIT 10
    ");
    $stmt->execute([$startDate]);
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