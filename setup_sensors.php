<?php
/**
 * Setup Sensors
 * Ensures the sensors table has the required Arduino sensor records
 */

require_once 'config/database.php';

echo "=== Setting up Arduino Sensors ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $pdo = getDatabaseConnection();
    
    // Define the Arduino sensors we need
    $sensors = [
        [
            'name' => 'Arduino Temperature Sensor',
            'type' => 'temperature',
            'location' => 'Farm Field',
            'pin' => 2,
            'min_threshold' => 20.0,
            'max_threshold' => 28.0
        ],
        [
            'name' => 'Arduino Humidity Sensor',
            'type' => 'humidity',
            'location' => 'Farm Field',
            'pin' => 3,
            'min_threshold' => 60.0,
            'max_threshold' => 80.0
        ],
        [
            'name' => 'Arduino Soil Moisture Sensor',
            'type' => 'soil_moisture',
            'location' => 'Farm Field',
            'pin' => 10,
            'min_threshold' => 40.0,
            'max_threshold' => 60.0
        ]
    ];
    
    foreach ($sensors as $sensor) {
        echo "Setting up {$sensor['name']}...\n";
        
        // Check if sensor already exists
        $stmt = $pdo->prepare("
            SELECT id FROM sensors 
            WHERE sensor_type = ? AND sensor_name LIKE ?
        ");
        $stmt->execute([$sensor['type'], 'Arduino%']);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing sensor
            $stmt = $pdo->prepare("
                UPDATE sensors 
                SET sensor_name = ?, 
                    location = ?, 
                    arduino_pin = ?, 
                    alert_threshold_min = ?, 
                    alert_threshold_max = ?,
                    status = 'online',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $sensor['name'],
                $sensor['location'],
                $sensor['pin'],
                $sensor['min_threshold'],
                $sensor['max_threshold'],
                $existing['id']
            ]);
            echo "   ✅ Updated existing sensor (ID: {$existing['id']})\n";
        } else {
            // Create new sensor
            $stmt = $pdo->prepare("
                INSERT INTO sensors (
                    sensor_name, sensor_type, location, arduino_pin, 
                    alert_threshold_min, alert_threshold_max, 
                    status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'online', NOW())
            ");
            $stmt->execute([
                $sensor['name'],
                $sensor['type'],
                $sensor['location'],
                $sensor['pin'],
                $sensor['min_threshold'],
                $sensor['max_threshold']
            ]);
            $newId = $pdo->lastInsertId();
            echo "   ✅ Created new sensor (ID: {$newId})\n";
        }
    }
    
    echo "\n";
    
    // Verify setup
    echo "Verifying sensor setup...\n";
    $stmt = $pdo->query("
        SELECT sensor_name, sensor_type, arduino_pin, status, 
               alert_threshold_min, alert_threshold_max
        FROM sensors 
        WHERE sensor_name LIKE 'Arduino%'
        ORDER BY sensor_type
    ");
    $allSensors = $stmt->fetchAll();
    
    foreach ($allSensors as $sensor) {
        echo sprintf("   %s (%s) - Pin %d - Status: %s - Thresholds: %.1f-%.1f\n",
            $sensor['sensor_name'],
            $sensor['sensor_type'],
            $sensor['arduino_pin'],
            $sensor['status'],
            $sensor['alert_threshold_min'],
            $sensor['alert_threshold_max']
        );
    }
    
    echo "\n✅ Sensor setup complete!\n";
    echo "Total Arduino sensors: " . count($allSensors) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>