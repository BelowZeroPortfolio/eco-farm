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
 * Get logging interval from local database
 */
function getLoggingInterval() {
    try {
        require_once __DIR__ . '/config/database.php';
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            SELECT setting_value 
            FROM user_settings 
            WHERE setting_key = 'sensor_logging_interval' 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result) {
            $minutes = floatval($result['setting_value']);
            return intval($minutes * 60); // Convert to seconds
        }
    } catch (Exception $e) {
        colorLog("Failed to get interval from database: " . $e->getMessage(), 'warning');
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
 * Upload sensor reading to InfinityFree
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Upload failed: {$error}");
    }
    
    if ($httpCode !== 200) {
        throw new Exception("Server returned HTTP {$httpCode}");
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['success']) || !$result['success']) {
        $errorMsg = $result['error'] ?? 'Unknown error';
        throw new Exception("Upload rejected: {$errorMsg}");
    }
    
    return $result;
}

/**
 * Sync all sensor data
 */
function syncSensorData() {
    try {
        // Fetch from Arduino
        colorLog("Fetching data from Arduino bridge...", 'info');
        $arduinoData = fetchArduinoData();
        
        $successCount = 0;
        $failCount = 0;
        
        // Upload each sensor type
        foreach ($arduinoData as $sensorType => $sensorInfo) {
            if (!isset($sensorInfo['value']) || $sensorInfo['value'] === null) {
                colorLog("⚠️  {$sensorType}: No data available", 'warning');
                continue;
            }
            
            $value = $sensorInfo['value'];
            $unit = getUnitForSensor($sensorType);
            
            try {
                uploadToInfinityFree($sensorType, $value, $unit);
                colorLog("✓ {$sensorType}: {$value}{$unit} uploaded successfully", 'success');
                $successCount++;
            } catch (Exception $e) {
                colorLog("✗ {$sensorType}: Upload failed - " . $e->getMessage(), 'error');
                $failCount++;
            }
        }
        
        // Summary
        $total = $successCount + $failCount;
        if ($successCount > 0) {
            colorLog("Sync complete: {$successCount}/{$total} sensors uploaded", 'success');
        } else {
            colorLog("Sync failed: No sensors uploaded", 'error');
        }
        
        return $successCount > 0;
        
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
        colorLog("=== Sync #{$syncCount} ===", 'info');
        
        syncSensorData();
        
        echo "\n";
        colorLog("Next sync in {$intervalDisplay}...", 'info');
        echo "\n";
        
        sleep($loggingInterval);
    }
}

// Run the service
main();
