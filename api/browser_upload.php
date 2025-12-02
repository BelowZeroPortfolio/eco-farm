<?php
/**
 * Browser-Based Sensor Upload API
 * 
 * This endpoint receives sensor data via:
 * 1. Direct form POST (from iframe)
 * 2. GET request with data in URL (simplest bypass)
 * 
 * UPLOAD THIS FILE TO INFINITYFREE: /api/browser_upload.php
 */

date_default_timezone_set('Asia/Manila');

// Allow both GET and POST
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';

$apiKey = Env::get('UPLOAD_API_KEY', 'sagayeco-farm-2024-secure-key-xyz789');

// Accept data from GET or POST
$data = $_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET : $_POST;

// Verify API key
$providedKey = $data['api_key'] ?? '';
if ($providedKey !== $apiKey) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid API key']);
    exit;
}

// Get sensor data
$temperature = isset($data['t']) ? floatval($data['t']) : (isset($data['temperature']) ? floatval($data['temperature']) : null);
$humidity = isset($data['h']) ? floatval($data['h']) : (isset($data['humidity']) ? floatval($data['humidity']) : null);
$soilMoisture = isset($data['s']) ? floatval($data['s']) : (isset($data['soil_moisture']) ? floatval($data['soil_moisture']) : null);

if ($temperature === null && $humidity === null && $soilMoisture === null) {
    echo json_encode(['success' => false, 'message' => 'No sensor data']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    $saved = 0;
    $skipped = 0;

    $sensors = [
        'temperature' => ['value' => $temperature, 'unit' => '°C'],
        'humidity' => ['value' => $humidity, 'unit' => '%'],
        'soil_moisture' => ['value' => $soilMoisture, 'unit' => '%']
    ];

    foreach ($sensors as $type => $info) {
        if ($info['value'] === null) continue;

        $sensorId = getOrCreateSensor($pdo, $type);
        
        if (!shouldLogReading($pdo, $sensorId)) {
            $skipped++;
            continue;
        }

        $stmt = $pdo->prepare("INSERT INTO sensor_readings (sensor_id, value, unit, recorded_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$sensorId, $info['value'], $info['unit']]);
        
        $pdo->prepare("UPDATE sensors SET last_reading_at = NOW(), status = 'online' WHERE id = ?")->execute([$sensorId]);
        $saved++;
    }

    echo json_encode([
        'success' => true,
        'message' => "Saved: $saved, Skipped: $skipped",
        'saved' => $saved,
        'skipped' => $skipped,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}

function shouldLogReading($pdo, $sensorId) {
    try {
        $stmt = $pdo->query("SELECT setting_value FROM user_settings WHERE setting_key = 'sensor_logging_interval' LIMIT 1");
        $result = $stmt->fetch();
        $intervalMinutes = $result ? floatval($result['setting_value']) : 30;

        $stmt = $pdo->prepare("SELECT TIMESTAMPDIFF(SECOND, MAX(recorded_at), NOW()) as sec FROM sensor_readings WHERE sensor_id = ?");
        $stmt->execute([$sensorId]);
        $result = $stmt->fetch();

        if (!$result || $result['sec'] === null) return true;
        return $result['sec'] >= ($intervalMinutes * 60 - 5);
    } catch (Exception $e) {
        return true;
    }
}

function getOrCreateSensor($pdo, $type) {
    $stmt = $pdo->prepare("SELECT id FROM sensors WHERE sensor_type = ? LIMIT 1");
    $stmt->execute([$type]);
    $sensor = $stmt->fetch();
    if ($sensor) return $sensor['id'];

    $name = "Arduino " . ucfirst(str_replace('_', ' ', $type));
    $pins = ['temperature' => 2, 'humidity' => 2, 'soil_moisture' => 10];
    $stmt = $pdo->prepare("INSERT INTO sensors (sensor_name, sensor_type, location, arduino_pin, status, created_at) VALUES (?, ?, 'Farm', ?, 'online', NOW())");
    $stmt->execute([$name, $type, $pins[$type] ?? 0]);
    return $pdo->lastInsertId();
}
