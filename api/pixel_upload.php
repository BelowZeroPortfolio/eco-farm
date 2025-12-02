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

    // Check interval using PHP time (avoids MySQL timezone issues)
    $stmt = $pdo->query("SELECT setting_value FROM user_settings WHERE setting_key = 'sensor_logging_interval' LIMIT 1");
    $result = $stmt->fetch();
    $intervalMinutes = $result ? floatval($result['setting_value']) : 30;

    $stmt = $pdo->query("SELECT MAX(ReadingTime) as last_reading FROM sensorreadings");
    $result = $stmt->fetch();

    // Check if interval has passed
    if ($result && $result['last_reading']) {
        $lastReadingTime = strtotime($result['last_reading']);
        $secondsPassed = time() - $lastReadingTime;
        if ($secondsPassed < ($intervalMinutes * 60 - 5)) {
            outputPixel(); // Skip - interval not reached
        }
    }

    // Get active plant ID
    $plantId = 1;
    $stmt = $pdo->query("SELECT SelectedPlantID FROM activeplant ORDER BY UpdatedAt DESC LIMIT 1");
    $result = $stmt->fetch();
    if ($result && $result['SelectedPlantID']) {
        $plantId = intval($result['SelectedPlantID']);
    }

    // Use PHP date() to ensure correct Philippine timezone
    $philippineTime = date('Y-m-d H:i:s');

    // Insert ALL sensors in ONE row to sensorreadings table
    $stmt = $pdo->prepare("
        INSERT INTO sensorreadings (PlantID, SoilMoisture, Temperature, Humidity, WarningLevel, ReadingTime) 
        VALUES (?, ?, ?, ?, 0, ?)
    ");
    $stmt->execute([
        $plantId,
        $soilMoisture ?? 0,
        $temperature ?? 0,
        $humidity ?? 0,
        $philippineTime
    ]);

    // Update sensor statuses
    $pdo->prepare("UPDATE sensors SET last_reading_at = ?, status = 'online' WHERE sensor_type IN ('temperature', 'humidity', 'soil_moisture')")->execute([$philippineTime]);

} catch (Exception $e) {
    // Silently fail
    error_log("Pixel upload error: " . $e->getMessage());
}

outputPixel();
