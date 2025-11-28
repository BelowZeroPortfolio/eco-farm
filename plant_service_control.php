<?php
session_start();
require_once 'config/database.php';
require_once 'includes/security.php';

// Require admin access
requireAdmin();

$pageTitle = "Plant Sensor Service Control";
$currentPage = "plant_service";

// Handle service control actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'start':
                // Start arduino_bridge.py in background
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // Windows
                    $command = 'start /B python arduino_bridge.py > logs/arduino_bridge.log 2>&1';
                    pclose(popen($command, 'r'));
                } else {
                    // Linux/Mac
                    $command = 'python3 arduino_bridge.py > logs/arduino_bridge.log 2>&1 &';
                    exec($command);
                }
                $response = ['success' => true, 'message' => 'Arduino bridge service started'];
                break;
                
            case 'stop':
                // Stop arduino_bridge.py
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // Windows
                    exec('taskkill /F /IM python.exe /FI "WINDOWTITLE eq arduino_bridge*" 2>&1', $output);
                } else {
                    // Linux/Mac
                    exec('pkill -f arduino_bridge.py 2>&1', $output);
                }
                $response = ['success' => true, 'message' => 'Arduino bridge service stopped'];
                break;
                
            case 'status':
                // Check if service is running
                $isRunning = false;
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    exec('tasklist /FI "IMAGENAME eq python.exe" 2>&1', $output);
                    $isRunning = count($output) > 3; // More than header lines
                } else {
                    exec('pgrep -f arduino_bridge.py', $output);
                    $isRunning = !empty($output);
                }
                
                // Check Arduino bridge API
                $apiStatus = @file_get_contents('http://127.0.0.1:5000/health');
                $apiRunning = $apiStatus !== false;
                
                $response = [
                    'success' => true,
                    'running' => $isRunning && $apiRunning,
                    'api_status' => $apiRunning ? json_decode($apiStatus, true) : null
                ];
                break;
                
            case 'test_connection':
                // Test connection to Arduino bridge
                $ch = curl_init('http://127.0.0.1:5000/data');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $data = json_decode($result, true);
                    $response = [
                        'success' => true,
                        'message' => 'Connection successful',
                        'sensor_data' => $data
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Cannot connect to Arduino bridge service'
                    ];
                }
                break;
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    
    echo json_encode($response);
    exit;
}

include 'includes/header.php';
?>

<style>
.service-card {
    background: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-online {
    background-color: #4CAF50;
    box-shadow: 0 0 10px #4CAF50;
}

.status-offline {
    background-color: #f44336;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    margin: 5px;
    font-weight: 600;
}

.btn-success {
    background-color: #4CAF50;
    color: white;
}

.btn-danger {
    background-color: #f44336;
    color: white;
}

.btn-info {
    background-color: #2196F3;
    color: white;
}

.btn:hover {
    opacity: 0.8;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.info-box {
    background-color: #e3f2fd;
    border-left: 4px solid #2196F3;
    padding: 15px;
    margin: 20px 0;
    border-radius: 5px;
}

.sensor-data {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.sensor-item {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.sensor-value {
    font-size: 32px;
    font-weight: bold;
    color: #4CAF50;
}

.sensor-label {
    font-size: 14px;
    color: #666;
    margin-top: 5px;
}

.log-box {
    background: #1e1e1e;
    color: #00ff00;
    padding: 15px;
    border-radius: 5px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    max-height: 300px;
    overflow-y: auto;
    margin-top: 20px;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<div class="container" style="padding: 20px;">
    <h1>🔧 Plant Sensor Service Control</h1>
    <p>Manage Arduino bridge service for plant monitoring</p>

    <div id="alertBox"></div>

    <div class="service-card">
        <h2>Service Status</h2>
        <div style="display: flex; align-items: center; margin: 20px 0;">
            <span class="status-indicator" id="statusIndicator"></span>
            <span id="statusText" style="font-size: 18px; font-weight: 600;">Checking...</span>
        </div>
        
        <div style="margin: 20px 0;">
            <button class="btn btn-success" id="startBtn" onclick="startService()">
                ▶️ Start Service
            </button>
            <button class="btn btn-danger" id="stopBtn" onclick="stopService()">
                ⏹️ Stop Service
            </button>
            <button class="btn btn-info" onclick="testConnection()">
                🔍 Test Connection
            </button>
            <button class="btn btn-info" onclick="refreshStatus()">
                🔄 Refresh Status
            </button>
        </div>

        <div class="info-box">
            <strong>ℹ️ Service Information:</strong><br>
            • The Arduino bridge service reads sensor data from Arduino<br>
            • Service runs on http://127.0.0.1:5000<br>
            • Sensor data is automatically synced with plant thresholds<br>
            • Notifications are generated when thresholds are violated
        </div>
    </div>

    <div class="service-card" id="sensorDataCard" style="display: none;">
        <h2>📊 Live Sensor Data</h2>
        <div class="sensor-data">
            <div class="sensor-item">
                <div class="sensor-value" id="tempValue">--</div>
                <div class="sensor-label">Temperature (°C)</div>
            </div>
            <div class="sensor-item">
                <div class="sensor-value" id="humidityValue">--</div>
                <div class="sensor-label">Humidity (%)</div>
            </div>
            <div class="sensor-item">
                <div class="sensor-value" id="soilValue">--</div>
                <div class="sensor-label">Soil Moisture (%)</div>
            </div>
        </div>
        <p style="text-align: center; margin-top: 15px; color: #666;">
            <small id="lastUpdate">Last updated: --</small>
        </p>
    </div>

    <div class="service-card">
        <h2>📝 Service Logs</h2>
        <div class="log-box" id="logBox">
            Waiting for service status...
        </div>
    </div>
</div>

<script>
let statusCheckInterval;

function addLog(message) {
    const logBox = document.getElementById('logBox');
    const timestamp = new Date().toLocaleTimeString();
    logBox.innerHTML += `[${timestamp}] ${message}\n`;
    logBox.scrollTop = logBox.scrollHeight;
}

function showAlert(message, type) {
    const alertBox = document.getElementById('alertBox');
    alertBox.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    setTimeout(() => alertBox.innerHTML = '', 5000);
}

function updateStatus(isRunning) {
    const indicator = document.getElementById('statusIndicator');
    const statusText = document.getElementById('statusText');
    const startBtn = document.getElementById('startBtn');
    const stopBtn = document.getElementById('stopBtn');
    
    if (isRunning) {
        indicator.className = 'status-indicator status-online';
        statusText.textContent = 'Service Running';
        statusText.style.color = '#4CAF50';
        startBtn.disabled = true;
        stopBtn.disabled = false;
    } else {
        indicator.className = 'status-indicator status-offline';
        statusText.textContent = 'Service Stopped';
        statusText.style.color = '#f44336';
        startBtn.disabled = false;
        stopBtn.disabled = true;
    }
}

function startService() {
    addLog('Starting Arduino bridge service...');
    fetch('plant_service_control.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=start'
    })
    .then(response => response.json())
    .then(data => {
        addLog(data.message);
        showAlert(data.message, data.success ? 'success' : 'error');
        setTimeout(refreshStatus, 2000);
    });
}

function stopService() {
    addLog('Stopping Arduino bridge service...');
    fetch('plant_service_control.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=stop'
    })
    .then(response => response.json())
    .then(data => {
        addLog(data.message);
        showAlert(data.message, data.success ? 'success' : 'error');
        setTimeout(refreshStatus, 1000);
    });
}

function testConnection() {
    addLog('Testing connection to Arduino bridge...');
    fetch('plant_service_control.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=test_connection'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addLog('✓ Connection successful');
            showAlert(data.message, 'success');
            if (data.sensor_data) {
                updateSensorDisplay(data.sensor_data);
            }
        } else {
            addLog('✗ Connection failed: ' + data.message);
            showAlert(data.message, 'error');
        }
    });
}

function refreshStatus() {
    fetch('plant_service_control.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=status'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateStatus(data.running);
            if (data.running) {
                addLog('Service is running');
                // Auto-fetch sensor data
                testConnection();
            } else {
                addLog('Service is stopped');
            }
        }
    });
}

function updateSensorDisplay(data) {
    if (data.data) {
        const sensorCard = document.getElementById('sensorDataCard');
        sensorCard.style.display = 'block';
        
        if (data.data.temperature && data.data.temperature.value !== null) {
            document.getElementById('tempValue').textContent = data.data.temperature.value.toFixed(1);
        }
        if (data.data.humidity && data.data.humidity.value !== null) {
            document.getElementById('humidityValue').textContent = data.data.humidity.value.toFixed(1);
        }
        if (data.data.soil_moisture && data.data.soil_moisture.value !== null) {
            document.getElementById('soilValue').textContent = data.data.soil_moisture.value.toFixed(1);
        }
        
        document.getElementById('lastUpdate').textContent = 'Last updated: ' + new Date().toLocaleTimeString();
    }
}

// Initial status check
refreshStatus();

// Auto-refresh every 10 seconds
statusCheckInterval = setInterval(refreshStatus, 10000);
</script>

<?php include 'includes/footer.php'; ?>
