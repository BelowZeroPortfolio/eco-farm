<?php
/**
 * Pixel-Based Sensor Upload
 * 
 * Uses the "tracking pixel" technique to bypass anti-bot.
 * Browser loads this as an image, data is passed via URL parameters.
 * 
 * Usage: <img src="https://yoursite.infinityfreeapp.com/api/pixel_upload.php?k=KEY&t=25.5&h=60&s=45">
 * 
 * UPLOAD THIS FILE TO INFINITYFREE: /api/pixel_upload.php
 */

date_default_timezone_set('Asia/Manila');

// Return a 1x1 transparent GIF regardless of outcome
function outputPixel() {
    header('Content-Type: image/gif');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    // 1x1 transparent GIF
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit;
}

// Silently fail - always return pixel
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/env.php';

    $apiKey = Env::get('UPLOAD_API_KEY', 'sagayeco-farm-2024-secure-key-xyz789');

    // Verify API key (short param 'k')
    $providedKey = $_GET['k'] ?? $_GET['api_key'] ?? '';
    if ($providedKey !== $apiKey) {
        outputPixel();
    }

    // Get sensor data (short params: t=temp, h=humidity, s=soil)
    $temperature = isset($_GET['t']) ? floatval($_GET['t']) : null;
    $humidity = isset($_GET['h']) ? floatval($_GET['h']) : null;
    $soilMoisture = isset($_GET['s']) ? floatval($_GET['s']) : null;

    if ($temperature === null && $humidity === null && $soilMoisture === null) {
        outputPixel();
    }

    $pdo = getDatabaseConnection();

    $sensors = [
        'temperature' => ['value' => $temperature, 'unit' => '°C'],
        'humidity' => ['value' => $humidity, 'unit' => '%'],
        'soil_moisture' => ['value' => $soilMoisture, 'unit' => '%']
    ];

    foreach ($sensors as $type => $info) {
        if ($info['value'] === null) continue;

        // Get or create sensor
        $stmt = $pdo->prepare("SELECT id FROM sensors WHERE sensor_type = ? LIMIT 1");
        $stmt->execute([$type]);
        $sensor = $stmt->fetch();
        
        if (!$sensor) {
            $name = "Arduino " . ucfirst(str_replace('_', ' ', $type));
            $pins = ['temperature' => 2, 'humidity' => 2, 'soil_moisture' => 10];
            $stmt = $pdo->prepare("INSERT INTO sensors (sensor_name, sensor_type, location, arduino_pin, status, created_at) VALUES (?, ?, 'Farm', ?, 'online', NOW())");
            $stmt->execute([$name, $type, $pins[$type] ?? 0]);
            $sensorId = $pdo->lastInsertId();
        } else {
            $sensorId = $sensor['id'];
        }

        // Check interval
        $stmt = $pdo->query("SELECT setting_value FROM user_settings WHERE setting_key = 'sensor_logging_interval' LIMIT 1");
        $result = $stmt->fetch();
        $intervalMinutes = $result ? floatval($result['setting_value']) : 30;

        $stmt = $pdo->prepare("SELECT TIMESTAMPDIFF(SECOND, MAX(recorded_at), NOW()) as sec FROM sensor_readings WHERE sensor_id = ?");
        $stmt->execute([$sensorId]);
        $result = $stmt->fetch();

        // Skip if interval not reached
        if ($result && $result['sec'] !== null && $result['sec'] < ($intervalMinutes * 60 - 5)) {
            continue;
        }

        // Insert reading
        $stmt = $pdo->prepare("INSERT INTO sensor_readings (sensor_id, value, unit, recorded_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$sensorId, $info['value'], $info['unit']]);
        
        $pdo->prepare("UPDATE sensors SET last_reading_at = NOW(), status = 'online' WHERE id = ?")->execute([$sensorId]);
    }

} catch (Exception $e) {
    // Silently fail
    error_log("Pixel upload error: " . $e->getMessage());
}

outputPixel();
