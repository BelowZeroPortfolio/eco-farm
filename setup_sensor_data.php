<?php
/**
 * Setup Sensor Data Script
 * This script ensures the database has sample sensor data for testing
 */

require_once 'config/database.php';

try {
    $pdo = getDatabaseConnection();
    
    echo "Setting up sensor data...\n";
    
    // Check if sensors already exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sensors WHERE sensor_name LIKE 'Arduino%'");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "Sample sensors already exist. Cleaning up old data...\n";
        
        // Delete old readings from sensorreadings table and sensors
        $pdo->exec("DELETE FROM sensorreadings WHERE ReadingTime >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $pdo->exec("DELETE FROM sensors WHERE sensor_name LIKE 'Arduino%'");
        
        echo "Old data cleaned up.\n";
    }
    
    // Insert sample sensors
    echo "Inserting sample sensors...\n";
    
    $sensors = [
        ['Arduino Temperature DHT22', 'temperature', 'Farm Field', 2, 20.0, 28.0],
        ['Arduino Humidity DHT22', 'humidity', 'Farm Field', 3, 60.0, 80.0],
        ['Arduino Soil Moisture', 'soil_moisture', 'Farm Field', 10, 40.0, 60.0]
    ];
    
    $sensorIds = [];
    
    foreach ($sensors as $sensor) {
        $stmt = $pdo->prepare("
            INSERT INTO sensors (sensor_name, sensor_type, location, arduino_pin, alert_threshold_min, alert_threshold_max, status, last_reading_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'online', NOW())
        ");
        
        $stmt->execute($sensor);
        $sensorIds[$sensor[1]] = $pdo->lastInsertId();
        echo "Created sensor: {$sensor[0]} (ID: {$sensorIds[$sensor[1]]})\n";
    }
    
    // Insert sample readings
    echo "Inserting sample sensor readings...\n";
    
    $readings = [
        // Temperature readings (Optimal: 20-28°C)
        [$sensorIds['temperature'], 24.5, '°C', 'DATE_SUB(NOW(), INTERVAL 6 HOUR)'],
        [$sensorIds['temperature'], 25.2, '°C', 'DATE_SUB(NOW(), INTERVAL 5 HOUR)'],
        [$sensorIds['temperature'], 24.8, '°C', 'DATE_SUB(NOW(), INTERVAL 4 HOUR)'],
        [$sensorIds['temperature'], 32.0, '°C', 'DATE_SUB(NOW(), INTERVAL 3 HOUR)'], // Warning High
        [$sensorIds['temperature'], 18.0, '°C', 'DATE_SUB(NOW(), INTERVAL 2 HOUR)'], // Warning Low
        [$sensorIds['temperature'], 38.0, '°C', 'DATE_SUB(NOW(), INTERVAL 1 HOUR)'], // Critical High
        [$sensorIds['temperature'], 25.5, '°C', 'NOW()'], // Current - Optimal
        
        // Humidity readings (Optimal: 60-80%)
        [$sensorIds['humidity'], 68.2, '%', 'DATE_SUB(NOW(), INTERVAL 6 HOUR)'],
        [$sensorIds['humidity'], 72.5, '%', 'DATE_SUB(NOW(), INTERVAL 5 HOUR)'],
        [$sensorIds['humidity'], 70.0, '%', 'DATE_SUB(NOW(), INTERVAL 4 HOUR)'],
        [$sensorIds['humidity'], 85.0, '%', 'DATE_SUB(NOW(), INTERVAL 3 HOUR)'], // Warning High
        [$sensorIds['humidity'], 55.0, '%', 'DATE_SUB(NOW(), INTERVAL 2 HOUR)'], // Warning Low
        [$sensorIds['humidity'], 92.0, '%', 'DATE_SUB(NOW(), INTERVAL 1 HOUR)'], // Critical High
        [$sensorIds['humidity'], 70.5, '%', 'NOW()'], // Current - Optimal
        
        // Soil Moisture readings (Optimal: 40-60%)
        [$sensorIds['soil_moisture'], 48.5, '%', 'DATE_SUB(NOW(), INTERVAL 6 HOUR)'],
        [$sensorIds['soil_moisture'], 52.0, '%', 'DATE_SUB(NOW(), INTERVAL 5 HOUR)'],
        [$sensorIds['soil_moisture'], 50.5, '%', 'DATE_SUB(NOW(), INTERVAL 4 HOUR)'],
        [$sensorIds['soil_moisture'], 65.0, '%', 'DATE_SUB(NOW(), INTERVAL 3 HOUR)'], // Warning High
        [$sensorIds['soil_moisture'], 35.0, '%', 'DATE_SUB(NOW(), INTERVAL 2 HOUR)'], // Warning Low
        [$sensorIds['soil_moisture'], 25.0, '%', 'DATE_SUB(NOW(), INTERVAL 1 HOUR)'], // Critical Low
        [$sensorIds['soil_moisture'], 50.0, '%', 'NOW()'] // Current - Optimal
    ];
    
    // Get active plant ID (default to 1)
    $stmt = $pdo->query("SELECT SelectedPlantID FROM activeplant ORDER BY UpdatedAt DESC LIMIT 1");
    $result = $stmt->fetch();
    $plantId = $result ? $result['SelectedPlantID'] : 1;
    
    // Insert sample readings into sensorreadings table
    // Group readings by time interval
    $sampleReadings = [
        ['SoilMoisture' => 48.5, 'Temperature' => 24.5, 'Humidity' => 68.2, 'time' => 'DATE_SUB(NOW(), INTERVAL 6 HOUR)'],
        ['SoilMoisture' => 52.0, 'Temperature' => 25.2, 'Humidity' => 72.5, 'time' => 'DATE_SUB(NOW(), INTERVAL 5 HOUR)'],
        ['SoilMoisture' => 50.5, 'Temperature' => 24.8, 'Humidity' => 70.0, 'time' => 'DATE_SUB(NOW(), INTERVAL 4 HOUR)'],
        ['SoilMoisture' => 65.0, 'Temperature' => 32.0, 'Humidity' => 85.0, 'time' => 'DATE_SUB(NOW(), INTERVAL 3 HOUR)'], // Warning
        ['SoilMoisture' => 35.0, 'Temperature' => 18.0, 'Humidity' => 55.0, 'time' => 'DATE_SUB(NOW(), INTERVAL 2 HOUR)'], // Warning Low
        ['SoilMoisture' => 25.0, 'Temperature' => 38.0, 'Humidity' => 92.0, 'time' => 'DATE_SUB(NOW(), INTERVAL 1 HOUR)'], // Critical
        ['SoilMoisture' => 50.0, 'Temperature' => 25.5, 'Humidity' => 70.5, 'time' => 'NOW()'] // Current - Optimal
    ];
    
    foreach ($sampleReadings as $reading) {
        $stmt = $pdo->prepare("
            INSERT INTO sensorreadings (PlantID, SoilMoisture, Temperature, Humidity, WarningLevel, ReadingTime) 
            VALUES (?, ?, ?, ?, 0, {$reading['time']})
        ");
        $stmt->execute([$plantId, $reading['SoilMoisture'], $reading['Temperature'], $reading['Humidity']]);
    }
    
    echo "Inserted " . count($sampleReadings) . " sensor readings into sensorreadings table.\n";
    
    // Set default logging interval
    echo "Setting default logging interval...\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO user_settings (user_id, setting_key, setting_value) 
        VALUES (1, 'sensor_logging_interval', '30')
        ON DUPLICATE KEY UPDATE setting_value = '30'
    ");
    $stmt->execute();
    
    echo "Default logging interval set to 30 minutes.\n";
    
    // Verify data
    echo "\nVerifying inserted data:\n";
    
    $stmt = $pdo->query("
        SELECT 
            'Arduino Temperature Sensor' as sensor_name,
            'temperature' as sensor_type,
            Temperature as value,
            '°C' as unit,
            CASE 
                WHEN Temperature >= 20 AND Temperature <= 28 THEN 'OPTIMAL'
                WHEN Temperature > 34 OR Temperature < 14 THEN 'CRITICAL'
                ELSE 'WARNING'
            END as Status,
            ReadingTime as recorded_at
        FROM sensorreadings
        ORDER BY ReadingTime DESC
        LIMIT 1
    ");
    $tempReading = $stmt->fetch();
    
    $stmt = $pdo->query("
        SELECT 
            'Arduino Humidity Sensor' as sensor_name,
            'humidity' as sensor_type,
            Humidity as value,
            '%' as unit,
            CASE 
                WHEN Humidity >= 60 AND Humidity <= 80 THEN 'OPTIMAL'
                WHEN Humidity > 90 OR Humidity < 50 THEN 'CRITICAL'
                ELSE 'WARNING'
            END as Status,
            ReadingTime as recorded_at
        FROM sensorreadings
        ORDER BY ReadingTime DESC
        LIMIT 1
    ");
    $humidityReading = $stmt->fetch();
    
    $stmt = $pdo->query("
        SELECT 
            'Arduino Soil Moisture Sensor' as sensor_name,
            'soil_moisture' as sensor_type,
            SoilMoisture as value,
            '%' as unit,
            CASE 
                WHEN SoilMoisture >= 40 AND SoilMoisture <= 60 THEN 'OPTIMAL'
                WHEN SoilMoisture > 70 OR SoilMoisture < 30 THEN 'CRITICAL'
                ELSE 'WARNING'
            END as Status,
            ReadingTime as recorded_at
        FROM sensorreadings
        ORDER BY ReadingTime DESC
        LIMIT 1
    ");
    $soilReading = $stmt->fetch();
    
    $latestReadings = array_filter([$tempReading, $humidityReading, $soilReading]);
    
    echo "\nLatest sensor readings:\n";
    foreach ($latestReadings as $reading) {
        echo "- {$reading['sensor_name']}: {$reading['value']}{$reading['unit']} ({$reading['Status']})\n";
    }
    
    // Count total readings
    $stmt = $pdo->query("SELECT COUNT(*) as reading_count FROM sensorreadings");
    
    $counts = $stmt->fetchAll();
    
    echo "\nTotal readings by sensor type:\n";
    foreach ($counts as $count) {
        echo "- {$count['sensor_type']}: {$count['reading_count']} readings\n";
    }
    
    echo "\n✅ Sample sensor data setup completed successfully!\n";
    echo "You can now view the data in sensors.php\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up sensor data: " . $e->getMessage() . "\n";
    exit(1);
}