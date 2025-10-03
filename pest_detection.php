<?php
// Start session and authentication check
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/security.php';

// Check page access permission
requirePageAccess('pest_detection');

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

// Handle AJAX requests for pest alert and camera actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        $pdo = getDatabaseConnection();

        switch ($_POST['action']) {
            case 'update_status':
                $alertId = filter_var($_POST['alert_id'], FILTER_VALIDATE_INT);
                $newStatus = trim(htmlspecialchars($_POST['status'] ?? '', ENT_QUOTES, 'UTF-8'));

                if (!$alertId || !in_array($newStatus, ['new', 'acknowledged', 'resolved'])) {
                    throw new Exception('Invalid parameters');
                }

                $stmt = $pdo->prepare("UPDATE pest_alerts SET status = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$newStatus, $alertId]);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Alert status updated successfully']);
                } else {
                    throw new Exception('Failed to update alert status');
                }
                break;

            case 'get_alert_details':
                $alertId = filter_var($_POST['alert_id'], FILTER_VALIDATE_INT);

                if (!$alertId) {
                    throw new Exception('Invalid alert ID');
                }

                $stmt = $pdo->prepare("
                    SELECT pa.*, c.camera_name, c.location as camera_location 
                    FROM pest_alerts pa 
                    LEFT JOIN cameras c ON pa.camera_id = c.id 
                    WHERE pa.id = ?
                ");
                $stmt->execute([$alertId]);
                $alert = $stmt->fetch();

                if ($alert) {
                    echo json_encode(['success' => true, 'alert' => $alert]);
                } else {
                    throw new Exception('Alert not found');
                }
                break;

            case 'get_cameras':
                $stmt = $pdo->query("SELECT * FROM cameras ORDER BY location, camera_name");
                $cameras = $stmt->fetchAll();
                echo json_encode(['success' => true, 'cameras' => $cameras]);
                break;

            case 'update_camera_settings':
                if (!hasRole('admin') && !hasRole('farmer')) {
                    throw new Exception('Insufficient permissions');
                }

                $cameraId = filter_var($_POST['camera_id'], FILTER_VALIDATE_INT);
                $cameraName = trim($_POST['camera_name'] ?? '');
                $location = trim($_POST['location'] ?? '');
                $ipAddress = filter_var($_POST['ip_address'] ?? '', FILTER_VALIDATE_IP);
                $port = filter_var($_POST['port'] ?? 80, FILTER_VALIDATE_INT);
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $cameraType = trim($_POST['camera_type'] ?? 'ip_camera');
                $resolution = trim($_POST['resolution'] ?? '1920x1080');
                $fps = filter_var($_POST['fps'] ?? 30, FILTER_VALIDATE_INT);
                $detectionEnabled = isset($_POST['detection_enabled']) ? 1 : 0;
                $detectionSensitivity = trim($_POST['detection_sensitivity'] ?? 'medium');

                if (!$cameraId || !$cameraName || !$location) {
                    throw new Exception('Required fields missing');
                }

                if (!in_array($cameraType, ['ip_camera', 'usb_camera', 'rtsp_stream'])) {
                    throw new Exception('Invalid camera type');
                }

                if (!in_array($detectionSensitivity, ['low', 'medium', 'high'])) {
                    throw new Exception('Invalid detection sensitivity');
                }

                // Hash password if provided
                $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

                if ($hashedPassword) {
                    $stmt = $pdo->prepare("
                        UPDATE cameras SET 
                        camera_name = ?, location = ?, ip_address = ?, port = ?, 
                        username = ?, password = ?, camera_type = ?, resolution = ?, 
                        fps = ?, detection_enabled = ?, detection_sensitivity = ?, 
                        updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $result = $stmt->execute([
                        $cameraName,
                        $location,
                        $ipAddress,
                        $port,
                        $username,
                        $hashedPassword,
                        $cameraType,
                        $resolution,
                        $fps,
                        $detectionEnabled,
                        $detectionSensitivity,
                        $cameraId
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE cameras SET 
                        camera_name = ?, location = ?, ip_address = ?, port = ?, 
                        username = ?, camera_type = ?, resolution = ?, 
                        fps = ?, detection_enabled = ?, detection_sensitivity = ?, 
                        updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $result = $stmt->execute([
                        $cameraName,
                        $location,
                        $ipAddress,
                        $port,
                        $username,
                        $cameraType,
                        $resolution,
                        $fps,
                        $detectionEnabled,
                        $detectionSensitivity,
                        $cameraId
                    ]);
                }

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Camera settings updated successfully']);
                } else {
                    throw new Exception('Failed to update camera settings');
                }
                break;

            case 'test_camera_connection':
                $ipAddress = filter_var($_POST['ip_address'] ?? '', FILTER_VALIDATE_IP);
                $port = filter_var($_POST['port'] ?? 80, FILTER_VALIDATE_INT);

                if (!$ipAddress || !$port) {
                    throw new Exception('Invalid IP address or port');
                }

                // Simulate connection test (in real implementation, this would actually test the connection)
                // For demo purposes, we'll simulate success most of the time
                $success = rand(1, 10) > 2; // 80% success rate for demo

                if ($success) {
                    echo json_encode(['success' => true, 'message' => "Camera connection successful! ({$ipAddress}:{$port})"]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Connection failed. Please check IP address and credentials.']);
                }
                break;

            case 'capture_test_image':
                $cameraId = filter_var($_POST['camera_id'] ?? 0, FILTER_VALIDATE_INT);

                if (!$cameraId) {
                    throw new Exception('Invalid camera ID');
                }

                // Get camera info
                $stmt = $pdo->prepare("SELECT camera_name, detection_enabled FROM cameras WHERE id = ?");
                $stmt->execute([$cameraId]);
                $camera = $stmt->fetch();

                if (!$camera) {
                    throw new Exception('Camera not found');
                }

                // Simulate image capture and AI analysis
                $confidence = rand(70, 95);
                $pestsDetected = rand(0, 10) > 7; // 30% chance of detecting pests for demo

                if ($pestsDetected && $camera['detection_enabled']) {
                    $pestTypes = ['Aphids', 'Caterpillars', 'Whiteflies', 'Spider Mites', 'Thrips', 'Beetles'];
                    $detectedPest = $pestTypes[array_rand($pestTypes)];
                    $severityLevels = ['low', 'medium', 'high'];
                    $severity = $severityLevels[array_rand($severityLevels)];

                    echo json_encode([
                        'success' => true,
                        'message' => "Test image captured from {$camera['camera_name']}",
                        'analysis' => [
                            'pests_detected' => true,
                            'pest_type' => $detectedPest,
                            'confidence' => $confidence,
                            'severity' => $severity,
                            'recommendation' => 'Immediate attention required - Consider applying appropriate pest control measures'
                        ]
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'message' => "Test image captured from {$camera['camera_name']}",
                        'analysis' => [
                            'pests_detected' => false,
                            'confidence' => $confidence,
                            'recommendation' => 'No pests detected - Continue regular monitoring'
                        ]
                    ]);
                }
                break;

            case 'get_camera_status':
                $stmt = $pdo->query("
                    SELECT 
                        COUNT(*) as total_cameras,
                        COUNT(CASE WHEN status = 'online' THEN 1 END) as online_cameras,
                        COUNT(CASE WHEN status = 'offline' THEN 1 END) as offline_cameras,
                        COUNT(CASE WHEN status = 'error' THEN 1 END) as error_cameras,
                        COUNT(CASE WHEN detection_enabled = 1 THEN 1 END) as detection_enabled_cameras
                    FROM cameras
                ");
                $stats = $stmt->fetch();
                echo json_encode(['success' => true, 'stats' => $stats]);
                break;

            case 'toggle_camera_detection':
                if (!hasRole('admin') && !hasRole('farmer')) {
                    throw new Exception('Insufficient permissions');
                }

                $cameraId = filter_var($_POST['camera_id'], FILTER_VALIDATE_INT);
                $enabled = isset($_POST['enabled']) ? 1 : 0;

                if (!$cameraId) {
                    throw new Exception('Invalid camera ID');
                }

                $stmt = $pdo->prepare("UPDATE cameras SET detection_enabled = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$enabled, $cameraId]);

                if ($result) {
                    $status = $enabled ? 'enabled' : 'disabled';
                    echo json_encode(['success' => true, 'message' => "Camera detection {$status} successfully"]);
                } else {
                    throw new Exception('Failed to update camera detection status');
                }
                break;

            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$severityFilter = $_GET['severity'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'detected_at';
$sortOrder = $_GET['order'] ?? 'desc';
$searchQuery = $_GET['search'] ?? '';

// Build WHERE clause for filters
$whereConditions = [];
$params = [];

if ($statusFilter !== 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}

if ($severityFilter !== 'all') {
    $whereConditions[] = "severity = ?";
    $params[] = $severityFilter;
}

if (!empty($searchQuery)) {
    $whereConditions[] = "(pest_type LIKE ? OR location LIKE ? OR description LIKE ?)";
    $searchParam = '%' . $searchQuery . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Validate sort parameters
$allowedSortFields = ['detected_at', 'pest_type', 'location', 'severity', 'status'];
$sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'detected_at';
$sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtolower($sortOrder) : 'desc';

// Get pest alerts with filtering and sorting
function getPestAlerts($whereClause, $params, $sortBy, $sortOrder)
{
    try {
        global $pdo;
        $sql = "SELECT * FROM pest_alerts $whereClause ORDER BY $sortBy $sortOrder";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get pest alerts: " . $e->getMessage());
        return [];
    }
}

// Get alert statistics
function getAlertStatistics()
{
    try {
        global $pdo;

        $stats = [];

        // Total alerts
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM pest_alerts");
        $stats['total'] = $stmt->fetch()['total'];

        // Alerts by status
        $stmt = $pdo->query("
            SELECT status, COUNT(*) as count 
            FROM pest_alerts 
            GROUP BY status
        ");
        $statusCounts = $stmt->fetchAll();
        $stats['by_status'] = [];
        foreach ($statusCounts as $status) {
            $stats['by_status'][$status['status']] = $status['count'];
        }

        // Alerts by severity
        $stmt = $pdo->query("
            SELECT severity, COUNT(*) as count 
            FROM pest_alerts 
            GROUP BY severity
        ");
        $severityCounts = $stmt->fetchAll();
        $stats['by_severity'] = [];
        foreach ($severityCounts as $severity) {
            $stats['by_severity'][$severity['severity']] = $severity['count'];
        }

        // Recent alerts (last 24 hours)
        $stmt = $pdo->query("
            SELECT COUNT(*) as recent 
            FROM pest_alerts 
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stats['recent'] = $stmt->fetch()['recent'];

        // Critical alerts
        $stmt = $pdo->query("
            SELECT COUNT(*) as critical 
            FROM pest_alerts 
            WHERE severity = 'critical' AND status != 'resolved'
        ");
        $stats['critical'] = $stmt->fetch()['critical'];

        return $stats;
    } catch (Exception $e) {
        error_log("Failed to get alert statistics: " . $e->getMessage());
        return [
            'total' => 0,
            'by_status' => [],
            'by_severity' => [],
            'recent' => 0,
            'critical' => 0
        ];
    }
}

// Get new alerts for notification panel
function getNewAlerts()
{
    try {
        global $pdo;
        $stmt = $pdo->query("
            SELECT * FROM pest_alerts 
            WHERE status = 'new' 
            ORDER BY detected_at DESC 
            LIMIT 5
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get new alerts: " . $e->getMessage());
        return [];
    }
}

// Get data
$pestAlerts = getPestAlerts($whereClause, $params, $sortBy, $sortOrder);
$alertStats = getAlertStatistics();
$newAlerts = getNewAlerts();

// Set page title
$pageTitle = 'Pest Detection - IoT Farm Monitoring System';

// Include header
include 'includes/header.php';
?>

<?php include 'includes/navigation.php'; ?>

<!-- Pest Detection Content -->
<div class="p-4 max-w-7xl mx-auto">
    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Live Camera Feed Section -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-video text-red-600 mr-2"></i>
                            Live Pest Detection Feed
                            <span class="ml-2 px-2 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 text-xs font-medium rounded-full animate-pulse">
                                LIVE
                            </span>
                        </h3>
                        <div class="flex items-center gap-2">
                            <select id="active-camera-select" class="text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded px-2 py-1 focus:ring-2 focus:ring-blue-500">
                                <option value="1">Greenhouse A - North</option>
                                <option value="2">Greenhouse A - South</option>
                                <option value="4">Field A - Section 1</option>
                                <option value="6">Greenhouse A - Bed 1</option>
                            </select>
                            <button id="stop-feed-btn" onclick="stopLiveFeed()" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded transition-colors">
                                <i class="fas fa-stop mr-1"></i>Stop
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <!-- Live Feed Display -->
                    <div id="live-feed-container" class="bg-black aspect-video flex items-center justify-center relative overflow-hidden">
                        <div id="camera-feed" class="w-full h-full bg-gradient-to-br from-gray-800 to-gray-900 flex items-center justify-center">
                            <div class="text-center text-white">
                                <div class="animate-pulse mb-4">
                                    <i class="fas fa-video text-6xl mb-4 text-blue-400"></i>
                                    <h4 class="text-xl font-semibold mb-2">AI Pest Detection Active</h4>
                                    <p class="text-gray-300">Greenhouse A - North Camera</p>
                                    <div class="mt-4 flex items-center justify-center gap-2">
                                        <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                                        <span class="text-sm">Recording & Analyzing</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- AI Detection Overlay -->
                        <div class="absolute top-4 left-4 bg-black bg-opacity-75 text-white px-3 py-2 rounded-lg">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-sm font-medium">AI Detection: ON</span>
                            </div>
                            <div class="text-xs text-gray-300">
                                Model: YoloV11 | Confidence: 94.7%
                            </div>
                        </div>

                        <!-- Live Stats Overlay -->
                        <div class="absolute top-4 right-4 bg-black bg-opacity-75 text-white px-3 py-2 rounded-lg">
                            <div class="text-xs space-y-1">
                                <div class="flex justify-between gap-4">
                                    <span>Resolution:</span>
                                    <span class="font-medium">1920x1080</span>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <span>FPS:</span>
                                    <span class="font-medium">30</span>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <span>Uptime:</span>
                                    <span class="font-medium text-green-400" id="feed-uptime">00:05:23</span>
                                </div>
                            </div>
                        </div>

                        <!-- Detection Boxes (simulated) -->
                        <div id="detection-boxes" class="absolute inset-0 pointer-events-none">
                            <!-- Detection boxes will be dynamically added here -->
                        </div>

                        <!-- Bottom Controls Overlay -->
                        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex items-center gap-3">
                            <button onclick="captureSnapshot()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
                                <i class="fas fa-camera mr-2"></i>Capture
                            </button>
                            <button onclick="toggleDetectionBoxes()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition-colors">
                                <i class="fas fa-square mr-2"></i>Toggle Boxes
                            </button>
                            <button onclick="fullscreenFeed()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg transition-colors">
                                <i class="fas fa-expand mr-2"></i>Fullscreen
                            </button>
                        </div>
                    </div>

                    <!-- Feed Status Bar -->
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-2 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-gray-600 dark:text-gray-400">Stream Active</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-brain text-purple-600 dark:text-purple-400"></i>
                                    <span class="text-gray-600 dark:text-gray-400">AI Processing: <span class="font-medium text-gray-900 dark:text-white">Real-time</span></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-bug text-yellow-600 dark:text-yellow-400"></i>
                                    <span class="text-gray-600 dark:text-gray-400">Detections Today: <span class="font-medium text-gray-900 dark:text-white" id="detections-count">3</span></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-600 dark:text-gray-400">Last Scan:</span>
                                <span class="font-medium text-gray-900 dark:text-white" id="last-scan-time">2 seconds ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Detection History -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-history text-blue-600 mr-2"></i>
                    Recent Detections (Live Feed)
                </h3>
                <div class="space-y-3" id="recent-detections">
                    <div class="flex items-start gap-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                        <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-bug text-yellow-600 dark:text-yellow-400 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Aphids Detected</h4>
                                <span class="text-xs text-gray-500">2 min ago</span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">Greenhouse A - North | Confidence: 89.3%</p>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 text-xs font-medium rounded-full">Medium Severity</span>
                                <button onclick="viewDetectionDetails(1)" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 text-xs font-medium">View Details</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="w-8 h-8 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-bug text-red-600 dark:text-red-400 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Spider Mites Detected</h4>
                                <span class="text-xs text-gray-500">8 min ago</span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">Greenhouse A - North | Confidence: 94.1%</p>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 text-xs font-medium rounded-full">High Severity</span>
                                <button onclick="viewDetectionDetails(2)" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 text-xs font-medium">View Details</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-shield-alt text-green-600 dark:text-green-400 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">No Threats Detected</h4>
                                <span class="text-xs text-gray-500">15 min ago</span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">Scan completed successfully | Confidence: 96.7%</p>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-medium rounded-full">All Clear</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Alerts Notification Panel -->
            <?php if (!empty($newAlerts)): ?>
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                                New Pest Alerts Detected
                            </h3>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                                <?php echo count($newAlerts); ?> new pest alert(s) require immediate attention.
                            </p>
                            <div class="space-y-2">
                                <?php foreach (array_slice($newAlerts, 0, 3) as $alert): ?>
                                    <div class="flex items-center justify-between bg-white/50 dark:bg-gray-800/50 rounded-lg p-2">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 text-xs font-medium rounded-full">
                                                <?php echo ucfirst($alert['severity']); ?>
                                            </span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($alert['pest_type']); ?>
                                            </span>
                                            <span class="text-xs text-gray-600 dark:text-gray-400">
                                                at <?php echo htmlspecialchars($alert['location']); ?>
                                            </span>
                                        </div>
                                        <button onclick="viewAlertDetails(<?php echo $alert['id']; ?>)"
                                            class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-xs font-medium">
                                            View
                                        </button>
                                    </div>
                                <?php endforeach; ?>

                                <?php if (count($newAlerts) > 3): ?>
                                    <div class="text-center pt-2">
                                        <button onclick="filterByStatus('new')"
                                            class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-xs font-medium">
                                            View all <?php echo count($newAlerts); ?> new alerts →
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            
        </div>

        <!-- Right Column -->
        <div class="space-y-4">
            <!-- Current Detection Status -->
            <div class="bg-green-600 text-white rounded-xl p-4">
                <h3 class="text-white/80 text-xs font-medium mb-3">Live Detection Status</h3>
                <div class="space-y-3">
                    <div class="text-center">
                        <div class="text-2xl font-bold mb-1">
                            <?php echo $alertStats['total'] > 0 ? $alertStats['critical'] + ($alertStats['by_status']['new'] ?? 0) : 0; ?>
                        </div>
                        <div class="text-white/80 text-xs mb-3">Active Threats</div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="text-center p-2 bg-white/10 rounded">
                            <div class="font-bold"><?php echo $alertStats['by_status']['new'] ?? 0; ?></div>
                            <div class="text-white/80">New</div>
                        </div>
                        <div class="text-center p-2 bg-white/10 rounded">
                            <div class="font-bold"><?php echo $alertStats['by_status']['resolved'] ?? 0; ?></div>
                            <div class="text-white/80">Resolved</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Camera Status Summary -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Camera Network</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-green-100 dark:bg-green-900 rounded flex items-center justify-center">
                                <i class="fas fa-video text-green-600 dark:text-green-400 text-xs"></i>
                            </div>
                            <span class="text-xs font-medium text-gray-900 dark:text-white">Online Cameras</span>
                        </div>
                        <span class="text-xs font-bold text-gray-900 dark:text-white">5/6</span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900 rounded flex items-center justify-center">
                                <i class="fas fa-brain text-blue-600 dark:text-blue-400 text-xs"></i>
                            </div>
                            <span class="text-xs font-medium text-gray-900 dark:text-white">AI Detection</span>
                        </div>
                        <span class="text-xs font-bold text-gray-900 dark:text-white">Active</span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-purple-100 dark:bg-purple-900 rounded flex items-center justify-center">
                                <i class="fas fa-clock text-purple-600 dark:text-purple-400 text-xs"></i>
                            </div>
                            <span class="text-xs font-medium text-gray-900 dark:text-white">Last Scan</span>
                        </div>
                        <span class="text-xs font-bold text-gray-900 dark:text-white">2 min ago</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Quick Actions</h3>
                <div class="space-y-2">
                    <button onclick="toggleCameraSettings()" class="w-full text-left p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-video text-blue-600 dark:text-blue-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Camera Settings</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Configure detection cameras</p>
                            </div>
                        </div>
                    </button>
                    <button class="w-full text-left p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-download text-green-600 dark:text-green-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Export Report</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Download detection data</p>
                            </div>
                        </div>
                    </button>
                    <button class="w-full text-left p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-cog text-purple-600 dark:text-purple-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">AI Settings</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Adjust detection sensitivity</p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Search -->
            <div class="flex-1 max-w-md">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400 dark:text-gray-500"></i>
                    </div>
                    <input type="text"
                        id="search-input"
                        placeholder="Search pest type, location, or description..."
                        value="<?php echo htmlspecialchars($searchQuery); ?>"
                        class="pl-10 pr-4 py-2 w-full text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <select id="status-filter"
                    class="text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>New</option>
                    <option value="acknowledged" <?php echo $statusFilter === 'acknowledged' ? 'selected' : ''; ?>>Acknowledged</option>
                    <option value="resolved" <?php echo $statusFilter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                </select>

                <select id="severity-filter"
                    class="text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" <?php echo $severityFilter === 'all' ? 'selected' : ''; ?>>All Severity</option>
                    <option value="critical" <?php echo $severityFilter === 'critical' ? 'selected' : ''; ?>>Critical</option>
                    <option value="high" <?php echo $severityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="medium" <?php echo $severityFilter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="low" <?php echo $severityFilter === 'low' ? 'selected' : ''; ?>>Low</option>
                </select>

                <select id="sort-select"
                    class="text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="detected_at-desc" <?php echo ($sortBy === 'detected_at' && $sortOrder === 'desc') ? 'selected' : ''; ?>>Newest First</option>
                    <option value="detected_at-asc" <?php echo ($sortBy === 'detected_at' && $sortOrder === 'asc') ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="severity-desc" <?php echo ($sortBy === 'severity' && $sortOrder === 'desc') ? 'selected' : ''; ?>>Severity (High to Low)</option>
                    <option value="pest_type-asc" <?php echo ($sortBy === 'pest_type' && $sortOrder === 'asc') ? 'selected' : ''; ?>>Pest Type (A-Z)</option>
                    <option value="location-asc" <?php echo ($sortBy === 'location' && $sortOrder === 'asc') ? 'selected' : ''; ?>>Location (A-Z)</option>
                </select>

                <button onclick="clearFilters()"
                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>Clear
                </button>
            </div>
        </div>
    </div>

    <!-- Pest Alerts Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-table text-yellow-600 mr-2"></i>
                Pest Alerts
                <span class="ml-2 px-2 py-1 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs rounded-full">
                    <?php echo count($pestAlerts); ?> alerts
                </span>
            </h3>
        </div>

        <?php if (empty($pestAlerts)): ?>
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-green-600 dark:text-green-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Pest Alerts Found</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    <?php if (!empty($searchQuery) || $statusFilter !== 'all' || $severityFilter !== 'all'): ?>
                        No alerts match your current filters. Try adjusting your search criteria.
                    <?php else: ?>
                        Your farm is currently pest-free! The monitoring system is actively scanning for threats.
                    <?php endif; ?>
                </p>
                <button onclick="clearFilters()" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Clear Filters
                </button>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Pest Type
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Location
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Severity
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Detected
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($pestAlerts as $alert):
                            $severityColors = [
                                'low' => 'green',
                                'medium' => 'yellow',
                                'high' => 'orange',
                                'critical' => 'red'
                            ];
                            $statusColors = [
                                'new' => 'red',
                                'acknowledged' => 'yellow',
                                'resolved' => 'green'
                            ];
                            $severityColor = $severityColors[$alert['severity']] ?? 'gray';
                            $statusColor = $statusColors[$alert['status']] ?? 'gray';
                        ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-<?php echo $severityColor; ?>-100 dark:bg-<?php echo $severityColor; ?>-900 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                            <i class="fas fa-bug text-<?php echo $severityColor; ?>-600 dark:text-<?php echo $severityColor; ?>-400 text-sm"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                <?php echo htmlspecialchars($alert['pest_type']); ?>
                                            </div>
                                            <?php if (isset($alert['confidence_score'])): ?>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    AI Confidence: <?php echo $alert['confidence_score']; ?>%
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <div class="flex items-center">
                                        <i class="fas fa-map-marker-alt text-gray-400 dark:text-gray-500 mr-1 text-xs"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($alert['location']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 bg-<?php echo $severityColor; ?>-100 dark:bg-<?php echo $severityColor; ?>-900 text-<?php echo $severityColor; ?>-800 dark:text-<?php echo $severityColor; ?>-200 text-xs font-medium rounded-full">
                                        <?php echo ucfirst($alert['severity']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <select onchange="updateAlertStatus(<?php echo $alert['id']; ?>, this.value)"
                                        class="px-2 py-1 bg-<?php echo $statusColor; ?>-100 dark:bg-<?php echo $statusColor; ?>-900 text-<?php echo $statusColor; ?>-800 dark:text-<?php echo $statusColor; ?>-200 text-xs font-medium rounded-full border-0 focus:ring-2 focus:ring-blue-500">
                                        <option value="new" <?php echo $alert['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                        <option value="acknowledged" <?php echo $alert['status'] === 'acknowledged' ? 'selected' : ''; ?>>Acknowledged</option>
                                        <option value="resolved" <?php echo $alert['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    </select>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?php
                                    $timestamp = strtotime($alert['detected_at']);
                                    $now = time();
                                    $diff = $now - $timestamp;

                                    if ($diff < 60) {
                                        echo 'Just now';
                                    } elseif ($diff < 3600) {
                                        echo floor($diff / 60) . 'm ago';
                                    } elseif ($diff < 86400) {
                                        echo floor($diff / 3600) . 'h ago';
                                    } else {
                                        echo date('M j', $timestamp);
                                    }
                                    ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <button onclick="viewAlertDetails(<?php echo $alert['id']; ?>)"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="View Details">
                                            <i class="fas fa-eye text-xs"></i>
                                        </button>
                                        <?php if ($alert['status'] !== 'resolved'): ?>
                                            <button onclick="updateAlertStatus(<?php echo $alert['id']; ?>, 'resolved')"
                                                class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300" title="Mark Resolved">
                                                <i class="fas fa-check text-xs"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Camera Settings Modal -->
<div id="camera-settings-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 xl:w-1/2 shadow-lg rounded-xl bg-white dark:bg-gray-800 max-h-screen overflow-y-auto">
        <div class="mt-3">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-secondary-200 dark:border-gray-700">
                <h3 class="text-heading-lg text-secondary-900 dark:text-white font-display">
                    <i class="fas fa-video text-blue-600 dark:text-blue-400 mr-2"></i>
                    Camera Management & Settings
                </h3>
                <button onclick="closeCameraSettingsModal()" class="text-secondary-400 dark:text-gray-500 hover:text-secondary-600 dark:hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="py-6 space-y-6">
                <!-- Camera Selection -->
                <div>
                    <h4 class="text-heading-md text-secondary-900 dark:text-white mb-4">Select Active Camera</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="camera-selection-grid">
                        <!-- Camera selection cards will be loaded here -->
                    </div>
                </div>

                <!-- Camera Settings Form -->
                <div id="camera-settings-form" class="hidden">
                    <h4 class="text-heading-md text-secondary-900 dark:text-white mb-4">Camera Configuration</h4>
                    <form id="camera-config-form" class="space-y-4">
                        <input type="hidden" id="selected-camera-id" name="camera_id">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                    Camera Name
                                </label>
                                <input type="text" id="camera-name" name="camera_name"
                                    class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>

                            <div>
                                <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                    Location
                                </label>
                                <input type="text" id="camera-location" name="location"
                                    class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>

                            <div>
                                <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                    IP Address
                                </label>
                                <input type="text" id="camera-ip" name="ip_address"
                                    class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>

                            <div>
                                <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                    Port
                                </label>
                                <input type="number" id="camera-port" name="port" min="1" max="65535"
                                    class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>

                            <div>
                                <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                    Resolution
                                </label>
                                <select id="camera-resolution" name="resolution"
                                    class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="640x480">640x480 (VGA)</option>
                                    <option value="1280x720">1280x720 (HD)</option>
                                    <option value="1920x1080">1920x1080 (Full HD)</option>
                                    <option value="2560x1440">2560x1440 (2K)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                    Frame Rate (FPS)
                                </label>
                                <select id="camera-fps" name="fps"
                                    class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="15">15 FPS</option>
                                    <option value="25">25 FPS</option>
                                    <option value="30">30 FPS</option>
                                    <option value="60">60 FPS</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                    Detection Sensitivity
                                </label>
                                <select id="detection-sensitivity" name="detection_sensitivity"
                                    class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="low">Low (90%+ confidence)</option>
                                    <option value="medium">Medium (80%+ confidence)</option>
                                    <option value="high">High (70%+ confidence)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                    Camera Type
                                </label>
                                <select id="camera-type" name="camera_type"
                                    class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="ip_camera">IP Camera</option>
                                    <option value="usb_camera">USB Camera</option>
                                    <option value="rtsp_stream">RTSP Stream</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="detection-enabled" name="detection_enabled"
                                class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="detection-enabled" class="ml-2 text-body-md font-medium text-secondary-700 dark:text-gray-300">
                                Enable AI Pest Detection
                            </label>
                        </div>

                        <!-- Authentication for IP Cameras -->
                        <div id="camera-auth" class="hidden">
                            <h5 class="text-heading-sm text-secondary-900 dark:text-white mb-3">Camera Authentication</h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                        Username
                                    </label>
                                    <input type="text" id="camera-username" name="username"
                                        class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>

                                <div>
                                    <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                        Password
                                    </label>
                                    <input type="password" id="camera-password" name="password"
                                        class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-secondary-200 dark:border-gray-700">
                            <button type="button" onclick="testCameraConnection()"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex-1 sm:flex-none">
                                <i class="fas fa-plug mr-2"></i>Test Connection
                            </button>

                            <button type="button" onclick="startLivePreview()"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200 flex-1 sm:flex-none">
                                <i class="fas fa-play mr-2"></i>Live Preview
                            </button>

                            <button type="submit"
                                class="btn-primary flex-1 sm:flex-none">
                                <i class="fas fa-save mr-2"></i>Save Settings
                            </button>

                            <button type="button" onclick="closeCameraSettingsModal()"
                                class="px-4 py-2 bg-secondary-600 hover:bg-secondary-700 text-white rounded-lg font-medium transition-colors duration-200 flex-1 sm:flex-none">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Live Preview Area -->
                <div id="live-preview-area" class="hidden">
                    <h4 class="text-heading-md text-secondary-900 dark:text-white mb-4">Live Camera Feed</h4>
                    <div class="bg-black rounded-lg overflow-hidden">
                        <div id="camera-preview" class="w-full h-64 bg-gray-900 flex items-center justify-center">
                            <div class="text-white text-center">
                                <i class="fas fa-video text-4xl mb-2"></i>
                                <p>Camera feed will appear here</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-center gap-3">
                        <button onclick="stopLivePreview()"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors duration-200">
                            <i class="fas fa-stop mr-2"></i>Stop Preview
                        </button>
                        <button onclick="captureTestImage()"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors duration-200">
                            <i class="fas fa-camera mr-2"></i>Capture Test Image
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Details Modal -->
<div id="alert-details-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-xl bg-white dark:bg-gray-800">
        <div class="mt-3">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-secondary-200 dark:border-gray-700">
                <h3 class="text-heading-lg text-secondary-900 dark:text-white font-display">
                    <i class="fas fa-bug text-yellow-600 dark:text-yellow-400 mr-2"></i>
                    Pest Alert Details
                </h3>
                <button onclick="closeAlertModal()" class="text-secondary-400 dark:text-gray-500 hover:text-secondary-600 dark:hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div id="modal-content" class="py-6">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
    // Filter and search functionality
    let searchTimeout;

    document.getElementById('search-input').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters();
        }, 500);
    });

    document.getElementById('status-filter').addEventListener('change', applyFilters);
    document.getElementById('severity-filter').addEventListener('change', applyFilters);
    document.getElementById('sort-select').addEventListener('change', applyFilters);

    function applyFilters() {
        const search = document.getElementById('search-input').value;
        const status = document.getElementById('status-filter').value;
        const severity = document.getElementById('severity-filter').value;
        const sort = document.getElementById('sort-select').value;

        const [sortBy, sortOrder] = sort.split('-');

        const params = new URLSearchParams();
        if (search) params.set('search', search);
        if (status !== 'all') params.set('status', status);
        if (severity !== 'all') params.set('severity', severity);
        if (sortBy) params.set('sort', sortBy);
        if (sortOrder) params.set('order', sortOrder);

        window.location.href = 'pest_detection.php?' + params.toString();
    }

    function clearFilters() {
        window.location.href = 'pest_detection.php';
    }

    function filterByStatus(status) {
        document.getElementById('status-filter').value = status;
        applyFilters();
    }

    // Alert management functions
    function updateAlertStatus(alertId, newStatus) {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('alert_id', alertId);
        formData.append('status', newStatus);

        fetch('pest_detection.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    // Optionally refresh the page or update the UI
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to update alert status', 'error');
            });
    }

    function viewAlertDetails(alertId) {
        const formData = new FormData();
        formData.append('action', 'get_alert_details');
        formData.append('alert_id', alertId);

        fetch('pest_detection.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayAlertDetails(data.alert);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to load alert details', 'error');
            });
    }

    function displayAlertDetails(alert) {
        const severityColors = {
            'low': 'green',
            'medium': 'yellow',
            'high': 'orange',
            'critical': 'red'
        };

        const statusColors = {
            'new': 'red',
            'acknowledged': 'yellow',
            'resolved': 'green'
        };

        const severityColor = severityColors[alert.severity] || 'gray';
        const statusColor = statusColors[alert.status] || 'gray';

        const modalContent = document.getElementById('modal-content');
        modalContent.innerHTML = `
        <div class="space-y-6">
            <!-- Alert Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-heading-md text-secondary-900 dark:text-white mb-3">Pest Information</h4>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <span class="text-body-md text-secondary-600 dark:text-gray-400 w-20">Type:</span>
                            <span class="text-body-md font-medium text-secondary-900 dark:text-white">${alert.pest_type}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-body-md text-secondary-600 dark:text-gray-400 w-20">Location:</span>
                            <span class="text-body-md font-medium text-secondary-900 dark:text-white">
                                <i class="fas fa-map-marker-alt text-secondary-400 dark:text-gray-500 mr-1"></i>
                                ${alert.location}
                            </span>
                        </div>
                        ${alert.camera_name ? `
                        <div class="flex items-center">
                            <span class="text-body-md text-secondary-600 dark:text-gray-400 w-20">Camera:</span>
                            <span class="text-body-md font-medium text-secondary-900 dark:text-white">
                                <i class="fas fa-video text-secondary-400 dark:text-gray-500 mr-1"></i>
                                ${alert.camera_name}
                            </span>
                        </div>
                        ` : ''}
                        ${alert.confidence_score ? `
                        <div class="flex items-center">
                            <span class="text-body-md text-secondary-600 dark:text-gray-400 w-20">AI Confidence:</span>
                            <span class="text-body-md font-medium text-secondary-900 dark:text-white">
                                ${alert.confidence_score}%
                            </span>
                        </div>
                        ` : ''}
                        <div class="flex items-center">
                            <span class="text-body-md text-secondary-600 dark:text-gray-400 w-20">Detected:</span>
                            <span class="text-body-md font-medium text-secondary-900 dark:text-white">
                                ${formatDate(alert.detected_at)}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-heading-md text-secondary-900 dark:text-white mb-3">Alert Status</h4>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <span class="text-body-md text-secondary-600 dark:text-gray-400 w-20">Severity:</span>
                            <span class="px-3 py-1 bg-${severityColor}-100 dark:bg-${severityColor}-900 text-${severityColor}-800 dark:text-${severityColor}-200 text-body-sm font-medium rounded-full">
                                ${alert.severity.charAt(0).toUpperCase() + alert.severity.slice(1)}
                            </span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-body-md text-secondary-600 dark:text-gray-400 w-20">Status:</span>
                            <span class="px-3 py-1 bg-${statusColor}-100 dark:bg-${statusColor}-900 text-${statusColor}-800 dark:text-${statusColor}-200 text-body-sm font-medium rounded-full">
                                ${alert.status.charAt(0).toUpperCase() + alert.status.slice(1)}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Captured Image -->
            ${alert.image_path ? `
            <div>
                <h4 class="text-heading-md text-secondary-900 dark:text-white mb-3">
                    <i class="fas fa-camera text-blue-500 mr-2"></i>
                    Detection Image
                </h4>
                <div class="bg-secondary-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="relative inline-block">
                        <img src="${alert.image_path}" alt="Pest detection image" 
                             class="max-w-full h-auto rounded-lg border border-secondary-200 dark:border-gray-600"
                             style="max-height: 300px;">
                        <div class="absolute top-2 right-2 bg-black bg-opacity-75 text-white px-2 py-1 rounded text-xs">
                            AI Detection: ${alert.confidence_score}%
                        </div>
                    </div>
                    <p class="text-body-sm text-secondary-600 dark:text-gray-400 mt-2">
                        Image captured by ${alert.camera_name || 'Camera'} on ${formatDate(alert.detected_at)}
                    </p>
                </div>
            </div>
            ` : ''}
            
            <!-- Description -->
            <div>
                <h4 class="text-heading-md text-secondary-900 dark:text-white mb-3">Description</h4>
                <div class="bg-secondary-50 dark:bg-gray-700 rounded-lg p-4">
                    <p class="text-body-md text-secondary-700 dark:text-gray-300">${alert.description || 'No description available.'}</p>
                </div>
            </div>
            
            <!-- Suggested Actions -->
            <div>
                <h4 class="text-heading-md text-secondary-900 dark:text-white mb-3">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                    Suggested Actions
                </h4>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <p class="text-body-md text-blue-800 dark:text-blue-200">${alert.suggested_actions || 'No specific actions suggested. Consult with agricultural experts.'}</p>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-secondary-200 dark:border-gray-700">
                ${alert.status === 'new' ? `
                    <button onclick="updateAlertStatus(${alert.id}, 'acknowledged'); closeAlertModal();" 
                            class="btn-primary flex-1 sm:flex-none">
                        <i class="fas fa-check mr-2"></i>
                        Acknowledge Alert
                    </button>
                ` : ''}
                
                ${alert.status !== 'resolved' ? `
                    <button onclick="updateAlertStatus(${alert.id}, 'resolved'); closeAlertModal();" 
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200 flex-1 sm:flex-none">
                        <i class="fas fa-check-circle mr-2"></i>
                        Mark as Resolved
                    </button>
                ` : ''}
                
                <button onclick="closeAlertModal()" 
                        class="px-4 py-2 bg-secondary-600 hover:bg-secondary-700 text-white rounded-lg font-medium transition-colors duration-200 flex-1 sm:flex-none">
                    Close
                </button>
            </div>
        </div>
    `;

        document.getElementById('alert-details-modal').classList.remove('hidden');
    }

    function closeAlertModal() {
        document.getElementById('alert-details-modal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('alert-details-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAlertModal();
        }
    });

    // Format date for display
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Camera Management Functions
    function loadCameraGrid() {
        // Static camera data based on the database schema
        const cameras = [{
                id: 1,
                name: 'Greenhouse A - North Camera',
                location: 'Greenhouse A - North',
                status: 'online',
                resolution: '1920x1080',
                fps: 30,
                detection_enabled: true,
                last_detection: '30 minutes ago',
                confidence: 87.5
            },
            {
                id: 2,
                name: 'Greenhouse A - South Camera',
                location: 'Greenhouse A - South',
                status: 'online',
                resolution: '1920x1080',
                fps: 30,
                detection_enabled: true,
                last_detection: '1 hour ago',
                confidence: 95.8
            },
            {
                id: 3,
                name: 'Field B - Center Camera',
                location: 'Field B - Center',
                status: 'offline',
                resolution: '1280x720',
                fps: 25,
                detection_enabled: true,
                last_detection: 'Never',
                confidence: 0
            },
            {
                id: 4,
                name: 'Field A - Section 1 Camera',
                location: 'Field A - Section 1',
                status: 'online',
                resolution: '1920x1080',
                fps: 30,
                detection_enabled: true,
                last_detection: '4 hours ago',
                confidence: 89.1
            },
            {
                id: 5,
                name: 'Field A - Section 2 Camera',
                location: 'Field A - Section 2',
                status: 'error',
                resolution: '1920x1080',
                fps: 30,
                detection_enabled: false,
                last_detection: 'Never',
                confidence: 0
            },
            {
                id: 6,
                name: 'Greenhouse A - Bed 1 Camera',
                location: 'Greenhouse A - Bed 1',
                status: 'online',
                resolution: '1280x720',
                fps: 25,
                detection_enabled: true,
                last_detection: '1 hour ago',
                confidence: 91.7
            }
        ];

        const cameraGrid = document.getElementById('camera-grid');
        const cameraSelectionGrid = document.getElementById('camera-selection-grid');

        if (cameraGrid) {
            cameraGrid.innerHTML = cameras.map(camera => createCameraCard(camera)).join('');
        }

        if (cameraSelectionGrid) {
            cameraSelectionGrid.innerHTML = cameras.map(camera => createCameraSelectionCard(camera)).join('');
        }
    }

    function createCameraCard(camera) {
        const statusColors = {
            'online': 'green',
            'offline': 'gray',
            'error': 'red'
        };

        const statusColor = statusColors[camera.status] || 'gray';

        return `
        <div class="bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-${statusColor}-100 dark:bg-${statusColor}-900 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-video text-${statusColor}-600 dark:text-${statusColor}-400 text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">${camera.name}</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate">${camera.location}</p>
                    </div>
                </div>
                <span class="px-2 py-1 bg-${statusColor}-100 dark:bg-${statusColor}-900 text-${statusColor}-800 dark:text-${statusColor}-200 text-xs font-medium rounded-full">
                    ${camera.status.charAt(0).toUpperCase() + camera.status.slice(1)}
                </span>
            </div>
            
            <div class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Resolution:</span>
                    <span class="text-gray-900 dark:text-white font-medium">${camera.resolution}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">FPS:</span>
                    <span class="text-gray-900 dark:text-white font-medium">${camera.fps}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">AI Detection:</span>
                    <span class="text-gray-900 dark:text-white font-medium">
                        ${camera.detection_enabled ? 'Enabled' : 'Disabled'}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Last Detection:</span>
                    <span class="text-gray-900 dark:text-white font-medium">${camera.last_detection}</span>
                </div>
                ${camera.confidence > 0 ? `
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Confidence:</span>
                    <span class="text-gray-900 dark:text-white font-medium">${camera.confidence}%</span>
                </div>
                ` : ''}
            </div>
            
            <div class="mt-4 flex gap-2">
                <button onclick="selectCamera(${camera.id})" 
                        class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg font-medium transition-colors duration-200">
                    <i class="fas fa-cog mr-1"></i>Configure
                </button>
                ${camera.status === 'online' ? `
                <button onclick="viewLiveFeed(${camera.id})" 
                        class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-xs rounded-lg font-medium transition-colors duration-200">
                    <i class="fas fa-eye mr-1"></i>View Live
                </button>
                ` : ''}
            </div>
        </div>
    `;
    }

    function createCameraSelectionCard(camera) {
        const statusColors = {
            'online': 'green',
            'offline': 'gray',
            'error': 'red'
        };

        const statusColor = statusColors[camera.status] || 'gray';

        return `
        <div class="camera-selection-card bg-white dark:bg-gray-700 rounded-lg border-2 border-secondary-200 dark:border-gray-600 p-4 cursor-pointer hover:border-primary-500 dark:hover:border-primary-400 transition-colors duration-200" 
             onclick="selectCamera(${camera.id})">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-${statusColor}-100 dark:bg-${statusColor}-900 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-video text-${statusColor}-600 dark:text-${statusColor}-400"></i>
                    </div>
                    <div>
                        <h5 class="text-body-md font-medium text-secondary-900 dark:text-white">${camera.name}</h5>
                        <p class="text-body-sm text-secondary-600 dark:text-gray-400">${camera.location}</p>
                    </div>
                </div>
                <span class="px-2 py-1 bg-${statusColor}-100 dark:bg-${statusColor}-900 text-${statusColor}-800 dark:text-${statusColor}-200 text-body-xs font-medium rounded-full">
                    ${camera.status.charAt(0).toUpperCase() + camera.status.slice(1)}
                </span>
            </div>
        </div>
    `;
    }

    function toggleCameraSettings() {
        document.getElementById('camera-settings-modal').classList.remove('hidden');
        loadCameraGrid();
    }

    function closeCameraSettingsModal() {
        document.getElementById('camera-settings-modal').classList.add('hidden');
        document.getElementById('camera-settings-form').classList.add('hidden');
        document.getElementById('live-preview-area').classList.add('hidden');
    }

    function selectCamera(cameraId) {
        // Static camera data - in real implementation, this would come from database
        const cameras = {
            1: {
                id: 1,
                name: 'Greenhouse A - North Camera',
                location: 'Greenhouse A - North',
                ip_address: '192.168.1.101',
                port: 80,
                username: 'admin',
                camera_type: 'ip_camera',
                resolution: '1920x1080',
                fps: 30,
                detection_enabled: true,
                detection_sensitivity: 'high'
            },
            2: {
                id: 2,
                name: 'Greenhouse A - South Camera',
                location: 'Greenhouse A - South',
                ip_address: '192.168.1.102',
                port: 80,
                username: 'admin',
                camera_type: 'ip_camera',
                resolution: '1920x1080',
                fps: 30,
                detection_enabled: true,
                detection_sensitivity: 'medium'
            },
            3: {
                id: 3,
                name: 'Field B - Center Camera',
                location: 'Field B - Center',
                ip_address: '192.168.1.103',
                port: 80,
                username: 'admin',
                camera_type: 'ip_camera',
                resolution: '1280x720',
                fps: 25,
                detection_enabled: true,
                detection_sensitivity: 'medium'
            },
            4: {
                id: 4,
                name: 'Field A - Section 1 Camera',
                location: 'Field A - Section 1',
                ip_address: '192.168.1.104',
                port: 80,
                username: 'admin',
                camera_type: 'ip_camera',
                resolution: '1920x1080',
                fps: 30,
                detection_enabled: true,
                detection_sensitivity: 'high'
            },
            5: {
                id: 5,
                name: 'Field A - Section 2 Camera',
                location: 'Field A - Section 2',
                ip_address: '192.168.1.105',
                port: 80,
                username: 'admin',
                camera_type: 'ip_camera',
                resolution: '1920x1080',
                fps: 30,
                detection_enabled: false,
                detection_sensitivity: 'low'
            },
            6: {
                id: 6,
                name: 'Greenhouse A - Bed 1 Camera',
                location: 'Greenhouse A - Bed 1',
                ip_address: '192.168.1.106',
                port: 80,
                username: 'admin',
                camera_type: 'ip_camera',
                resolution: '1280x720',
                fps: 25,
                detection_enabled: true,
                detection_sensitivity: 'high'
            }
        };

        const camera = cameras[cameraId];
        if (!camera) return;

        // Populate form fields
        document.getElementById('selected-camera-id').value = camera.id;
        document.getElementById('camera-name').value = camera.name;
        document.getElementById('camera-location').value = camera.location;
        document.getElementById('camera-ip').value = camera.ip_address;
        document.getElementById('camera-port').value = camera.port;
        document.getElementById('camera-username').value = camera.username;
        document.getElementById('camera-type').value = camera.camera_type;
        document.getElementById('camera-resolution').value = camera.resolution;
        document.getElementById('camera-fps').value = camera.fps;
        document.getElementById('detection-enabled').checked = camera.detection_enabled;
        document.getElementById('detection-sensitivity').value = camera.detection_sensitivity;

        // Show/hide authentication fields based on camera type
        const authSection = document.getElementById('camera-auth');
        if (camera.camera_type === 'ip_camera') {
            authSection.classList.remove('hidden');
        } else {
            authSection.classList.add('hidden');
        }

        // Show the settings form
        document.getElementById('camera-settings-form').classList.remove('hidden');

        // Highlight selected camera
        document.querySelectorAll('.camera-selection-card').forEach(card => {
            card.classList.remove('border-primary-500', 'dark:border-primary-400');
            card.classList.add('border-secondary-200', 'dark:border-gray-600');
        });

        const selectedCard = document.querySelector(`[onclick="selectCamera(${cameraId})"]`);
        if (selectedCard) {
            selectedCard.classList.remove('border-secondary-200', 'dark:border-gray-600');
            selectedCard.classList.add('border-primary-500', 'dark:border-primary-400');
        }
    }

    function testCameraConnection() {
        const cameraId = document.getElementById('selected-camera-id').value;
        const ip = document.getElementById('camera-ip').value;
        const port = document.getElementById('camera-port').value;

        if (!ip || !port) {
            showToast('Please enter IP address and port', 'error');
            return;
        }

        showToast('Testing camera connection...', 'info');

        const formData = new FormData();
        formData.append('action', 'test_camera_connection');
        formData.append('ip_address', ip);
        formData.append('port', port);

        fetch('pest_detection.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Connection test failed', 'error');
            });
    }

    function startLivePreview() {
        const cameraId = document.getElementById('selected-camera-id').value;
        const cameraName = document.getElementById('camera-name').value;

        if (!cameraId) {
            showToast('Please select a camera first', 'error');
            return;
        }

        // Show preview area
        document.getElementById('live-preview-area').classList.remove('hidden');

        // Simulate live feed (in real implementation, this would connect to actual camera)
        const previewArea = document.getElementById('camera-preview');
        previewArea.innerHTML = `
        <div class="w-full h-full bg-gray-800 flex items-center justify-center relative">
            <div class="text-white text-center">
                <div class="animate-pulse">
                    <i class="fas fa-video text-4xl mb-2"></i>
                    <p class="text-lg font-medium">${cameraName}</p>
                    <p class="text-sm opacity-75">Live Feed Active</p>
                    <div class="mt-4 flex justify-center">
                        <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                        <span class="ml-2 text-sm">REC</span>
                    </div>
                </div>
            </div>
            <div class="absolute top-2 left-2 bg-black bg-opacity-50 text-white px-2 py-1 rounded text-xs">
                ${new Date().toLocaleTimeString()}
            </div>
            <div class="absolute top-2 right-2 bg-green-500 text-white px-2 py-1 rounded text-xs">
                AI Detection: ON
            </div>
        </div>
    `;

        showToast('Live preview started', 'success');
    }

    function stopLivePreview() {
        const previewArea = document.getElementById('camera-preview');
        previewArea.innerHTML = `
        <div class="w-full h-full bg-gray-900 flex items-center justify-center">
            <div class="text-white text-center">
                <i class="fas fa-video text-4xl mb-2"></i>
                <p>Camera feed will appear here</p>
            </div>
        </div>
    `;

        showToast('Live preview stopped', 'info');
    }

    function captureTestImage() {
        const cameraId = document.getElementById('selected-camera-id').value;
        const cameraName = document.getElementById('camera-name').value;

        if (!cameraId) {
            showToast('Please select a camera first', 'error');
            return;
        }

        showToast(`Capturing test image from ${cameraName}...`, 'info');

        const formData = new FormData();
        formData.append('action', 'capture_test_image');
        formData.append('camera_id', cameraId);

        fetch('pest_detection.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');

                    if (data.analysis) {
                        setTimeout(() => {
                            if (data.analysis.pests_detected) {
                                showToast(`AI Detection: ${data.analysis.pest_type} detected with ${data.analysis.confidence}% confidence (${data.analysis.severity} severity)`, 'warning');
                                setTimeout(() => {
                                    showToast(data.analysis.recommendation, 'info');
                                }, 2000);
                            } else {
                                showToast(`AI Analysis: ${data.analysis.recommendation} (${data.analysis.confidence}% confidence)`, 'success');
                            }
                        }, 1500);
                    }
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to capture test image', 'error');
            });
    }

    function viewLiveFeed(cameraId) {
        selectCamera(cameraId);
        toggleCameraSettings();
        setTimeout(() => {
            startLivePreview();
        }, 500);
    }

    // Live Feed Functionality
    let feedUptime = 0;
    let detectionCount = 3;
    let lastScanTime = 2;
    let detectionBoxesVisible = true;
    let activeCameraId = 1;

    // Camera data
    const cameras = {
        1: {
            name: 'Greenhouse A - North',
            location: 'Greenhouse A - North'
        },
        2: {
            name: 'Greenhouse A - South',
            location: 'Greenhouse A - South'
        },
        4: {
            name: 'Field A - Section 1',
            location: 'Field A - Section 1'
        },
        6: {
            name: 'Greenhouse A - Bed 1',
            location: 'Greenhouse A - Bed 1'
        }
    };

    // Update feed statistics
    function updateFeedStats() {
        feedUptime++;
        lastScanTime++;

        // Update uptime display
        const hours = Math.floor(feedUptime / 3600);
        const minutes = Math.floor((feedUptime % 3600) / 60);
        const seconds = feedUptime % 60;
        const uptimeStr = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        const uptimeElement = document.getElementById('feed-uptime');
        if (uptimeElement) {
            uptimeElement.textContent = uptimeStr;
        }

        // Update last scan time
        const lastScanElement = document.getElementById('last-scan-time');
        if (lastScanElement) {
            if (lastScanTime < 60) {
                lastScanElement.textContent = `${lastScanTime} seconds ago`;
            } else {
                const mins = Math.floor(lastScanTime / 60);
                lastScanElement.textContent = `${mins} minute${mins > 1 ? 's' : ''} ago`;
            }
        }

        // Simulate new scan every 10-15 seconds
        if (lastScanTime > Math.random() * 5 + 10) {
            simulateNewScan();
            lastScanTime = 0;
        }
    }

    // Simulate AI detection scan
    function simulateNewScan() {
        // 30% chance of detecting something
        if (Math.random() < 0.3) {
            const pests = ['Aphids', 'Spider Mites', 'Whiteflies', 'Thrips', 'Caterpillars'];
            const severities = ['Low', 'Medium', 'High'];
            const colors = ['green', 'yellow', 'red'];

            const pest = pests[Math.floor(Math.random() * pests.length)];
            const severityIndex = Math.floor(Math.random() * severities.length);
            const severity = severities[severityIndex];
            const color = colors[severityIndex];
            const confidence = (Math.random() * 20 + 80).toFixed(1); // 80-100%

            detectionCount++;
            const countElement = document.getElementById('detections-count');
            if (countElement) {
                countElement.textContent = detectionCount;
            }

            // Add detection box
            addDetectionBox(pest, confidence);

            // Add to recent detections
            addRecentDetection(pest, severity, color, confidence);

            // Show toast notification
            if (typeof showToast === 'function') {
                showToast(`${pest} detected with ${confidence}% confidence`, 'warning');
            }
        }
    }

    // Add detection box overlay
    function addDetectionBox(pest, confidence) {
        if (!detectionBoxesVisible) return;

        const container = document.getElementById('detection-boxes');
        if (!container) return;

        const box = document.createElement('div');

        // Random position
        const left = Math.random() * 70 + 10; // 10-80%
        const top = Math.random() * 60 + 20; // 20-80%

        box.className = 'absolute border-2 border-red-500 bg-red-500 bg-opacity-20 rounded';
        box.style.left = left + '%';
        box.style.top = top + '%';
        box.style.width = '80px';
        box.style.height = '60px';

        box.innerHTML = `
            <div class="absolute -top-8 left-0 bg-red-500 text-white px-2 py-1 rounded text-xs whitespace-nowrap">
                ${pest} (${confidence}%)
            </div>
        `;

        container.appendChild(box);

        // Remove after 5 seconds
        setTimeout(() => {
            if (container.contains(box)) {
                container.removeChild(box);
            }
        }, 5000);
    }

    // Add recent detection to history
    function addRecentDetection(pest, severity, color, confidence) {
        const container = document.getElementById('recent-detections');
        if (!container) return;

        const detection = document.createElement('div');

        detection.className = `flex items-start gap-3 p-3 bg-${color}-50 dark:bg-${color}-900/20 border border-${color}-200 dark:border-${color}-800 rounded-lg`;
        detection.innerHTML = `
            <div class="w-8 h-8 bg-${color}-100 dark:bg-${color}-900 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bug text-${color}-600 dark:text-${color}-400 text-sm"></i>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between mb-1">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">${pest} Detected</h4>
                    <span class="text-xs text-gray-500">Just now</span>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">${cameras[activeCameraId].location} | Confidence: ${confidence}%</p>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-1 bg-${color}-100 dark:bg-${color}-900 text-${color}-800 dark:text-${color}-200 text-xs font-medium rounded-full">${severity} Severity</span>
                    <button onclick="viewDetectionDetails(${Date.now()})" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 text-xs font-medium">View Details</button>
                </div>
            </div>
        `;

        // Add to top of list
        container.insertBefore(detection, container.firstChild);

        // Keep only last 5 detections
        while (container.children.length > 5) {
            container.removeChild(container.lastChild);
        }
    }

    // Live feed controls
    function stopLiveFeed() {
        const feedContainer = document.getElementById('camera-feed');
        if (!feedContainer) return;

        feedContainer.innerHTML = `
            <div class="text-center text-gray-400">
                <i class="fas fa-video-slash text-6xl mb-4"></i>
                <h4 class="text-xl font-semibold mb-2">Live Feed Stopped</h4>
                <p class="text-gray-500 mb-4">Camera feed has been disconnected</p>
                <button onclick="startLiveFeed()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-play mr-2"></i>Start Feed
                </button>
            </div>
        `;

        // Clear detection boxes
        const detectionBoxes = document.getElementById('detection-boxes');
        if (detectionBoxes) {
            detectionBoxes.innerHTML = '';
        }

        if (typeof showToast === 'function') {
            showToast('Live feed stopped', 'info');
        }
    }

    function startLiveFeed() {
        location.reload(); // Simple restart for demo
    }

    function captureSnapshot() {
        if (typeof showToast === 'function') {
            showToast('Snapshot captured and saved to gallery', 'success');
        }

        // Simulate flash effect
        const container = document.getElementById('live-feed-container');
        if (container) {
            container.style.filter = 'brightness(1.5)';
            setTimeout(() => {
                container.style.filter = 'brightness(1)';
            }, 200);
        }
    }

    function toggleDetectionBoxes() {
        detectionBoxesVisible = !detectionBoxesVisible;
        const container = document.getElementById('detection-boxes');

        if (container) {
            if (detectionBoxesVisible) {
                container.style.display = 'block';
                if (typeof showToast === 'function') {
                    showToast('Detection boxes enabled', 'info');
                }
            } else {
                container.style.display = 'none';
                if (typeof showToast === 'function') {
                    showToast('Detection boxes disabled', 'info');
                }
            }
        }
    }

    function fullscreenFeed() {
        const container = document.getElementById('live-feed-container');
        if (container) {
            if (container.requestFullscreen) {
                container.requestFullscreen();
            } else if (container.webkitRequestFullscreen) {
                container.webkitRequestFullscreen();
            } else if (container.msRequestFullscreen) {
                container.msRequestFullscreen();
            }
        }
    }

    function viewDetectionDetails(id) {
        if (typeof showToast === 'function') {
            showToast('Opening detection details...', 'info');
        }
        // In real implementation, this would open a detailed view
    }

    // Update live time
    function updateTimeAndDate() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', {
            hour12: false,
            hour: '2-digit',
            minute: '2-digit'
        });
        const dayString = now.toLocaleDateString('en-US', {
            weekday: 'short',
            day: 'numeric',
            month: 'short'
        });

        const timeElement = document.getElementById('live-time');
        const dateElement = document.getElementById('live-date');

        if (timeElement) {
            timeElement.style.transform = 'scale(1.1)';
            timeElement.textContent = timeString;
            setTimeout(() => {
                timeElement.style.transform = 'scale(1)';
            }, 200);
        }

        if (dateElement) {
            dateElement.textContent = dayString;
        }
    }

    // Handle camera settings form submission
    document.addEventListener('DOMContentLoaded', function() {
        const cameraConfigForm = document.getElementById('camera-config-form');
        if (cameraConfigForm) {
            cameraConfigForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                formData.append('action', 'update_camera_settings');

                const cameraName = formData.get('camera_name');
                const submitButton = this.querySelector('button[type="submit"]');
                const hideLoading = showLoading(submitButton);

                fetch('pest_detection.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        hideLoading();
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => {
                                closeCameraSettingsModal();
                                loadCameraGrid(); // Refresh camera grid
                            }, 1000);
                        } else {
                            showToast(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        console.error('Error:', error);
                        showToast('Failed to save camera settings', 'error');
                    });
            });
        }

        // Load camera grid on page load
        loadCameraGrid();

        // Handle camera type change to show/hide auth fields
        const cameraTypeSelect = document.getElementById('camera-type');
        if (cameraTypeSelect) {
            cameraTypeSelect.addEventListener('change', function() {
                const authSection = document.getElementById('camera-auth');
                if (this.value === 'ip_camera') {
                    authSection.classList.remove('hidden');
                } else {
                    authSection.classList.add('hidden');
                }
            });
        }

        // Initialize live feed functionality
        const cameraSelect = document.getElementById('active-camera-select');
        if (cameraSelect) {
            cameraSelect.addEventListener('change', function() {
                activeCameraId = parseInt(this.value);
                const camera = cameras[activeCameraId];

                // Update camera info in feed
                const feedContainer = document.getElementById('camera-feed');
                const cameraInfo = feedContainer.querySelector('.text-gray-300');
                if (cameraInfo) {
                    cameraInfo.textContent = camera.name;
                }

                if (typeof showToast === 'function') {
                    showToast(`Switched to ${camera.name}`, 'success');
                }

                // Reset detection boxes
                const detectionBoxes = document.getElementById('detection-boxes');
                if (detectionBoxes) {
                    detectionBoxes.innerHTML = '';
                }
            });
        }

        // Start live time updates
        updateTimeAndDate();
        setInterval(updateTimeAndDate, 1000);

        // Start feed statistics updates
        setInterval(updateFeedStats, 1000);

        // Initial detection simulation after 3 seconds
        setTimeout(() => {
            simulateNewScan();
        }, 3000);
    });
</script>

<?php include 'includes/footer.php'; ?>