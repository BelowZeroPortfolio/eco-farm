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
    $savedCount = 0;
    $skippedCount = 0;
    $results = [];
    
    foreach ($sensorData as $sensorType => $info) {
        if (!isset($info['value']) || $info['value'] === null) {
            $results[$sensorType] = 'no_data';
            continue;
        }
        
        $value = $info['value'];
        $unit = ['temperature' => '°C', 'humidity' => '%', 'soil_moisture' => '%'][$sensorType] ?? '';
        
        // Get or create sensor
        $stmt = $pdo->prepare("SELECT id FROM sensors WHERE sensor_type = ? LIMIT 1");
        $stmt->execute([$sensorType]);
        $sensor = $stmt->fetch();
        
        if (!$sensor) {
            $name = "Arduino " . ucfirst(str_replace('_', ' ', $sensorType));
            $pdo->prepare("INSERT INTO sensors (sensor_name, sensor_type, location, status, created_at) VALUES (?, ?, 'Farm', 'online', NOW())")
                ->execute([$name, $sensorType]);
            $sensorId = $pdo->lastInsertId();
        } else {
            $sensorId = $sensor['id'];
        }
        
        // Check interval
        $stmt = $pdo->prepare("SELECT setting_value FROM user_settings WHERE setting_key = 'sensor_logging_interval' LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch();
        $intervalMinutes = $result ? floatval($result['setting_value']) : 30;
        
        $stmt = $pdo->prepare("SELECT TIMESTAMPDIFF(SECOND, MAX(recorded_at), NOW()) as seconds_passed FROM sensor_readings WHERE sensor_id = ?");
        $stmt->execute([$sensorId]);
        $result = $stmt->fetch();
        
        $secondsPassed = $result && $result['seconds_passed'] !== null ? intval($result['seconds_passed']) : 999999;
        $intervalSeconds = $intervalMinutes * 60;
        $shouldLog = $secondsPassed >= ($intervalSeconds - 1);
        
        if ($shouldLog) {
            $pdo->prepare("INSERT INTO sensor_readings (sensor_id, value, unit, recorded_at) VALUES (?, ?, ?, NOW())")
                ->execute([$sensorId, $value, $unit]);
            $pdo->prepare("UPDATE sensors SET last_reading_at = NOW(), status = 'online' WHERE id = ?")
                ->execute([$sensorId]);
            $savedCount++;
            $results[$sensorType] = 'saved';
        } else {
            $skippedCount++;
            $results[$sensorType] = 'skipped_interval';
        }
    }
    
    echo json_encode([
        'success' => true,
        'saved' => $savedCount,
        'skipped' => $skippedCount,
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
