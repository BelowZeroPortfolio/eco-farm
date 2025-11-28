<?php
/**
 * Reset Plant Violations Counter
 * Resets the consecutive violation count for the active plant
 */

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
    $stmt = $pdo->query("SELECT SelectedPlantID FROM ActivePlant LIMIT 1");
    $result = $stmt->fetch();
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'No active plant configured']);
        exit;
    }
    
    $activePlantID = $result['SelectedPlantID'];
    
    // Insert a dummy reading with 0 violations to reset the counter
    // This simulates all sensors being within thresholds
    $stmt = $pdo->prepare("
        INSERT INTO SensorReadings (PlantID, SoilMoisture, Temperature, Humidity, WarningLevel)
        SELECT ?, 
               (MinSoilMoisture + MaxSoilMoisture) / 2,
               (MinTemperature + MaxTemperature) / 2,
               (MinHumidity + MaxHumidity) / 2,
               0
        FROM Plants
        WHERE PlantID = ?
    ");
    
    $stmt->execute([$activePlantID, $activePlantID]);
    
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
