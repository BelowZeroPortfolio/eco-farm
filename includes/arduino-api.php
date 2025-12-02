
<?php
/**
 * Arduino Bridge API Client
 * Handles communication with the Python Arduino bridge service
 */

// Load environment configuration
require_once __DIR__ . '/../config/env.php';

class ArduinoBridge
{
    private $serviceUrl;
    private $timeout;
    private $connectTimeout;
    private $useNgrok = false;
    private $ngrokHeaders = [];

    public function __construct($serviceUrl = null, $timeout = 5, $connectTimeout = 3)
    {
        if ($serviceUrl === null) {
            // Check if we should use ngrok (for InfinityFree/remote) or local
            $ngrokHost = Env::get('ARDUINO_SENSOR_HOST', '');
            $localHost = Env::get('ARDUINO_SERVICE_HOST', '127.0.0.1');
            $localPort = Env::get('ARDUINO_SERVICE_PORT', '5001');
            
            // Try local first, fall back to ngrok if local unreachable
            if ($this->canReachLocal($localHost, $localPort)) {
                $serviceUrl = "http://{$localHost}:{$localPort}";
            } elseif (!empty($ngrokHost)) {
                // Use ngrok tunnel (for InfinityFree)
                $protocol = Env::get('ARDUINO_SENSOR_PROTOCOL', 'https');
                $port = Env::get('ARDUINO_SENSOR_PORT', '443');
                $serviceUrl = "{$protocol}://{$ngrokHost}";
                if ($port != '443' && $port != '80') {
                    $serviceUrl .= ":{$port}";
                }
                $this->useNgrok = true;
                $this->ngrokHeaders = ['ngrok-skip-browser-warning: true'];
            } else {
                // Fallback to local even if unreachable
                $serviceUrl = "http://{$localHost}:{$localPort}";
            }
        }
        
        $this->serviceUrl = rtrim($serviceUrl, '/');
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
    }

    /**
     * Check if local Arduino bridge is reachable
     */
    private function canReachLocal($host, $port)
    {
        $socket = @fsockopen($host, $port, $errno, $errstr, 1);
        if ($socket) {
            fclose($socket);
            return true;
        }
        return false;
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
     * Now stores to sensorreadings table which has all sensors in one row
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
            
            // Get or create sensor record (for status tracking)
            $sensorId = $this->getOrCreateSensor($sensorType);
            if (!$sensorId) {
                throw new Exception("Failed to get sensor ID for {$sensorType}");
            }

            // Check if enough time has passed since last reading (respects interval setting)
            if (!$this->shouldLogReading($sensorId)) {
                error_log("storeSensorReading: Skipping {$sensorType} - interval not reached");
                return false; // Skip logging, interval not reached
            }

            // Get active plant ID (default to 1 if none set)
            $plantId = $this->getActivePlantId($pdo);
            
            // Map sensor type to column name
            $columnMap = [
                'temperature' => 'Temperature',
                'humidity' => 'Humidity',
                'soil_moisture' => 'SoilMoisture'
            ];
            $column = $columnMap[$sensorType];
            
            // Check if there's a recent reading (within last minute) to update
            $stmt = $pdo->prepare("
                SELECT ReadingID FROM sensorreadings 
                WHERE PlantID = ? AND ReadingTime >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                ORDER BY ReadingTime DESC LIMIT 1
            ");
            $stmt->execute([$plantId]);
            $existingReading = $stmt->fetch();
            
            // Use PHP date() to ensure correct Philippine timezone
            $philippineTime = date('Y-m-d H:i:s');
            
            if ($existingReading) {
                // Update existing reading
                $stmt = $pdo->prepare("UPDATE sensorreadings SET {$column} = ? WHERE ReadingID = ?");
                $stmt->execute([$value, $existingReading['ReadingID']]);
            } else {
                // Insert new reading with default values for other sensors
                $defaults = ['SoilMoisture' => 0, 'Temperature' => 0, 'Humidity' => 0];
                $defaults[$column] = $value;
                
                $stmt = $pdo->prepare("
                    INSERT INTO sensorreadings (PlantID, SoilMoisture, Temperature, Humidity, WarningLevel, ReadingTime) 
                    VALUES (?, ?, ?, ?, 0, ?)
                ");
                $stmt->execute([$plantId, $defaults['SoilMoisture'], $defaults['Temperature'], $defaults['Humidity'], $philippineTime]);
            }
            
            error_log("storeSensorReading: Successfully logged {$sensorType} = {$value}{$unit}");
            
            // Update sensor last reading time and status
            $updateStmt = $pdo->prepare("
                UPDATE sensors 
                SET last_reading_at = ?, status = 'online' 
                WHERE id = ?
            ");
            
            if (!$updateStmt->execute([$philippineTime, $sensorId])) {
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
     * Get active plant ID from activeplant table
     */
    private function getActivePlantId($pdo)
    {
        try {
            $stmt = $pdo->query("SELECT SelectedPlantID FROM activeplant ORDER BY UpdatedAt DESC LIMIT 1");
            $result = $stmt->fetch();
            return $result ? $result['SelectedPlantID'] : 1;
        } catch (Exception $e) {
            return 1; // Default to plant ID 1
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
            
            // Get last reading time from sensorreadings table
            $stmt = $pdo->prepare("
                SELECT 
                    MAX(ReadingTime) as last_reading,
                    TIMESTAMPDIFF(SECOND, MAX(ReadingTime), NOW()) as seconds_passed
                FROM sensorreadings
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            
            // If no previous reading, allow logging
            if (!$result || !$result['last_reading']) {
                error_log("shouldLogReading: No previous reading found, allowing log");
                return true;
            }
            
            // Get seconds passed from MySQL calculation
            $secondsPassed = intval($result['seconds_passed']);
            
            // Convert interval from minutes to seconds
            $intervalSeconds = $interval * 60;
            
            // Check if interval has passed (with 1 second tolerance for timing issues)
            $shouldLog = $secondsPassed >= ($intervalSeconds - 1);
            
            // Debug logging for small intervals
            if ($interval <= 1) {
                error_log(sprintf(
                    "shouldLogReading [sensor_id=%d]: %d seconds passed, need %.1f seconds, result=%s",
                    $sensorId,
                    $secondsPassed,
                    $intervalSeconds,
                    $shouldLog ? 'ALLOW' : 'SKIP'
                ));
            }
            
            return $shouldLog;
            
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
     * Saves ALL sensors in ONE row to ensure data consistency
     */
    public function syncToDatabase()
    {
        $sensorData = $this->getAllSensorData();
        if (!$sensorData) {
            return false;
        }

        // Check if we should log based on interval (check once for all sensors)
        $sensorId = $this->getOrCreateSensor('temperature'); // Use any sensor for interval check
        if (!$this->shouldLogReading($sensorId)) {
            error_log("syncToDatabase: Skipping - interval not reached");
            return 0;
        }

        // Extract all sensor values
        $temperature = isset($sensorData['temperature']['value']) ? floatval($sensorData['temperature']['value']) : 0;
        $humidity = isset($sensorData['humidity']['value']) ? floatval($sensorData['humidity']['value']) : 0;
        $soilMoisture = isset($sensorData['soil_moisture']['value']) ? floatval($sensorData['soil_moisture']['value']) : 0;

        try {
            $pdo = getDatabaseConnection();
            
            // Get active plant ID
            $plantId = $this->getActivePlantId($pdo);
            
            // Use PHP date() to ensure correct Philippine timezone
            $philippineTime = date('Y-m-d H:i:s');
            
            // Insert ALL sensor values in ONE row
            $stmt = $pdo->prepare("
                INSERT INTO sensorreadings (PlantID, SoilMoisture, Temperature, Humidity, WarningLevel, ReadingTime) 
                VALUES (?, ?, ?, ?, 0, ?)
            ");
            $stmt->execute([$plantId, $soilMoisture, $temperature, $humidity, $philippineTime]);
            
            // Update all sensor statuses
            $pdo->prepare("UPDATE sensors SET last_reading_at = ?, status = 'online' WHERE sensor_type IN ('temperature', 'humidity', 'soil_moisture')")
                ->execute([$philippineTime]);
            
            error_log("syncToDatabase: Saved all sensors - Temp={$temperature}, Hum={$humidity}, Soil={$soilMoisture}");
            return 3; // All 3 sensors saved
            
        } catch (Exception $e) {
            error_log("syncToDatabase error: " . $e->getMessage());
            return 0;
        }
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
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'IoT Farm Monitor/1.0'
        ];
        
        // Add ngrok headers if using ngrok tunnel
        if ($this->useNgrok && !empty($this->ngrokHeaders)) {
            $options[CURLOPT_HTTPHEADER] = $this->ngrokHeaders;
        }
        
        curl_setopt_array($ch, $options);
        
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

    /**
     * Check if using ngrok tunnel
     */
    public function isUsingNgrok()
    {
        return $this->useNgrok;
    }

    /**
     * Get the service URL being used
     */
    public function getServiceUrl()
    {
        return $this->serviceUrl;
    }
}
