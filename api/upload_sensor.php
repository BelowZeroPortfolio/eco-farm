<?php
/**
 * API Endpoint: Upload Sensor Data
 * Receives sensor readings from local Arduino system
 * Stores data in sensorreadings table (linked to active plant)
 * Upload this file to InfinityFree
 */

// Set timezone to Philippines (UTC+8) - IMPORTANT for interval calculations
date_default_timezone_set('Asia/Manila');

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

// Check if this is a bulk upload (all sensors at once)
if (isset($_POST['bulk']) && $_POST['bulk'] === 'true') {
    handleBulkUpload();
    exit;
}

// Get sensor data (single sensor upload - legacy support)
$sensorType = $_POST['sensor_type'] ?? '';
$value = $_POST['value'] ?? '';
$unit = $_POST['unit'] ?? '';

// Validate inputs
if (empty($sensorType) || $value === '') {
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
    
    // Set MySQL session timezone to Philippines (UTC+8)
    $pdo->exec("SET time_zone = '+08:00'");
    
    // Get or create sensor (for sensors table status tracking)
    $sensorId = getOrCreateSensor($pdo, $sensorType);
    
    if (!$sensorId) {
        throw new Exception('Failed to get sensor ID');
    }
    
    // Check if enough time has passed since last reading (respects interval setting)
    if (!shouldLogReading($pdo)) {
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
    
    // Get active plant ID
    $plantId = getActivePlantId($pdo);
    
    if (!$plantId) {
        throw new Exception('No active plant selected');
    }
    
    // For single sensor upload, we need to get existing values or use defaults
    $currentValues = getCurrentSensorValues($pdo, $plantId);
    
    // Update the specific sensor value
    $currentValues[$sensorType] = floatval($value);
    
    // Insert into sensorreadings table
    // Use PHP date() instead of MySQL NOW() to ensure correct Philippine timezone
    $philippineTime = date('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare("
        INSERT INTO sensorreadings (PlantID, SoilMoisture, Temperature, Humidity, WarningLevel, ReadingTime) 
        VALUES (?, ?, ?, ?, 0, ?)
    ");
    
    $stmt->execute([
        $plantId,
        intval($currentValues['soil_moisture']),
        floatval($currentValues['temperature']),
        intval($currentValues['humidity']),
        $philippineTime
    ]);
    
    // Update sensor status in sensors table
    $updateStmt = $pdo->prepare("
        UPDATE sensors 
        SET last_reading_at = ?, status = 'online' 
        WHERE id = ?
    ");
    $updateStmt->execute([$philippineTime, $sensorId]);
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Sensor reading uploaded to sensorreadings table',
        'sensor_type' => $sensorType,
        'value' => $value,
        'plant_id' => $plantId,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

/**
 * Handle bulk upload of all sensor data at once
 * This is the preferred method - uploads all 3 sensors in one request
 */
function handleBulkUpload()
{
    $temperature = $_POST['temperature'] ?? null;
    $humidity = $_POST['humidity'] ?? null;
    $soilMoisture = $_POST['soil_moisture'] ?? null;
    
    // Validate at least one value is provided
    if ($temperature === null && $humidity === null && $soilMoisture === null) {
        http_response_code(400);
        echo json_encode(['error' => 'At least one sensor value required']);
        exit;
    }
    
    try {
        $pdo = getDatabaseConnection();
        
        // Set MySQL session timezone to Philippines (UTC+8)
        $pdo->exec("SET time_zone = '+08:00'");
        
        // Check if enough time has passed since last reading
        if (!shouldLogReading($pdo)) {
            echo json_encode([
                'success' => true,
                'message' => 'Skipped - logging interval not reached',
                'skipped' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;
        }
        
        // Get active plant ID
        $plantId = getActivePlantId($pdo);
        
        if (!$plantId) {
            throw new Exception('No active plant selected');
        }
        
        // Use provided values or defaults
        $temp = $temperature !== null ? floatval($temperature) : 0;
        $hum = $humidity !== null ? intval($humidity) : 0;
        $soil = $soilMoisture !== null ? intval($soilMoisture) : 0;
        
        // Insert into sensorreadings table
        // Use PHP date() instead of MySQL NOW() to ensure correct Philippine timezone
        $philippineTime = date('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("
            INSERT INTO sensorreadings (PlantID, SoilMoisture, Temperature, Humidity, WarningLevel, ReadingTime) 
            VALUES (?, ?, ?, ?, 0, ?)
        ");
        
        $stmt->execute([$plantId, $soil, $temp, $hum, $philippineTime]);
        
        // Update all sensor statuses
        $pdo->prepare("UPDATE sensors SET last_reading_at = ?, status = 'online' WHERE sensor_type IN ('temperature', 'humidity', 'soil_moisture')")->execute([$philippineTime]);
        
        echo json_encode([
            'success' => true,
            'message' => 'All sensor readings uploaded to sensorreadings table',
            'plant_id' => $plantId,
            'values' => [
                'temperature' => $temp,
                'humidity' => $hum,
                'soil_moisture' => $soil
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get the currently active/selected plant ID
 */
function getActivePlantId($pdo)
{
    // First try activeplant table (single selected plant)
    $stmt = $pdo->query("SELECT SelectedPlantID FROM activeplant ORDER BY UpdatedAt DESC LIMIT 1");
    $result = $stmt->fetch();
    
    if ($result && $result['SelectedPlantID']) {
        return intval($result['SelectedPlantID']);
    }
    
    // Fallback to activeplants table (first active plant)
    $stmt = $pdo->query("SELECT PlantID FROM activeplants ORDER BY ActivatedAt DESC LIMIT 1");
    $result = $stmt->fetch();
    
    if ($result && $result['PlantID']) {
        return intval($result['PlantID']);
    }
    
    // Final fallback: first plant in plants table
    $stmt = $pdo->query("SELECT PlantID FROM plants ORDER BY PlantID LIMIT 1");
    $result = $stmt->fetch();
    
    return $result ? intval($result['PlantID']) : null;
}

/**
 * Get current sensor values (for partial updates)
 */
function getCurrentSensorValues($pdo, $plantId)
{
    // Get the most recent reading for this plant
    $stmt = $pdo->prepare("
        SELECT SoilMoisture, Temperature, Humidity 
        FROM sensorreadings 
        WHERE PlantID = ? 
        ORDER BY ReadingTime DESC 
        LIMIT 1
    ");
    $stmt->execute([$plantId]);
    $result = $stmt->fetch();
    
    if ($result) {
        return [
            'soil_moisture' => intval($result['SoilMoisture']),
            'temperature' => floatval($result['Temperature']),
            'humidity' => intval($result['Humidity'])
        ];
    }
    
    // Default values if no previous reading
    return [
        'soil_moisture' => 0,
        'temperature' => 0,
        'humidity' => 0
    ];
}

/**
 * Check if enough time has passed to log a new reading based on interval setting
 * Uses PHP time comparison to avoid MySQL timezone issues
 */
function shouldLogReading($pdo)
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
        
        // Get the last reading time from sensorreadings table
        $stmt = $pdo->query("SELECT MAX(ReadingTime) as last_reading FROM sensorreadings");
        $result = $stmt->fetch();
        
        // If no previous reading, allow logging
        if (!$result || !$result['last_reading']) {
            return true;
        }
        
        // Calculate seconds passed using PHP (avoids MySQL timezone issues)
        $lastReadingTime = strtotime($result['last_reading']);
        $currentTime = time(); // PHP time in configured timezone (Asia/Manila)
        $secondsPassed = $currentTime - $lastReadingTime;
        
        $intervalSeconds = $intervalMinutes * 60;
        
        // Allow logging if interval has passed (with 1 second tolerance)
        return $secondsPassed >= ($intervalSeconds - 1);
        
    } catch (Exception $e) {
        error_log("Interval check error: " . $e->getMessage());
        return true; // Default to allowing on error
    }
}

/**
 * Get or create sensor record (for status tracking in sensors table)
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
