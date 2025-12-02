<?php
/**
 * Automatic Background Sync
 * This runs automatically from sensors.php every scan interval
 * Does the same thing as manual_sync_test.php but returns JSON
 */

// Suppress HTML error output - always return JSON
error_reporting(0);
ini_set('display_errors', 0);

// Catch fatal errors and return JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error: ' . $error['message']
        ]);
    }
});

header('Content-Type: application/json');

// Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

try {
    require_once 'config/database.php';
    require_once 'config/env.php';
    require_once 'includes/plant-monitor-logic.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Include error: ' . $e->getMessage()]);
    exit;
}

try {
    // Determine Arduino bridge URL based on environment
    $isLocal = (isset($_SERVER['HTTP_HOST']) && 
                (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
                 strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false));
    
    if ($isLocal) {
        // Local environment - use localhost
        $arduinoUrl = 'http://127.0.0.1:5001/data';
        $headers = "User-Agent: Mozilla/5.0\r\n";
    } else {
        // Online environment - use ngrok tunnel from env.php
        $protocol = Env::get('ARDUINO_SENSOR_PROTOCOL', 'https');
        $host = Env::get('ARDUINO_SENSOR_HOST', 'fredda-unprecisive-unashamedly.ngrok-free.dev');
        $arduinoUrl = "{$protocol}://{$host}/data";
        // Ngrok requires this header to bypass browser warning
        $headers = "User-Agent: Mozilla/5.0\r\n" .
                   "ngrok-skip-browser-warning: true\r\n";
    }
    
    // Get sensor data from Arduino bridge
    $response = @file_get_contents($arduinoUrl, false, stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true,
            'header' => $headers
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]));
    
    if (!$response) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot connect to Arduino bridge',
            'url' => $arduinoUrl,
            'environment' => $isLocal ? 'local' : 'online'
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
