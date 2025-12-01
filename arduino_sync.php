<?php
/**
 * Arduino Data Sync Script
 * Fetches real-time data from Arduino bridge and stores in database based on logging interval
 * Handles both real-time display and interval-based database logging
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
                    // Get current thresholds for status calculation
                    $thresholds = getSensorThresholds();
                    
                    // Add status information to each sensor reading
                    foreach ($allData as $sensorType => &$data) {
                        if (isset($data['value']) && isset($thresholds[$sensorType])) {
                            $value = floatval($data['value']);
                            $threshold = $thresholds[$sensorType];
                            
                            if ($value >= $threshold['min'] && $value <= $threshold['max']) {
                                $data['status_level'] = 'optimal';
                                $data['status_text'] = 'Optimal';
                                $data['status_color'] = 'green';
                            } elseif ($value < $threshold['min'] - 10 || $value > $threshold['max'] + 10) {
                                $data['status_level'] = 'critical';
                                $data['status_text'] = 'Critical';
                                $data['status_color'] = 'red';
                            } else {
                                $data['status_level'] = 'warning';
                                $data['status_text'] = 'Warning';
                                $data['status_color'] = 'yellow';
                            }
                        }
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $allData,
                        'timestamp' => date('Y-m-d H:i:s')
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
                        'humidity' => $humidityData,
                        'timestamp' => date('Y-m-d H:i:s')
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
                        'temperature' => $tempData,
                        'timestamp' => date('Y-m-d H:i:s')
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
                        'soil_moisture' => $soilData,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No soil moisture data available'
                    ]);
                }
                break;
                
            case 'sync_to_db':
                // Force sync to database (respects interval settings)
                $synced = $arduino->syncToDatabase();
                echo json_encode([
                    'success' => true,
                    'synced_count' => $synced,
                    'message' => "Synced {$synced} sensor readings to database"
                ]);
                break;
                
            case 'get_interval_info':
                // Get current logging interval information
                $intervalInfo = $arduino->getLoggingIntervalSetting();
                echo json_encode([
                    'success' => true,
                    'interval' => $intervalInfo
                ]);
                break;
                
            case 'get_historical':
                // Get historical sensor data for analytics
                $hours = isset($_GET['hours']) ? intval($_GET['hours']) : 6;
                $historicalData = getHistoricalSensorData($hours);
                echo json_encode([
                    'success' => true,
                    'data' => $historicalData,
                    'hours' => $hours
                ]);
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

/**
 * Get sensor thresholds for status calculation
 */
function getSensorThresholds() {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                sensor_type,
                AVG(alert_threshold_min) as min_threshold,
                AVG(alert_threshold_max) as max_threshold
            FROM sensors
            WHERE alert_threshold_min IS NOT NULL 
            AND alert_threshold_max IS NOT NULL
            GROUP BY sensor_type
        ");
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        // Convert to associative array
        $thresholds = [];
        foreach ($results as $row) {
            $thresholds[$row['sensor_type']] = [
                'min' => floatval($row['min_threshold']),
                'max' => floatval($row['max_threshold'])
            ];
        }
        
        // Fallback to defaults if no thresholds in database
        if (empty($thresholds)) {
            return [
                'temperature' => ['min' => 20, 'max' => 28],
                'humidity' => ['min' => 60, 'max' => 80],
                'soil_moisture' => ['min' => 40, 'max' => 60]
            ];
        }
        
        return $thresholds;
    } catch (Exception $e) {
        error_log("Failed to get sensor thresholds: " . $e->getMessage());
        // Return defaults on error
        return [
            'temperature' => ['min' => 20, 'max' => 28],
            'humidity' => ['min' => 60, 'max' => 80],
            'soil_moisture' => ['min' => 40, 'max' => 60]
        ];
    }
}

/**
 * Get historical sensor data for dashboard analytics
 * Returns readings for the last 6 time slots based on the logging interval
 * Compatible with MySQL 5.7 (InfinityFree)
 */
function getHistoricalSensorData($hours = 6) {
    try {
        $pdo = getDatabaseConnection();
        
        // Get logging interval from settings (in minutes)
        $intervalStmt = $pdo->prepare("
            SELECT setting_value 
            FROM user_settings 
            WHERE setting_key = 'sensor_logging_interval' 
            LIMIT 1
        ");
        $intervalStmt->execute();
        $intervalResult = $intervalStmt->fetch();
        $intervalMinutes = $intervalResult ? floatval($intervalResult['setting_value']) : 30;
        $intervalSeconds = $intervalMinutes * 60;
        
        // Calculate time window for 6 historical slots
        $totalSecondsNeeded = $intervalSeconds * 6;
        
        // Get readings within the time window (MySQL 5.7 compatible)
        $stmt = $pdo->prepare("
            SELECT 
                s.sensor_type,
                sr.value,
                sr.unit,
                sr.recorded_at,
                TIMESTAMPDIFF(SECOND, sr.recorded_at, NOW()) as seconds_ago
            FROM sensors s
            JOIN sensor_readings sr ON s.id = sr.sensor_id
            WHERE s.sensor_name LIKE 'Arduino%'
            AND sr.recorded_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ORDER BY s.sensor_type, sr.recorded_at DESC
        ");
        $stmt->execute([$totalSecondsNeeded + $intervalSeconds]);
        $results = $stmt->fetchAll();
        
        // Group by sensor type and assign to time slots
        $slotData = [];
        $sensorTypes = ['temperature', 'humidity', 'soil_moisture'];
        
        foreach ($sensorTypes as $type) {
            $slotData[$type] = [];
        }
        
        // Process readings and assign to time slots
        foreach ($results as $row) {
            $type = $row['sensor_type'];
            if (!in_array($type, $sensorTypes)) continue;
            
            $secondsAgo = intval($row['seconds_ago']);
            
            // Calculate slot index (0 = most recent, 5 = oldest)
            $slotIndex = floor($secondsAgo / $intervalSeconds);
            
            // Only include readings for slots 0-5
            if ($slotIndex >= 0 && $slotIndex < 6) {
                if (!isset($slotData[$type][$slotIndex])) {
                    $slotData[$type][$slotIndex] = [
                        'value' => $row['value'],
                        'unit' => $row['unit'],
                        'recorded_at' => $row['recorded_at']
                    ];
                }
            }
        }
        
        // Convert to sequential arrays (oldest to newest)
        $finalData = [];
        foreach ($sensorTypes as $type) {
            $finalData[$type] = [];
            for ($i = 5; $i >= 0; $i--) {
                if (isset($slotData[$type][$i])) {
                    $finalData[$type][] = $slotData[$type][$i];
                }
            }
        }
        
        return $finalData;
        
    } catch (Exception $e) {
        error_log("Failed to get historical sensor data: " . $e->getMessage());
        return [];
    }
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