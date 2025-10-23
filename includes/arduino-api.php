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
     * Store Arduino sensor reading in database with interval checking
     */
    public function storeSensorReading($sensorType, $value, $unit = '%')
    {
        try {
            // Validate sensor type
            if (!in_array($sensorType, ['temperature', 'humidity', 'soil_moisture'])) {
                throw new Exception("Invalid sensor type: {$sensorType}");
            }

            // Validate value
            if (!is_numeric($value)) {
                throw new Exception("Invalid sensor value: must be numeric");
            }

            // Validate unit
            if (empty($unit) || strlen($unit) > 20) {
                throw new Exception("Invalid unit: must be 1-20 characters");
            }

            $pdo = getDatabaseConnection();
            
            // Get or create sensor record
            $sensorId = $this->getOrCreateSensor($sensorType);
            if (!$sensorId) {
                throw new Exception("Failed to get sensor ID for {$sensorType}");
            }

            // Check if enough time has passed since last reading (respects interval setting)
            if (!$this->shouldLogReading($sensorId)) {
                return false; // Skip logging, interval not reached
            }

            // Insert reading
            $stmt = $pdo->prepare("
                INSERT INTO sensor_readings (sensor_id, value, unit, recorded_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            if (!$stmt->execute([$sensorId, $value, $unit])) {
                throw new Exception("Failed to insert sensor reading");
            }
            
            // Update sensor last reading time and status
            $updateStmt = $pdo->prepare("
                UPDATE sensors 
                SET last_reading_at = NOW(), status = 'online' 
                WHERE id = ?
            ");
            
            if (!$updateStmt->execute([$sensorId])) {
                throw new Exception("Failed to update sensor status");
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Database error storing sensor reading: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Failed to store sensor reading: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if enough time has passed to log a new reading based on interval setting
     */
    private function shouldLogReading($sensorId)
    {
        try {
            $pdo = getDatabaseConnection();
            
            // Get logging interval from settings (in minutes)
            $interval = $this->getLoggingInterval();
            
            // Get last reading time for this sensor
            $stmt = $pdo->prepare("
                SELECT MAX(recorded_at) as last_reading 
                FROM sensor_readings 
                WHERE sensor_id = ?
            ");
            $stmt->execute([$sensorId]);
            $result = $stmt->fetch();
            
            // If no previous reading, allow logging
            if (!$result || !$result['last_reading']) {
                return true;
            }
            
            // Calculate time difference in minutes
            $lastReading = strtotime($result['last_reading']);
            $now = time();
            $minutesPassed = ($now - $lastReading) / 60;
            
            // Check if interval has passed
            return $minutesPassed >= $interval;
            
        } catch (Exception $e) {
            error_log("Error checking logging interval: " . $e->getMessage());
            return true; // Default to allowing logging on error
        }
    }

    /**
     * Get sensor logging interval from settings (in minutes)
     */
    private function getLoggingInterval()
    {
        try {
            $pdo = getDatabaseConnection();
            
            if (!$pdo) {
                throw new Exception("Database connection failed");
            }
            
            // Try to get from user_settings table
            $stmt = $pdo->prepare("
                SELECT setting_value 
                FROM user_settings 
                WHERE setting_key = 'sensor_logging_interval' 
                LIMIT 1
            ");
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query");
            }
            
            $result = $stmt->fetch();
            
            if ($result && is_numeric($result['setting_value'])) {
                $interval = (float)$result['setting_value'];
                // Validate interval is within acceptable range (5 seconds to 24 hours)
                // 0.0833 minutes = 5 seconds
                if ($interval >= 0.0833 && $interval <= 1440) {
                    return $interval;
                }
            }
            
            // Default to 30 minutes if not set or invalid
            return 30;
            
        } catch (PDOException $e) {
            error_log("Database error getting logging interval: " . $e->getMessage());
            return 30; // Default fallback
        } catch (Exception $e) {
            error_log("Error getting logging interval: " . $e->getMessage());
            return 30; // Default fallback
        }
    }

    /**
     * Set sensor logging interval (admin only)
     */
    public function setLoggingInterval($intervalMinutes, $userId)
    {
        try {
            // Validate interval
            if (!is_numeric($intervalMinutes)) {
                throw new Exception("Interval must be numeric");
            }

            $interval = (float)$intervalMinutes;

            // Validate range (5 seconds to 24 hours)
            // 0.0833 minutes = 5 seconds
            if ($interval < 0.0833 || $interval > 1440) {
                throw new Exception("Interval must be between 5 seconds and 24 hours");
            }

            // Validate user ID
            if (!is_numeric($userId) || $userId <= 0) {
                throw new Exception("Invalid user ID");
            }

            $pdo = getDatabaseConnection();
            
            if (!$pdo) {
                throw new Exception("Database connection failed");
            }
            
            // Check if setting exists
            $stmt = $pdo->prepare("
                SELECT id FROM user_settings 
                WHERE setting_key = 'sensor_logging_interval' 
                LIMIT 1
            ");
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to check existing settings");
            }
            
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Update existing setting
                $stmt = $pdo->prepare("
                    UPDATE user_settings 
                    SET setting_value = ?, updated_at = NOW() 
                    WHERE setting_key = 'sensor_logging_interval'
                ");
                $result = $stmt->execute([$interval]);
            } else {
                // Insert new setting
                $stmt = $pdo->prepare("
                    INSERT INTO user_settings (user_id, setting_key, setting_value, created_at) 
                    VALUES (?, 'sensor_logging_interval', ?, NOW())
                ");
                $result = $stmt->execute([$userId, $interval]);
            }

            if (!$result) {
                throw new Exception("Failed to save logging interval setting");
            }

            // Format message based on interval
            $formattedInterval = $this->formatInterval($interval);

            return [
                'success' => true,
                'message' => "Sensor logging interval set to {$formattedInterval}",
                'interval' => $interval
            ];
            
        } catch (PDOException $e) {
            error_log("Database error setting logging interval: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Database error: " . $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log("Error setting logging interval: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get current logging interval setting
     */
    public function getLoggingIntervalSetting()
    {
        try {
            $interval = $this->getLoggingInterval();
            
            // Convert to human-readable format
            $formatted = $this->formatInterval($interval);
            
            return [
                'success' => true,
                'interval_minutes' => $interval,
                'formatted' => $formatted
            ];
            
        } catch (Exception $e) {
            error_log("Error getting logging interval setting: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'interval_minutes' => 30,
                'formatted' => '30 minutes'
            ];
        }
    }

    /**
     * Format interval in minutes to human-readable string
     */
    private function formatInterval($minutes)
    {
        if ($minutes < 1) {
            // Less than 1 minute, show in seconds
            $seconds = round($minutes * 60);
            return "{$seconds} seconds";
        } elseif ($minutes < 60) {
            return "{$minutes} minutes";
        } elseif ($minutes == 60) {
            return "1 hour";
        } elseif ($minutes < 1440) {
            $hours = $minutes / 60;
            return number_format($hours, 1) . " hours";
        } else {
            return "24 hours";
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