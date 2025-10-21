<?php
/**
 * Arduino Data Sync Script
 * Fetches data from Arduino bridge and stores in database
 * Can be run manually or via cron job
 */

require_once 'config/database.php';
require_once 'includes/arduino-api.php';

// Initialize Arduino bridge
$arduino = new ArduinoBridge();

// Handle AJAX requests for real-time data
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_GET['action']) {
            case 'get_all':
                $allData = $arduino->getAllSensorData();
                if ($allData) {
                    echo json_encode([
                        'success' => true,
                        'data' => $allData
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No sensor data available'
                    ]);
                }
                break;
                
            case 'get_humidity':
                $humidityData = $arduino->getHumidityData();
                if ($humidityData) {
                    echo json_encode([
                        'success' => true,
                        'humidity' => $humidityData
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No humidity data available'
                    ]);
                }
                break;
                
            case 'get_temperature':
                $tempData = $arduino->getTemperatureData();
                if ($tempData) {
                    echo json_encode([
                        'success' => true,
                        'temperature' => $tempData
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No temperature data available'
                    ]);
                }
                break;
                
            case 'get_soil':
                $soilData = $arduino->getSoilMoistureData();
                if ($soilData) {
                    echo json_encode([
                        'success' => true,
                        'soil_moisture' => $soilData
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No soil moisture data available'
                    ]);
                }
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid action'
                ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

echo "Arduino Data Sync - " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 50) . "\n";

// Check if service is healthy
if (!$arduino->isHealthy()) {
    echo "❌ Arduino bridge service is not healthy\n";
    echo "   Make sure arduino_bridge.py is running\n";
    exit(1);
}

echo "✅ Arduino bridge service is healthy\n";

// Get current sensor data
$sensorData = $arduino->getAllSensorData();
if (!$sensorData) {
    echo "❌ Failed to get sensor data\n";
    exit(1);
}

echo "📊 Current sensor readings:\n";
foreach ($sensorData as $sensorType => $data) {
    $status = $data['status'] ?? 'unknown';
    $value = $data['value'] ?? 'N/A';
    $timestamp = $data['timestamp'] ?? 'N/A';
    
    echo "   {$sensorType}: {$value} ({$status}) - {$timestamp}\n";
}

// Sync to database
echo "\n🔄 Syncing to database...\n";
$synced = $arduino->syncToDatabase();

if ($synced > 0) {
    echo "✅ Successfully synced {$synced} sensor readings\n";
} else {
    echo "⚠️ No data synced\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "Sync completed at " . date('Y-m-d H:i:s') . "\n";