<?php
// Start session and authentication check
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/pest-config-helper.php'; // Database-driven pest config
require_once 'YOLODetector2.php'; // Flask-based detector

// Check page access permission
requirePageAccess('pest_detection');

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

// ============================================================================
// AJAX REQUEST HANDLER
// ============================================================================

// Handle both GET and POST requests for different actions
$isAjaxRequest = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) || 
                 ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']));

if ($isAjaxRequest) {
    header('Content-Type: application/json');

    try {
        // Get action from either POST or GET
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        // Only connect to database for actions that need it
        $needsDatabase = !in_array($action, ['check_service_health']);
        $pdo = $needsDatabase ? getDatabaseConnection() : null;

        switch ($action) {
            case 'detect_webcam':
                // Real-time webcam pest detection endpoint using Flask service
                if (!isset($_FILES['image'])) {
                    throw new Exception('No image file provided');
                }

                // Validate uploaded file
                $file = $_FILES['image'];
                if (!is_uploaded_file($file['tmp_name'])) {
                    throw new Exception('Invalid file upload');
                }

                // Check file size (max 5MB)
                if ($file['size'] > 5242880) {
                    throw new Exception('File size exceeds maximum allowed size');
                }

                // Check MIME type
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mimeType, $allowedMimeTypes)) {
                    throw new Exception('Invalid file type. Only JPEG and PNG images are allowed');
                }

                // Ensure temp directory exists
                $tempDir = 'temp/';
                if (!file_exists($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }

                // Clean up old temp files (older than 1 hour)
                $files = glob($tempDir . 'pest_*');
                $now = time();
                foreach ($files as $tempFile) {
                    if (is_file($tempFile) && ($now - filemtime($tempFile)) > 3600) {
                        @unlink($tempFile);
                    }
                }

                // Generate temp filename and save uploaded file
                $tempFile = $tempDir . uniqid('pest_', true) . '.jpg';
                if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
                    throw new Exception('Failed to save uploaded file');
                }

                try {
                    // ===== FLASK SERVICE METHOD (NEW) =====
                    // Initialize Flask-based YOLO detector (uses .env config)
                    $detector = new YOLODetector2();

                    // Check if service is healthy
                    if (!$detector->isHealthy()) {
                        throw new Exception('YOLO detection service is not available. Please ensure the service is running.');
                    }

                    // Detect pests using Flask service (get full response with annotated image)
                    $data = $detector->detectPests($tempFile, true);

                    // Extract annotated image path if available
                    $annotatedImagePath = $data['annotated_image'] ?? null;
                    // ===== END FLASK SERVICE METHOD =====

                    // Process detections
                    $detections = [];
                    $allDetections = []; // All detections for display (including low confidence)
                    $confidenceThreshold = 60; // 60% confidence threshold for logging
                    $rateLimitSeconds = 60; // Rate limit: same pest type within 60 seconds

                    foreach ($data['pests'] as $pest) {
                        $pestType = $pest['type'] ?? 'unknown';
                        $confidence = $pest['confidence'] ?? 0;
                        $logged = false;
                        $severity = 'low';

                        // Check if confidence is high enough for logging
                        if ($confidence >= $confidenceThreshold) {
                            // Check rate limiting
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) as count 
                                FROM pest_alerts 
                                WHERE pest_type = ? 
                                AND detected_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
                            ");
                            $stmt->execute([$pestType, $rateLimitSeconds]);
                            $result = $stmt->fetch();

                            // Log to database if not rate-limited
                            if ($result['count'] == 0) {
                                // Get pest-specific severity and actions from database
                                $pestInfo = getPestInfo($pestType);
                                $severity = $pestInfo['severity'];
                                $suggestedActions = $pestInfo['actions'];
                                $description = $pestInfo['description']; // Use database description
                                $commonName = $pestInfo['common_name']; // Get common name

                                // Insert detection into pest_alerts
                                try {
                                    $stmt = $pdo->prepare("
                                        INSERT INTO pest_alerts 
                                        (pest_type, common_name, location, severity, confidence_score, description, suggested_actions, detected_at, is_read, notification_sent) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), FALSE, FALSE)
                                    ");

                                    $location = 'Webcam Detection';

                                    $logged = $stmt->execute([
                                        $pestType,
                                        $commonName,
                                        $location,
                                        $severity,
                                        $confidence,
                                        $description,
                                        $suggestedActions
                                    ]);
                                } catch (PDOException $e) {
                                    error_log("Database insert error: " . $e->getMessage());
                                    $logged = false;
                                }
                            }

                            // Add to high-confidence detections
                            $detections[] = [
                                'type' => $pestType,
                                'confidence' => round($confidence, 2),
                                'logged' => $logged,
                                'severity' => $severity
                            ];
                        } else {
                            // Low confidence - get severity but don't log
                            $pestInfo = getPestInfo($pestType);
                            $severity = $pestInfo['severity'];
                        }

                        // Add ALL detections to allDetections (for display purposes)
                        $allDetections[] = [
                            'type' => $pestType,
                            'confidence' => round($confidence, 2),
                            'logged' => $logged,
                            'severity' => $severity,
                            'is_low_confidence' => $confidence < $confidenceThreshold
                        ];
                    }

                    // Clean up temp file
                    @unlink($tempFile);

                    // Return results with annotated image
                    // detections = high confidence only (logged to DB)
                    // all_detections = all detections including low confidence (for display)
                    echo json_encode([
                        'success' => true,
                        'detections' => $detections,
                        'all_detections' => $allDetections,
                        'annotated_image' => $annotatedImagePath
                    ]);
                } catch (Exception $e) {
                    // Clean up temp file on error
                    @unlink($tempFile);
                    throw $e;
                }
                break;

            case 'get_recent_detections':
                // Get recent detections for live feed display
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
                $limit = max(1, min($limit, 200)); // Clamp between 1 and 200

                $stmt = $pdo->prepare("
                    SELECT 
                        id, 
                        pest_type,
                        common_name,
                        location, 
                        severity, 
                        confidence_score, 
                        detected_at,
                        is_read,
                        read_at,
                        suggested_actions,
                        description
                    FROM pest_alerts 
                    WHERE location = 'Webcam Detection'
                    ORDER BY detected_at DESC 
                    LIMIT ?
                ");
                $stmt->execute([$limit]);
                $detections = $stmt->fetchAll();

                // Format for display
                foreach ($detections as &$detection) {
                    $detection['confidence_score'] = round($detection['confidence_score'], 2);

                    // Get pest info if suggested_actions is empty
                    if (empty($detection['suggested_actions'])) {
                        $pestInfo = getPestInfo($detection['pest_type']);
                        $detection['suggested_actions'] = $pestInfo['actions'];
                    }
                    
                    // If common_name is null in database, get it from pest_config
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

            case 'get_detection_stats':
                // Get detection statistics
                $stmt = $pdo->query("
                    SELECT 
                        COUNT(*) as total_detections,
                        COUNT(CASE WHEN DATE(detected_at) = CURDATE() THEN 1 END) as today_detections,
                        COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_count,
                        COUNT(CASE WHEN severity = 'high' THEN 1 END) as high_count
                    FROM pest_alerts
                    WHERE location = 'Webcam Detection'
                ");
                $stats = $stmt->fetch();

                echo json_encode([
                    'success' => true,
                    'stats' => $stats
                ]);
                break;

            case 'mark_as_read':
                // Mark a specific alert as read
                $alertId = isset($_POST['alert_id']) ? intval($_POST['alert_id']) : 0;

                if ($alertId <= 0) {
                    throw new Exception('Invalid alert ID');
                }

                $stmt = $pdo->prepare("
                    UPDATE pest_alerts 
                    SET is_read = TRUE, read_at = NOW(), read_by = ? 
                    WHERE id = ?
                ");
                $result = $stmt->execute([$currentUser['id'], $alertId]);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Alert marked as read']);
                } else {
                    throw new Exception('Failed to mark alert as read');
                }
                break;

            case 'mark_all_as_read':
                // Mark all alerts as read
                $stmt = $pdo->prepare("
                    UPDATE pest_alerts 
                    SET is_read = TRUE, read_at = NOW(), read_by = ? 
                    WHERE is_read = FALSE
                ");
                $result = $stmt->execute([$currentUser['id']]);

                if ($result) {
                    $count = $stmt->rowCount();
                    echo json_encode(['success' => true, 'message' => "{$count} alerts marked as read"]);
                } else {
                    throw new Exception('Failed to mark alerts as read');
                }
                break;

            case 'get_unread_count':
                // Get count of unread notifications
                $stmt = $pdo->query("
                    SELECT 
                        COUNT(*) as total_unread,
                        COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_unread,
                        COUNT(CASE WHEN severity = 'high' THEN 1 END) as high_unread,
                        COUNT(CASE WHEN severity = 'medium' THEN 1 END) as medium_unread,
                        COUNT(CASE WHEN severity = 'low' THEN 1 END) as low_unread
                    FROM pest_alerts 
                    WHERE is_read = FALSE
                ");
                $counts = $stmt->fetch();

                echo json_encode([
                    'success' => true,
                    'unread' => $counts
                ]);
                break;

            case 'get_alert_details':
                // Get detailed information about a specific alert
                $alertId = isset($_GET['alert_id']) ? intval($_GET['alert_id']) : 0;

                if ($alertId <= 0) {
                    throw new Exception('Invalid alert ID');
                }

                $stmt = $pdo->prepare("
                    SELECT 
                        pa.*,
                        u.username as read_by_username
                    FROM pest_alerts pa
                    LEFT JOIN users u ON pa.read_by = u.id
                    WHERE pa.id = ?
                ");
                $stmt->execute([$alertId]);
                $alert = $stmt->fetch();

                if (!$alert) {
                    throw new Exception('Alert not found');
                }

                // Get pest info from database
                $pestInfo = getPestInfo($alert['pest_type']);

                echo json_encode([
                    'success' => true,
                    'alert' => $alert,
                    'pest_info' => $pestInfo
                ]);
                break;

            case 'clear_webcam_detections':
                // Clear webcam detections
                if (!hasRole('admin') && !hasRole('farmer')) {
                    throw new Exception('Insufficient permissions');
                }

                $stmt = $pdo->prepare("DELETE FROM pest_alerts WHERE location = 'Webcam Detection'");
                $result = $stmt->execute();

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Webcam detections cleared successfully']);
                } else {
                    throw new Exception('Failed to clear detections');
                }
                break;

            case 'check_service_health':
                // Check if Flask service is running (uses .env config)
                $detector = new YOLODetector2();
                $isHealthy = $detector->isHealthy();

                echo json_encode([
                    'success' => true,
                    'healthy' => $isHealthy,
                    'message' => $isHealthy ? 'Service is running' : 'Service is not available'
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

// Set page title
$pageTitle = 'Real-Time Pest Detection (Flask Optimized) - IoT Farm Monitoring System';

// Include header
include 'includes/header.php';
?>

<?php include 'includes/navigation.php'; ?>

<!-- Real-Time Pest Detection Content -->
<div class="p-4 max-w-7xl mx-auto">

    <!-- Notification Panel (Hidden by default) -->
    <div id="notification-panel" class="hidden mb-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-700 dark:to-gray-800">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-bell text-blue-600 mr-2"></i>
                    Unread Notifications
                    <span id="notification-count" class="ml-3 px-3 py-1 bg-blue-600 text-white text-xs font-medium rounded-full">0</span>
                </h3>
                <button onclick="toggleNotificationPanel()" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div id="notification-list" class="max-h-96 overflow-y-auto p-4">
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <i class="fas fa-inbox text-4xl mb-2"></i>
                <p>No unread notifications</p>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Left Column - Live Feed -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Pest Detection Control -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-video text-blue-600 mr-2"></i>
                            Pest Detection Control
                        </h3>
                        <span id="yolo-status-indicator" class="px-3 py-1 bg-gray-400 text-white text-xs font-medium rounded-full">
                            <i class="fas fa-spinner fa-spin mr-1"></i>CHECKING...
                        </span>
                    </div>
                </div>
                
                <div class="p-6 space-y-4">
                    <!-- Service Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-brain text-purple-600 mr-1"></i>
                            AI Service Status
                        </label>
                        <div id="yolo-status-card" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-center py-6">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                <span class="ml-3 text-gray-600 dark:text-gray-400">Checking YOLO service status...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Camera Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-camera text-blue-600 mr-1"></i>
                            Select Camera
                        </label>
                        <div class="flex gap-2">
                            <select id="camera-select" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Loading cameras...</option>
                            </select>
                            <button onclick="saveCameraConfig()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                <i class="fas fa-save mr-2"></i>Save
                            </button>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-2">
                        <div class="grid grid-cols-2 gap-3">
                            <button onclick="openPestMonitorPopup()" class="col-span-2 px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors flex items-center justify-center">
                                <i class="fas fa-external-link-alt mr-2"></i>Open Pest Monitor
                            </button>
                            <button id="start-service-btn" onclick="startYoloService()" class="hidden px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                                <i class="fas fa-play mr-2"></i>Start Service
                            </button>
                            <button id="stop-service-btn" onclick="stopYoloService()" class="hidden px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                                <i class="fas fa-stop mr-2"></i>Stop Service
                            </button>
                            <button onclick="refreshYoloStatus()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                <i class="fas fa-sync-alt mr-2"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Detections -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-history text-blue-600 mr-2"></i>
                        Recent Detections
                        <span id="detection-total-count" class="ml-3 px-2 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-full">0</span>
                    </h3>
                </div>
                <div id="recent-detections" class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>No detections yet. Start monitoring to see results here.</p>
                    </div>
                </div>
                <!-- Pagination Controls -->
                <div id="detection-pagination" class="hidden px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <div class="flex items-center justify-between text-sm">
                        <div class="text-gray-600 dark:text-gray-400">
                            Showing <span id="detection-showing-start">1</span> to <span id="detection-showing-end">10</span> of <span id="detection-total">0</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="changeDetectionPage('prev')" id="detection-prev-btn" class="px-3 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors" disabled>
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span class="text-gray-700 dark:text-gray-300">
                                Page <span id="detection-current-page">1</span> of <span id="detection-total-pages">1</span>
                            </span>
                            <button onclick="changeDetectionPage('next')" id="detection-next-btn" class="px-3 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors" disabled>
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Stats & Info -->
        <div class="space-y-6">
            <!-- Detection Statistics -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl p-6 shadow-lg">
                <h3 class="text-white/80 text-sm font-medium mb-4">Detection Statistics</h3>
                <div class="space-y-4">
                    <div class="text-center">
                        <div class="text-3xl font-bold mb-1" id="total-detections">0</div>
                        <div class="text-white/80 text-sm">Total Detections</div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="text-center p-3 bg-white/10 rounded-lg">
                            <div class="font-bold text-lg" id="today-detections">0</div>
                            <div class="text-white/80">Today</div>
                        </div>
                        <div class="text-center p-3 bg-white/10 rounded-lg">
                            <div class="font-bold text-lg" id="critical-detections">0</div>
                            <div class="text-white/80">Critical</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Detection -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Latest Detection</h3>

                <!-- Detection Preview -->
                <div id="detection-preview" class="relative bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden aspect-video mb-4">
                    <div id="no-detection-placeholder" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                        <i class="fas fa-bug text-4xl mb-2"></i>
                        <p class="text-sm">No detections yet</p>
                    </div>
                    <img id="detection-image" class="hidden w-full h-full object-contain" alt="Latest detection">
                </div>

                <!-- Detection Info -->
                <div id="detection-info" class="hidden space-y-2 mb-4">
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Pest Type:</span>
                        <span id="detection-label" class="text-sm font-semibold text-gray-900 dark:text-white">-</span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Confidence:</span>
                        <span id="detection-confidence" class="text-sm font-semibold text-gray-900 dark:text-white">-</span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Time:</span>
                        <span id="detection-time" class="text-sm font-semibold text-gray-900 dark:text-white">-</span>
                    </div>
                </div>
            </div>


            <!-- System Status -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">System Status</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-brain text-purple-600 dark:text-purple-400 text-sm"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">YOLO AI Service</span>
                        </div>
                        <span id="yolo-status-badge" class="text-xs font-bold text-gray-600 dark:text-gray-400">Checking...</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-video text-blue-600 dark:text-blue-400 text-sm"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Camera Config</span>
                        </div>
                        <span id="camera-config-badge" class="text-xs font-bold text-gray-600 dark:text-gray-400">Not Set</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-database text-green-600 dark:text-green-400 text-sm"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Database</span>
                        </div>
                        <span class="text-xs font-bold text-green-600 dark:text-green-400">Connected</span>
                    </div>
                </div>
            </div>

            <!-- Quick Info -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-200 mb-3 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>
                    How It Works
                </h3>
                <ul class="space-y-2 text-xs text-blue-800 dark:text-blue-300">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-blue-600 dark:text-blue-400 mt-0.5"></i>
                        <span>Configure your default camera for monitoring</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-blue-600 dark:text-blue-400 mt-0.5"></i>
                        <span>Click "Open Pest Monitor" to start detection</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-blue-600 dark:text-blue-400 mt-0.5"></i>
                        <span>Monitor runs in separate popup window</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-blue-600 dark:text-blue-400 mt-0.5"></i>
                        <span>View detection logs and statistics here</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-blue-600 dark:text-blue-400 mt-0.5"></i>
                        <span>YOLO service status is monitored automatically</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>


<script>
    // ============================================================================
    // GLOBAL STATE
    // ============================================================================

    let selectedDeviceId = null;
    let yoloStatusCheckInterval = null;

    // ============================================================================
    // YOLO SERVICE STATUS
    // ============================================================================

    /**
     * Check YOLO service health status
     */
    async function checkYoloStatus() {
        try {
            const data = await apiCall('check_service_health', {}, 'GET');
            
            const indicator = document.getElementById('yolo-status-indicator');
            const badge = document.getElementById('yolo-status-badge');
            const statusCard = document.getElementById('yolo-status-card');
            const startBtn = document.getElementById('start-service-btn');
            const stopBtn = document.getElementById('stop-service-btn');
            
            if (data.success && data.healthy) {
                // Service is running
                indicator.innerHTML = '<i class="fas fa-check-circle mr-1"></i>ONLINE';
                indicator.className = 'px-3 py-1 bg-green-600 text-white text-xs font-medium rounded-full';
                
                badge.textContent = 'Online';
                badge.className = 'text-xs font-bold text-green-600 dark:text-green-400';
                
                statusCard.innerHTML = `
                    <div class="flex items-center gap-3 text-green-600 dark:text-green-400">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                            <i class="fas fa-check-circle text-2xl"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">Service Running</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">YOLO AI detection service is online and ready</div>
                        </div>
                    </div>
                `;
                
                // Show stop button, hide start button
                startBtn.classList.add('hidden');
                stopBtn.classList.remove('hidden');
            } else {
                // Service is not running
                indicator.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i>OFFLINE';
                indicator.className = 'px-3 py-1 bg-red-600 text-white text-xs font-medium rounded-full';
                
                badge.textContent = 'Offline';
                badge.className = 'text-xs font-bold text-red-600 dark:text-red-400';
                
                statusCard.innerHTML = `
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-red-600 dark:text-red-400">
                            <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-2xl"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Service Offline</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">YOLO AI detection service is not running</div>
                            </div>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                <i class="fas fa-info-circle mr-2"></i>
                                Click "Start Service" below to launch the YOLO detection service.
                            </p>
                        </div>
                    </div>
                `;
                
                // Show start button, hide stop button
                startBtn.classList.remove('hidden');
                stopBtn.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error checking YOLO status:', error);
            
            const indicator = document.getElementById('yolo-status-indicator');
            const badge = document.getElementById('yolo-status-badge');
            const statusCard = document.getElementById('yolo-status-card');
            const startBtn = document.getElementById('start-service-btn');
            const stopBtn = document.getElementById('stop-service-btn');
            
            indicator.innerHTML = '<i class="fas fa-question-circle mr-1"></i>ERROR';
            indicator.className = 'px-3 py-1 bg-gray-600 text-white text-xs font-medium rounded-full';
            
            badge.textContent = 'Error';
            badge.className = 'text-xs font-bold text-gray-600 dark:text-gray-400';
            
            statusCard.innerHTML = `
                <div class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                    <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                        <i class="fas fa-question-circle text-2xl"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">Status Unknown</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Unable to check service status</div>
                    </div>
                </div>
            `;
            
            // Show start button by default
            startBtn.classList.remove('hidden');
            stopBtn.classList.add('hidden');
        }
    }

    /**
     * Refresh YOLO status manually
     */
    function refreshYoloStatus() {
        showToast('Refreshing YOLO service status...', 'info');
        checkYoloStatus();
    }

    /**
     * Start YOLO service
     */
    async function startYoloService() {
        const startBtn = document.getElementById('start-service-btn');
        const originalHtml = startBtn.innerHTML;
        
        // Disable button and show loading
        startBtn.disabled = true;
        startBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Starting...';
        
        try {
            const formData = new FormData();
            formData.append('action', 'start');
            
            const response = await fetch('yolo_service_control.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message || 'YOLO service started successfully', 'success');
                // Wait a moment then refresh status
                setTimeout(() => {
                    checkYoloStatus();
                }, 2000);
            } else {
                showToast(data.message || 'Failed to start YOLO service', 'error');
                startBtn.disabled = false;
                startBtn.innerHTML = originalHtml;
            }
        } catch (error) {
            console.error('Error starting service:', error);
            showToast('Error starting YOLO service', 'error');
            startBtn.disabled = false;
            startBtn.innerHTML = originalHtml;
        }
    }

    /**
     * Stop YOLO service
     */
    async function stopYoloService() {
        if (!confirm('Are you sure you want to stop the YOLO detection service? This will stop all active monitoring.')) {
            return;
        }
        
        const stopBtn = document.getElementById('stop-service-btn');
        const originalHtml = stopBtn.innerHTML;
        
        // Disable button and show loading
        stopBtn.disabled = true;
        stopBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Stopping...';
        
        try {
            const formData = new FormData();
            formData.append('action', 'stop');
            
            const response = await fetch('yolo_service_control.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message || 'YOLO service stopped successfully', 'success');
                // Wait a moment then refresh status
                setTimeout(() => {
                    checkYoloStatus();
                }, 1000);
            } else {
                showToast(data.message || 'Failed to stop YOLO service', 'error');
                stopBtn.disabled = false;
                stopBtn.innerHTML = originalHtml;
            }
        } catch (error) {
            console.error('Error stopping service:', error);
            showToast('Error stopping YOLO service', 'error');
            stopBtn.disabled = false;
            stopBtn.innerHTML = originalHtml;
        }
    }

    // ============================================================================
    // CAMERA CONFIGURATION
    // ============================================================================

    /**
     * Load available cameras into select dropdown
     */
    async function loadCameraList() {
        try {
            // Request camera permission first
            const tempStream = await navigator.mediaDevices.getUserMedia({ video: true });
            tempStream.getTracks().forEach(track => track.stop());
            
            // Enumerate cameras
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');
            
            const cameraSelect = document.getElementById('camera-select');
            const cameraConfigBadge = document.getElementById('camera-config-badge');
            
            if (videoDevices.length === 0) {
                cameraSelect.innerHTML = '<option value="">No cameras found</option>';
                cameraConfigBadge.textContent = 'No Cameras';
                cameraConfigBadge.className = 'text-xs font-bold text-red-600 dark:text-red-400';
                return;
            }
            
            // Get saved camera
            const savedCameraId = localStorage.getItem('defaultCameraId');
            
            // Populate dropdown
            cameraSelect.innerHTML = '';
            videoDevices.forEach((device, index) => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.textContent = device.label || `Camera ${index + 1}`;
                
                if (device.deviceId === savedCameraId) {
                    option.selected = true;
                    selectedDeviceId = device.deviceId;
                }
                
                cameraSelect.appendChild(option);
            });
            
            // Update badge
            if (savedCameraId) {
                cameraConfigBadge.textContent = 'Configured';
                cameraConfigBadge.className = 'text-xs font-bold text-green-600 dark:text-green-400';
            } else {
                cameraConfigBadge.textContent = 'Not Set';
                cameraConfigBadge.className = 'text-xs font-bold text-yellow-600 dark:text-yellow-400';
            }
            
        } catch (error) {
            console.error('Error loading cameras:', error);
            const cameraSelect = document.getElementById('camera-select');
            cameraSelect.innerHTML = '<option value="">Camera access denied</option>';
            
            const cameraConfigBadge = document.getElementById('camera-config-badge');
            cameraConfigBadge.textContent = 'Access Denied';
            cameraConfigBadge.className = 'text-xs font-bold text-red-600 dark:text-red-400';
        }
    }

    /**
     * Save camera configuration
     */
    function saveCameraConfig() {
        const cameraSelect = document.getElementById('camera-select');
        const selectedCamera = cameraSelect.value;
        
        if (!selectedCamera) {
            showToast('Please select a camera', 'error');
            return;
        }
        
        const cameraName = cameraSelect.options[cameraSelect.selectedIndex].textContent;
        
        localStorage.setItem('defaultCameraId', selectedCamera);
        localStorage.setItem('defaultCameraName', cameraName);
        
        selectedDeviceId = selectedCamera;
        
        const cameraConfigBadge = document.getElementById('camera-config-badge');
        cameraConfigBadge.textContent = 'Configured';
        cameraConfigBadge.className = 'text-xs font-bold text-green-600 dark:text-green-400';
        
        showToast(`Camera configured: ${cameraName}`, 'success');
    }

    // ============================================================================
    // DATA LOADING
    // ============================================================================

    /**
     * Update latest detection display with annotated image
     * Shows ALL detections (including low confidence) in the image
     */
    function updateLatestDetection(imagePath, detections) {
        const detectionImage = document.getElementById('detection-image');
        const noDetectionPlaceholder = document.getElementById('no-detection-placeholder');
        const detectionInfo = document.getElementById('detection-info');
        const detectionLabel = document.getElementById('detection-label');
        const detectionConfidence = document.getElementById('detection-confidence');
        const detectionTime = document.getElementById('detection-time');

        if (imagePath && detections && detections.length > 0) {
            // Show annotated image (contains ALL detections including low confidence)
            detectionImage.src = 'detections/' + imagePath + '?t=' + Date.now(); // Add timestamp to prevent caching
            detectionImage.classList.remove('hidden');
            noDetectionPlaceholder.classList.add('hidden');
            detectionInfo.classList.remove('hidden');

            // Get the first HIGH CONFIDENCE detection, or first detection if none are high confidence
            const highConfDetections = detections.filter(d => !d.is_low_confidence);
            const firstDetection = highConfDetections.length > 0 ? highConfDetections[0] : detections[0];

            // Update detection info
            let labelText = firstDetection.type;
            
            // Add indicator if this is low confidence
            if (firstDetection.is_low_confidence) {
                labelText += ' (Low Conf)';
            }
            
            // Show count if multiple detections
            if (detections.length > 1) {
                const lowConfCount = detections.filter(d => d.is_low_confidence).length;
                const highConfCount = detections.length - lowConfCount;
                
                if (lowConfCount > 0) {
                    labelText += ` +${detections.length - 1} more (${highConfCount} high, ${lowConfCount} low)`;
                } else {
                    labelText += ` +${detections.length - 1} more`;
                }
            }
            
            detectionLabel.textContent = labelText;
            detectionConfidence.textContent = firstDetection.confidence + '%';
            detectionTime.textContent = new Date().toLocaleTimeString();

            // Add severity color to label (dimmed if low confidence)
            const severityColors = {
                'low': 'text-blue-600',
                'medium': 'text-yellow-600',
                'high': 'text-orange-600',
                'critical': 'text-red-600'
            };
            let colorClass = severityColors[firstDetection.severity] || 'text-gray-600';
            
            // Dim the color if low confidence
            if (firstDetection.is_low_confidence) {
                colorClass = colorClass.replace('600', '400'); // Lighter shade
            }
            
            detectionLabel.className = `text-sm font-semibold ${colorClass}`;
        }
    }

    // ============================================================================
    // PAGINATION STATE
    // ============================================================================

    let allDetections = [];
    let currentDetectionPage = 1;
    const detectionsPerPage = 10;

    /**
     * Load recent detections from server
     */
    async function loadRecentDetections() {
        try {
            const data = await apiCall('get_recent_detections', { limit: 100 }, 'GET');

            if (data.success) {
                allDetections = data.detections;
                currentDetectionPage = 1;
                displayRecentDetections();
            }
        } catch (error) {
            console.error('Error loading recent detections:', error);
            showToast('Failed to load recent detections', 'error');
        }
    }

    /**
     * Change detection page
     */
    function changeDetectionPage(direction) {
        const totalPages = Math.ceil(allDetections.length / detectionsPerPage);

        if (direction === 'prev' && currentDetectionPage > 1) {
            currentDetectionPage--;
        } else if (direction === 'next' && currentDetectionPage < totalPages) {
            currentDetectionPage++;
        }

        displayRecentDetections();
    }

    /**
     * Display recent detections in the UI with pagination
     */
    function displayRecentDetections() {
        const container = document.getElementById('recent-detections');
        const paginationEl = document.getElementById('detection-pagination');
        const totalCountEl = document.getElementById('detection-total-count');

        if (!allDetections || allDetections.length === 0) {
            container.innerHTML = `
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>No detections yet. Start monitoring to see results here.</p>
                    </div>
                `;
            paginationEl.classList.add('hidden');
            totalCountEl.textContent = '0';
            return;
        }

        // Calculate pagination
        const totalPages = Math.ceil(allDetections.length / detectionsPerPage);
        const startIndex = (currentDetectionPage - 1) * detectionsPerPage;
        const endIndex = Math.min(startIndex + detectionsPerPage, allDetections.length);
        const pageDetections = allDetections.slice(startIndex, endIndex);

        // Update total count badge
        totalCountEl.textContent = allDetections.length;

        // Build compact detection rows
        let html = '';
        pageDetections.forEach(detection => {
            const severityColors = {
                'low': 'blue',
                'medium': 'yellow',
                'high': 'orange',
                'critical': 'red'
            };
            const color = severityColors[detection.severity] || 'gray';
            const timeAgo = formatTimeAgo(detection.detected_at);
            const readClass = detection.is_read ? 'bg-gray-50 dark:bg-gray-700/50' : 'bg-white dark:bg-gray-800';

            html += `
                    <div class="${readClass} hover:bg-gray-50 dark:hover:bg-gray-700 p-3 cursor-pointer transition-colors" onclick="viewAlertDetails(${detection.id || 0})">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-${color}-100 dark:bg-${color}-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-bug text-${color}-600 dark:text-${color}-400 text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">${escapeHtml(detection.common_name || detection.pest_type)}</h4>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">${timeAgo}</span>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">${detection.confidence_score}%</span>
                                    <span class="text-xs text-gray-400">•</span>
                                    <span class="px-2 py-0.5 bg-${color}-100 dark:bg-${color}-900 text-${color}-800 dark:text-${color}-200 text-xs font-medium rounded">
                                        ${detection.severity.charAt(0).toUpperCase() + detection.severity.slice(1)}
                                    </span>
                                    ${!detection.is_read ? `<span class="w-2 h-2 bg-${color}-500 rounded-full ml-auto"></span>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
        });

        container.innerHTML = html;

        // Update pagination controls
        paginationEl.classList.remove('hidden');
        document.getElementById('detection-showing-start').textContent = startIndex + 1;
        document.getElementById('detection-showing-end').textContent = endIndex;
        document.getElementById('detection-total').textContent = allDetections.length;
        document.getElementById('detection-current-page').textContent = currentDetectionPage;
        document.getElementById('detection-total-pages').textContent = totalPages;

        // Update button states
        const prevBtn = document.getElementById('detection-prev-btn');
        const nextBtn = document.getElementById('detection-next-btn');

        prevBtn.disabled = currentDetectionPage === 1;
        nextBtn.disabled = currentDetectionPage === totalPages;
    }

    /**
     * Load detection statistics
     */
    async function loadDetectionStats() {
        try {
            const data = await apiCall('get_detection_stats', {}, 'GET');

            if (data.success) {
                document.getElementById('total-detections').textContent = data.stats.total_detections || 0;
                document.getElementById('today-detections').textContent = data.stats.today_detections || 0;
                document.getElementById('critical-detections').textContent = data.stats.critical_count || 0;
            }
        } catch (error) {
            console.error('Error loading detection stats:', error);
        }
    }

    /**
     * Clear webcam detections
     */
    async function clearWebcamDetections() {
        if (!confirm('Are you sure you want to clear all webcam detections?')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'clear_webcam_detections');

            const response = await fetch('pest_detection.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast('Detections cleared successfully', 'success');
                loadRecentDetections();
                loadDetectionStats();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error clearing detections:', error);
            showToast('Failed to clear detections', 'error');
        }
    }

    // ============================================================================
    // NOTIFICATION FUNCTIONS
    // ============================================================================

    /**
     * Toggle notification panel visibility
     */
    function toggleNotificationPanel() {
        const panel = document.getElementById('notification-panel');
        panel.classList.toggle('hidden');

        if (!panel.classList.contains('hidden')) {
            loadUnreadNotifications();
        }
    }

    /**
     * Load unread notifications
     */
    async function loadUnreadNotifications() {
        try {
            const data = await apiCall('get_recent_detections', { limit: 20 }, 'GET');

            if (data.success) {
                const unreadDetections = data.detections.filter(d => !d.is_read);
                displayNotifications(unreadDetections);
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    /**
     * Display notifications in the panel
     */
    function displayNotifications(notifications) {
        const listEl = document.getElementById('notification-list');
        const countEl = document.getElementById('notification-count');

        countEl.textContent = notifications.length;

        if (notifications.length === 0) {
            listEl.innerHTML = `
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>No unread notifications</p>
                    </div>
                `;
            return;
        }

        listEl.innerHTML = notifications.map(notif => {
            const severityColors = {
                critical: 'bg-red-100 dark:bg-red-900/30 border-red-300 dark:border-red-700',
                high: 'bg-orange-100 dark:bg-orange-900/30 border-orange-300 dark:border-orange-700',
                medium: 'bg-yellow-100 dark:bg-yellow-900/30 border-yellow-300 dark:border-yellow-700',
                low: 'bg-blue-100 dark:bg-blue-900/30 border-blue-300 dark:border-blue-700'
            };

            const severityIcons = {
                critical: 'fa-exclamation-triangle text-red-600',
                high: 'fa-exclamation-circle text-orange-600',
                medium: 'fa-info-circle text-yellow-600',
                low: 'fa-check-circle text-blue-600'
            };

            return `
                    <div class="border ${severityColors[notif.severity]} rounded-lg p-4 cursor-pointer hover:shadow-md transition-shadow" 
                         onclick="viewAlertDetails(${notif.id || 0})">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <i class="fas ${severityIcons[notif.severity]}"></i>
                                <span class="font-semibold text-gray-900 dark:text-white">${escapeHtml(notif.pest_type)}</span>
                                <span class="px-2 py-1 text-xs font-bold uppercase rounded ${notif.severity === 'critical' ? 'bg-red-600 text-white' : notif.severity === 'high' ? 'bg-orange-600 text-white' : notif.severity === 'medium' ? 'bg-yellow-600 text-white' : 'bg-blue-600 text-white'}">${notif.severity}</span>
                            </div>
                            <button onclick="event.stopPropagation(); markAsRead(${notif.id})" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            <i class="fas fa-map-marker-alt mr-1"></i>${escapeHtml(notif.location)}
                            <span class="mx-2">•</span>
                            <i class="fas fa-clock mr-1"></i>${formatTimeAgo(notif.detected_at)}
                            <span class="mx-2">•</span>
                            <i class="fas fa-percentage mr-1"></i>${notif.confidence_score}% confidence
                        </div>
                        ${notif.suggested_actions ? `
                            <div class="text-xs text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 p-2 rounded mt-2">
                                <strong>Action:</strong> ${escapeHtml(notif.suggested_actions.substring(0, 100))}${notif.suggested_actions.length > 100 ? '...' : ''}
                            </div>
                        ` : ''}
                    </div>
                `;
        }).join('');
    }

    /**
     * Mark a specific alert as read
     */
    async function markAsRead(alertId) {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_as_read');
            formData.append('alert_id', alertId);

            const response = await fetch('pest_detection.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast('Alert marked as read', 'success');
                await updateUnreadCount();
                await loadUnreadNotifications();
                await loadRecentDetections();
            } else {
                showToast(data.message || 'Failed to mark as read', 'error');
            }
        } catch (error) {
            console.error('Error marking as read:', error);
            showToast('Error marking alert as read', 'error');
        }
    }

    /**
     * Mark all alerts as read
     */
    async function markAllAsRead() {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_all_as_read');

            const response = await fetch('pest_detection.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');
                await updateUnreadCount();
                await loadUnreadNotifications();
                await loadRecentDetections();
            } else {
                showToast(data.message || 'Failed to mark all as read', 'error');
            }
        } catch (error) {
            console.error('Error marking all as read:', error);
            showToast('Error marking alerts as read', 'error');
        }
    }

    /**
     * Update unread notification count
     */
    async function updateUnreadCount() {
        try {
            const formData = new FormData();
            formData.append('action', 'get_unread_count');

            const response = await fetch('pest_detection.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                const totalUnread = parseInt(data.unread.total_unread);
                const badge = document.getElementById('unread-badge');

                // Only update if badge element exists
                if (badge) {
                    if (totalUnread > 0) {
                        badge.textContent = totalUnread > 99 ? '99+' : totalUnread;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }

                // Also update notification count in panel if it exists
                const notificationCount = document.getElementById('notification-count');
                if (notificationCount) {
                    notificationCount.textContent = totalUnread;
                }
            }
        } catch (error) {
            console.error('Error updating unread count:', error);
        }
    }

    /**
     * View alert details in a modal
     */
    async function viewAlertDetails(alertId) {
        // Validate alertId before making API call
        if (alertId === undefined || alertId === null || alertId === '' || isNaN(parseInt(alertId)) || parseInt(alertId) <= 0) {
            console.error('viewAlertDetails called with invalid alertId:', alertId);
            showToast('Unable to load alert details: Invalid alert ID', 'error');
            return;
        }
        
        // Ensure alertId is an integer
        alertId = parseInt(alertId);
        
        // Show loading state
        const loadingModal = `
            <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" id="loading-modal">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-8 text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                    <p class="text-gray-700 dark:text-gray-300">Loading alert details...</p>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', loadingModal);
        
        try {
            // Use optimized API call helper
            const data = await apiCall('get_alert_details', { alert_id: alertId }, 'GET');
            
            // Remove loading modal
            document.getElementById('loading-modal')?.remove();

            if (data.success) {
                const alert = data.alert;
                const pestInfo = data.pest_info;

                // Create modal content
                const displayName = pestInfo.common_name || alert.pest_type;
                const scientificName = pestInfo.common_name ? `<div class="text-sm text-gray-500 dark:text-gray-400 italic mt-1">Scientific: ${escapeHtml(alert.pest_type)}</div>` : '';
                
                const modalContent = `
                        <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" onclick="this.remove()">
                            <div class="bg-white dark:bg-gray-800 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
                                <div class="p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                                <i class="fas fa-bug text-red-600 mr-2"></i>${escapeHtml(displayName)}
                                            </h2>
                                            ${scientificName}
                                        </div>
                                        <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                            <i class="fas fa-times text-2xl"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        ${alert.image_path ? `
                                            <div class="bg-gray-100 dark:bg-gray-900 rounded-lg overflow-hidden">
                                                <img src="${escapeHtml(alert.image_path)}" alt="Detection Image" class="w-full h-auto object-contain max-h-96" 
                                                     onerror="this.parentElement.innerHTML='<div class=\\'text-center py-8 text-gray-500 dark:text-gray-400\\'><i class=\\'fas fa-image-slash text-4xl mb-2\\'></i><p class=\\'text-sm\\'>Image not available</p></div>'">
                                            </div>
                                        ` : ''}
                                        
                                        <div class="flex items-center gap-4">
                                            <span class="px-3 py-1 text-sm font-bold uppercase rounded ${alert.severity === 'critical' ? 'bg-red-600 text-white' : alert.severity === 'high' ? 'bg-orange-600 text-white' : alert.severity === 'medium' ? 'bg-yellow-600 text-white' : 'bg-blue-600 text-white'}">${alert.severity}</span>
                                            <span class="text-gray-600 dark:text-gray-400">${alert.confidence_score}% confidence</span>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-600 dark:text-gray-400">Location:</span>
                                                <span class="font-medium text-gray-900 dark:text-white ml-2">${escapeHtml(alert.location)}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-600 dark:text-gray-400">Detected:</span>
                                                <span class="font-medium text-gray-900 dark:text-white ml-2">${new Date(alert.detected_at).toLocaleString()}</span>
                                            </div>
                                        </div>
                                        
                                        ${alert.description ? `
                                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Description</h3>
                                                <p class="text-gray-700 dark:text-gray-300">${escapeHtml(alert.description)}</p>
                                            </div>
                                        ` : ''}
                                        
                                        ${pestInfo.economic_threshold ? `
                                            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border-2 border-yellow-400 dark:border-yellow-600">
                                                <h3 class="font-semibold text-yellow-900 dark:text-yellow-200 mb-2 flex items-center">
                                                    <i class="fas fa-exclamation-triangle mr-2"></i>Economic Threshold
                                                </h3>
                                                <p class="text-yellow-800 dark:text-yellow-300 text-sm font-medium">
                                                    Take action when: <span class="font-bold">${escapeHtml(pestInfo.economic_threshold)}</span>
                                                </p>
                                                <p class="text-yellow-700 dark:text-yellow-400 text-xs mt-1">
                                                    This is the population level at which treatment becomes economically justified
                                                </p>
                                            </div>
                                        ` : ''}
                                        
                                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                                            <h3 class="font-semibold text-blue-900 dark:text-blue-200 mb-2 flex items-center">
                                                <i class="fas fa-clipboard-list mr-2"></i>Suggested Actions
                                            </h3>
                                            <p class="text-blue-800 dark:text-blue-300 text-sm whitespace-pre-line">${escapeHtml(pestInfo.actions)}</p>
                                        </div>
                                        
                                        ${pestInfo.remarks ? `
                                            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg border-l-4 border-gray-400 dark:border-gray-500">
                                                <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-1 flex items-center">
                                                    <i class="fas fa-info-circle mr-2"></i>Additional Notes
                                                </h3>
                                                <p class="text-gray-700 dark:text-gray-300 text-xs">${escapeHtml(pestInfo.remarks)}</p>
                                            </div>
                                        ` : ''}
                                        
                                        <div class="flex gap-3">
                                            ${!alert.is_read ? `
                                                <button onclick="markAsRead(${alert.id}); this.closest('.fixed').remove();" class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                                                    <i class="fas fa-check mr-2"></i>Mark as Read
                                                </button>
                                            ` : `
                                                <div class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 font-medium rounded-lg text-center">
                                                    <i class="fas fa-check-circle mr-2"></i>Already Read
                                                </div>
                                            `}
                                            <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                                                Close
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                document.body.insertAdjacentHTML('beforeend', modalContent);
            } else {
                showToast(data.message || 'Failed to load alert details', 'error');
            }
        } catch (error) {
            // Remove loading modal on error
            document.getElementById('loading-modal')?.remove();
            
            console.error('Error loading alert details:', error);
            showToast(error.message || 'Failed to load alert details', 'error');
        }
    }

    // ============================================================================
    // UTILITY FUNCTIONS
    // ============================================================================

    /**
     * Optimized API call helper with error handling
     */
    async function apiCall(action, params = {}, method = 'GET') {
        try {
            let url = `pest_detection.php?action=${action}`;
            let options = {
                method: method,
                headers: {
                    'Accept': 'application/json'
                }
            };

            if (method === 'GET') {
                // Add params to URL for GET requests
                Object.keys(params).forEach(key => {
                    url += `&${key}=${encodeURIComponent(params[key])}`;
                });
            } else if (method === 'POST') {
                // Use FormData for POST requests
                const formData = new FormData();
                formData.append('action', action);
                Object.keys(params).forEach(key => {
                    formData.append(key, params[key]);
                });
                options.body = formData;
            }

            const response = await fetch(url, options);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text.substring(0, 200));
                throw new Error('Server returned non-JSON response');
            }

            const data = await response.json();

            if (!data.success && data.message) {
                throw new Error(data.message);
            }

            return data;
        } catch (error) {
            console.error(`API call failed [${action}]:`, error);
            throw error;
        }
    }

    /**
     * Format timestamp as time ago
     */
    function formatTimeAgo(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`;
        return date.toLocaleDateString();
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Show toast notification
     */
    function showToast(message, type = 'info') {
        // Use existing toast system if available, otherwise create simple toast
        if (typeof window.notificationSystem !== 'undefined' && window.notificationSystem.showToast) {
            window.notificationSystem.showToast({
                title: type.charAt(0).toUpperCase() + type.slice(1),
                message: message,
                type: type,
                timestamp: new Date().toISOString()
            });
        } else {
            // Fallback: create simple toast
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 max-w-sm bg-white dark:bg-gray-800 rounded-lg shadow-lg border-l-4 ${
                type === 'error' ? 'border-red-500' : 
                type === 'success' ? 'border-green-500' : 
                type === 'warning' ? 'border-yellow-500' : 'border-blue-500'
            } p-4 animate-fade-in`;
            
            toast.innerHTML = `
                <div class="flex items-start">
                    <i class="fas fa-${
                        type === 'error' ? 'exclamation-circle text-red-500' : 
                        type === 'success' ? 'check-circle text-green-500' : 
                        type === 'warning' ? 'exclamation-triangle text-yellow-500' : 'info-circle text-blue-500'
                    } mr-3 mt-0.5"></i>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">${escapeHtml(message)}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }
    }

    // ============================================================================
    // POPUP WINDOW
    // ============================================================================

    /**
     * Open pest monitor in popup window
     */
    function openPestMonitorPopup() {
        const width = 1400;
        const height = 900;
        const left = (screen.width - width) / 2;
        const top = (screen.height - height) / 2;
        
        const popup = window.open(
            'pest_monitor_popup.php',
            'PestMonitor',
            `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes,status=no,toolbar=no,menubar=no,location=no`
        );
        
        if (!popup || popup.closed || typeof popup.closed == 'undefined') {
            showToast('Popup was blocked! Please allow popups for this site.', 'error');
        } else {
            showToast('Pest monitor opened in new window', 'success');
        }
    }

    // ============================================================================
    // EVENT LISTENERS
    // ============================================================================

    // Stop detection button (onclick handler in HTML)

    // ============================================================================
    // INITIALIZATION
    // ============================================================================

    document.addEventListener('DOMContentLoaded', async function() {
        console.log('Pest Detection Control Center initializing...');
        
        // Load camera list for configuration
        await loadCameraList();
        
        // Check YOLO service status
        await checkYoloStatus();
        
        // Load initial data
        await loadRecentDetections();
        await loadDetectionStats();
        await updateUnreadCount();

        console.log('Pest Detection Control Center ready');

        // Auto-refresh recent detections every 10 seconds
        setInterval(async () => {
            await loadRecentDetections();
            await loadDetectionStats();
        }, 10000);
        
        // Update unread count every 30 seconds
        setInterval(updateUnreadCount, 30000);
        
        // Check YOLO status every 60 seconds
        yoloStatusCheckInterval = setInterval(checkYoloStatus, 60000);
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (yoloStatusCheckInterval) {
            clearInterval(yoloStatusCheckInterval);
        }
    });
</script>



<?php include 'includes/footer.php'; ?>