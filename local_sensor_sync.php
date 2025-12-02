<?php
/**
 * Local Sensor Sync Service
 * Saves Arduino data to LOCAL database only (for historical records)
 * InfinityFree pulls live data via ngrok - bypasses antibot!
 *
 * USAGE: php local_sensor_sync.php
 */

// Set timezone to Philippines (UTC+8) - IMPORTANT for correct timestamps
date_default_timezone_set('Asia/Manila');

define('ARDUINO_BRIDGE_URL', 'http://127.0.0.1:5001/data');

function colorLog($message, $type = 'info') {
    $colors = [
        'success' => "\033[0;32m",
        'error' => "\033[0;31m",
        'warning' => "\033[0;33m",
        'info' => "\033[0;36m",
        'reset' => "\033[0m"
    ];
    $timestamp = date('Y-m-d H:i:s');
    $color = $colors[$type] ?? $colors['info'];
    echo "{$color}[{$timestamp}] {$message}{$colors['reset']}\n";
}

function getLoggingInterval() {
    try {
        require_once __DIR__ . '/config/database.php';
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT setting_value FROM user_settings WHERE setting_key = 'sensor_logging_interval' LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch();
        if ($result) {
            return intval(floatval($result['setting_value']) * 60);
        }
    } catch (Exception $e) {
        colorLog("DB interval error: " . $e->getMessage(), 'warning');
    }
    return 60;
}

function fetchArduinoData() {
    $ch = curl_init(ARDUINO_BRIDGE_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) throw new Exception("Connection failed: {$error}");
    if ($httpCode !== 200) throw new Exception("HTTP {$httpCode}");
    
    $data = json_decode($response, true);
    if (!$data || $data['status'] !== 'success') throw new Exception("Invalid response");
    return $data['data'];
}

/**
 * Save ALL sensor readings to database in ONE row
 * This ensures all 3 values are saved together, not separately
 */
function saveAllSensorsToLocalDB($sensorData) {
    require_once __DIR__ . '/config/database.php';
    $pdo = getDatabaseConnection();
    
    // Extract values from sensor data
    $temperature = isset($sensorData['temperature']['value']) ? floatval($sensorData['temperature']['value']) : 0;
    $humidity = isset($sensorData['humidity']['value']) ? floatval($sensorData['humidity']['value']) : 0;
    $soilMoisture = isset($sensorData['soil_moisture']['value']) ? floatval($sensorData['soil_moisture']['value']) : 0;
    
    // Get active plant ID
    $plantId = getActivePlantId($pdo);
    
    // Use PHP date() to ensure correct Philippine timezone
    $philippineTime = date('Y-m-d H:i:s');
    
    // Insert all sensor values in ONE row
    $stmt = $pdo->prepare("
        INSERT INTO sensorreadings (PlantID, SoilMoisture, Temperature, Humidity, WarningLevel, ReadingTime) 
        VALUES (?, ?, ?, ?, 0, ?)
    ");
    $stmt->execute([$plantId, $soilMoisture, $temperature, $humidity, $philippineTime]);
    
    // Update all sensor statuses
    $pdo->prepare("UPDATE sensors SET last_reading_at = ?, status = 'online' WHERE sensor_type IN ('temperature', 'humidity', 'soil_moisture')")
        ->execute([$philippineTime]);
    
    return true;
}

/**
 * Get active plant ID from activeplant table
 */
function getActivePlantId($pdo) {
    try {
        $stmt = $pdo->query("SELECT SelectedPlantID FROM activeplant ORDER BY UpdatedAt DESC LIMIT 1");
        $result = $stmt->fetch();
        return $result ? $result['SelectedPlantID'] : 1;
    } catch (Exception $e) {
        return 1; // Default to plant ID 1
    }
}

// Note: Interval checking is handled by the main loop's sleep($interval)
// All sensors are saved together in one row per sync cycle

function getUnit($type) {
    return ['temperature' => '°C', 'humidity' => '%', 'soil_moisture' => '%'][$type] ?? '';
}

function syncData() {
    try {
        $data = fetchArduinoData();
        
        // Check if we have any valid sensor data
        $hasData = false;
        foreach ($data as $type => $info) {
            if (isset($info['value']) && $info['value'] !== null) {
                $hasData = true;
                colorLog("✓ {$type}: {$info['value']}" . getUnit($type), 'success');
            }
        }
        
        if ($hasData) {
            // Save ALL sensors in ONE database row
            saveAllSensorsToLocalDB($data);
            colorLog("Saved all readings to local DB in single row", 'success');
        } else {
            colorLog("No sensor data available", 'warning');
        }
        
        return true;
    } catch (Exception $e) {
        colorLog("Error: " . $e->getMessage(), 'error');
        return false;
    }
}

echo "\n╔══════════════════════════════════════════════════════════╗\n";
echo "║  LOCAL SENSOR SYNC (InfinityFree pulls via ngrok)       ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

colorLog("Arduino: " . ARDUINO_BRIDGE_URL, 'info');
$interval = getLoggingInterval();
colorLog("Interval: " . ($interval >= 60 ? round($interval/60) . " min" : $interval . " sec"), 'info');
colorLog("Press Ctrl+C to stop\n", 'warning');

sleep(5);
$syncNum = 0;
while (true) {
    // Re-read interval from database on each loop (in case it changed)
    $interval = getLoggingInterval();
    $intervalDisplay = $interval >= 60 ? round($interval/60) . " min" : $interval . " sec";
    
    colorLog("=== Sync #" . (++$syncNum) . " (interval: {$intervalDisplay}) ===", 'info');
    syncData();
    echo "\n";
    sleep($interval);
}
