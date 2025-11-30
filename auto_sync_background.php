<?php
/**
 * Automatic Background Sync
 * This runs automatically from sensors.php every scan interval
 * Does the same thing as manual_sync_test.php but returns JSON
 */

header('Content-Type: application/json');

// Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

require_once 'config/database.php';
require_once 'includes/plant-monitor-logic.php';

try {
    // Get sensor data from Arduino bridge
    $response = @file_get_contents('http://127.0.0.1:5000/data', false, stream_context_create([
        'http' => ['timeout' => 2]
    ]));
    
    if (!$response) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot connect to Arduino bridge'
        ]);
        exit;
    }
    
    $data = json_decode($response, true);
    if (!$data || $data['status'] !== 'success') {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid response from Arduino bridge'
        ]);
        exit;
    }
    
    $temperature = $data['data']['temperature']['value'] ?? null;
    $humidity = $data['data']['humidity']['value'] ?? null;
    $soilMoisture = $data['data']['soil_moisture']['value'] ?? null;
    
    if ($temperature === null || $humidity === null || $soilMoisture === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Incomplete sensor data'
        ]);
        exit;
    }
    
    // Initialize plant monitor
    $monitor = new PlantMonitor();
    $activePlant = $monitor->getActivePlant();
    
    if (!$activePlant) {
        echo json_encode([
            'success' => false,
            'message' => 'No active plant configured'
        ]);
        exit;
    }
    
    // Get logging interval from settings (in seconds)
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("SELECT setting_value FROM user_settings WHERE setting_key = 'sensor_logging_interval' LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();
    $intervalMinutes = $result ? floatval($result['setting_value']) : 30;
    $intervalSeconds = $intervalMinutes * 60;
    
    // Check if enough time has passed since last reading
    $stmt = $pdo->prepare("
        SELECT ReadingTime, WarningLevel
        FROM sensorreadings 
        WHERE PlantID = ? 
        ORDER BY ReadingTime DESC 
        LIMIT 1
    ");
    $stmt->execute([$activePlant['PlantID']]);
    $lastReading = $stmt->fetch();
    
    $shouldProcess = false;
    $currentViolationCount = 0;
    
    if ($lastReading) {
        $lastReadingTime = strtotime($lastReading['ReadingTime']);
        $currentTime = time();
        $timeDiff = $currentTime - $lastReadingTime;
        
        // Only process if interval has passed
        if ($timeDiff >= $intervalSeconds) {
            $shouldProcess = true;
        } else {
            // Return current violation count without processing
            echo json_encode([
                'success' => true,
                'plant_name' => $activePlant['PlantName'],
                'plant_local_name' => $activePlant['LocalName'],
                'warning_trigger' => $activePlant['WarningTrigger'],
                'consecutive_violations' => intval($lastReading['WarningLevel']),
                'current_violations' => 0,
                'violations' => [],
                'notification_triggered' => false,
                'sensor_data' => [
                    'temperature' => $temperature,
                    'humidity' => $humidity,
                    'soil_moisture' => $soilMoisture
                ],
                'timestamp' => date('Y-m-d H:i:s'),
                'next_check_in' => $intervalSeconds - $timeDiff,
                'skipped' => true,
                'reason' => 'Interval not reached yet'
            ]);
            exit;
        }
    } else {
        // No previous reading, process first one
        $shouldProcess = true;
    }
    
    // Process the sensor reading only if interval has passed
    $result = $monitor->processSensorReading($soilMoisture, $temperature, $humidity);
    
    if (!$result['success']) {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
        exit;
    }
    
    // Return result with all necessary data
    echo json_encode([
        'success' => true,
        'plant_name' => $activePlant['PlantName'],
        'plant_local_name' => $activePlant['LocalName'],
        'warning_trigger' => $activePlant['WarningTrigger'],
        'consecutive_violations' => $result['consecutive_violations'],
        'current_violations' => $result['current_violations'],
        'violations' => $result['violations'],
        'notification_triggered' => $result['notification_triggered'],
        'sensor_data' => [
            'temperature' => $temperature,
            'humidity' => $humidity,
            'soil_moisture' => $soilMoisture
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
