<?php
/**
 * Browser-Based Sensor Upload API
 * 
 * This endpoint receives sensor data from browser AJAX requests.
 * It bypasses InfinityFree's anti-bot protection because:
 * 1. Request comes from a real browser with cookies/session
 * 2. User has already passed the anti-bot check on the main site
 * 
 * UPLOAD THIS FILE TO INFINITYFREE: /api/browser_upload.php
 */

// Set timezone
date_default_timezone_set('Asia/Manila');

// CORS headers - allow browser requests from your local development
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Or specify your local URL
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';

// API Key verification
$apiKey = Env::get('UPLOAD_API_KEY', 'sagayeco-farm-2024-secure-key-xyz789');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify API key
$providedKey = $_POST['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($providedKey !== $apiKey) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid API key']);
    exit;
}

// Get sensor data from POST
$temperature = isset($_POST['temperature']) ? floatval($_POST['temperature']) : null;
$humidity = isset($_POST['humidity']) ? floatval($_POST['humidity']) : null;
$soilMoisture = isset($_POST['soil_moisture']) ? floatval($_POST['soil_moisture']) : null;
$source = $_POST['source'] ?? 'unknown';

// Validate data
if ($temperature === null && $humidity === null && $soilMoisture === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No sensor data provided']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    $savedCount = 0;
    $skippedCount = 0;
    $errors = [];

    // Process each sensor type
    $sensors = [
        'temperature' => ['value' => $temperature, 'unit' => '°C'],
        'humidity' => ['value' => $humidity, 'unit' => '%'],
        'soil_moisture' => ['value' => $soilMoisture, 'unit' => '%']
    ];

    foreach ($sensors as $type => $data) {
        if ($data['value'] === null) continue;

        try {
            // Get or create sensor
            $sensorId = getOrCreateSensor($pdo, $type);
            
            // Check interval
            if (!shouldLogReading($pdo, $sensorId)) {
                $skippedCount++;
                continue;
            }

            // Insert reading
            $stmt = $pdo->prepare("
                INSERT INTO sensor_readings (sensor_id, value, unit, recorded_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$sensorId, $data['value'], $data['unit']]);

            // Update sensor status
            $pdo->prepare("
                UPDATE sensors SET last_reading_at = NOW(), status = 'online' WHERE id = ?
            ")->execute([$sensorId]);

            $savedCount++;

        } catch (Exception $e) {
            $errors[] = "$type: " . $e->getMessage();
        }
    }

    // Return result
    echo json_encode([
        'success' => true,
        'message' => "Saved: $savedCount, Skipped: $skippedCount (interval)",
        'saved' => $savedCount,
        'skipped' => $skippedCount,
        'source' => $source,
        'timestamp' => date('Y-m-d H:i:s'),
        'errors' => $errors
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

/**
 * Check if enough time has passed based on logging interval
 */
function shouldLogReading($pdo, $sensorId) {
    try {
        // Get interval setting (in minutes)
        $stmt = $pdo->prepare("
            SELECT setting_value FROM user_settings 
            WHERE setting_key = 'sensor_logging_interval' LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        $intervalMinutes = $result ? floatval($result['setting_value']) : 30;

        // Check time since last reading
        $stmt = $pdo->prepare("
            SELECT TIMESTAMPDIFF(SECOND, MAX(recorded_at), NOW()) as seconds_passed
            FROM sensor_readings WHERE sensor_id = ?
        ");
        $stmt->execute([$sensorId]);
        $result = $stmt->fetch();

        if (!$result || $result['seconds_passed'] === null) {
            return true; // No previous reading
        }

        $intervalSeconds = $intervalMinutes * 60;
        return $result['seconds_passed'] >= ($intervalSeconds - 5); // 5 sec tolerance

    } catch (Exception $e) {
        return true; // Allow on error
    }
}

/**
 * Get or create sensor record
 */
function getOrCreateSensor($pdo, $sensorType) {
    $stmt = $pdo->prepare("SELECT id FROM sensors WHERE sensor_type = ? LIMIT 1");
    $stmt->execute([$sensorType]);
    $sensor = $stmt->fetch();

    if ($sensor) {
        return $sensor['id'];
    }

    // Create new sensor
    $sensorName = "Arduino " . ucfirst(str_replace('_', ' ', $sensorType));
    $pins = ['temperature' => 2, 'humidity' => 2, 'soil_moisture' => 10];

    $stmt = $pdo->prepare("
        INSERT INTO sensors (sensor_name, sensor_type, location, arduino_pin, status, created_at) 
        VALUES (?, ?, 'Farm Field', ?, 'online', NOW())
    ");
    $stmt->execute([$sensorName, $sensorType, $pins[$sensorType] ?? 0]);

    return $pdo->lastInsertId();
}
