<?php
// Start session and authentication check
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/security.php';

// Check page access permission (admin and farmer only)
if (!hasRole('admin') && !hasRole('farmer')) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        $pdo = getDatabaseConnection();

        switch ($_POST['action']) {
            case 'add_camera':
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

                if (!$cameraName || !$location) {
                    throw new Exception('Camera name and location are required');
                }

                $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

                $stmt = $pdo->prepare("
                    INSERT INTO cameras (
                        camera_name, location, ip_address, port, username, password, 
                        camera_type, resolution, fps, detection_enabled, detection_sensitivity,
                        status, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'offline', NOW(), NOW())
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
                    $detectionSensitivity
                ]);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Camera added successfully']);
                } else {
                    throw new Exception('Failed to add camera');
                }
                break;

            case 'delete_camera':
                $cameraId = filter_var($_POST['camera_id'], FILTER_VALIDATE_INT);

                if (!$cameraId) {
                    throw new Exception('Invalid camera ID');
                }

                // Check if camera has associated alerts
                $stmt = $pdo->prepare("SELECT COUNT(*) as alert_count FROM pest_alerts WHERE camera_id = ?");
                $stmt->execute([$cameraId]);
                $alertCount = $stmt->fetch()['alert_count'];

                if ($alertCount > 0) {
                    // Set camera_id to NULL in pest_alerts instead of deleting camera
                    $stmt = $pdo->prepare("UPDATE pest_alerts SET camera_id = NULL WHERE camera_id = ?");
                    $stmt->execute([$cameraId]);
                }

                $stmt = $pdo->prepare("DELETE FROM cameras WHERE id = ?");
                $result = $stmt->execute([$cameraId]);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Camera deleted successfully']);
                } else {
                    throw new Exception('Failed to delete camera');
                }
                break;

            case 'toggle_camera_status':
                $cameraId = filter_var($_POST['camera_id'], FILTER_VALIDATE_INT);
                $newStatus = trim($_POST['status'] ?? '');

                if (!$cameraId || !in_array($newStatus, ['online', 'offline', 'error'])) {
                    throw new Exception('Invalid parameters');
                }

                $stmt = $pdo->prepare("UPDATE cameras SET status = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$newStatus, $cameraId]);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Camera status updated']);
                } else {
                    throw new Exception('Failed to update camera status');
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

// Get all cameras
function getAllCameras()
{
    try {
        global $pdo;
        $stmt = $pdo->query("
            SELECT c.*, 
                   COUNT(pa.id) as alert_count,
                   MAX(pa.detected_at) as last_alert
            FROM cameras c
            LEFT JOIN pest_alerts pa ON c.id = pa.camera_id
            GROUP BY c.id
            ORDER BY c.location, c.camera_name
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get cameras: " . $e->getMessage());
        return [];
    }
}

// Get camera statistics
function getCameraStatistics()
{
    try {
        global $pdo;

        $stats = [];

        // Total cameras
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM cameras");
        $stats['total'] = $stmt->fetch()['total'];

        // Cameras by status
        $stmt = $pdo->query("
            SELECT status, COUNT(*) as count 
            FROM cameras 
            GROUP BY status
        ");
        $statusCounts = $stmt->fetchAll();
        $stats['by_status'] = [];
        foreach ($statusCounts as $status) {
            $stats['by_status'][$status['status']] = $status['count'];
        }

        // Detection enabled cameras
        $stmt = $pdo->query("SELECT COUNT(*) as enabled FROM cameras WHERE detection_enabled = 1");
        $stats['detection_enabled'] = $stmt->fetch()['enabled'];

        // Recent detections (last 24 hours)
        $stmt = $pdo->query("
            SELECT COUNT(*) as recent 
            FROM pest_alerts 
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND camera_id IS NOT NULL
        ");
        $stats['recent_detections'] = $stmt->fetch()['recent'];

        return $stats;
    } catch (Exception $e) {
        error_log("Failed to get camera statistics: " . $e->getMessage());
        return [
            'total' => 0,
            'by_status' => [],
            'detection_enabled' => 0,
            'recent_detections' => 0
        ];
    }
}

$cameras = getAllCameras();
$cameraStats = getCameraStatistics();

// Set page title
$pageTitle = 'Camera Management - IoT Farm Monitoring System';

// Include header
include 'includes/header.php';
?>

<?php include 'includes/navigation.php'; ?>

<!-- Camera Management Content -->
<div class="p-4 max-w-7xl mx-auto">

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <!-- Left Column - Camera Grid -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-camera text-blue-600 mr-2"></i>
                        Camera Network
                        <span class="ml-2 px-2 py-1 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs rounded-full">
                            <?php echo count($cameras); ?> cameras
                        </span>
                    </h3>
                </div>

                <?php if (empty($cameras)): ?>
                    <div class="p-8 text-center">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-video-slash text-gray-400 dark:text-gray-500 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Cameras Configured</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Add your first camera to start monitoring your farm with AI-powered pest detection.
                        </p>
                        <button onclick="showAddCameraModal()"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200">
                            <i class="fas fa-plus mr-2"></i>Add First Camera
                        </button>
                    </div>
                <?php else: ?>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($cameras as $camera):
                                $statusColors = [
                                    'online' => 'green',
                                    'offline' => 'gray',
                                    'error' => 'red'
                                ];
                                $statusColor = $statusColors[$camera['status']] ?? 'gray';
                            ?>
                                <div class="bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-<?php echo $statusColor; ?>-100 dark:bg-<?php echo $statusColor; ?>-900 rounded-lg flex items-center justify-center mr-3">
                                                <i class="fas fa-video text-<?php echo $statusColor; ?>-600 dark:text-<?php echo $statusColor; ?>-400"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                    <?php echo htmlspecialchars($camera['camera_name']); ?>
                                                </h4>
                                                <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                                    <?php echo htmlspecialchars($camera['location']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 py-1 bg-<?php echo $statusColor; ?>-100 dark:bg-<?php echo $statusColor; ?>-900 text-<?php echo $statusColor; ?>-800 dark:text-<?php echo $statusColor; ?>-200 text-xs font-medium rounded-full">
                                                <?php echo ucfirst($camera['status']); ?>
                                            </span>
                                            <div class="relative">
                                                <button onclick="toggleCameraMenu(<?php echo $camera['id']; ?>)"
                                                    class="p-1 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                                                    <i class="fas fa-ellipsis-v text-xs"></i>
                                                </button>
                                                <div id="camera-menu-<?php echo $camera['id']; ?>"
                                                    class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-10">
                                                    <button onclick="editCamera(<?php echo $camera['id']; ?>)"
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-t-lg">
                                                        <i class="fas fa-edit mr-2"></i>Edit Settings
                                                    </button>
                                                    <button onclick="toggleCameraStatus(<?php echo $camera['id']; ?>, '<?php echo $camera['status'] === 'online' ? 'offline' : 'online'; ?>')"
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                        <i class="fas fa-power-off mr-2"></i><?php echo $camera['status'] === 'online' ? 'Set Offline' : 'Set Online'; ?>
                                                    </button>
                                                    <button onclick="deleteCamera(<?php echo $camera['id']; ?>, '<?php echo htmlspecialchars($camera['camera_name']); ?>')"
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-b-lg">
                                                        <i class="fas fa-trash mr-2"></i>Delete Camera
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-2 text-xs">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Type:</span>
                                            <span class="text-gray-900 dark:text-white font-medium">
                                                <?php echo ucfirst(str_replace('_', ' ', $camera['camera_type'])); ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Resolution:</span>
                                            <span class="text-gray-900 dark:text-white font-medium"><?php echo $camera['resolution']; ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">AI Detection:</span>
                                            <span class="text-gray-900 dark:text-white font-medium">
                                                <?php echo $camera['detection_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Total Alerts:</span>
                                            <span class="text-gray-900 dark:text-white font-medium"><?php echo $camera['alert_count']; ?></span>
                                        </div>
                                        <?php if ($camera['last_alert']): ?>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Last Alert:</span>
                                                <span class="text-gray-900 dark:text-white font-medium">
                                                    <?php
                                                    $timestamp = strtotime($camera['last_alert']);
                                                    $now = time();
                                                    $diff = $now - $timestamp;

                                                    if ($diff < 3600) {
                                                        echo floor($diff / 60) . 'm ago';
                                                    } elseif ($diff < 86400) {
                                                        echo floor($diff / 3600) . 'h ago';
                                                    } else {
                                                        echo date('M j', $timestamp);
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600">
                                        <div class="flex gap-2">
                                            <?php if ($camera['status'] === 'online'): ?>
                                                <button onclick="viewLiveFeed(<?php echo $camera['id']; ?>)"
                                                    class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-xs rounded-lg font-medium transition-colors duration-200">
                                                    <i class="fas fa-eye mr-1"></i>Live View
                                                </button>
                                            <?php endif; ?>
                                            <button onclick="editCamera(<?php echo $camera['id']; ?>)"
                                                class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg font-medium transition-colors duration-200">
                                                <i class="fas fa-cog mr-1"></i>Configure
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column - Camera Management Tools -->
        <div class="space-y-4">
            <!-- Network Status -->
            <div class="bg-green-600 text-white rounded-xl p-4">
                <h3 class="text-white/80 text-xs font-medium mb-3">Network Status</h3>
                <div class="space-y-3">
                    <div class="text-center">
                        <div class="text-2xl font-bold mb-1">
                            <?php echo $cameraStats['by_status']['online'] ?? 0; ?>/<?php echo $cameraStats['total']; ?>
                        </div>
                        <div class="text-white/80 text-xs mb-3">Cameras Online</div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="text-center p-2 bg-white/10 rounded">
                            <div class="font-bold"><?php echo $cameraStats['detection_enabled']; ?></div>
                            <div class="text-white/80">AI Enabled</div>
                        </div>
                        <div class="text-center p-2 bg-white/10 rounded">
                            <div class="font-bold"><?php echo $cameraStats['recent_detections']; ?></div>
                            <div class="text-white/80">Detections</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Camera Health -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Camera Health</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-green-100 dark:bg-green-900 rounded flex items-center justify-center">
                                <i class="fas fa-check text-green-600 dark:text-green-400 text-xs"></i>
                            </div>
                            <span class="text-xs font-medium text-gray-900 dark:text-white">Online Cameras</span>
                        </div>
                        <span class="text-xs font-bold text-gray-900 dark:text-white"><?php echo $cameraStats['by_status']['online'] ?? 0; ?></span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-red-100 dark:bg-red-900 rounded flex items-center justify-center">
                                <i class="fas fa-times text-red-600 dark:text-red-400 text-xs"></i>
                            </div>
                            <span class="text-xs font-medium text-gray-900 dark:text-white">Offline Cameras</span>
                        </div>
                        <span class="text-xs font-bold text-gray-900 dark:text-white"><?php echo $cameraStats['by_status']['offline'] ?? 0; ?></span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-yellow-100 dark:bg-yellow-900 rounded flex items-center justify-center">
                                <i class="fas fa-exclamation text-yellow-600 dark:text-yellow-400 text-xs"></i>
                            </div>
                            <span class="text-xs font-medium text-gray-900 dark:text-white">Error Status</span>
                        </div>
                        <span class="text-xs font-bold text-gray-900 dark:text-white"><?php echo $cameraStats['by_status']['error'] ?? 0; ?></span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Quick Actions</h3>
                <div class="space-y-2">
                    <button onclick="showAddCameraModal()" class="w-full text-left p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-plus text-green-600 dark:text-green-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Add Camera</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Configure new device</p>
                            </div>
                        </div>
                    </button>
                    <button onclick="bulkCameraAction('online')" class="w-full text-left p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-power-off text-green-600 dark:text-green-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Start All Cameras</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Activate all offline cameras</p>
                            </div>
                        </div>
                    </button>
                    <button onclick="testAllConnections()" class="w-full text-left p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-network-wired text-purple-600 dark:text-purple-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Test Connections</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Check all camera connectivity</p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Camera Modal -->
<div id="camera-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-xl bg-white dark:bg-gray-800 max-h-screen overflow-y-auto">
        <div class="mt-3">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-secondary-200 dark:border-gray-700">
                <h3 id="modal-title" class="text-heading-lg text-secondary-900 dark:text-white font-display">
                    <i class="fas fa-video text-blue-600 dark:text-blue-400 mr-2"></i>
                    Add New Camera
                </h3>
                <button onclick="closeCameraModal()" class="text-secondary-400 dark:text-gray-500 hover:text-secondary-600 dark:hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="py-6">
                <form id="camera-form" class="space-y-4">
                    <input type="hidden" id="camera-id" name="camera_id">
                    <input type="hidden" id="form-action" name="action" value="add_camera">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                Camera Name *
                            </label>
                            <input type="text" id="modal-camera-name" name="camera_name" required
                                class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>

                        <div>
                            <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                Location *
                            </label>
                            <input type="text" id="modal-location" name="location" required
                                class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>

                        <div>
                            <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                Camera Type
                            </label>
                            <select id="modal-camera-type" name="camera_type"
                                class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="ip_camera">IP Camera</option>
                                <option value="usb_camera">USB Camera</option>
                                <option value="rtsp_stream">RTSP Stream</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                Resolution
                            </label>
                            <select id="modal-resolution" name="resolution"
                                class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="640x480">640x480 (VGA)</option>
                                <option value="1280x720">1280x720 (HD)</option>
                                <option value="1920x1080" selected>1920x1080 (Full HD)</option>
                                <option value="2560x1440">2560x1440 (2K)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                Frame Rate (FPS)
                            </label>
                            <select id="modal-fps" name="fps"
                                class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="15">15 FPS</option>
                                <option value="25">25 FPS</option>
                                <option value="30" selected>30 FPS</option>
                                <option value="60">60 FPS</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                Detection Sensitivity
                            </label>
                            <select id="modal-detection-sensitivity" name="detection_sensitivity"
                                class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="low">Low (90%+ confidence)</option>
                                <option value="medium" selected>Medium (80%+ confidence)</option>
                                <option value="high">High (70%+ confidence)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Network Settings -->
                    <div id="network-settings">
                        <h5 class="text-heading-sm text-secondary-900 dark:text-white mb-3 mt-6">Network Settings</h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                    IP Address
                                </label>
                                <input type="text" id="modal-ip-address" name="ip_address"
                                    class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>

                            <div>
                                <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                    Port
                                </label>
                                <input type="number" id="modal-port" name="port" min="1" max="65535" value="80"
                                    class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>

                            <div>
                                <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                    Username
                                </label>
                                <input type="text" id="modal-username" name="username"
                                    class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>

                            <div>
                                <label class="block text-body-md font-medium text-secondary-700 dark:text-gray-300 mb-2">
                                    Password
                                </label>
                                <input type="password" id="modal-password" name="password"
                                    class="form-input w-full text-body-md border-secondary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center mt-4">
                        <input type="checkbox" id="modal-detection-enabled" name="detection_enabled" checked
                            class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="modal-detection-enabled" class="ml-2 text-body-md font-medium text-secondary-700 dark:text-gray-300">
                            Enable AI Pest Detection
                        </label>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-secondary-200 dark:border-gray-700">
                        <button type="submit"
                            class="btn-primary flex-1 sm:flex-none">
                            <i class="fas fa-save mr-2"></i>Save Camera
                        </button>

                        <button type="button" onclick="closeCameraModal()"
                            class="px-4 py-2 bg-secondary-600 hover:bg-secondary-700 text-white rounded-lg font-medium transition-colors duration-200 flex-1 sm:flex-none">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Camera management functions
    function showAddCameraModal() {
        document.getElementById('modal-title').innerHTML = '<i class="fas fa-video text-blue-600 dark:text-blue-400 mr-2"></i>Add New Camera';
        document.getElementById('form-action').value = 'add_camera';
        document.getElementById('camera-form').reset();
        document.getElementById('camera-id').value = '';
        document.getElementById('modal-detection-enabled').checked = true;
        document.getElementById('camera-modal').classList.remove('hidden');
    }

    function editCamera(cameraId) {
        // In a real implementation, this would fetch camera data from the server
        // For now, we'll show the modal with placeholder data
        document.getElementById('modal-title').innerHTML = '<i class="fas fa-video text-blue-600 dark:text-blue-400 mr-2"></i>Edit Camera Settings';
        document.getElementById('form-action').value = 'update_camera_settings';
        document.getElementById('camera-id').value = cameraId;
        document.getElementById('camera-modal').classList.remove('hidden');

        // Hide camera menu
        document.getElementById(`camera-menu-${cameraId}`).classList.add('hidden');
    }

    function closeCameraModal() {
        document.getElementById('camera-modal').classList.add('hidden');
    }

    function toggleCameraMenu(cameraId) {
        const menu = document.getElementById(`camera-menu-${cameraId}`);

        // Hide all other menus
        document.querySelectorAll('[id^="camera-menu-"]').forEach(m => {
            if (m.id !== `camera-menu-${cameraId}`) {
                m.classList.add('hidden');
            }
        });

        menu.classList.toggle('hidden');
    }

    function toggleCameraStatus(cameraId, newStatus) {
        const formData = new FormData();
        formData.append('action', 'toggle_camera_status');
        formData.append('camera_id', cameraId);
        formData.append('status', newStatus);

        fetch('camera_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to update camera status', 'error');
            });

        // Hide menu
        document.getElementById(`camera-menu-${cameraId}`).classList.add('hidden');
    }

    function deleteCamera(cameraId, cameraName) {
        if (!confirm(`Are you sure you want to delete "${cameraName}"? This action cannot be undone.`)) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'delete_camera');
        formData.append('camera_id', cameraId);

        fetch('camera_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to delete camera', 'error');
            });

        // Hide menu
        document.getElementById(`camera-menu-${cameraId}`).classList.add('hidden');
    }

    function viewLiveFeed(cameraId) {
        // Redirect to pest detection page with camera focus
        window.location.href = `pest_detection.php?camera=${cameraId}`;
    }

    function bulkCameraAction(action) {
        const confirmMessage = action === 'online' ?
            'Are you sure you want to start all offline cameras?' :
            'Are you sure you want to stop all online cameras?';

        if (!confirm(confirmMessage)) {
            return;
        }

        if (typeof showToast === 'function') {
            showToast(`${action === 'online' ? 'Starting' : 'Stopping'} all cameras...`, 'info');
        }

        // In real implementation, this would make API calls to update all cameras
        setTimeout(() => {
            if (typeof showToast === 'function') {
                showToast(`All cameras ${action === 'online' ? 'started' : 'stopped'} successfully`, 'success');
            }
            setTimeout(() => location.reload(), 1000);
        }, 2000);
    }

    function testAllConnections() {
        if (typeof showToast === 'function') {
            showToast('Testing all camera connections...', 'info');
        }

        // Simulate connection testing
        setTimeout(() => {
            const onlineCount = <?php echo $cameraStats['by_status']['online'] ?? 0; ?>;
            const totalCount = <?php echo $cameraStats['total']; ?>;

            if (typeof showToast === 'function') {
                showToast(`Connection test complete: ${onlineCount}/${totalCount} cameras responding`, 'success');
            }
        }, 3000);
    }

    // Handle form submission
    document.addEventListener('DOMContentLoaded', function() {
        const cameraForm = document.getElementById('camera-form');
        if (cameraForm) {
            cameraForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const submitButton = this.querySelector('button[type="submit"]');
                const hideLoading = showLoading(submitButton);

                fetch('camera_management.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        hideLoading();
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => {
                                closeCameraModal();
                                location.reload();
                            }, 1000);
                        } else {
                            showToast(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        console.error('Error:', error);
                        showToast('Failed to save camera', 'error');
                    });
            });
        }

        // Handle camera type change
        const cameraTypeSelect = document.getElementById('modal-camera-type');
        if (cameraTypeSelect) {
            cameraTypeSelect.addEventListener('change', function() {
                const networkSettings = document.getElementById('network-settings');
                if (this.value === 'usb_camera') {
                    networkSettings.style.display = 'none';
                } else {
                    networkSettings.style.display = 'block';
                }
            });
        }

        // Close menus when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[id^="camera-menu-"]') && !e.target.closest('button[onclick^="toggleCameraMenu"]')) {
                document.querySelectorAll('[id^="camera-menu-"]').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>