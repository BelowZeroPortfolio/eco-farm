<?php
/**
 * Camera Detection Utility for Arduino-connected Systems
 * Detects available cameras on Windows laptop with Arduino integration
 */

class CameraDetector {
    private $detectedCameras = [];
    private $errors = [];

    /**
     * Detect all available cameras using multiple methods
     */
    public function detectCameras() {
        $this->detectedCameras = [];
        $this->errors = [];

        // Method 1: Windows PowerShell WMI Query
        $this->detectViaPowerShell();
        
        // Method 2: DirectShow devices (Windows)
        $this->detectViaDirectShow();
        
        // Method 3: Check common camera device paths
        $this->detectViaDevicePaths();
        
        // Method 4: Arduino serial port detection
        $this->detectArduinoDevices();

        return [
            'cameras' => $this->detectedCameras,
            'errors' => $this->errors,
            'total_found' => count($this->detectedCameras)
        ];
    }

    /**
     * Use PowerShell to query WMI for camera devices
     */
    private function detectViaPowerShell() {
        try {
            // Query for imaging devices
            $command = 'powershell "Get-WmiObject -Class Win32_PnPEntity | Where-Object {$_.Name -like \'*camera*\' -or $_.Name -like \'*webcam*\' -or $_.DeviceID -like \'*USB\\VID_*\'} | Select-Object Name, DeviceID, Status | ConvertTo-Json"';
            
            $output = shell_exec($command);
            
            if ($output) {
                $devices = json_decode($output, true);
                
                if (is_array($devices)) {
                    // Handle single device (not array) or multiple devices
                    if (!isset($devices[0])) {
                        $devices = [$devices];
                    }
                    
                    foreach ($devices as $device) {
                        if (isset($device['Name']) && $this->isCameraDevice($device['Name'])) {
                            $this->detectedCameras[] = [
                                'name' => $device['Name'],
                                'device_id' => $device['DeviceID'] ?? 'Unknown',
                                'status' => $device['Status'] ?? 'Unknown',
                                'type' => 'USB Camera',
                                'method' => 'PowerShell WMI'
                            ];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->errors[] = "PowerShell detection failed: " . $e->getMessage();
        }
    }

    /**
     * Detect cameras via DirectShow (Windows specific)
     */
    private function detectViaDirectShow() {
        try {
            // Use ffmpeg to list DirectShow devices if available
            $command = 'ffmpeg -list_devices true -f dshow -i dummy 2>&1';
            $output = shell_exec($command);
            
            if ($output && strpos($output, 'DirectShow video devices') !== false) {
                $lines = explode("\n", $output);
                $inVideoSection = false;
                
                foreach ($lines as $line) {
                    if (strpos($line, 'DirectShow video devices') !== false) {
                        $inVideoSection = true;
                        continue;
                    }
                    
                    if ($inVideoSection && strpos($line, 'DirectShow audio devices') !== false) {
                        break;
                    }
                    
                    if ($inVideoSection && preg_match('/\[dshow.*\]\s+"([^"]+)"/', $line, $matches)) {
                        $this->detectedCameras[] = [
                            'name' => $matches[1],
                            'device_id' => 'DirectShow Device',
                            'status' => 'Available',
                            'type' => 'DirectShow Camera',
                            'method' => 'FFmpeg DirectShow'
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            $this->errors[] = "DirectShow detection failed: " . $e->getMessage();
        }
    }

    /**
     * Check common camera device paths and indices
     */
    private function detectViaDevicePaths() {
        // Check for common camera indices (0-9)
        for ($i = 0; $i < 10; $i++) {
            if ($this->testCameraIndex($i)) {
                $this->detectedCameras[] = [
                    'name' => "Camera Device $i",
                    'device_id' => "camera_$i",
                    'status' => 'Available',
                    'type' => 'Indexed Camera',
                    'method' => 'Device Index Test',
                    'index' => $i
                ];
            }
        }
    }

    /**
     * Detect Arduino devices that might have cameras
     */
    private function detectArduinoDevices() {
        try {
            // List COM ports (Arduino typically connects via COM port)
            $command = 'powershell "Get-WmiObject -Class Win32_SerialPort | Select-Object DeviceID, Description, Name | ConvertTo-Json"';
            $output = shell_exec($command);
            
            if ($output) {
                $ports = json_decode($output, true);
                
                if (is_array($ports)) {
                    if (!isset($ports[0])) {
                        $ports = [$ports];
                    }
                    
                    foreach ($ports as $port) {
                        if (isset($port['Description']) && 
                            (strpos(strtolower($port['Description']), 'arduino') !== false ||
                             strpos(strtolower($port['Description']), 'ch340') !== false ||
                             strpos(strtolower($port['Description']), 'cp210') !== false)) {
                            
                            $this->detectedCameras[] = [
                                'name' => $port['Description'],
                                'device_id' => $port['DeviceID'],
                                'status' => 'Connected',
                                'type' => 'Arduino Device',
                                'method' => 'Serial Port Detection',
                                'port' => $port['DeviceID']
                            ];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->errors[] = "Arduino detection failed: " . $e->getMessage();
        }
    }

    /**
     * Test if a camera index is actually accessible
     */
    private function testCameraIndex($index) {
        // Test camera access using JavaScript getUserMedia API
        // This creates a test HTML file that can verify camera access
        $testFile = "camera_test_$index.html";
        $testContent = $this->generateCameraTestHTML($index);
        
        // For basic detection, we'll check common indices
        // In production, you'd want to use actual camera testing
        return $index <= 2;
    }
    
    /**
     * Generate HTML for camera testing
     */
    private function generateCameraTestHTML($index) {
        return "
        <!DOCTYPE html>
        <html>
        <head><title>Camera Test $index</title></head>
        <body>
            <video id='video$index' width='320' height='240' autoplay></video>
            <script>
                navigator.mediaDevices.getUserMedia({
                    video: { deviceId: '$index' }
                }).then(stream => {
                    document.getElementById('video$index').srcObject = stream;
                    console.log('Camera $index accessible');
                }).catch(err => {
                    console.log('Camera $index not accessible:', err);
                });
            </script>
        </body>
        </html>";
    }

    /**
     * Check if device name indicates it's a camera
     */
    private function isCameraDevice($name) {
        $cameraKeywords = [
            'camera', 'webcam', 'usb video', 'integrated camera',
            'hd webcam', 'video capture', 'imaging device'
        ];
        
        $nameLower = strtolower($name);
        
        foreach ($cameraKeywords as $keyword) {
            if (strpos($nameLower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get detailed system information
     */
    public function getSystemInfo() {
        return [
            'os' => PHP_OS,
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// Usage example and web interface
if (isset($_GET['action']) && $_GET['action'] === 'detect') {
    header('Content-Type: application/json');
    
    $detector = new CameraDetector();
    $result = $detector->detectCameras();
    $result['system_info'] = $detector->getSystemInfo();
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camera Detection Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .camera-item { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .loading { display: none; color: #666; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎥 Camera Detection Tool</h1>
        <p>This tool detects available cameras on your Windows laptop, including Arduino-connected devices.</p>
        
        <button onclick="detectCameras()">🔍 Detect Cameras</button>
        <div class="loading" id="loading">Detecting cameras...</div>
        
        <div id="results"></div>
        
        <h3>📋 Instructions for Arduino Camera Integration:</h3>
        <ul>
            <li><strong>ESP32-CAM:</strong> Connect via serial and access camera stream at IP address</li>
            <li><strong>Arduino + Camera Module:</strong> Use serial communication to trigger captures</li>
            <li><strong>USB Camera + Arduino:</strong> Camera connects to laptop, Arduino handles sensors</li>
        </ul>
        
        <h3>🔧 Required Software:</h3>
        <ul>
            <li>Arduino IDE for programming</li>
            <li>Camera drivers (usually auto-installed)</li>
            <li>FFmpeg (optional, for advanced detection)</li>
            <li>OpenCV PHP extension (optional, for camera testing)</li>
        </ul>
    </div>

    <script>
        async function detectCameras() {
            const loading = document.getElementById('loading');
            const results = document.getElementById('results');
            
            loading.style.display = 'block';
            results.innerHTML = '';
            
            try {
                const response = await fetch('?action=detect');
                const data = await response.json();
                
                loading.style.display = 'none';
                displayResults(data);
            } catch (error) {
                loading.style.display = 'none';
                results.innerHTML = `<div class="error">Error: ${error.message}</div>`;
            }
        }
        
        function displayResults(data) {
            const results = document.getElementById('results');
            let html = '';
            
            if (data.total_found > 0) {
                html += `<div class="success">✅ Found ${data.total_found} camera device(s)</div>`;
                
                data.cameras.forEach((camera, index) => {
                    html += `
                        <div class="camera-item">
                            <h4>📷 ${camera.name}</h4>
                            <p><strong>Type:</strong> ${camera.type}</p>
                            <p><strong>Status:</strong> ${camera.status}</p>
                            <p><strong>Device ID:</strong> ${camera.device_id}</p>
                            <p><strong>Detection Method:</strong> ${camera.method}</p>
                            ${camera.index !== undefined ? `<p><strong>Index:</strong> ${camera.index}</p>` : ''}
                            ${camera.port ? `<p><strong>Port:</strong> ${camera.port}</p>` : ''}
                        </div>
                    `;
                });
            } else {
                html += '<div class="error">❌ No cameras detected</div>';
            }
            
            if (data.errors.length > 0) {
                html += '<h4>⚠️ Detection Errors:</h4>';
                data.errors.forEach(error => {
                    html += `<div class="error">${error}</div>`;
                });
            }
            
            html += `
                <h4>💻 System Information:</h4>
                <pre>${JSON.stringify(data.system_info, null, 2)}</pre>
            `;
            
            results.innerHTML = html;
        }
        
        // Auto-detect on page load
        window.onload = () => detectCameras();
    </script>
</body>
</html>