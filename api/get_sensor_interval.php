<?php
/**
 * Get Sensor Logging Interval API
 * Returns the current sensor logging interval from settings
 */

// Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDatabaseConnection();
    
    // Get sensor logging interval from settings (in minutes)
    $stmt = $pdo->prepare("
        SELECT setting_value 
        FROM user_settings 
        WHERE setting_key = 'sensor_logging_interval' 
        LIMIT 1
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    
    $intervalMinutes = $result ? floatval($result['setting_value']) : 30;
    $intervalSeconds = intval($intervalMinutes * 60);
    
    echo json_encode([
        'success' => true,
        'interval_minutes' => $intervalMinutes,
        'interval_seconds' => $intervalSeconds,
        'display' => $intervalSeconds >= 60 ? round($intervalSeconds / 60) . 'm' : $intervalSeconds . 's'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'interval_seconds' => 1800, // Default 30 minutes
        'interval_minutes' => 30
    ]);
}
