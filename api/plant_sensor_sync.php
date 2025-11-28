<?php
/**
 * Plant Sensor Sync API
 * Receives sensor data from Arduino bridge and processes it
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

// Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../includes/plant-monitor-logic.php';

try {
    $monitor = new PlantMonitor();
    
    // Handle GET request - return active plant info
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $activePlant = $monitor->getActivePlant();
        
        if (!$activePlant) {
            echo json_encode([
                'success' => false,
                'message' => 'No active plant configured'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'active_plant' => [
                'id' => $activePlant['PlantID'],
                'name' => $activePlant['PlantName'],
                'local_name' => $activePlant['LocalName'],
                'thresholds' => [
                    'soil_moisture' => [
                        'min' => $activePlant['MinSoilMoisture'],
                        'max' => $activePlant['MaxSoilMoisture']
                    ],
                    'temperature' => [
                        'min' => $activePlant['MinTemperature'],
                        'max' => $activePlant['MaxTemperature']
                    ],
                    'humidity' => [
                        'min' => $activePlant['MinHumidity'],
                        'max' => $activePlant['MaxHumidity']
                    ]
                ],
                'warning_trigger' => $activePlant['WarningTrigger']
            ]
        ]);
        exit;
    }
    
    // Handle POST request - process sensor data
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Support both JSON and form data
        if (!$input) {
            $input = $_POST;
        }
        
        // Validate required fields
        if (!isset($input['soil_moisture']) || !isset($input['temperature']) || !isset($input['humidity'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing required sensor data: soil_moisture, temperature, humidity'
            ]);
            exit;
        }
        
        $soilMoisture = floatval($input['soil_moisture']);
        $temperature = floatval($input['temperature']);
        $humidity = floatval($input['humidity']);
        
        // Process the reading
        $result = $monitor->processSensorReading($soilMoisture, $temperature, $humidity);
        
        echo json_encode($result);
        exit;
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
