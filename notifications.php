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
                $success = markNotificationAsRead($notificationId);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
            }
            exit;

        case 'mark_all_read':
            // In a real implementation, this would mark all notifications as read
            echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
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
    'warning' => count(array_filter($allNotifications, fn($n) => $n['type'] === 'warning')),
    'info' => count(array_filter($allNotifications, fn($n) => $n['type'] === 'info')),
    'success' => count(array_filter($allNotifications, fn($n) => $n['type'] === 'success'))
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

    <!-- Filter Tabs -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-6 overflow-x-auto">
                <?php
                $filterTabs = [
                    'all' => ['label' => 'All', 'icon' => 'fa-list'],
                    'unread' => ['label' => 'Unread', 'icon' => 'fa-circle'],
                    'critical' => ['label' => 'Critical', 'icon' => 'fa-exclamation-triangle'],
                    'warning' => ['label' => 'Warning', 'icon' => 'fa-exclamation-circle'],
                    'info' => ['label' => 'Info', 'icon' => 'fa-info-circle'],
                    'success' => ['label' => 'Success', 'icon' => 'fa-check-circle']
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
                    $config = NOTIFICATION_TYPES[$type];
                    $color = $config['color'];
                    $icon = $config['icon'];
                    $timeAgo = getTimeAgo($notification['timestamp']);
                    $readClass = $notification['read'] ? 'bg-gray-50 dark:bg-gray-700/50' : 'bg-white dark:bg-gray-800';
                ?>
                    <div class="notification-item <?php echo $readClass; ?> hover:bg-gray-50 dark:hover:bg-gray-700 p-4 cursor-pointer transition-colors duration-200"
                        data-notification-id="<?php echo $notification['id']; ?>"
                        onclick="toggleNotificationDetails(<?php echo $notification['id']; ?>)">
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
                                            <button onclick="markAsRead(<?php echo $notification['id']; ?>); event.stopPropagation();"
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
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
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
        fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_read&notification_id=${notificationId}`
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