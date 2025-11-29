<?php
/**
 * API Endpoint: Get Sensor Data via ngrok
 * Fetches real-time sensor data from local Arduino through ngrok tunnel
 * Upload this file to InfinityFree
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/env.php';

// Get Arduino ngrok configuration
$arduinoHost = Env::get('ARDUINO_SENSOR_HOST', '');
$arduinoPort = Env::get('ARDUINO_SENSOR_PORT', '443');
$arduinoProtocol = Env::get('ARDUINO_SENSOR_PROTOCOL', 'https');

// Check if ngrok is configured
if (empty($arduinoHost)) {
    echo json_encode([
        'success' => false,
        'error' => 'Arduino ngrok tunnel not configured',
        'instructions' => [
            'Set up ngrok tunnel for Arduino bridge (port 5001)',
            'Update ARDUINO_SENSOR_HOST in config/env.php',
            'Upload config/env.php to InfinityFree'
        ]
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
    
    if (!$data) {
        throw new Exception("Invalid JSON response from Arduino bridge");
    }
    
    // Return the sensor data
    echo json_encode([
        'success' => true,
        'data' => $data['data'] ?? $data,
        'timestamp' => date('Y-m-d H:i:s'),
        'source' => 'ngrok_tunnel'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'arduino_url' => $arduinoUrl,
        'instructions' => [
            'Make sure Arduino bridge is running (python arduino_bridge.py)',
            'Make sure ngrok tunnel is active (ngrok http 5001)',
            'Verify ARDUINO_SENSOR_HOST in config/env.php matches your ngrok URL'
        ]
    ]);
}
