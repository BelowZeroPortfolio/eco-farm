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

    // Check interval using PHP time (avoids MySQL timezone issues)
    $stmt = $pdo->query("SELECT setting_value FROM user_settings WHERE setting_key = 'sensor_logging_interval' LIMIT 1");
    $result = $stmt->fetch();
    $intervalMinutes = $result ? floatval($result['setting_value']) : 30;

    $stmt = $pdo->query("SELECT MAX(ReadingTime) as last_reading FROM sensorreadings");
    $result = $stmt->fetch();

    // Check if interval has passed
    $shouldLog = true;
    if ($result && $result['last_reading']) {
        $lastReadingTime = strtotime($result['last_reading']);
        $secondsPassed = time() - $lastReadingTime;
        $shouldLog = $secondsPassed >= ($intervalMinutes * 60 - 5);
    }

    if (!$shouldLog) {
        echo json_encode([
            'success' => true,
            'message' => 'Skipped - interval not reached',
            'saved' => 0,
            'skipped' => 1,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    // Get active plant ID
    $plantId = 1;
    $stmt = $pdo->query("SELECT SelectedPlantID FROM activeplant ORDER BY UpdatedAt DESC LIMIT 1");
    $result = $stmt->fetch();
    if ($result && $result['SelectedPlantID']) {
        $plantId = intval($result['SelectedPlantID']);
    }

    // Use PHP date() to ensure correct Philippine timezone
    $philippineTime = date('Y-m-d H:i:s');

    // Insert ALL sensors in ONE row to sensorreadings table
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
        'message' => 'Saved to sensorreadings table',
        'saved' => 1,
        'skipped' => 0,
        'plant_id' => $plantId,
        'timestamp' => $philippineTime
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
