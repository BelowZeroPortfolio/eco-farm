<?php
/**
 * Plant Monitoring Logic
 * Reads sensors, compares with active plant thresholds, generates notifications
 */

require_once __DIR__ . '/../config/database.php';

class PlantMonitor {
    private $pdo;
    private $activePlant = null;
    
    public function __construct() {
        // Set timezone to Philippines (UTC+8)
        date_default_timezone_set('Asia/Manila');
        
        $this->pdo = getDatabaseConnection();
        $this->loadActivePlant();
    }
    
    /**
     * Load active plant configuration
     */
    private function loadActivePlant() {
        $stmt = $this->pdo->query("
            SELECT p.* 
            FROM plants p
            INNER JOIN activeplant ap ON p.PlantID = ap.SelectedPlantID
            LIMIT 1
        ");
        $this->activePlant = $stmt->fetch();
    }
    
    /**
     * Get active plant data
     */
    public function getActivePlant() {
        return $this->activePlant;
    }
    
    /**
     * Process sensor readings and check thresholds
     * Tracks consecutive violations over time
     */
    public function processSensorReading($soilMoisture, $temperature, $humidity) {
        if (!$this->activePlant) {
            return [
                'success' => false,
                'message' => 'No active plant configured'
            ];
        }
        
        $plantID = $this->activePlant['PlantID'];
        $violations = [];
        $currentViolationCount = 0;
        
        // Check soil moisture
        if ($soilMoisture < $this->activePlant['MinSoilMoisture']) {
            $violations[] = [
                'sensor' => 'Soil Moisture',
                'status' => 'Below Minimum',
                'current' => $soilMoisture,
                'range' => $this->activePlant['MinSoilMoisture'] . '–' . $this->activePlant['MaxSoilMoisture'] . '%'
            ];
            $currentViolationCount++;
        } elseif ($soilMoisture > $this->activePlant['MaxSoilMoisture']) {
            $violations[] = [
                'sensor' => 'Soil Moisture',
                'status' => 'Above Maximum',
                'current' => $soilMoisture,
                'range' => $this->activePlant['MinSoilMoisture'] . '–' . $this->activePlant['MaxSoilMoisture'] . '%'
            ];
            $currentViolationCount++;
        }
        
        // Check temperature
        if ($temperature < $this->activePlant['MinTemperature']) {
            $violations[] = [
                'sensor' => 'Temperature',
                'status' => 'Below Minimum',
                'current' => $temperature,
                'range' => $this->activePlant['MinTemperature'] . '–' . $this->activePlant['MaxTemperature'] . '°C'
            ];
            $currentViolationCount++;
        } elseif ($temperature > $this->activePlant['MaxTemperature']) {
            $violations[] = [
                'sensor' => 'Temperature',
                'status' => 'Above Maximum',
                'current' => $temperature,
                'range' => $this->activePlant['MinTemperature'] . '–' . $this->activePlant['MaxTemperature'] . '°C'
            ];
            $currentViolationCount++;
        }
        
        // Check humidity
        if ($humidity < $this->activePlant['MinHumidity']) {
            $violations[] = [
                'sensor' => 'Humidity',
                'status' => 'Below Minimum',
                'current' => $humidity,
                'range' => $this->activePlant['MinHumidity'] . '–' . $this->activePlant['MaxHumidity'] . '%'
            ];
            $currentViolationCount++;
        } elseif ($humidity > $this->activePlant['MaxHumidity']) {
            $violations[] = [
                'sensor' => 'Humidity',
                'status' => 'Above Maximum',
                'current' => $humidity,
                'range' => $this->activePlant['MinHumidity'] . '–' . $this->activePlant['MaxHumidity'] . '%'
            ];
            $currentViolationCount++;
        }
        
        // Get consecutive violation count from previous readings
        $consecutiveViolations = $this->getConsecutiveViolations($plantID);
        
        // If there are current violations, increment the consecutive count
        if ($currentViolationCount > 0) {
            $consecutiveViolations++;
        } else {
            // Reset consecutive violations if all sensors are within thresholds
            $consecutiveViolations = 0;
        }
        
        // Save sensor reading with consecutive violation count
        $readingID = $this->saveSensorReading($plantID, $soilMoisture, $temperature, $humidity, $consecutiveViolations);
        
        // Generate notifications if consecutive violations reached warning trigger
        $notificationTriggered = false;
        if ($consecutiveViolations >= $this->activePlant['WarningTrigger']) {
            // Only send notification once when threshold is reached
            if ($consecutiveViolations == $this->activePlant['WarningTrigger']) {
                foreach ($violations as $violation) {
                    $this->generateNotification($plantID, $violation, $consecutiveViolations);
                }
                $notificationTriggered = true;
            }
        }
        
        return [
            'success' => true,
            'reading_id' => $readingID,
            'warning_level' => $consecutiveViolations,
            'current_violations' => $currentViolationCount,
            'consecutive_violations' => $consecutiveViolations,
            'violations' => $violations,
            'notification_triggered' => $notificationTriggered
        ];
    }
    
    /**
     * Get consecutive violation count from recent readings
     */
    private function getConsecutiveViolations($plantID) {
        try {
            // Get the most recent reading
            $stmt = $this->pdo->prepare("
                SELECT WarningLevel 
                FROM sensorreadings 
                WHERE PlantID = ? 
                ORDER BY ReadingTime DESC 
                LIMIT 1
            ");
            $stmt->execute([$plantID]);
            $result = $stmt->fetch();
            
            if ($result) {
                return intval($result['WarningLevel']);
            }
            
            return 0;
        } catch (Exception $e) {
            error_log("Failed to get consecutive violations: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Save sensor reading to database
     */
    private function saveSensorReading($plantID, $soilMoisture, $temperature, $humidity, $warningLevel) {
        $stmt = $this->pdo->prepare("
            INSERT INTO sensorreadings (PlantID, SoilMoisture, Temperature, Humidity, WarningLevel)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$plantID, $soilMoisture, $temperature, $humidity, $warningLevel]);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Generate notification for threshold violation
     */
    private function generateNotification($plantID, $violation, $warningLevel) {
        $message = sprintf(
            "%s %s is %s. Current value: %s, Required range: %s",
            $this->activePlant['PlantName'],
            $violation['sensor'],
            $violation['status'],
            $violation['current'],
            $violation['range']
        );
        
        $sensorType = strtolower(str_replace(' ', '_', $violation['sensor']));
        
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications 
            (PlantID, Message, SensorType, Level, SuggestedAction, CurrentValue, RequiredRange, Status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $plantID,
            $message,
            $sensorType,
            $warningLevel,
            $this->activePlant['SuggestedAction'],
            $violation['current'],
            $violation['range'],
            $violation['status']
        ]);
    }
    
    /**
     * Get recent notifications
     */
    public function getNotifications($limit = 10, $unreadOnly = false) {
        $sql = "
            SELECT n.*, p.PlantName, p.LocalName
            FROM notifications n
            INNER JOIN plants p ON n.PlantID = p.PlantID
        ";
        
        if ($unreadOnly) {
            $sql .= " WHERE n.IsRead = 0";
        }
        
        $sql .= " ORDER BY n.CreatedAt DESC LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationRead($notificationID) {
        $stmt = $this->pdo->prepare("
            UPDATE Notifications 
            SET IsRead = 1, ReadAt = NOW() 
            WHERE NotificationID = ?
        ");
        return $stmt->execute([$notificationID]);
    }
    
    /**
     * Get notification as JSON format
     */
    public function getNotificationJSON($notificationID) {
        $stmt = $this->pdo->prepare("
            SELECT n.*, p.PlantName, p.LocalName
            FROM notifications n
            INNER JOIN plants p ON n.PlantID = p.PlantID
            WHERE n.NotificationID = ?
        ");
        $stmt->execute([$notificationID]);
        $notification = $stmt->fetch();
        
        if (!$notification) {
            return null;
        }
        
        return [
            'plant' => $notification['PlantName'],
            'localName' => $notification['LocalName'],
            'sensor' => ucwords(str_replace('_', ' ', $notification['SensorType'])),
            'status' => $notification['Status'],
            'currentValue' => $notification['CurrentValue'],
            'requiredRange' => $notification['RequiredRange'],
            'recommendation' => $notification['SuggestedAction'],
            'warningLevel' => $notification['Level'],
            'timestamp' => $notification['CreatedAt']
        ];
    }
    
    /**
     * Get latest sensor readings
     */
    public function getLatestReadings($limit = 20) {
        $stmt = $this->pdo->prepare("
            SELECT sr.*, p.PlantName, p.LocalName
            FROM sensorreadings sr
            INNER JOIN plants p ON sr.PlantID = p.PlantID
            ORDER BY sr.ReadingTime DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get sensor statistics
     */
    public function getSensorStatistics($hours = 24) {
        $stmt = $this->pdo->prepare("
            SELECT 
                AVG(SoilMoisture) as avg_soil,
                AVG(Temperature) as avg_temp,
                AVG(Humidity) as avg_humidity,
                MIN(Temperature) as min_temp,
                MAX(Temperature) as max_temp,
                COUNT(*) as reading_count
            FROM sensorreadings
            WHERE ReadingTime >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        ");
        $stmt->execute([$hours]);
        return $stmt->fetch();
    }
}
