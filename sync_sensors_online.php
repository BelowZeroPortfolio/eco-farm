<?php
/**
 * Online Sensor Sync Script
 * Fetches data from local Arduino bridge and uploads to InfinityFree
 * 
 * USAGE: php sync_sensors_online.php
 * 
 * This script runs continuously and syncs sensor data at the configured interval
 */

// Configuration
define('ARDUINO_BRIDGE_URL', 'http://127.0.0.1:5001/data');
define('INFINITYFREE_API_URL', 'https://sagayecofarm.infinityfreeapp.com/api/upload_sensor.php');
define('API_KEY', 'sagayeco-farm-2024-secure-key-xyz789');  // Must match InfinityFree .env

// Logging interval (in seconds) - will be read from database
$loggingInterval = 60; // Default: 1 minute

// Colors for terminal output
function colorLog($message, $type = 'info') {
    $colors = [
        'success' => "\033[0;32m",  // Green
        'error' => "\033[0;31m",    // Red
        'warning' => "\033[0;33m",  // Yellow
        'info' => "\033[0;36m",     // Cyan
        'reset' => "\033[0m"
    ];
    
    $timestamp = date('Y-m-d H:i:s');
    $color = $colors[$type] ?? $colors['info'];
    echo "{$color}[{$timestamp}] {$message}{$colors['reset']}\n";
}

/**
 * Get logging interval from InfinityFree API
 * Cannot connect directly to InfinityFree database from external machine
 */
function getLoggingInterval() {
    try {
        $apiUrl = 'https://sagayecofarm.infinityfreeapp.com/api/get_sensor_interval.php';
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SagayeEcoFarm-Sync/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['interval_minutes'])) {
                $minutes = floatval($data['interval_minutes']);
                return max(5, intval($minutes * 60)); // Minimum 5 seconds
            }
        }
    } catch (Exception $e) {
        colorLog("Failed to get interval from API: " . $e->getMessage(), 'warning');
    }
    
    return 60; // Default: 1 minute
}

/**
 * Fetch sensor data from local Arduino bridge
 */
function fetchArduinoData() {
    $ch = curl_init(ARDUINO_BRIDGE_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Arduino bridge connection failed: {$error}");
    }
    
    if ($httpCode !== 200) {
        throw new Exception("Arduino bridge returned HTTP {$httpCode}");
    }
    
    $data = json_decode($response, true);
    
    if (!$data || $data['status'] !== 'success') {
        throw new Exception("Invalid response from Arduino bridge");
    }
    
    return $data['data'];
}

/**
 * Upload all sensor readings to InfinityFree in bulk
 * Uses the sensorreadings table (stores all 3 values in one row)
 */
function uploadBulkToInfinityFree($temperature, $humidity, $soilMoisture) {
    $postData = [
        'api_key' => API_KEY,
        'bulk' => 'true',
        'temperature' => $temperature,
        'humidity' => $humidity,
        'soil_moisture' => $soilMoisture
    ];
    
    $ch = curl_init(INFINITYFREE_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SagayeEcoFarm-Sync/1.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("cURL error: {$error}");
    }
    
    if ($httpCode !== 200) {
        throw new Exception("HTTP {$httpCode}");
    }
    
    // Check for HTML response (anti-bot or error page)
    if (strpos($response, '<!DOCTYPE') !== false || strpos($response, '<html') !== false) {
        throw new Exception("Got HTML instead of JSON (anti-bot?)");
    }
    
    $result = json_decode($response, true);
    
    if ($result === null) {
        throw new Exception("Invalid JSON: " . substr($response, 0, 50));
    }
    
    // Handle skipped response (interval not reached) - this is OK
    if (isset($result['skipped']) && $result['skipped']) {
        return $result;
    }
    
    if (!isset($result['success']) || !$result['success']) {
        $errorMsg = $result['error'] ?? $result['message'] ?? 'Server rejected';
        throw new Exception($errorMsg);
    }
    
    return $result;
}

/**
 * Upload single sensor reading to InfinityFree (legacy support)
 */
function uploadToInfinityFree($sensorType, $value, $unit) {
    $postData = [
        'api_key' => API_KEY,
        'sensor_type' => $sensorType,
        'value' => $value,
        'unit' => $unit
    ];
    
    $ch = curl_init(INFINITYFREE_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SagayeEcoFarm-Sync/1.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("cURL error: {$error}");
    }
    
    if ($httpCode !== 200) {
        throw new Exception("HTTP {$httpCode}");
    }
    
    $result = json_decode($response, true);
    
    if ($result === null) {
        throw new Exception("Invalid JSON: " . substr($response, 0, 50));
    }
    
    if (isset($result['skipped']) && $result['skipped']) {
        return $result;
    }
    
    if (!isset($result['success']) || !$result['success']) {
        $errorMsg = $result['error'] ?? $result['message'] ?? 'Server rejected';
        throw new Exception($errorMsg);
    }
    
    return $result;
}

/**
 * Sync all sensor data (bulk upload to sensorreadings table)
 */
function syncSensorData() {
    try {
        // Fetch from Arduino
        colorLog("Fetching data from Arduino bridge...", 'info');
        $arduinoData = fetchArduinoData();
        
        // Extract sensor values
        $temperature = null;
        $humidity = null;
        $soilMoisture = null;
        
        foreach ($arduinoData as $sensorType => $sensorInfo) {
            if (!isset($sensorInfo['value']) || $sensorInfo['value'] === null) {
                colorLog("⚠️  {$sensorType}: No data available", 'warning');
                continue;
            }
            
            $value = $sensorInfo['value'];
            $unit = getUnitForSensor($sensorType);
            colorLog("📊 {$sensorType}: {$value}{$unit}", 'info');
            
            switch ($sensorType) {
                case 'temperature':
                    $temperature = $value;
                    break;
                case 'humidity':
                    $humidity = $value;
                    break;
                case 'soil_moisture':
                    $soilMoisture = $value;
                    break;
            }
        }
        
        // Check if we have at least one sensor value
        if ($temperature === null && $humidity === null && $soilMoisture === null) {
            colorLog("✗ No sensor data available to upload", 'error');
            return false;
        }
        
        // Bulk upload all sensors at once to sensorreadings table
        try {
            $result = uploadBulkToInfinityFree(
                $temperature ?? 0,
                $humidity ?? 0,
                $soilMoisture ?? 0
            );
            
            if (isset($result['skipped']) && $result['skipped']) {
                colorLog("⏭ Skipped - logging interval not reached", 'info');
            } else {
                colorLog("✓ All sensors uploaded to sensorreadings table", 'success');
                if (isset($result['plant_id'])) {
                    colorLog("  Plant ID: {$result['plant_id']}", 'info');
                }
            }
            return true;
            
        } catch (Exception $e) {
            colorLog("✗ Bulk upload failed: " . $e->getMessage(), 'error');
            return false;
        }
        
    } catch (Exception $e) {
        colorLog("Sync error: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Get unit for sensor type
 */
function getUnitForSensor($sensorType) {
    $units = [
        'temperature' => '°C',
        'humidity' => '%',
        'soil_moisture' => '%'
    ];
    return $units[$sensorType] ?? '';
}

/**
 * Main loop
 */
function main() {
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║     SAGAYE ECO FARM - ONLINE SENSOR SYNC SERVICE          ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    
    colorLog("Starting online sync service...", 'info');
    colorLog("Arduino Bridge: " . ARDUINO_BRIDGE_URL, 'info');
    colorLog("InfinityFree API: " . INFINITYFREE_API_URL, 'info');
    
    // Get logging interval
    global $loggingInterval;
    $loggingInterval = getLoggingInterval();
    $intervalDisplay = $loggingInterval >= 60 ? round($loggingInterval / 60) . ' minute(s)' : $loggingInterval . ' second(s)';
    colorLog("Logging Interval: {$intervalDisplay}", 'info');
    
    echo "\n";
    colorLog("Press Ctrl+C to stop", 'warning');
    echo "\n";
    
    $syncCount = 0;
    
    while (true) {
        $syncCount++;
        
        // Re-read interval from database on each loop (in case it changed)
        $loggingInterval = getLoggingInterval();
        $intervalDisplay = $loggingInterval >= 60 ? round($loggingInterval / 60) . ' minute(s)' : $loggingInterval . ' second(s)';
        
        colorLog("=== Sync #{$syncCount} (interval: {$intervalDisplay}) ===", 'info');
        
        syncSensorData();
        
        echo "\n";
        colorLog("Next sync in {$intervalDisplay}...", 'info');
        echo "\n";
        
        sleep($loggingInterval);
    }
}

// Run the service
main();
