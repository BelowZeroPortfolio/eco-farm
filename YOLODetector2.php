<?php

/**
 * YOLODetector2 - Flask Service Client
 * Interfaces with Flask-based YOLO detection service
 */
class YOLODetector2
{
    private $serviceUrl;
    private $timeout;
    private $connectTimeout;

    /**
     * Constructor
     * 
     * @param string $serviceUrl URL of Flask service (default: http://127.0.0.1:5000)
     * @param int $timeout Request timeout in seconds (default: 10)
     * @param int $connectTimeout Connection timeout in seconds (default: 5)
     */
    public function __construct($serviceUrl = 'http://127.0.0.1:5000', $timeout = 10, $connectTimeout = 5)
    {
        $this->serviceUrl = rtrim($serviceUrl, '/');
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
    }

    /**
     * Check if the YOLO service is healthy and running
     * 
     * @return bool True if service is healthy, false otherwise
     */
    public function isHealthy()
    {
        try {
            $ch = curl_init($this->serviceUrl . '/health');
            
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 2,
                CURLOPT_CONNECTTIMEOUT => 1
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return isset($data['status']) && $data['status'] === 'healthy';
            }
            
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Detect pests in an image
     * 
     * @param string $imagePath Path to the image file
     * @param bool $returnFullResponse Return full response including annotated image path
     * @return array Array of detections with type and confidence, or full response
     * @throws Exception If detection fails
     */
    public function detectPests($imagePath, $returnFullResponse = false)
    {
        // Validate image file exists
        if (!file_exists($imagePath)) {
            throw new Exception("Image file not found: " . $imagePath);
        }

        // Check if service is healthy
        if (!$this->isHealthy()) {
            throw new Exception("YOLO service is not available. Please ensure the service is running.");
        }

        // Send request to Flask service
        $response = $this->sendDetectionRequest($imagePath);

        // Parse results
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response: " . json_last_error_msg());
        }

        if (!isset($data['pests']) || !is_array($data['pests'])) {
            throw new Exception("Invalid detection results structure");
        }

        // Return full response or just pests array
        return $returnFullResponse ? $data : $data['pests'];
    }

    /**
     * Send detection request to Flask service
     * 
     * @param string $imagePath Path to the image file
     * @return string JSON response from service
     * @throws Exception If request fails
     */
    private function sendDetectionRequest($imagePath)
    {
        $ch = curl_init($this->serviceUrl . '/detect');
        
        // Create CURLFile for image upload
        $cfile = new CURLFile($imagePath, 'image/jpeg', 'frame.jpg');
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['image' => $cfile],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Check for cURL errors
        if ($response === false) {
            throw new Exception("cURL error: " . $error);
        }
        
        // Check HTTP status code
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = isset($errorData['message']) ? $errorData['message'] : "HTTP $httpCode";
            throw new Exception("YOLO service error: " . $errorMsg);
        }
        
        return $response;
    }

    /**
     * Parse JSON results from Flask service
     * 
     * @param string $response Raw JSON response
     * @return array Parsed detections
     * @throws Exception If parsing fails
     */
    private function parseResults($response)
    {
        // Decode JSON
        $data = json_decode($response, true);

        // Check for JSON errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response: " . json_last_error_msg());
        }

        // Validate structure
        if (!isset($data['pests']) || !is_array($data['pests'])) {
            throw new Exception("Invalid detection results structure");
        }

        return $data['pests'];
    }

    /**
     * Get model information from service
     * 
     * @return array Model information
     * @throws Exception If request fails
     */
    public function getModelInfo()
    {
        $ch = curl_init($this->serviceUrl . '/info');
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 2
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Failed to get model info");
        }
        
        return json_decode($response, true);
    }

    /**
     * Get service URL
     * 
     * @return string Service URL
     */
    public function getServiceUrl()
    {
        return $this->serviceUrl;
    }
}
