<?php
// Start session and authentication check
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/pest-config-helper.php';
require_once 'YOLODetector2.php';

// Check page access permission
requirePageAccess('pest_detection');

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

// Handle AJAX requests (same as pest_detection.php)
$isAjaxRequest = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) || 
                 ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']));

if ($isAjaxRequest) {
    header('Content-Type: application/json');

    try {
        $pdo = getDatabaseConnection();
        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        switch ($action) {
            case 'detect_webcam':
                if (!isset($_FILES['image'])) {
                    throw new Exception('No image file provided');
                }

                $file = $_FILES['image'];
                if (!is_uploaded_file($file['tmp_name'])) {
                    throw new Exception('Invalid file upload');
                }

                if ($file['size'] > 5242880) {
                    throw new Exception('File size exceeds maximum allowed size');
                }

                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mimeType, $allowedMimeTypes)) {
                    throw new Exception('Invalid file type. Only JPEG and PNG images are allowed');
                }

                $tempDir = 'temp/';
                if (!file_exists($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }

                $files = glob($tempDir . 'pest_*');
                $now = time();
                foreach ($files as $tempFile) {
                    if (is_file($tempFile) && ($now - filemtime($tempFile)) > 3600) {
                        @unlink($tempFile);
                    }
                }

                $tempFile = $tempDir . uniqid('pest_', true) . '.jpg';
                if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
                    throw new Exception('Failed to save uploaded file');
                }

                try {
                    $detector = new YOLODetector2();

                    if (!$detector->isHealthy()) {
                        throw new Exception('YOLO detection service is not available. Please ensure the service is running.');
                    }

                    $data = $detector->detectPests($tempFile, true);
                    $annotatedImagePath = $data['annotated_image'] ?? null;
                    
                    // Convert to proper relative path for browser
                    if ($annotatedImagePath && !str_starts_with($annotatedImagePath, 'detections/')) {
                        $annotatedImagePath = 'detections/' . $annotatedImagePath;
                    }

                    $detections = [];
                    $allDetections = [];
                    $confidenceThreshold = 60;
                    $rateLimitSeconds = 60;

                    foreach ($data['pests'] as $pest) {
                        $pestType = $pest['type'] ?? 'unknown';
                        $confidence = $pest['confidence'] ?? 0;
                        $logged = false;
                        $severity = 'low';

                        if ($confidence >= $confidenceThreshold) {
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) as count 
                                FROM pest_alerts 
                                WHERE pest_type = ? 
                                AND detected_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
                            ");
                            $stmt->execute([$pestType, $rateLimitSeconds]);
                            $result = $stmt->fetch();

                            if ($result['count'] == 0) {
                                $pestInfo = getPestInfo($pestType);
                                $severity = $pestInfo['severity'];
                                $suggestedActions = $pestInfo['actions'];
                                $description = $pestInfo['description'];
                                $commonName = $pestInfo['common_name'];

                                try {
                                    $stmt = $pdo->prepare("
                                        INSERT INTO pest_alerts 
                                        (pest_type, common_name, location, severity, confidence_score, description, suggested_actions, image_path, detected_at, is_read, notification_sent) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), FALSE, FALSE)
                                    ");

                                    $location = 'Webcam Detection';

                                    $logged = $stmt->execute([
                                        $pestType,
                                        $commonName,
                                        $location,
                                        $severity,
                                        $confidence,
                                        $description,
                                        $suggestedActions,
                                        $annotatedImagePath
                                    ]);
                                } catch (PDOException $e) {
                                    error_log("Database insert error: " . $e->getMessage());
                                    $logged = false;
                                }
                            }

                            $detections[] = [
                                'type' => $pestType,
                                'confidence' => round($confidence, 2),
                                'logged' => $logged,
                                'severity' => $severity
                            ];
                        } else {
                            $pestInfo = getPestInfo($pestType);
                            $severity = $pestInfo['severity'];
                        }

                        $allDetections[] = [
                            'type' => $pestType,
                            'confidence' => round($confidence, 2),
                            'logged' => $logged,
                            'severity' => $severity,
                            'is_low_confidence' => $confidence < $confidenceThreshold
                        ];
                    }

                    @unlink($tempFile);

                    echo json_encode([
                        'success' => true,
                        'detections' => $detections,
                        'all_detections' => $allDetections,
                        'annotated_image' => $annotatedImagePath
                    ]);
                } catch (Exception $e) {
                    @unlink($tempFile);
                    throw $e;
                }
                break;

            case 'get_recent_detections':
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
                $limit = max(1, min($limit, 200));

                $stmt = $pdo->prepare("
                    SELECT 
                        id, pest_type, common_name, location, severity, 
                        confidence_score, detected_at, is_read, suggested_actions
                    FROM pest_alerts 
                    WHERE location = 'Webcam Detection'
                    ORDER BY detected_at DESC 
                    LIMIT ?
                ");
                $stmt->execute([$limit]);
                $detections = $stmt->fetchAll();

                foreach ($detections as &$detection) {
                    $detection['confidence_score'] = round($detection['confidence_score'], 2);
                    if (empty($detection['suggested_actions'])) {
                        $pestInfo = getPestInfo($detection['pest_type']);
                        $detection['suggested_actions'] = $pestInfo['actions'];
                    }
                    if (empty($detection['common_name'])) {
                        $pestInfo = getPestInfo($detection['pest_type']);
                        $detection['common_name'] = $pestInfo['common_name'] ?? null;
                    }
                }

                echo json_encode([
                    'success' => true,
                    'detections' => $detections
                ]);
                break;

            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pest Monitor - IoT Farm System</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        #video-element {
            transform: scaleX(-1);
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">

<div class="h-screen flex flex-col">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-4 py-3 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <i class="fas fa-video text-2xl"></i>
            <div>
                <h1 class="text-lg font-bold">Pest Detection Monitor</h1>
                <p class="text-xs text-blue-100">Real-time AI Monitoring</p>
            </div>
        </div>
        <button onclick="window.close()" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition-colors">
            <i class="fas fa-times mr-2"></i>Close Window
        </button>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Left: Camera Feed (70%) -->
        <div class="flex-1 flex flex-col p-4">
            <!-- Camera Controls -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg mb-4 p-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div id="camera-status-indicator" class="w-3 h-3 bg-gray-400 rounded-full"></div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Camera Status: 
                            <span id="camera-status-text" class="font-bold">OFF</span>
                        </span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <!-- Upload Image Button -->
                        <button onclick="document.getElementById('upload-input').click()" 
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                            <i class="fas fa-upload"></i>
                            <span>Upload Image</span>
                        </button>
                        
                        <!-- Toggle Camera Button -->
                        <button id="camera-toggle-btn" onclick="toggleCamera()" 
                                class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                            <i class="fas fa-play"></i>
                            <span>Start Camera</span>
                        </button>
                        
                        <!-- Detection Toggle (only shows when camera is on) -->
                        <button id="detection-toggle-btn" onclick="toggleDetection()" 
                                class="hidden px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                            <i class="fas fa-brain"></i>
                            <span>Start AI Detection</span>
                        </button>
                    </div>
                </div>
                
                <!-- Hidden file input -->
                <input type="file" id="upload-input" accept="image/jpeg,image/png,image/jpg" class="hidden" onchange="handleImageUpload(event)">
                
                <!-- Stats Bar -->
                <div id="stats-bar" class="hidden mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between text-sm">
                    <div class="flex items-center gap-4">
                        <span class="text-gray-600 dark:text-gray-400">Scans: <strong id="scan-count">0</strong></span>
                        <span class="text-gray-600 dark:text-gray-400">Detections: <strong id="detection-count" class="text-yellow-600">0</strong></span>
                        <span class="text-gray-600 dark:text-gray-400">Uptime: <strong id="uptime" class="text-green-600">00:00</strong></span>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Last scan: <span id="last-scan">Never</span></span>
                </div>
            </div>

            <!-- Video Feed -->
            <div class="flex-1 bg-black rounded-lg shadow-lg overflow-hidden relative">
                <video id="video-element" autoplay playsinline class="w-full h-full object-contain hidden"></video>
                <canvas id="capture-canvas" class="hidden"></canvas>
                
                <!-- Placeholder -->
                <div id="camera-placeholder" class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900">
                    <div class="text-center text-white">
                        <i class="fas fa-video text-6xl mb-4 text-blue-400"></i>
                        <h3 class="text-xl font-semibold mb-2">Camera Off</h3>
                        <p class="text-gray-400">Click "Start Camera" to begin monitoring</p>
                    </div>
                </div>
                
                <!-- AI Active Overlay -->
                <div id="ai-overlay" class="hidden absolute top-4 left-4 bg-black bg-opacity-75 text-white px-4 py-2 rounded-lg">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-sm font-medium">AI Detection: ACTIVE</span>
                    </div>
                </div>
                
                <!-- Upload Processing Overlay -->
                <div id="upload-overlay" class="hidden absolute inset-0 bg-black bg-opacity-90 flex items-center justify-center">
                    <div class="text-center text-white p-6">
                        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-white mx-auto mb-4"></div>
                        <p class="text-lg font-semibold">Analyzing uploaded image...</p>
                        <p class="text-sm text-gray-300 mt-2">Please wait</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Recent Detections (30%) -->
        <div class="w-96 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-history text-blue-600 mr-2"></i>
                    Recent Detections
                    <span id="detection-total-count" class="ml-2 px-2 py-1 bg-gray-200 dark:bg-gray-700 text-xs font-medium rounded-full">0</span>
                </h2>
            </div>
            
            <div id="recent-detections" class="flex-1 overflow-y-auto divide-y divide-gray-200 dark:divide-gray-700">
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p class="text-sm">No detections yet</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/yolo-auto-start.js"></script>
<script>
// ============================================================================
// GLOBAL STATE
// ============================================================================

let currentStream = null;
let detectionInterval = null;
let isDetecting = false;
let isCameraOn = false;
let scanCount = 0;
let detectionCount = 0;
let startTime = null;
let uptimeInterval = null;

const videoElement = document.getElementById('video-element');
const captureCanvas = document.getElementById('capture-canvas');
const cameraPlaceholder = document.getElementById('camera-placeholder');
const aiOverlay = document.getElementById('ai-overlay');

// ============================================================================
// CAMERA CONTROL - TOGGLE BUTTON
// ============================================================================

async function toggleCamera() {
    if (isCameraOn) {
        stopCamera();
    } else {
        await startCamera();
    }
}

async function startCamera() {
    try {
        const constraints = {
            video: {
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        };

        currentStream = await navigator.mediaDevices.getUserMedia(constraints);
        videoElement.srcObject = currentStream;

        cameraPlaceholder.classList.add('hidden');
        videoElement.classList.remove('hidden');
        
        isCameraOn = true;
        updateCameraUI();
        
        // Show detection button
        document.getElementById('detection-toggle-btn').classList.remove('hidden');
        
        console.log('Camera started');
    } catch (error) {
        console.error('Error starting camera:', error);
        alert('Failed to start camera. Please check permissions.');
    }
}

function stopCamera() {
    // Stop detection first if active
    if (isDetecting) {
        stopDetection();
    }
    
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
        currentStream = null;
        videoElement.srcObject = null;
    }

    videoElement.classList.add('hidden');
    cameraPlaceholder.classList.remove('hidden');
    
    isCameraOn = false;
    updateCameraUI();
    
    // Hide detection button
    document.getElementById('detection-toggle-btn').classList.add('hidden');
    document.getElementById('stats-bar').classList.add('hidden');
    
    console.log('Camera stopped');
}

function updateCameraUI() {
    const btn = document.getElementById('camera-toggle-btn');
    const indicator = document.getElementById('camera-status-indicator');
    const statusText = document.getElementById('camera-status-text');
    
    if (isCameraOn) {
        btn.innerHTML = '<i class="fas fa-stop"></i><span>Stop Camera</span>';
        btn.className = 'px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2';
        indicator.className = 'w-3 h-3 bg-green-500 rounded-full animate-pulse';
        statusText.textContent = 'ON';
        statusText.className = 'font-bold text-green-600';
    } else {
        btn.innerHTML = '<i class="fas fa-play"></i><span>Start Camera</span>';
        btn.className = 'px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2';
        indicator.className = 'w-3 h-3 bg-gray-400 rounded-full';
        statusText.textContent = 'OFF';
        statusText.className = 'font-bold text-gray-600';
    }
}

// ============================================================================
// DETECTION CONTROL - TOGGLE BUTTON
// ============================================================================

function toggleDetection() {
    if (isDetecting) {
        stopDetection();
    } else {
        startDetection();
    }
}

function startDetection() {
    if (!isCameraOn) {
        alert('Please start the camera first');
        return;
    }
    
    isDetecting = true;
    scanCount = 0;
    detectionCount = 0;
    startTime = Date.now();
    
    updateDetectionUI();
    
    document.getElementById('stats-bar').classList.remove('hidden');
    aiOverlay.classList.remove('hidden');
    
    detectionInterval = setInterval(captureAndDetect, 5000);
    uptimeInterval = setInterval(updateUptime, 1000);
    
    captureAndDetect();
    loadRecentDetections();
    
    console.log('Detection started');
}

function stopDetection() {
    isDetecting = false;
    
    if (detectionInterval) {
        clearInterval(detectionInterval);
        detectionInterval = null;
    }
    if (uptimeInterval) {
        clearInterval(uptimeInterval);
        uptimeInterval = null;
    }
    
    updateDetectionUI();
    aiOverlay.classList.add('hidden');
    
    console.log('Detection stopped');
}

function updateDetectionUI() {
    const btn = document.getElementById('detection-toggle-btn');
    
    if (isDetecting) {
        btn.innerHTML = '<i class="fas fa-stop"></i><span>Stop AI Detection</span>';
        btn.className = 'px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2';
    } else {
        btn.innerHTML = '<i class="fas fa-brain"></i><span>Start AI Detection</span>';
        btn.className = 'px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2';
    }
}

// ============================================================================
// DETECTION LOGIC
// ============================================================================

async function captureAndDetect() {
    if (!isDetecting) return;

    try {
        const blob = await captureFrame();
        if (!blob) return;

        scanCount++;
        document.getElementById('scan-count').textContent = scanCount;

        await sendFrameForDetection(blob);
        document.getElementById('last-scan').textContent = 'Just now';

    } catch (error) {
        console.error('Error in capture and detect:', error);
    }
}

function captureFrame() {
    return new Promise((resolve) => {
        try {
            captureCanvas.width = videoElement.videoWidth;
            captureCanvas.height = videoElement.videoHeight;

            const ctx = captureCanvas.getContext('2d');
            ctx.drawImage(videoElement, 0, 0, captureCanvas.width, captureCanvas.height);

            captureCanvas.toBlob((blob) => {
                resolve(blob);
            }, 'image/jpeg', 0.85);

        } catch (error) {
            console.error('Error capturing frame:', error);
            resolve(null);
        }
    });
}

async function sendFrameForDetection(blob) {
    if (!isDetecting) return;

    try {
        const formData = new FormData();
        formData.append('action', 'detect_webcam');
        formData.append('image', blob, 'frame.jpg');

        const response = await fetch('pest_monitor_popup.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!isDetecting) return;

        if (data.success) {
            console.log('Detections:', data.detections);

            if (data.detections && data.detections.length > 0) {
                const newDetections = data.detections.filter(d => d.logged);

                if (newDetections.length > 0) {
                    detectionCount += newDetections.length;
                    document.getElementById('detection-count').textContent = detectionCount;
                    loadRecentDetections();
                }
            }
        }

    } catch (error) {
        console.error('Error sending frame:', error);
    }
}

function updateUptime() {
    if (!startTime) return;

    const elapsed = Math.floor((Date.now() - startTime) / 1000);
    const minutes = Math.floor(elapsed / 60);
    const seconds = elapsed % 60;

    document.getElementById('uptime').textContent =
        `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

// ============================================================================
// DATA LOADING
// ============================================================================

async function loadRecentDetections() {
    try {
        const response = await fetch('pest_monitor_popup.php?action=get_recent_detections&limit=50');
        const data = await response.json();

        if (data.success) {
            displayRecentDetections(data.detections);
        }
    } catch (error) {
        console.error('Error loading detections:', error);
    }
}

function displayRecentDetections(detections) {
    const container = document.getElementById('recent-detections');
    const totalCountEl = document.getElementById('detection-total-count');

    if (!detections || detections.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <i class="fas fa-inbox text-4xl mb-2"></i>
                <p class="text-sm">No detections yet</p>
            </div>
        `;
        totalCountEl.textContent = '0';
        return;
    }

    totalCountEl.textContent = detections.length;

    const severityColors = {
        'low': 'blue',
        'medium': 'yellow',
        'high': 'orange',
        'critical': 'red'
    };

    let html = '';
    detections.forEach(detection => {
        const color = severityColors[detection.severity] || 'gray';
        const timeAgo = formatTimeAgo(detection.detected_at);

        html += `
            <div class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 bg-${color}-100 dark:bg-${color}-900 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-bug text-${color}-600 dark:text-${color}-400"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">${escapeHtml(detection.common_name || detection.pest_type)}</h4>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-gray-600 dark:text-gray-400">${detection.confidence_score}%</span>
                            <span class="px-2 py-0.5 bg-${color}-100 dark:bg-${color}-900 text-${color}-800 dark:text-${color}-200 text-xs font-medium rounded">
                                ${detection.severity.toUpperCase()}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${timeAgo}</p>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// ============================================================================
// IMAGE UPLOAD FUNCTIONALITY
// ============================================================================

async function handleImageUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!allowedTypes.includes(file.type)) {
        alert('Please select a valid image file (JPG or PNG)');
        event.target.value = '';
        return;
    }
    
    // Validate file size (5MB max)
    if (file.size > 5242880) {
        alert('File size exceeds 5MB limit');
        event.target.value = '';
        return;
    }
    
    // Show processing overlay
    const overlay = document.getElementById('upload-overlay');
    overlay.classList.remove('hidden');
    
    try {
        const formData = new FormData();
        formData.append('action', 'detect_webcam');
        formData.append('image', file);
        
        const response = await fetch('pest_monitor_popup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update scan count if detection is active
            if (isDetecting) {
                scanCount++;
                document.getElementById('scan-count').textContent = scanCount;
            }
            
            // Show success notification
            showUploadNotification(data);
            
            // Reload recent detections
            loadRecentDetections();
            
            // Update detection count
            if (data.detections && data.detections.length > 0) {
                const newDetections = data.detections.filter(d => d.logged);
                if (newDetections.length > 0) {
                    detectionCount += newDetections.length;
                    document.getElementById('detection-count').textContent = detectionCount;
                }
            }
        } else {
            throw new Error(data.message || 'Analysis failed');
        }
        
    } catch (error) {
        console.error('Error analyzing uploaded image:', error);
        showUploadError(error.message);
    } finally {
        // Hide overlay
        overlay.classList.add('hidden');
        // Clear file input
        event.target.value = '';
    }
}

function showUploadNotification(data) {
    const detectionCount = data.detections ? data.detections.length : 0;
    const allDetectionCount = data.all_detections ? data.all_detections.length : 0;
    
    let message = '';
    let icon = '';
    let bgColor = '';
    
    if (detectionCount > 0) {
        const pestNames = data.detections.map(d => d.type).join(', ');
        message = `✓ Detected ${detectionCount} pest(s): ${pestNames}`;
        icon = 'fa-check-circle';
        bgColor = 'bg-green-600';
    } else if (allDetectionCount > 0) {
        message = `⚠ Found ${allDetectionCount} low-confidence detection(s)`;
        icon = 'fa-exclamation-circle';
        bgColor = 'bg-yellow-600';
    } else {
        message = '✓ No pests detected in image';
        icon = 'fa-info-circle';
        bgColor = 'bg-blue-600';
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-20 right-4 ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-md animate-slide-in`;
    notification.innerHTML = `
        <div class="flex items-start gap-3">
            <i class="fas ${icon} text-2xl"></i>
            <div class="flex-1">
                <p class="font-semibold mb-1">Upload Analysis Complete</p>
                <p class="text-sm">${message}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

function showUploadError(message) {
    const notification = document.createElement('div');
    notification.className = 'fixed top-20 right-4 bg-red-600 text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-md';
    notification.innerHTML = `
        <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-triangle text-2xl"></i>
            <div class="flex-1">
                <p class="font-semibold mb-1">Upload Failed</p>
                <p class="text-sm">${message}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    return `${Math.floor(seconds / 86400)}d ago`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================================================
// INITIALIZATION
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Pest Monitor Popup initialized');
    loadRecentDetections();
    
    // Refresh detections every 10 seconds
    setInterval(loadRecentDetections, 10000);
});

// Cleanup on window close
window.addEventListener('beforeunload', function() {
    if (isDetecting) stopDetection();
    if (isCameraOn) stopCamera();
});
</script>

</body>
</html>
