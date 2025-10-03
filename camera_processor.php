<?php

/**
 * Camera Image Processor with Arduino Data Integration
 * Processes captured images and sensor data from Arduino
 */

header('Content-Type: application/json');

class CameraProcessor
{
    private $uploadDir = 'uploads/captures/';
    private $dbConnection = null;

    public function __construct()
    {
        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        // Initialize database connection
        $this->initDatabase();
    }

    private function initDatabase()
    {
        try {
            // Use your existing database configuration
            if (file_exists('config/database.php')) {
                require_once 'config/database.php';
                // Assuming you have database connection setup
            }
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Process uploaded image with Arduino sensor data
     */
    public function processCapture()
    {
        try {
            if (!isset($_FILES['image'])) {
                throw new Exception('No image uploaded');
            }

            $image = $_FILES['image'];
            $arduinoData = json_decode($_POST['arduino_data'] ?? '{}', true);

            // Validate image
            $this->validateImage($image);

            // Generate unique filename
            $filename = $this->generateFilename($image['name']);
            $filepath = $this->uploadDir . $filename;

            // Move uploaded file
            if (!move_uploaded_file($image['tmp_name'], $filepath)) {
                throw new Exception('Failed to save image');
            }

            // Process image (resize, analyze, etc.)
            $imageInfo = $this->analyzeImage($filepath);

            // Save to database
            $recordId = $this->saveToDatabase($filename, $filepath, $imageInfo, $arduinoData);

            // Perform any additional processing
            $analysis = $this->performImageAnalysis($filepath, $arduinoData);

            return [
                'success' => true,
                'record_id' => $recordId,
                'filename' => $filename,
                'filepath' => $filepath,
                'image_info' => $imageInfo,
                'arduino_data' => $arduinoData,
                'analysis' => $analysis,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }

    private function validateImage($image)
    {
        // Check for upload errors
        if ($image['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error: ' . $image['error']);
        }

        // Check file size (max 10MB)
        if ($image['size'] > 10 * 1024 * 1024) {
            throw new Exception('Image too large (max 10MB)');
        }

        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $image['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid image type: ' . $mimeType);
        }
    }

    private function generateFilename($originalName)
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $extension;
    }

    private function analyzeImage($filepath)
    {
        $imageInfo = getimagesize($filepath);

        return [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'type' => $imageInfo['mime'],
            'size' => filesize($filepath),
            'created' => date('Y-m-d H:i:s', filemtime($filepath))
        ];
    }

    private function saveToDatabase($filename, $filepath, $imageInfo, $arduinoData)
    {
        // Create a simple file-based storage if no database
        $record = [
            'id' => uniqid(),
            'filename' => $filename,
            'filepath' => $filepath,
            'image_info' => $imageInfo,
            'arduino_data' => $arduinoData,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $recordsFile = 'data/camera_records.json';

        // Create data directory if needed
        if (!file_exists('data')) {
            mkdir('data', 0755, true);
        }

        // Load existing records
        $records = [];
        if (file_exists($recordsFile)) {
            $records = json_decode(file_get_contents($recordsFile), true) ?: [];
        }

        // Add new record
        $records[] = $record;

        // Save records
        file_put_contents($recordsFile, json_encode($records, JSON_PRETTY_PRINT));

        return $record['id'];
    }

    private function performImageAnalysis($filepath, $arduinoData)
    {
        $analysis = [
            'brightness' => $this->calculateBrightness($filepath),
            'environmental_conditions' => $this->analyzeEnvironmentalConditions($arduinoData),
            'recommendations' => []
        ];

        // Add recommendations based on sensor data
        if (isset($arduinoData['temperature'])) {
            $temp = floatval($arduinoData['temperature']);
            if ($temp > 30) {
                $analysis['recommendations'][] = 'High temperature detected - consider cooling';
            } elseif ($temp < 15) {
                $analysis['recommendations'][] = 'Low temperature detected - consider heating';
            }
        }

        if (isset($arduinoData['humidity'])) {
            $humidity = floatval($arduinoData['humidity']);
            if ($humidity > 80) {
                $analysis['recommendations'][] = 'High humidity - check ventilation';
            } elseif ($humidity < 30) {
                $analysis['recommendations'][] = 'Low humidity - consider humidification';
            }
        }

        if (isset($arduinoData['motion']) && $arduinoData['motion'] === 'Yes') {
            $analysis['recommendations'][] = 'Motion detected - possible pest activity';
        }

        return $analysis;
    }

    private function calculateBrightness($filepath)
    {
        // Simple brightness calculation
        $image = imagecreatefromjpeg($filepath);
        if (!$image) return 0;

        $width = imagesx($image);
        $height = imagesy($image);
        $totalBrightness = 0;
        $pixelCount = 0;

        // Sample every 10th pixel for performance
        for ($x = 0; $x < $width; $x += 10) {
            for ($y = 0; $y < $height; $y += 10) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Calculate perceived brightness
                $brightness = (0.299 * $r + 0.587 * $g + 0.114 * $b);
                $totalBrightness += $brightness;
                $pixelCount++;
            }
        }

        imagedestroy($image);

        return $pixelCount > 0 ? round($totalBrightness / $pixelCount, 2) : 0;
    }

    private function analyzeEnvironmentalConditions($arduinoData)
    {
        $conditions = ['status' => 'normal', 'alerts' => []];

        if (isset($arduinoData['temperature'])) {
            $temp = floatval($arduinoData['temperature']);
            if ($temp > 35 || $temp < 10) {
                $conditions['status'] = 'warning';
                $conditions['alerts'][] = 'Temperature out of optimal range';
            }
        }

        if (isset($arduinoData['humidity'])) {
            $humidity = floatval($arduinoData['humidity']);
            if ($humidity > 85 || $humidity < 25) {
                $conditions['status'] = 'warning';
                $conditions['alerts'][] = 'Humidity out of optimal range';
            }
        }

        return $conditions;
    }

    /**
     * Get recent captures
     */
    public function getRecentCaptures($limit = 10)
    {
        $recordsFile = 'data/camera_records.json';

        if (!file_exists($recordsFile)) {
            return [];
        }

        $records = json_decode(file_get_contents($recordsFile), true) ?: [];

        // Sort by timestamp (newest first)
        usort($records, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($records, 0, $limit);
    }
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $processor = new CameraProcessor();
    $result = $processor->processCapture();
    echo json_encode($result);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'recent') {
    $processor = new CameraProcessor();
    $limit = intval($_GET['limit'] ?? 10);
    $recent = $processor->getRecentCaptures($limit);
    echo json_encode(['success' => true, 'captures' => $recent]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
