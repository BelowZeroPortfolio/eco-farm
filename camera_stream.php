<?php
/**
 * Camera Stream Handler for Arduino Integration
 * Handles camera streaming and Arduino communication
 */

class CameraStream {
    private $cameraIndex;
    private $streamActive = false;
    private $arduinoPort = null;
    
    public function __construct($cameraIndex = 0) {
        $this->cameraIndex = $cameraIndex;
    }
    
    /**
     * Start camera stream with Arduino integration
     */
    public function startStream($arduinoPort = null) {
        $this->arduinoPort = $arduinoPort;
        
        // Generate streaming page
        return $this->generateStreamPage();
    }
    
    /**
     * Generate HTML page for camera streaming
     */
    private function generateStreamPage() {
        return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arduino Camera Stream</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f0f0f0; }
        .container { max-width: 1200px; margin: 0 auto; }
        .camera-container { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        video { width: 100%; max-width: 640px; height: auto; border: 2px solid #ddd; border-radius: 8px; }
        .controls { margin-top: 15px; }
        button { padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .status.connected { background: #d4edda; color: #155724; }
        .status.disconnected { background: #f8d7da; color: #721c24; }
        .arduino-data { background: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 15px; }
        canvas { display: none; }
        .captured-image { max-width: 200px; margin: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎥 Arduino Camera Stream</h1>
        
        <div class="camera-container">
            <h3>Camera Feed</h3>
            <video id="cameraVideo" autoplay playsinline></video>
            <canvas id="captureCanvas"></canvas>
            
            <div class="controls">
                <button class="btn-primary" onclick="startCamera()">📹 Start Camera</button>
                <button class="btn-danger" onclick="stopCamera()">⏹️ Stop Camera</button>
                <button class="btn-success" onclick="captureImage()">📸 Capture Image</button>
                <button class="btn-warning" onclick="toggleArduino()">🔌 Toggle Arduino</button>
            </div>
            
            <div id="cameraStatus" class="status disconnected">Camera: Disconnected</div>
            <div id="arduinoStatus" class="status disconnected">Arduino: Disconnected</div>
        </div>
        
        <div class="camera-container">
            <h3>📊 Arduino Sensor Data</h3>
            <div id="sensorData" class="arduino-data">
                <p>Temperature: <span id="temperature">--</span>°C</p>
                <p>Humidity: <span id="humidity">--</span>%</p>
                <p>Light Level: <span id="lightLevel">--</span></p>
                <p>Motion Detected: <span id="motion">No</span></p>
            </div>
        </div>
        
        <div class="camera-container">
            <h3>📷 Captured Images</h3>
            <div id="capturedImages"></div>
        </div>
    </div>

    <script>
        let video = document.getElementById("cameraVideo");
        let canvas = document.getElementById("captureCanvas");
        let ctx = canvas.getContext("2d");
        let stream = null;
        let arduinoConnected = false;
        let captureCount = 0;
        
        // Camera functions
        async function startCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { 
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        deviceId: "' . $this->cameraIndex . '"
                    }
                });
                
                video.srcObject = stream;
                updateStatus("cameraStatus", "Camera: Connected", "connected");
                
                // Start Arduino communication simulation
                startArduinoSimulation();
                
            } catch (error) {
                console.error("Camera access error:", error);
                updateStatus("cameraStatus", "Camera: Error - " + error.message, "disconnected");
            }
        }
        
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                video.srcObject = null;
                stream = null;
                updateStatus("cameraStatus", "Camera: Disconnected", "disconnected");
            }
        }
        
        function captureImage() {
            if (!stream) {
                alert("Please start camera first");
                return;
            }
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0);
            
            // Convert to blob and display
            canvas.toBlob(function(blob) {
                const url = URL.createObjectURL(blob);
                const img = document.createElement("img");
                img.src = url;
                img.className = "captured-image";
                img.title = "Captured at " + new Date().toLocaleString();
                
                document.getElementById("capturedImages").appendChild(img);
                
                // Send to server for processing
                sendImageToServer(blob);
                
                captureCount++;
            });
        }
        
        function sendImageToServer(blob) {
            const formData = new FormData();
            formData.append("image", blob, "capture_" + Date.now() + ".jpg");
            formData.append("arduino_data", JSON.stringify(getCurrentSensorData()));
            
            fetch("camera_processor.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log("Image processed:", data);
            })
            .catch(error => {
                console.error("Upload error:", error);
            });
        }
        
        // Arduino simulation functions
        function startArduinoSimulation() {
            updateStatus("arduinoStatus", "Arduino: Connected", "connected");
            arduinoConnected = true;
            
            // Simulate sensor data updates
            setInterval(updateSensorData, 2000);
        }
        
        function toggleArduino() {
            arduinoConnected = !arduinoConnected;
            if (arduinoConnected) {
                updateStatus("arduinoStatus", "Arduino: Connected", "connected");
            } else {
                updateStatus("arduinoStatus", "Arduino: Disconnected", "disconnected");
            }
        }
        
        function updateSensorData() {
            if (!arduinoConnected) return;
            
            // Simulate sensor readings
            const temperature = (20 + Math.random() * 15).toFixed(1);
            const humidity = (40 + Math.random() * 40).toFixed(1);
            const lightLevel = Math.floor(Math.random() * 1024);
            const motion = Math.random() > 0.8 ? "Yes" : "No";
            
            document.getElementById("temperature").textContent = temperature;
            document.getElementById("humidity").textContent = humidity;
            document.getElementById("lightLevel").textContent = lightLevel;
            document.getElementById("motion").textContent = motion;
        }
        
        function getCurrentSensorData() {
            return {
                temperature: document.getElementById("temperature").textContent,
                humidity: document.getElementById("humidity").textContent,
                lightLevel: document.getElementById("lightLevel").textContent,
                motion: document.getElementById("motion").textContent,
                timestamp: new Date().toISOString()
            };
        }
        
        function updateStatus(elementId, message, className) {
            const element = document.getElementById(elementId);
            element.textContent = message;
            element.className = "status " + className;
        }
        
        // Auto-start camera on page load
        window.onload = function() {
            startCamera();
        };
    </script>
</body>
</html>';
    }
    
    /**
     * Get available camera devices
     */
    public function getAvailableCameras() {
        // Use the CameraDetector class
        require_once 'camera_detection.php';
        $detector = new CameraDetector();
        return $detector->detectCameras();
    }
}

// Handle direct access
if (basename($_SERVER['PHP_SELF']) === 'camera_stream.php') {
    $cameraIndex = $_GET['camera'] ?? 0;
    $stream = new CameraStream($cameraIndex);
    echo $stream->startStream();
}
?>