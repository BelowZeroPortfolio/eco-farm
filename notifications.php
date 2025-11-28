<?php

/**
 * Notifications Page for IoT Farm Monitoring System
 * 
 * Displays all notifications with filtering and management options
 */

require_once 'config/database.php';
// Start session
session_start();

// Simple authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/notifications.php';

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'mark_read':
            $notificationId = $_POST['notification_id'] ?? null;
            if ($notificationId) {
                // Check if it's a pest alert
                if (strpos($notificationId, 'pest_') === 0) {
                    $alertId = str_replace('pest_', '', $notificationId);
                    try {
                        $pdo = getDatabaseConnection();
                        $stmt = $pdo->prepare("
                            UPDATE pest_alerts 
                            SET is_read = TRUE, read_at = NOW(), read_by = ? 
                            WHERE id = ?
                        ");
                        $success = $stmt->execute([$currentUser['id'], $alertId]);
                        echo json_encode(['success' => $success]);
                    } catch (Exception $e) {
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    }
                }
                // Check if it's a plant alert
                elseif (strpos($notificationId, 'plant_') === 0) {
                    $alertId = str_replace('plant_', '', $notificationId);
                    try {
                        $pdo = getDatabaseConnection();
                        $stmt = $pdo->prepare("
                            UPDATE Notifications 
                            SET IsRead = 1, ReadAt = NOW() 
                            WHERE NotificationID = ?
                        ");
                        $success = $stmt->execute([$alertId]);
                        echo json_encode(['success' => $success]);
                    } catch (Exception $e) {
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    }
                } else {
                    $success = markNotificationAsRead($notificationId);
                    echo json_encode(['success' => $success]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
            }
            exit;

        case 'mark_all_read':
            try {
                $pdo = getDatabaseConnection();
                
                // Mark all pest alerts as read
                $stmt = $pdo->prepare("
                    UPDATE pest_alerts 
                    SET is_read = TRUE, read_at = NOW(), read_by = ? 
                    WHERE is_read = FALSE
                ");
                $stmt->execute([$currentUser['id']]);
                $pestCount = $stmt->rowCount();
                
                // Mark all plant alerts as read
                $stmt = $pdo->prepare("
                    UPDATE Notifications 
                    SET IsRead = 1, ReadAt = NOW() 
                    WHERE IsRead = 0
                ");
                $stmt->execute();
                $plantCount = $stmt->rowCount();
                
                $totalCount = $pestCount + $plantCount;

                echo json_encode(['success' => true, 'message' => "{$totalCount} notifications marked as read"]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        case 'delete_all':
            try {
                $pdo = getDatabaseConnection();
                
                // Delete all pest alerts
                $stmt = $pdo->prepare("DELETE FROM pest_alerts");
                $stmt->execute();
                $pestCount = $stmt->rowCount();
                
                // Delete all plant alerts
                $stmt = $pdo->prepare("DELETE FROM Notifications");
                $stmt->execute();
                $plantCount = $stmt->rowCount();
                
                $totalCount = $pestCount + $plantCount;

                echo json_encode(['success' => true, 'message' => "{$totalCount} notifications deleted"]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
}

// Get filter parameters
$filter = $_GET['filter'] ?? 'all'; // all, unread, critical, warning, info, success
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Get notifications based on filter
$allNotifications = getNotifications();

// Apply filters
$filteredNotifications = array_filter($allNotifications, function ($notification) use ($filter) {
    switch ($filter) {
        case 'unread':
            return !$notification['read'];
        case 'critical':
        case 'high':
        case 'medium':
        case 'low':
        case 'warning':
        case 'info':
        case 'success':
            return $notification['type'] === $filter;
        default:
            return true;
    }
});

// Pagination
$totalNotifications = count($filteredNotifications);
$totalPages = ceil($totalNotifications / $perPage);
$offset = ($page - 1) * $perPage;
$notifications = array_slice($filteredNotifications, $offset, $perPage);

// Get notification counts for filter badges
$notificationCounts = [
    'all' => count($allNotifications),
    'unread' => count(array_filter($allNotifications, fn($n) => !$n['read'])),
    'critical' => count(array_filter($allNotifications, fn($n) => $n['type'] === 'critical')),
    'high' => count(array_filter($allNotifications, fn($n) => $n['type'] === 'high')),
    'medium' => count(array_filter($allNotifications, fn($n) => $n['type'] === 'medium')),
    'low' => count(array_filter($allNotifications, fn($n) => $n['type'] === 'low'))
];

// Set page title for header component
$pageTitle = 'Notifications - IoT Farm Monitoring System';

// Include shared header
include 'includes/header.php';
?>
<?php
// Include shared navigation component (sidebar)
include 'includes/navigation.php';
?>

<!-- Notifications Content -->
<div class="p-4 max-w-7xl mx-auto">

    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 flex items-center">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-xl flex items-center justify-center mr-3">
                        <i class="fas fa-bell text-blue-600 dark:text-blue-400 text-xl"></i>
                    </div>
                    Notifications
                </h1>
                <p class="text-gray-600 dark:text-gray-400 ml-15">
                    Stay updated with system alerts and pest detections
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="refreshNotifications()" class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-all duration-200 flex items-center shadow-sm hover:shadow">
                    <i class="fas fa-sync-alt mr-2"></i>
                    <span class="hidden sm:inline">Refresh</span>
                </button>
                <?php if ($notificationCounts['all'] > 0): ?>
                <button onclick="deleteAllNotifications()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-all duration-200 flex items-center shadow-sm hover:shadow">
                    <i class="fas fa-trash mr-2"></i>
                    <span class="hidden sm:inline">Delete All</span>
                </button>
                <?php endif; ?>
                <?php if ($notificationCounts['unread'] > 0): ?>
                <button onclick="markAllAsRead()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-all duration-200 flex items-center shadow-sm hover:shadow">
                    <i class="fas fa-check-double mr-2"></i>
                    <span class="hidden sm:inline">Mark All Read</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <?php if ($notificationCounts['unread'] > 0 || $notificationCounts['critical'] > 0): ?>
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <?php if ($notificationCounts['unread'] > 0): ?>
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-envelope text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-blue-900 dark:text-blue-100"><?php echo $notificationCounts['unread']; ?> Unread</p>
                        <p class="text-xs text-blue-700 dark:text-blue-300">Requires attention</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($notificationCounts['critical'] > 0): ?>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-900 dark:text-red-100"><?php echo $notificationCounts['critical']; ?> Critical</p>
                        <p class="text-xs text-red-700 dark:text-red-300">Immediate action needed</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($notificationCounts['high'] > 0): ?>
            <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-3">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-orange-600 dark:text-orange-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-orange-900 dark:text-orange-100"><?php echo $notificationCounts['high']; ?> High Priority</p>
                        <p class="text-xs text-orange-700 dark:text-orange-300">Review soon</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-6 overflow-x-auto">
                <?php
                $filterTabs = [
                    'all' => ['label' => 'All', 'icon' => 'fa-list'],
                    'unread' => ['label' => 'Unread', 'icon' => 'fa-circle'],
                    'critical' => ['label' => 'Critical', 'icon' => 'fa-exclamation-triangle'],
                    'high' => ['label' => 'High', 'icon' => 'fa-exclamation-circle'],
                    'medium' => ['label' => 'Medium', 'icon' => 'fa-info-circle'],
                    'low' => ['label' => 'Low', 'icon' => 'fa-check-circle']
                ];

                foreach ($filterTabs as $key => $tab):
                    $isActive = $filter === $key;
                    $count = $notificationCounts[$key];
                    $activeClasses = $isActive
                        ? 'border-green-500 text-green-600 dark:text-green-400'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600';
                ?>
                    <a href="?filter=<?php echo $key; ?>"
                        class="<?php echo $activeClasses; ?> whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center transition-colors duration-200">
                        <i class="fas <?php echo $tab['icon']; ?> mr-2"></i>
                        <?php echo $tab['label']; ?>
                        <?php if ($count > 0): ?>
                            <span class="ml-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 py-0.5 px-2 rounded-full text-xs font-medium">
                                <?php echo $count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-12">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-bell-slash text-gray-400 dark:text-gray-500 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No notifications found</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <?php if ($filter === 'unread'): ?>
                        You're all caught up! No unread notifications.
                    <?php else: ?>
                        No notifications match the current filter.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($notifications as $notification):
                    $type = $notification['type'];
                    // Get config with fallback for unknown types
                    $config = NOTIFICATION_TYPES[$type] ?? NOTIFICATION_TYPES['info'];
                    $color = $config['color'];
                    $icon = $config['icon'];
                    $timeAgo = getTimeAgo($notification['timestamp']);
                    $readClass = $notification['read'] ? 'bg-gray-50 dark:bg-gray-700/50' : 'bg-white dark:bg-gray-800';
                ?>
                    <div class="notification-item <?php echo $readClass; ?> hover:bg-gray-50 dark:hover:bg-gray-700 p-4 cursor-pointer transition-colors duration-200"
                        data-notification-id="<?php echo htmlspecialchars($notification['id'], ENT_QUOTES); ?>"
                        onclick="toggleNotificationDetails('<?php echo htmlspecialchars($notification['id'], ENT_QUOTES); ?>')">
                        <div class="flex items-start space-x-3">
                            <!-- Icon -->
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-<?php echo $color; ?>-100 dark:bg-<?php echo $color; ?>-900 rounded-lg flex items-center justify-center">
                                    <i class="fas <?php echo $icon; ?> text-<?php echo $color; ?>-600 dark:text-<?php echo $color; ?>-400 text-sm"></i>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>
                                        <div class="flex items-center space-x-3 text-xs text-gray-500 dark:text-gray-400">
                                            <span class="flex items-center">
                                                <i class="fas fa-clock mr-1"></i>
                                                <?php echo $timeAgo; ?>
                                            </span>
                                            <span class="px-2 py-1 bg-<?php echo $color; ?>-100 dark:bg-<?php echo $color; ?>-900 text-<?php echo $color; ?>-800 dark:text-<?php echo $color; ?>-200 rounded-full text-xs font-medium">
                                                <?php echo ucfirst($type); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center space-x-2 ml-4">
                                        <?php if (!$notification['read']): ?>
                                            <button onclick="markAsRead('<?php echo htmlspecialchars($notification['id'], ENT_QUOTES); ?>'); event.stopPropagation();"
                                                class="p-1 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 rounded transition-colors duration-200"
                                                title="Mark as read">
                                                <i class="fas fa-check text-xs"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($notification['action_url']): ?>
                                            <a href="<?php echo htmlspecialchars($notification['action_url']); ?>"
                                                onclick="event.stopPropagation();"
                                                class="p-1 text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 rounded transition-colors duration-200"
                                                title="<?php echo htmlspecialchars($notification['action_text'] ?? 'View details'); ?>">
                                                <i class="fas fa-external-link-alt text-xs"></i>
                                            </a>
                                        <?php endif; ?>

                                        <div class="w-2 h-2 <?php echo $notification['read'] ? 'bg-transparent' : 'bg-' . $color . '-500'; ?> rounded-full"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Expandable Details (Hidden by default) -->
                        <div id="details-<?php echo $notification['id']; ?>" class="hidden mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Notification Details</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs mb-4">
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Type:</span>
                                        <span class="text-gray-600 dark:text-gray-400 ml-2"><?php echo ucfirst($type); ?></span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Status:</span>
                                        <span class="text-gray-600 dark:text-gray-400 ml-2"><?php echo $notification['read'] ? 'Read' : 'Unread'; ?></span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Timestamp:</span>
                                        <span class="text-gray-600 dark:text-gray-400 ml-2"><?php echo date('M j, Y g:i A', strtotime($notification['timestamp'])); ?></span>
                                    </div>
                                    <?php if ($notification['action_url']): ?>
                                        <div>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Action:</span>
                                            <a href="<?php echo htmlspecialchars($notification['action_url']); ?>"
                                                class="text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 ml-2">
                                                <?php echo htmlspecialchars($notification['action_text'] ?? 'View details'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (isset($notification['suggested_action']) && !empty($notification['suggested_action'])): ?>
                                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                        <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center">
                                            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                                            Suggested Action
                                        </h5>
                                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                            <p class="text-sm text-blue-900 dark:text-blue-100">
                                                <?php echo htmlspecialchars($notification['suggested_action']); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between mt-4 px-4 py-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalNotifications); ?> of <?php echo $totalNotifications; ?> notifications
                </div>

                <div class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>"
                            class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>"
                            class="px-3 py-2 <?php echo $i === $page ? 'bg-green-600 text-white' : 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?> rounded-lg transition-colors duration-200">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>"
                            class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
    /**
     * Notification management functions
     */

    function markAsRead(notificationId) {
        console.log('Marking notification as read:', notificationId);

        fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_read&notification_id=${encodeURIComponent(notificationId)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    if (item) {
                        item.classList.remove('bg-white');
                        item.classList.add('bg-gray-50');

                        // Remove unread indicator
                        const indicator = item.querySelector('.w-2.h-2:not(.bg-transparent)');
                        if (indicator) {
                            indicator.className = 'w-2 h-2 bg-transparent rounded-full';
                        }

                        // Remove mark as read button
                        const markReadBtn = item.querySelector('button[onclick*="markAsRead"]');
                        if (markReadBtn) {
                            markReadBtn.remove();
                        }
                    }

                    showToast('Notification marked as read', 'success');
                } else {
                    showToast(data.message || 'Failed to mark notification as read', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Network error occurred', 'error');
            });
    }

    function markAllAsRead() {
        if (!confirm('Mark all notifications as read?')) {
            return;
        }

        fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_all_read'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast(data.message || 'Failed to mark all notifications as read', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Network error occurred', 'error');
            });
    }

    function deleteAllNotifications() {
        if (!confirm('⚠️ Delete ALL notifications permanently?\n\nThis action cannot be undone!\n\nThis will delete:\n• All pest alerts\n• All plant threshold alerts\n• All system notifications')) {
            return;
        }

        // Second confirmation for safety
        if (!confirm('Are you absolutely sure? This will permanently delete all notification data from the database.')) {
            return;
        }

        fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=delete_all'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast(data.message || 'Failed to delete notifications', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Network error occurred', 'error');
            });
    }

    function refreshNotifications() {
        showToast('Refreshing notifications...', 'info');
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }

    function toggleNotificationDetails(notificationId) {
        const details = document.getElementById(`details-${notificationId}`);
        if (details) {
            details.classList.toggle('hidden');
        }
    }
</script>

<?php
// Include shared footer
include 'includes/footer.php';
?>