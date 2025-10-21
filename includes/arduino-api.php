<?php
/**
 * Arduino Bridge API Client
 * Handles communication with the Python Arduino bridge service
 */

class ArduinoBridge
{
    private $serviceUrl;
    private $timeout;
    private $connectTimeout;

    public function __construct($serviceUrl = 'http://127.0.0.1:5000', $timeout = 5, $connectTimeout = 3)
    {
        $this->serviceUrl = rtrim($serviceUrl, '/');
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
    }

    /**
     * Check if Arduino bridge service is healthy
     */
    public function isHealthy()
    {
        try {
            $response = $this->makeRequest('/health');
            return isset($response['status']) && $response['status'] === 'healthy';
        } catch (Exception $e) {
            error_log("Arduino bridge health check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all sensor data from Arduino
     */
    public function getAllSensorData()
    {
        try {
            $response = $this->makeRequest('/data');
            if (isset($response['status']) && $response['status'] === 'success') {
                return $response['data'];
            }
            return null;
        } catch (Exception $e) {
            error_log("Failed to get Arduino sensor data: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get specific sensor data
     */
    public function getSensorData($sensorType)
    {
        try {
            $response = $this->makeRequest("/data/{$sensorType}");
            if (isset($response['status']) && $response['status'] === 'success') {
                return $response['data'];
            }
            return null;
        } catch (Exception $e) {
            error_log("Failed to get {$sensorType} data: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get specific sensor data methods
     */
    public function getHumidityData()
    {
        return $this->getSensorData('humidity');
    }
    
    public function getTemperatureData()
    {
        return $this->getSensorData('temperature');
    }
    
    public function getSoilMoistureData()
    {
        return $this->getSensorData('soil_moisture');
    }

    /**
     * Store Arduino sensor reading in database
     */
    public function storeSensorReading($sensorType, $value, $unit = '%')
    {
        try {
            $pdo = getDatabaseConnection();
            
            // Get or create sensor record
            $sensorId = $this->getOrCreateSensor($sensorType);
            if (!$sensorId) {
                throw new Exception("Failed to get sensor ID for {$sensorType}");
            }

            // Insert reading
            $stmt = $pdo->prepare("
                INSERT INTO sensor_readings (sensor_id, value, unit, recorded_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([$sensorId, $value, $unit]);
            
            // Update sensor last reading time and status
            $updateStmt = $pdo->prepare("
                UPDATE sensors 
                SET last_reading_at = NOW(), status = 'online' 
                WHERE id = ?
            ");
            $updateStmt->execute([$sensorId]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to store sensor reading: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get or create sensor record in database
     */
    private function getOrCreateSensor($sensorType)
    {
        try {
            $pdo = getDatabaseConnection();
            
            // Try to find existing sensor
            $stmt = $pdo->prepare("SELECT id FROM sensors WHERE sensor_type = ? AND sensor_name LIKE ? LIMIT 1");
            $stmt->execute([$sensorType, "Arduino {$sensorType}%"]);
            $sensor = $stmt->fetch();
            
            if ($sensor) {
                return $sensor['id'];
            }
            
            // Create new sensor
            $sensorName = "Arduino " . ucfirst(str_replace('_', ' ', $sensorType));
            $location = "Farm Field";
            $arduinoPin = $this->getDefaultPin($sensorType);
            
            $insertStmt = $pdo->prepare("
                INSERT INTO sensors (sensor_name, sensor_type, location, arduino_pin, status, created_at) 
                VALUES (?, ?, ?, ?, 'online', NOW())
            ");
            
            $insertStmt->execute([$sensorName, $sensorType, $location, $arduinoPin]);
            return $pdo->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Failed to get/create sensor: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get default Arduino pin for sensor type
     */
    private function getDefaultPin($sensorType)
    {
        $pinMapping = [
            'temperature' => 2,    // DHT on pin 2
            'humidity' => 2,       // DHT on pin 2
            'soil_moisture' => 10  // A10 analog pin
        ];
        
        return $pinMapping[$sensorType] ?? 0;
    }

    /**
     * Sync all Arduino data to database
     */
    public function syncToDatabase()
    {
        $sensorData = $this->getAllSensorData();
        if (!$sensorData) {
            return false;
        }

        $synced = 0;
        foreach ($sensorData as $sensorType => $data) {
            if (isset($data['value']) && $data['value'] !== null) {
                $unit = $this->getUnit($sensorType);
                if ($this->storeSensorReading($sensorType, $data['value'], $unit)) {
                    $synced++;
                }
            }
        }

        return $synced;
    }

    /**
     * Get unit for sensor type
     */
    private function getUnit($sensorType)
    {
        $units = [
            'temperature' => '°C',
            'humidity' => '%',
            'soil_moisture' => '%'
        ];
        
        return $units[$sensorType] ?? '';
    }

    /**
     * Make HTTP request to Arduino bridge service
     */
    private function makeRequest($endpoint)
    {
        $url = $this->serviceUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'IoT Farm Monitor/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: {$error}");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP error: {$httpCode}");
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON decode error: " . json_last_error_msg());
        }
        
        return $decoded;
    }
}