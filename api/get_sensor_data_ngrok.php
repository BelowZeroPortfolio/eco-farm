<?php
/**
 * API Endpoint: Get Sensor Data via ngrok
 * Fetches real-time sensor data from local Arduino through ngrok tunnel
 * Upload this file to InfinityFree
 */

// Set timezone
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Save sensor data to sensorreadings table (for historical records)
 * Respects logging interval setting
 */
function saveSensorDataToDatabase($sensorData) {
    try {
        $pdo = getDatabaseConnection();
        
        // Set MySQL session timezone to Philippines (UTC+8)
        $pdo->exec("SET time_zone = '+08:00'");
        
        // Extract sensor values
        $temperature = null;
        $humidity = null;
        $soilMoisture = null;
        
        foreach ($sensorData as $sensorType => $info) {
            if (!isset($info['value']) || $info['value'] === null) continue;
            
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
            return false;
        }
        
        // Check interval
        $stmt = $pdo->prepare("SELECT setting_value FROM user_settings WHERE setting_key = 'sensor_logging_interval' LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch();
        $intervalMinutes = $result ? floatval($result['setting_value']) : 30;
        
        $stmt = $pdo->query("SELECT TIMESTAMPDIFF(SECOND, MAX(ReadingTime), NOW()) as seconds_passed FROM sensorreadings");
        $result = $stmt->fetch();
        
        $shouldLog = !$result || $result['seconds_passed'] === null || intval($result['seconds_passed']) >= ($intervalMinutes * 60 - 1);
        
        if (!$shouldLog) {
            return false;
        }
        
        // Get active plant ID
        $plantId = getActivePlantId($pdo);
        if (!$plantId) {
            error_log("saveSensorDataToDatabase: No active plant found");
            return false;
        }
        
        // Insert into sensorreadings table
        $stmt = $pdo->prepare("
            INSERT INTO sensorreadings (PlantID, SoilMoisture, Temperature, Humidity, WarningLevel, ReadingTime) 
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([
            $plantId,
            $soilMoisture ?? 0,
            $temperature ?? 0,
            $humidity ?? 0
        ]);
        
        // Update sensor statuses
        $pdo->exec("UPDATE sensors SET last_reading_at = NOW(), status = 'online' WHERE sensor_type IN ('temperature', 'humidity', 'soil_moisture')");
        
        return true;
    } catch (Exception $e) {
        error_log("saveSensorDataToDatabase error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get the currently active/selected plant ID
 */
function getActivePlantId($pdo) {
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
    
    $sensorData = $data['data'] ?? $data;
    
    // Also save to database for historical records (if save=1 parameter)
    $saved = false;
    if (isset($_GET['save']) && $_GET['save'] == '1') {
        $saved = saveSensorDataToDatabase($sensorData);
    }
    
    // Return the sensor data
    echo json_encode([
        'success' => true,
        'data' => $sensorData,
        'timestamp' => date('Y-m-d H:i:s'),
        'source' => 'ngrok_tunnel',
        'saved_to_db' => $saved
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
