<?php
/**
 * Plant Threshold Checker
 * Monitors sensor readings and generates notifications based on active plant thresholds
 * This should be called periodically (e.g., via cron or when sensor data is received)
 */

// Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

require_once 'config/database.php';
require_once 'includes/plant-monitor-logic.php';

// Get latest sensor readings from Arduino bridge
function getLatestSensorReadings() {
    try {
        $response = @file_get_contents('http://127.0.0.1:5000/data');
        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data['status'] === 'success') {
                return [
                    'temperature' => $data['data']['temperature']['value'] ?? null,
                    'humidity' => $data['data']['humidity']['value'] ?? null,
                    'soil_moisture' => $data['data']['soil_moisture']['value'] ?? null
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Failed to get sensor readings: " . $e->getMessage());
    }
    return null;
}

// Main execution
try {
    $monitor = new PlantMonitor();
    $activePlant = $monitor->getActivePlant();
    
    if (!$activePlant) {
        echo json_encode(['success' => false, 'message' => 'No active plant configured']);
        exit;
    }
    
    // Get latest sensor readings
    $readings = getLatestSensorReadings();
    
    if (!$readings || !$readings['temperature'] || !$readings['humidity'] || !$readings['soil_moisture']) {
        echo json_encode(['success' => false, 'message' => 'No sensor data available']);
        exit;
    }
    
    // Process the readings and check thresholds
    $result = $monitor->processSensorReading(
        $readings['soil_moisture'],
        $readings['temperature'],
        $readings['humidity']
    );
    
    // Log notification if triggered
    if ($result['notification_triggered']) {
        error_log("Plant notification triggered for {$activePlant['PlantName']} - Warning Level: {$result['warning_level']}");
        foreach ($result['violations'] as $violation) {
            error_log("  - {$violation['sensor']}: {$violation['status']} (Current: {$violation['current']}, Required: {$violation['range']})");
        }
    }
    
    echo json_encode([
        'success' => true,
        'plant' => $activePlant['PlantName'],
        'readings' => $readings,
        'warning_level' => $result['warning_level'],
        'violations' => $result['violations'],
        'notification_triggered' => $result['notification_triggered'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
