<?php
/**
 * Local Sensor Sync Service
 * Saves Arduino data to LOCAL database only (for historical records)
 * InfinityFree pulls live data via ngrok - bypasses antibot!
 *
 * USAGE: php local_sensor_sync.php
 */

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

function saveToLocalDB($sensorType, $value, $unit) {
    require_once __DIR__ . '/config/database.php';
    $pdo = getDatabaseConnection();
    
    $stmt = $pdo->prepare("SELECT id FROM sensors WHERE sensor_type = ? LIMIT 1");
    $stmt->execute([$sensorType]);
    $sensor = $stmt->fetch();
    
    if (!$sensor) {
        $name = "Arduino " . ucfirst(str_replace('_', ' ', $sensorType));
        $pdo->prepare("INSERT INTO sensors (sensor_name, sensor_type, location, status, created_at) VALUES (?, ?, 'Farm', 'online', NOW())")
            ->execute([$name, $sensorType]);
        $sensorId = $pdo->lastInsertId();
    } else {
        $sensorId = $sensor['id'];
    }
    
    $pdo->prepare("INSERT INTO sensor_readings (sensor_id, value, unit, recorded_at) VALUES (?, ?, ?, NOW())")
        ->execute([$sensorId, $value, $unit]);
    $pdo->prepare("UPDATE sensors SET last_reading_at = NOW(), status = 'online' WHERE id = ?")
        ->execute([$sensorId]);
    return true;
}

function getUnit($type) {
    return ['temperature' => '°C', 'humidity' => '%', 'soil_moisture' => '%'][$type] ?? '';
}

function syncData() {
    try {
        $data = fetchArduinoData();
        $count = 0;
        foreach ($data as $type => $info) {
            if (isset($info['value']) && $info['value'] !== null) {
                saveToLocalDB($type, $info['value'], getUnit($type));
                colorLog("✓ {$type}: {$info['value']}" . getUnit($type), 'success');
                $count++;
            }
        }
        colorLog("Saved {$count} readings to local DB", 'success');
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
    colorLog("=== Sync #" . (++$syncNum) . " ===", 'info');
    syncData();
    echo "\n";
    sleep($interval);
}
