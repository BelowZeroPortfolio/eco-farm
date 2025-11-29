<?php
/**
 * Arduino Push Service
 * Fetches data from local Arduino bridge (port 5001) and pushes to InfinityFree
 * Similar to how YOLO works with ngrok
 *
 * USAGE: php arduino_push_service.php
 */
 
// Configuration
define('ARDUINO_BRIDGE_URL', 'http://127.0.0.1:5001/data');
define('INFINITYFREE_API_URL', 'https://sagayecofarm.infinityfreeapp.com/api/upload_sensor.php');
define('API_KEY', 'sagayeco-farm-2024-secure-key-xyz789');
 
// Colors for terminal output
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
            return intval($minutes * 60);
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
 * Handles InfinityFree's anti-bot protection with cookie persistence
 */
function uploadToInfinityFree($sensorType, $value, $unit) {
    static $cookieFile = null;
   
    // Create persistent cookie file
    if ($cookieFile === null) {
        $cookieFile = sys_get_temp_dir() . '/arduino_push_cookies.txt';
    }
   
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
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
   
    // Cookie handling for InfinityFree anti-bot
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
   
    // Set user agent
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
   
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
   
    // Check if we got the anti-bot challenge page
    if (strpos($response, 'aes.js') !== false || strpos($response, '__test') !== false) {
        // Wait a moment and retry with cookies
        sleep(2);
       
        $ch = curl_init(INFINITYFREE_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
       
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }
   
    $result = json_decode($response, true);
   
    if (!$result) {
        // Still not JSON - InfinityFree blocking
        throw new Exception("InfinityFree anti-bot protection active. Please upload api/upload_sensor.php to whitelist this script.");
    }
   
    if (!isset($result['success']) || !$result['success']) {
        $errorMsg = $result['error'] ?? 'Unknown error';
        throw new Exception("Upload rejected: {$errorMsg}");
    }
   
    return $result;
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
 * Sync all sensor data
 */
function syncSensorData() {
    try {
        colorLog("Fetching data from Arduino bridge (port 5001)...", 'info');
        $arduinoData = fetchArduinoData();
       
        $successCount = 0;
        $failCount = 0;
       
        foreach ($arduinoData as $sensorType => $sensorInfo) {
            if (!isset($sensorInfo['value']) || $sensorInfo['value'] === null) {
                colorLog("⚠️  {$sensorType}: No data available", 'warning');
                continue;
            }
           
            $value = $sensorInfo['value'];
            $unit = getUnitForSensor($sensorType);
           
            try {
                uploadToInfinityFree($sensorType, $value, $unit);
                colorLog("✓ {$sensorType}: {$value}{$unit} uploaded to InfinityFree", 'success');
                $successCount++;
            } catch (Exception $e) {
                colorLog("✗ {$sensorType}: Upload failed - " . $e->getMessage(), 'error');
                $failCount++;
            }
        }
       
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
 * Main loop
 */
function main() {
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║   ARDUINO SENSOR PUSH SERVICE (via ngrok)                 ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\n";
   
    colorLog("Starting Arduino push service...", 'info');
    colorLog("Arduino Bridge: " . ARDUINO_BRIDGE_URL, 'info');
    colorLog("InfinityFree API: " . INFINITYFREE_API_URL, 'info');
   
    $loggingInterval = getLoggingInterval();
    $intervalDisplay = $loggingInterval >= 60 ? round($loggingInterval / 60) . ' minute(s)' : $loggingInterval . ' second(s)';
    colorLog("Logging Interval: {$intervalDisplay}", 'info');
   
    echo "\n";
    colorLog("Waiting 10 seconds for Arduino to collect initial readings...", 'info');
    sleep(10);
   
    echo "\n";
    colorLog("Press Ctrl+C to stop", 'warning');
    echo "\n";
   
    $syncCount = 0;
    $lastIntervalCheck = time();
   
    while (true) {
        $syncCount++;
        colorLog("=== Sync #{$syncCount} ===", 'info');
       
        syncSensorData();
       
        // Check for interval changes every 60 seconds
        if (time() - $lastIntervalCheck >= 60) {
            $oldInterval = $loggingInterval;
            $loggingInterval = getLoggingInterval();
            if ($oldInterval != $loggingInterval) {
                $intervalDisplay = $loggingInterval >= 60 ? round($loggingInterval / 60) . ' minute(s)' : $loggingInterval . ' second(s)';
                colorLog("Interval updated: {$intervalDisplay}", 'info');
            }
            $lastIntervalCheck = time();
        }
       
        echo "\n";
        colorLog("Next sync in {$intervalDisplay}...", 'info');
        echo "\n";
       
        sleep($loggingInterval);
    }
}
 
main();