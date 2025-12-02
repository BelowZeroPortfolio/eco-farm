<?php
/**
 * Reset Plant Violations Counter
 * Resets the consecutive violation count for the active plant
 */

// Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

session_start();
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

require_once 'config/database.php';

try {
    $pdo = getDatabaseConnection();
    
    // Get active plant ID
    $stmt = $pdo->query("SELECT SelectedPlantID FROM activeplant LIMIT 1");
    $result = $stmt->fetch();
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'No active plant configured']);
        exit;
    }
    
    $activePlantID = $result['SelectedPlantID'];
    
    // Use Philippine time for ReadingTime
    $philippineTime = date('Y-m-d H:i:s');
    
    // Insert a dummy reading with 0 violations to reset the counter
    // This simulates all sensors being within thresholds
    $stmt = $pdo->prepare("
        INSERT INTO sensorreadings (PlantID, SoilMoisture, Temperature, Humidity, WarningLevel, ReadingTime)
        SELECT ?, 
               (MinSoilMoisture + MaxSoilMoisture) / 2,
               (MinTemperature + MaxTemperature) / 2,
               (MinHumidity + MaxHumidity) / 2,
               0,
               ?
        FROM plants
        WHERE PlantID = ?
    ");
    
    $stmt->execute([$activePlantID, $philippineTime, $activePlantID]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Violation counter reset to 0',
        'plant_id' => $activePlantID
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
