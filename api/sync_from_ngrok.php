<?php
/**
 * API Endpoint: Sync Sensor Data from ngrok to Database
 * This endpoint pulls data from the ngrok tunnel and saves to InfinityFree database
 * Can be called by JavaScript on dashboard or by cron job
 * 
 * Upload this file to InfinityFree
 */

// Set timezone
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// Get Arduino ngrok configuration
$arduinoHost = Env::get('ARDUINO_SENSOR_HOST', '');
$arduinoPort = Env::get('ARDUINO_SENSOR_PORT', '443');
$arduinoProtocol = Env::get('ARDUINO_SENSOR_PROTOCOL', 'https');

if (empty($arduinoHost)) {
    echo json_encode([
        'success' => false,
        'error' => 'Arduino ngrok tunnel not configured'
    ]);
    exit;
}

// Build Arduino bridge URL
$arduinoUrl = "{$arduinoProtocol}://{$arduinoHost}";
if ($arduinoPort != '443' && $arduinoPort != '80') {
    $arduinoUrl .= ":{$arduinoPort}";
}
$arduinoUrl .= "/data";

try {
    // Fetch data from Arduino bridge through ngrok
    $ch = curl_init($arduinoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'ngrok-skip-browser-warning: true'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Connection failed: {$error}");
    }
    
    if ($httpCode !== 200) {
        throw new Exception("Arduino bridge returned HTTP {$httpCode}");
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['data'])) {
        throw new Exception("Invalid response from Arduino bridge");
    }
    
    $sensorData = $data['data'];
    $pdo = getDatabaseConnection();
    
    // Set MySQL session timezone to Philippines (UTC+8)
    $pdo->exec("SET time_zone = '+08:00'");
    
    $results = [];
    
    // Extract sensor values
    $temperature = null;
    $humidity = null;
    $soilMoisture = null;
    
    foreach ($sensorData as $sensorType => $info) {
        if (!isset($info['value']) || $info['value'] === null) {
            $results[$sensorType] = 'no_data';
            continue;
        }
        
        $results[$sensorType] = $info['value'];
        
        switch ($sensorType) {
            case 'temperature':
                $temperature = floatval($info['value']);
                break;
            case 'humidity':
                $humidity = intval($info['value']);
                break;
            case 'soil_moisture':
                $soilMoisture = intval($info['value']);
                break;
        }
    }
    
    // Check if we have at least one value
    if ($temperature === null && $humidity === null && $soilMoisture === null) {
        echo json_encode([
            'success' => false,
            'error' => 'No sensor data available',
            'results' => $results
        ]);
        exit;
    }
    
    // Check interval using PHP time comparison (avoids MySQL timezone issues)
    $stmt = $pdo->prepare("SELECT setting_value FROM user_settings WHERE setting_key = 'sensor_logging_interval' LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();
    $intervalMinutes = $result ? floatval($result['setting_value']) : 30;
    
    $stmt = $pdo->query("SELECT MAX(ReadingTime) as last_reading FROM sensorreadings");
    $result = $stmt->fetch();
    
    $shouldLog = true;
    if ($result && $result['last_reading']) {
        $lastReadingTime = strtotime($result['last_reading']);
        $secondsPassed = time() - $lastReadingTime;
        $shouldLog = $secondsPassed >= ($intervalMinutes * 60 - 1);
    }
    
    if (!$shouldLog) {
        echo json_encode([
            'success' => true,
            'saved' => 0,
            'skipped' => 1,
            'message' => 'Skipped - logging interval not reached',
            'results' => $results,
            'timestamp' => date('Y-m-d H:i:s'),
            'ngrok_host' => $arduinoHost
        ]);
        exit;
    }
    
    // Get active plant ID
    $plantId = null;
    $stmt = $pdo->query("SELECT SelectedPlantID FROM activeplant ORDER BY UpdatedAt DESC LIMIT 1");
    $result = $stmt->fetch();
    if ($result && $result['SelectedPlantID']) {
        $plantId = intval($result['SelectedPlantID']);
    } else {
        $stmt = $pdo->query("SELECT PlantID FROM activeplants ORDER BY ActivatedAt DESC LIMIT 1");
        $result = $stmt->fetch();
        if ($result && $result['PlantID']) {
            $plantId = intval($result['PlantID']);
        } else {
            $stmt = $pdo->query("SELECT PlantID FROM plants ORDER BY PlantID LIMIT 1");
            $result = $stmt->fetch();
            $plantId = $result ? intval($result['PlantID']) : null;
        }
    }
    
    if (!$plantId) {
        echo json_encode([
            'success' => false,
            'error' => 'No active plant found',
            'results' => $results
        ]);
        exit;
    }
    
    // Use PHP date() to ensure correct Philippine timezone
    $philippineTime = date('Y-m-d H:i:s');
    
    // Insert into sensorreadings table
    $stmt = $pdo->prepare("
        INSERT INTO sensorreadings (PlantID, SoilMoisture, Temperature, Humidity, WarningLevel, ReadingTime) 
        VALUES (?, ?, ?, ?, 0, ?)
    ");
    $stmt->execute([
        $plantId,
        $soilMoisture ?? 0,
        $temperature ?? 0,
        $humidity ?? 0,
        $philippineTime
    ]);
    
    // Update sensor statuses
    $pdo->prepare("UPDATE sensors SET last_reading_at = ?, status = 'online' WHERE sensor_type IN ('temperature', 'humidity', 'soil_moisture')")->execute([$philippineTime]);
    
    echo json_encode([
        'success' => true,
        'saved' => 1,
        'skipped' => 0,
        'plant_id' => $plantId,
        'values' => [
            'temperature' => $temperature ?? 0,
            'humidity' => $humidity ?? 0,
            'soil_moisture' => $soilMoisture ?? 0
        ],
        'results' => $results,
        'timestamp' => date('Y-m-d H:i:s'),
        'ngrok_host' => $arduinoHost
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'arduino_url' => $arduinoUrl
    ]);
}
