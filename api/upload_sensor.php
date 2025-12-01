<?php
/**
 * API Endpoint: Upload Sensor Data
 * Receives sensor readings from local Arduino system
 * Upload this file to InfinityFree
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';

// API Key for security (change this to a strong random key)
define('API_KEY', Env::get('UPLOAD_API_KEY', 'change-this-to-random-key-12345'));

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// Verify API key
$providedKey = $_POST['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($providedKey !== API_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

// Get sensor data
$sensorType = $_POST['sensor_type'] ?? '';
$value = $_POST['value'] ?? '';
$unit = $_POST['unit'] ?? '';

// Validate inputs
if (empty($sensorType) || empty($value)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: sensor_type, value']);
    exit;
}

// Validate sensor type
$validTypes = ['temperature', 'humidity', 'soil_moisture'];
if (!in_array($sensorType, $validTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid sensor_type. Must be: temperature, humidity, or soil_moisture']);
    exit;
}

// Validate value is numeric
if (!is_numeric($value)) {
    http_response_code(400);
    echo json_encode(['error' => 'Value must be numeric']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    
    // Get or create sensor
    $sensorId = getOrCreateSensor($pdo, $sensorType);
    
    if (!$sensorId) {
        throw new Exception('Failed to get sensor ID');
    }
    
    // Check if enough time has passed since last reading (respects interval setting)
    if (!shouldLogReading($pdo, $sensorId)) {
        // Return success but indicate skipped (interval not reached)
        echo json_encode([
            'success' => true,
            'message' => 'Skipped - logging interval not reached',
            'sensor_type' => $sensorType,
            'value' => $value,
            'skipped' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Insert reading
    $stmt = $pdo->prepare("
        INSERT INTO sensor_readings (sensor_id, value, unit, recorded_at) 
        VALUES (?, ?, ?, NOW())
    ");
    
    $stmt->execute([$sensorId, $value, $unit]);
    
    // Update sensor status
    $updateStmt = $pdo->prepare("
        UPDATE sensors 
        SET last_reading_at = NOW(), status = 'online' 
        WHERE id = ?
    ");
    $updateStmt->execute([$sensorId]);
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Sensor reading uploaded successfully',
        'sensor_type' => $sensorType,
        'value' => $value,
        'unit' => $unit,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

/**
 * Check if enough time has passed to log a new reading based on interval setting
 */
function shouldLogReading($pdo, $sensorId)
{
    try {
        // Get logging interval from settings (in minutes)
        $stmt = $pdo->prepare("
            SELECT setting_value 
            FROM user_settings 
            WHERE setting_key = 'sensor_logging_interval' 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        $intervalMinutes = $result ? floatval($result['setting_value']) : 30;
        
        // Get seconds since last reading using MySQL's time functions
        $stmt = $pdo->prepare("
            SELECT TIMESTAMPDIFF(SECOND, MAX(recorded_at), NOW()) as seconds_passed
            FROM sensor_readings 
            WHERE sensor_id = ?
        ");
        $stmt->execute([$sensorId]);
        $result = $stmt->fetch();
        
        // If no previous reading, allow logging
        if (!$result || $result['seconds_passed'] === null) {
            return true;
        }
        
        $secondsPassed = intval($result['seconds_passed']);
        $intervalSeconds = $intervalMinutes * 60;
        
        // Allow logging if interval has passed (with 1 second tolerance)
        return $secondsPassed >= ($intervalSeconds - 1);
        
    } catch (Exception $e) {
        error_log("Interval check error: " . $e->getMessage());
        return true; // Default to allowing on error
    }
}

/**
 * Get or create sensor record
 */
function getOrCreateSensor($pdo, $sensorType)
{
    // Try to find existing sensor
    $stmt = $pdo->prepare("SELECT id FROM sensors WHERE sensor_type = ? LIMIT 1");
    $stmt->execute([$sensorType]);
    $sensor = $stmt->fetch();
    
    if ($sensor) {
        return $sensor['id'];
    }
    
    // Create new sensor
    $sensorName = "Arduino " . ucfirst(str_replace('_', ' ', $sensorType));
    $location = "Farm Field";
    $arduinoPin = getDefaultPin($sensorType);
    
    $insertStmt = $pdo->prepare("
        INSERT INTO sensors (sensor_name, sensor_type, location, arduino_pin, status, created_at) 
        VALUES (?, ?, ?, ?, 'online', NOW())
    ");
    
    $insertStmt->execute([$sensorName, $sensorType, $location, $arduinoPin]);
    return $pdo->lastInsertId();
}

/**
 * Get default Arduino pin for sensor type
 */
function getDefaultPin($sensorType)
{
    $pinMapping = [
        'temperature' => 2,
        'humidity' => 2,
        'soil_moisture' => 10
    ];
    return $pinMapping[$sensorType] ?? 0;
}
