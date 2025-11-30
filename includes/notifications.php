<?php

/**
 * Notification System for IoT Farm Monitoring System
 * 
 * Handles web-based notifications, toast messages, and alert management
 */

/**
 * Notification types and their priorities
 */
define('NOTIFICATION_TYPES', [
    'critical' => ['priority' => 1, 'color' => 'red', 'icon' => 'fa-exclamation-triangle'],
    'high' => ['priority' => 2, 'color' => 'orange', 'icon' => 'fa-exclamation-circle'],
    'warning' => ['priority' => 2, 'color' => 'yellow', 'icon' => 'fa-exclamation-circle'],
    'medium' => ['priority' => 3, 'color' => 'yellow', 'icon' => 'fa-info-circle'],
    'low' => ['priority' => 4, 'color' => 'blue', 'icon' => 'fa-check-circle'],
    'info' => ['priority' => 3, 'color' => 'blue', 'icon' => 'fa-info-circle'],
    'success' => ['priority' => 5, 'color' => 'green', 'icon' => 'fa-check-circle']
]);

/**
 * Get pest alerts from database
 */
function getPestAlertNotifications($limit = null)
{
    try {
        // Get the correct path to database config
        $configPath = __DIR__ . '/../config/database.php';
        
        // Check if database config exists
        if (!file_exists($configPath)) {
            error_log("Database config not found at: " . $configPath);
            return [];
        }
        
        require_once $configPath;
        
        // Check if function exists
        if (!function_exists('getDatabaseConnection')) {
            error_log("getDatabaseConnection function not found");
            return [];
        }
        
        $pdo = getDatabaseConnection();
        
        // Check if connection is valid
        if (!$pdo) {
            error_log("Database connection is null");
            return [];
        }

        $query = "
            SELECT 
                id,
                pest_type,
                location,
                severity,
                confidence_score,
                description,
                detected_at,
                is_read,
                read_at
            FROM pest_alerts 
            ORDER BY detected_at DESC
        ";

        if ($limit) {
            $query .= " LIMIT " . intval($limit);
        }

        $stmt = $pdo->query($query);
        
        if (!$stmt) {
            error_log("Failed to execute pest_alerts query");
            return [];
        }
        
        $pestAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($pestAlerts) . " pest alerts in database");

        // Convert pest alerts to notification format
        $notifications = [];
        foreach ($pestAlerts as $alert) {
            $notifications[] = [
                'id' => 'pest_' . $alert['id'],
                'type' => $alert['severity'], // critical, high, medium, low
                'title' => ucfirst($alert['pest_type']) . ' Detected',
                'message' => $alert['description'] ?? "Detected at {$alert['location']} with {$alert['confidence_score']}% confidence",
                'timestamp' => $alert['detected_at'],
                'read' => (bool)$alert['is_read'],
                'action_url' => 'pest_detection.php?alert_id=' . $alert['id'],
                'action_text' => 'View Details'
            ];
        }

        return $notifications;
    } catch (PDOException $e) {
        error_log("Database error fetching pest alerts: " . $e->getMessage());
        return [];
    } catch (Exception $e) {
        error_log("Error fetching pest alerts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get plant threshold alert notifications from database
 */
function getPlantAlertNotifications($limit = null)
{
    try {
        $configPath = __DIR__ . '/../config/database.php';
        
        if (!file_exists($configPath)) {
            error_log("Database config not found at: " . $configPath);
            return [];
        }
        
        require_once $configPath;
        
        if (!function_exists('getDatabaseConnection')) {
            error_log("getDatabaseConnection function not found");
            return [];
        }
        
        $pdo = getDatabaseConnection();
        
        if (!$pdo) {
            error_log("Database connection is null");
            return [];
        }

        $query = "
            SELECT 
                n.NotificationID,
                n.PlantID,
                n.Message,
                n.SensorType,
                n.Level,
                n.SuggestedAction,
                n.CurrentValue,
                n.RequiredRange,
                n.Status,
                n.IsRead,
                n.ReadAt,
                n.CreatedAt,
                p.PlantName,
                p.LocalName
            FROM notifications n
            INNER JOIN plants p ON n.PlantID = p.PlantID
            ORDER BY n.CreatedAt DESC
        ";

        if ($limit) {
            $query .= " LIMIT " . intval($limit);
        }

        $stmt = $pdo->query($query);
        
        if (!$stmt) {
            error_log("Failed to execute plant notifications query");
            return [];
        }
        
        $plantAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($plantAlerts) . " plant alerts in database");

        // Convert plant alerts to notification format
        $notifications = [];
        foreach ($plantAlerts as $alert) {
            // Determine severity based on sensor type and status
            $severity = 'high'; // Default to high for plant alerts
            if ($alert['Level'] >= 5) {
                $severity = 'critical';
            } elseif ($alert['Level'] >= 3) {
                $severity = 'high';
            } else {
                $severity = 'medium';
            }
            
            $sensorName = ucwords(str_replace('_', ' ', $alert['SensorType']));
            
            $notifications[] = [
                'id' => 'plant_' . $alert['NotificationID'],
                'type' => $severity,
                'title' => "🌱 {$alert['PlantName']} Alert",
                'message' => "{$sensorName}: {$alert['Status']} - Current: {$alert['CurrentValue']}, Required: {$alert['RequiredRange']}",
                'timestamp' => $alert['CreatedAt'],
                'read' => (bool)$alert['IsRead'],
                'action_url' => 'notifications.php?notification_id=' . $alert['NotificationID'],
                'action_text' => 'View Action',
                'suggested_action' => $alert['SuggestedAction']
            ];
        }

        return $notifications;
    } catch (PDOException $e) {
        error_log("Database error fetching plant alerts: " . $e->getMessage());
        return [];
    } catch (Exception $e) {
        error_log("Error fetching plant alerts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get sample notifications for demonstration
 */
function getSampleNotifications()
{
    return [
        [
            'id' => 'sample_1',
            'type' => 'warning',
            'title' => 'Sensor Offline',
            'message' => 'Temperature sensor in Field B has been offline for 2 hours.',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'read' => false,
            'action_url' => 'sensors.php',
            'action_text' => 'Check Sensors'
        ],
        [
            'id' => 'sample_2',
            'type' => 'info',
            'title' => 'Daily Report Ready',
            'message' => 'Your daily farm monitoring report is now available for download.',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-4 hours')),
            'read' => true,
            'action_url' => 'reports.php',
            'action_text' => 'View Report'
        ],
        [
            'id' => 'sample_3',
            'type' => 'success',
            'title' => 'System Update Complete',
            'message' => 'All sensors have been successfully updated to the latest firmware.',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'read' => true,
            'action_url' => null,
            'action_text' => null
        ]
    ];
}

/**
 * Get notifications with priority sorting (includes pest alerts and plant alerts)
 */
function getNotifications($limit = null, $unreadOnly = false)
{
    // Merge pest alerts, plant alerts, and sample notifications
    $pestAlerts = getPestAlertNotifications(20); // Get recent pest alerts
    $plantAlerts = getPlantAlertNotifications(20); // Get recent plant alerts
    $sampleNotifications = getSampleNotifications();
    $notifications = array_merge($pestAlerts, $plantAlerts, $sampleNotifications);

    // Filter unread only if requested
    if ($unreadOnly) {
        $notifications = array_filter($notifications, function ($notification) {
            return !$notification['read'];
        });
    }

    // Map severity levels to notification types for sorting
    $severityToPriority = [
        'critical' => 1,
        'high' => 2,
        'medium' => 3,
        'low' => 4,
        'warning' => 2,
        'info' => 3,
        'success' => 4
    ];

    // Sort by priority (critical first) then by timestamp (newest first)
    usort($notifications, function ($a, $b) use ($severityToPriority) {
        $priorityA = $severityToPriority[$a['type']] ?? 5;
        $priorityB = $severityToPriority[$b['type']] ?? 5;

        if ($priorityA === $priorityB) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        }

        return $priorityA - $priorityB;
    });

    // Apply limit if specified
    if ($limit !== null) {
        $notifications = array_slice($notifications, 0, $limit);
    }

    return $notifications;
}

/**
 * Get unread notification count (includes pest alerts)
 */
function getUnreadNotificationCount()
{
    $notifications = getNotifications(null, true); // Get all unread
    return count($notifications);
}

/**
 * Get notification by ID
 */
function getNotificationById($id)
{
    $notifications = getSampleNotifications();
    foreach ($notifications as $notification) {
        if ($notification['id'] == $id) {
            return $notification;
        }
    }
    return null;
}

/**
 * Mark notification as read (static implementation)
 */
function markNotificationAsRead($id)
{
    // In a real implementation, this would update the database
    // For now, we'll just return success for demonstration
    // The $id parameter would be used to identify which notification to mark as read
    return true;
}

/**
 * Generate toast notification HTML
 */
function generateToastNotification($notification)
{
    $type = $notification['type'];
    $config = NOTIFICATION_TYPES[$type];
    $color = $config['color'];
    $icon = $config['icon'];

    $timeAgo = getTimeAgo($notification['timestamp']);

    return "
    <div class='toast-notification fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-lg shadow-lg border-l-4 border-{$color}-500 transform translate-x-full transition-transform duration-300 ease-in-out' 
         data-notification-id='{$notification['id']}' data-type='{$type}'>
        <div class='p-4'>
            <div class='flex items-start'>
                <div class='flex-shrink-0'>
                    <i class='fas {$icon} text-{$color}-500 text-lg'></i>
                </div>
                <div class='ml-3 w-0 flex-1'>
                    <p class='text-sm font-medium text-gray-900'>
                        {$notification['title']}
                    </p>
                    <p class='mt-1 text-sm text-gray-500'>
                        {$notification['message']}
                    </p>
                    <p class='mt-2 text-xs text-gray-400'>
                        {$timeAgo}
                    </p>
                </div>
                <div class='ml-4 flex-shrink-0 flex'>
                    <button class='toast-close bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-{$color}-500'>
                        <span class='sr-only'>Close</span>
                        <i class='fas fa-times text-sm'></i>
                    </button>
                </div>
            </div>
            " . ($notification['action_url'] ? "
            <div class='mt-3'>
                <a href='{$notification['action_url']}' class='text-{$color}-600 hover:text-{$color}-500 text-sm font-medium'>
                    {$notification['action_text']} →
                </a>
            </div>
            " : "") . "
        </div>
    </div>";
}

/**
 * Generate notification dropdown HTML
 */
function generateNotificationDropdown($notifications)
{
    if (empty($notifications)) {
        return "
        <div class='p-6 text-center'>
            <i class='fas fa-bell-slash text-gray-400 text-3xl mb-3'></i>
            <p class='text-gray-500 text-sm'>No notifications</p>
        </div>";
    }

    $html = "<div class='max-h-96 overflow-y-auto'>";

    foreach ($notifications as $notification) {
        $type = $notification['type'];
        $config = NOTIFICATION_TYPES[$type];
        $color = $config['color'];
        $icon = $config['icon'];
        $timeAgo = getTimeAgo($notification['timestamp']);
        $readClass = $notification['read'] ? 'bg-gray-50' : 'bg-white';

        $html .= "
        <div class='notification-item {$readClass} hover:bg-gray-100 border-b border-gray-200 p-4 cursor-pointer transition-colors duration-200'
             data-notification-id='{$notification['id']}'>
            <div class='flex items-start space-x-3'>
                <div class='flex-shrink-0'>
                    <div class='w-8 h-8 bg-{$color}-100 rounded-full flex items-center justify-center'>
                        <i class='fas {$icon} text-{$color}-600 text-sm'></i>
                    </div>
                </div>
                <div class='flex-1 min-w-0'>
                    <p class='text-sm font-medium text-gray-900'>
                        {$notification['title']}
                    </p>
                    <p class='text-sm text-gray-600 mt-1'>
                        {$notification['message']}
                    </p>
                    <p class='text-xs text-gray-400 mt-2'>
                        {$timeAgo}
                    </p>
                </div>
                " . (!$notification['read'] ? "
                <div class='flex-shrink-0'>
                    <div class='w-2 h-2 bg-{$color}-500 rounded-full'></div>
                </div>
                " : "") . "
            </div>
        </div>";
    }

    $html .= "</div>";

    return $html;
}

/**
 * Get time ago string
 */
function getTimeAgo($timestamp)
{
    $time = time() - strtotime($timestamp);

    if ($time < 60) {
        return 'Just now';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', strtotime($timestamp));
    }
}

/**
 * Generate notification bell icon with count
 */
function generateNotificationBell()
{
    try {
        $unreadCount = getUnreadNotificationCount();
        $hasCritical = false;
        
        // Check if there are critical notifications
        $notifications = getNotifications(5, true);
        foreach ($notifications as $notification) {
            if ($notification['type'] === 'critical') {
                $hasCritical = true;
                break;
            }
        }
        
        $bellClass = $hasCritical ? 'fa-bell animate-pulse' : 'fa-bell';
        $badgeColor = $hasCritical ? 'bg-red-600' : 'bg-red-500';

        return "
        <div class='relative'>
            <button id='notification-bell' 
                    class='relative p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-lg transition-all duration-200'
                    aria-label='Notifications'
                    aria-haspopup='true'
                    aria-expanded='false'>
                <i class='fas {$bellClass} text-xl'></i>
                " . ($unreadCount > 0 ? "
                <span class='absolute -top-1 -right-1 {$badgeColor} text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold shadow-lg' aria-label='{$unreadCount} unread notifications'>
                    " . ($unreadCount > 99 ? '99+' : $unreadCount) . "
                </span>
                " : "") . "
            </button>
        </div>";
    } catch (Exception $e) {
        error_log("Error generating notification bell: " . $e->getMessage());
        // Return a basic bell without count on error
        return "
        <div class='relative'>
            <button id='notification-bell' 
                    class='relative p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-lg transition-all duration-200'
                    aria-label='Notifications'>
                <i class='fas fa-bell text-xl'></i>
            </button>
        </div>";
    }
}

/**
 * Initialize notification system JavaScript
 */
function initializeNotificationSystem()
{
    return "
    <script>
    class NotificationSystem {
        constructor() {
            this.notifications = [];
            this.unreadCount = " . getUnreadNotificationCount() . ";
            this.dropdownOpen = false;
            this.eventListeners = [];
            this.init();
        }
        
        init() {
            this.setupEventListeners();
            this.loadNotifications();
            
            // Only auto-show critical notifications on dashboard and only once per session
            const currentPage = window.location.pathname.split('/').pop().replace('.php', '');
            if (currentPage === 'dashboard') {
                // Use sessionStorage instead of localStorage to show once per session
                const shownThisSession = sessionStorage.getItem('notificationsShownThisSession');
                if (!shownThisSession) {
                    this.checkAndShowNewNotifications();
                    sessionStorage.setItem('notificationsShownThisSession', 'true');
                }
            }
            
            // Add smooth animations
            this.addAnimationStyles();
            
            // Start real-time polling for notification updates
            this.startPolling();
        }
        
        startPolling() {
            // Poll for new notifications every 30 seconds
            this.pollingInterval = setInterval(() => {
                this.checkForNewNotifications();
            }, 30000); // 30 seconds
            
            // Also check when page becomes visible again
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this.checkForNewNotifications();
                }
            });
        }
        
        async checkForNewNotifications() {
            try {
                // Fetch latest notification count
                const response = await fetch('includes/notification_api.php?action=get_unread_count', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) return;
                
                const data = await response.json();
                
                if (data.success && data.unread_count !== undefined) {
                    const newCount = data.unread_count;
                    const oldCount = this.unreadCount;
                    
                    // Update count
                    this.unreadCount = newCount;
                    this.updateBellCount();
                    
                    // If there are new notifications, show a subtle indicator
                    if (newCount > oldCount && newCount > 0) {
                        this.showNewNotificationIndicator();
                        
                        // Optionally fetch and show new critical notifications
                        if (data.has_critical) {
                            this.fetchAndShowNewCritical();
                        }
                    }
                }
            } catch (error) {
                console.error('Error checking for new notifications:', error);
            }
        }
        
        showNewNotificationIndicator() {
            const bell = document.getElementById('notification-bell');
            if (bell) {
                // Add a subtle pulse animation
                bell.classList.add('notification-pulse');
                setTimeout(() => {
                    bell.classList.remove('notification-pulse');
                }, 2000);
            }
        }
        
        async fetchAndShowNewCritical() {
            // Only show if not on dashboard (dashboard already shows on load)
            const currentPage = window.location.pathname.split('/').pop().replace('.php', '');
            if (currentPage === 'dashboard') return;
            
            try {
                const response = await fetch('includes/notification_api.php?action=get_new_critical', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) return;
                
                const data = await response.json();
                
                if (data.success && data.notifications && data.notifications.length > 0) {
                    // Show only the first critical notification as a toast
                    const notification = data.notifications[0];
                    this.showToast(notification);
                }
            } catch (error) {
                console.error('Error fetching critical notifications:', error);
            }
        }
        
        addAnimationStyles() {
            if (!document.getElementById('notification-animations')) {
                const style = document.createElement('style');
                style.id = 'notification-animations';
                style.textContent = `
                    @keyframes slideInRight {
                        from {
                            transform: translateX(100%);
                            opacity: 0;
                        }
                        to {
                            transform: translateX(0);
                            opacity: 1;
                        }
                    }
                    
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    
                    @keyframes pulse {
                        0%, 100% { opacity: 1; }
                        50% { opacity: 0.5; }
                    }
                    
                    .toast-notification {
                        animation: slideInRight 0.3s ease-out;
                    }
                    
                    .notification-dropdown-enter {
                        animation: fadeIn 0.2s ease-out;
                    }
                    
                    .notification-pulse {
                        animation: pulse 2s ease-in-out infinite;
                    }
                    
                    @keyframes bellShake {
                        0%, 100% { transform: rotate(0deg); }
                        10%, 30%, 50%, 70%, 90% { transform: rotate(-10deg); }
                        20%, 40%, 60%, 80% { transform: rotate(10deg); }
                    }
                    
                    .notification-pulse i {
                        animation: bellShake 0.5s ease-in-out;
                    }
                `;
                document.head.appendChild(style);
            }
        }
        
        setupEventListeners() {
            // Notification bell click
            const bell = document.getElementById('notification-bell');
            if (bell) {
                const bellClickHandler = (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleNotificationDropdown();
                };
                bell.addEventListener('click', bellClickHandler);
                this.eventListeners.push({ element: bell, event: 'click', handler: bellClickHandler });
            }
            
            // Close toast notifications
            const toastCloseHandler = (e) => {
                if (e.target.closest('.toast-close')) {
                    const toast = e.target.closest('.toast-notification');
                    this.closeToast(toast);
                }
            };
            document.addEventListener('click', toastCloseHandler);
            this.eventListeners.push({ element: document, event: 'click', handler: toastCloseHandler });
            
            // Mark notifications as read when clicked
            const notificationClickHandler = (e) => {
                if (e.target.closest('.notification-item')) {
                    const item = e.target.closest('.notification-item');
                    const notificationId = item.dataset.notificationId;
                    this.markAsRead(notificationId);
                    
                    // Navigate to action URL if exists
                    const notification = this.notifications.find(n => n.id === notificationId);
                    if (notification && notification.action_url) {
                        window.location.href = notification.action_url;
                    }
                }
            };
            document.addEventListener('click', notificationClickHandler);
            this.eventListeners.push({ element: document, event: 'click', handler: notificationClickHandler });
            
            // Keyboard navigation for accessibility
            const keydownHandler = (e) => {
                if (e.key === 'Escape' && this.dropdownOpen) {
                    this.closeNotificationDropdown();
                }
            };
            document.addEventListener('keydown', keydownHandler);
            this.eventListeners.push({ element: document, event: 'keydown', handler: keydownHandler });
        }
        
        // Cleanup method to prevent memory leaks
        destroy() {
            this.eventListeners.forEach(({ element, event, handler }) => {
                element.removeEventListener(event, handler);
            });
            this.eventListeners = [];
            
            // Clear polling interval
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
                this.pollingInterval = null;
            }
        }
        
        loadNotifications() {
            // In a real implementation, this would fetch from the server
            // For now, we'll use the static data
        }
        
        checkAndShowNewNotifications() {
            // Only show critical notifications (not warnings) to reduce noise
            this.showCriticalNotifications();
        }
        
        showCriticalNotifications() {
            // Show only critical notifications as toasts (limit to 2 to avoid overwhelming)
            const criticalNotifications = " . json_encode(getNotifications(10, true)) . ";
            
            const criticalOnly = criticalNotifications.filter(n => n.type === 'critical').slice(0, 2);
            
            if (criticalOnly.length > 0) {
                criticalOnly.forEach((notification, index) => {
                    setTimeout(() => {
                        this.showToast(notification);
                    }, index * 800); // Stagger by 800ms for better UX
                });
            }
        }
        
        // Method to manually trigger notifications (for testing)
        showTestNotifications() {
            // Clear session flag to allow showing again
            sessionStorage.removeItem('notificationsShownThisSession');
            this.showCriticalNotifications();
        }
        
        showToast(notification) {
            const type = notification.type;
            const config = {
                'critical': { color: 'red', icon: 'fa-exclamation-triangle' },
                'warning': { color: 'yellow', icon: 'fa-exclamation-circle' },
                'info': { color: 'blue', icon: 'fa-info-circle' },
                'success': { color: 'green', icon: 'fa-check-circle' }
            }[type] || { color: 'gray', icon: 'fa-bell' };
            
            const timeAgo = this.getTimeAgo(notification.timestamp);
            
            const toastHtml = `
                <div class='toast-notification fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-lg shadow-lg border-l-4 border-\${config.color}-500 transform translate-x-full transition-transform duration-300 ease-in-out' 
                     data-notification-id='\${notification.id}' data-type='\${type}'>
                    <div class='p-4'>
                        <div class='flex items-start'>
                            <div class='flex-shrink-0'>
                                <i class='fas \${config.icon} text-\${config.color}-500 text-lg'></i>
                            </div>
                            <div class='ml-3 w-0 flex-1'>
                                <p class='text-sm font-medium text-gray-900'>
                                    \${notification.title}
                                </p>
                                <p class='mt-1 text-sm text-gray-500'>
                                    \${notification.message}
                                </p>
                                <p class='mt-2 text-xs text-gray-400'>
                                    \${timeAgo}
                                </p>
                            </div>
                            <div class='ml-4 flex-shrink-0 flex'>
                                <button class='toast-close bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-\${config.color}-500'>
                                    <span class='sr-only'>Close</span>
                                    <i class='fas fa-times text-sm'></i>
                                </button>
                            </div>
                        </div>
                        \${notification.action_url ? `
                        <div class='mt-3'>
                            <a href='\${notification.action_url}' class='text-\${config.color}-600 hover:text-\${config.color}-500 text-sm font-medium'>
                                \${notification.action_text} →
                            </a>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', toastHtml);
            
            const toast = document.querySelector(`[data-notification-id=\"\${notification.id}\"]`);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);
            
            // Auto-close after 5 seconds
            setTimeout(() => {
                this.closeToast(toast);
            }, 5000);
        }
        
        closeToast(toast) {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }
        
        toggleNotificationDropdown() {
            let dropdown = document.getElementById('notification-dropdown');
            
            if (dropdown) {
                this.closeNotificationDropdown();
                return;
            }
            
            this.openNotificationDropdown();
        }
        
        openNotificationDropdown() {
            this.dropdownOpen = true;
            
            // Show loading state
            const bell = document.getElementById('notification-bell');
            const loadingHtml = `
                <div id='notification-dropdown' class='absolute right-0 mt-2 w-[calc(100vw-2rem)] max-w-80 sm:w-80 sm:max-w-96 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50 animate-fade-in' role='menu' aria-label='Notifications'>
                    <div class='p-8 text-center'>
                        <i class='fas fa-spinner fa-spin text-gray-400 text-2xl'></i>
                        <p class='text-gray-500 dark:text-gray-400 text-sm mt-2'>Loading notifications...</p>
                    </div>
                </div>
            `;
            bell.parentElement.insertAdjacentHTML('beforeend', loadingHtml);
            
            // Fetch fresh notifications from server
            this.fetchAndRenderNotifications();
            
            // Close dropdown when clicking outside
            setTimeout(() => {
                document.addEventListener('click', this.closeDropdownOnOutsideClick, true);
            }, 100);
        }
        
        async fetchAndRenderNotifications() {
            try {
                const response = await fetch('includes/notification_api.php?action=get_notifications&limit=10', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to fetch notifications');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    this.notifications = data.notifications;
                    this.unreadCount = data.unread_count;
                    this.renderNotificationDropdown();
                } else {
                    throw new Error(data.message || 'Failed to load notifications');
                }
            } catch (error) {
                console.error('Error fetching notifications:', error);
                // Fallback to static notifications
                this.renderNotificationDropdown();
            }
        }
        
        renderNotificationDropdown() {
            const dropdown = document.getElementById('notification-dropdown');
            if (!dropdown) return;
            
            // Use fetched notifications if available, otherwise use static data
            const notifications = this.notifications.length > 0 ? this.notifications : " . json_encode(getNotifications(10)) . ";
            
            let notificationsHtml = '';
            
            if (notifications.length === 0) {
                notificationsHtml = `
                    <div class='p-8 text-center'>
                        <i class='fas fa-bell-slash text-gray-400 text-4xl mb-3'></i>
                        <p class='text-gray-500 text-sm font-medium'>No notifications</p>
                        <p class='text-gray-400 text-xs mt-1'>You're all caught up!</p>
                    </div>
                `;
            } else {
                notificationsHtml = '<div class=\"max-h-[50vh] sm:max-h-80 overflow-y-auto scrollbar-thin\">';
                notifications.forEach(notification => {
                    const config = this.getNotificationConfig(notification.type);
                    const timeAgo = this.getTimeAgo(notification.timestamp);
                    const readClass = notification.read ? 'bg-gray-50 dark:bg-gray-700' : 'bg-white dark:bg-gray-800';
                    
                    notificationsHtml += `
                        <div class='notification-item \${readClass} hover:bg-gray-100 dark:hover:bg-gray-600 border-b border-gray-200 dark:border-gray-700 p-4 cursor-pointer transition-colors duration-200'
                             data-notification-id='\${this.escapeHtml(notification.id)}'
                             role='menuitem'
                             tabindex='0'>
                            <div class='flex items-start space-x-3'>
                                <div class='flex-shrink-0'>
                                    <div class='w-10 h-10 bg-\${config.color}-100 dark:bg-\${config.color}-900 rounded-full flex items-center justify-center'>
                                        <i class='fas \${config.icon} text-\${config.color}-600 dark:text-\${config.color}-400 text-sm'></i>
                                    </div>
                                </div>
                                <div class='flex-1 min-w-0'>
                                    <p class='text-sm font-semibold text-gray-900 dark:text-white'>
                                        \${this.escapeHtml(notification.title)}
                                    </p>
                                    <p class='text-sm text-gray-600 dark:text-gray-300 mt-1 line-clamp-2'>
                                        \${this.escapeHtml(notification.message)}
                                    </p>
                                    <p class='text-xs text-gray-400 dark:text-gray-500 mt-2 flex items-center'>
                                        <i class='far fa-clock mr-1'></i>
                                        \${timeAgo}
                                    </p>
                                </div>
                                \${!notification.read ? `
                                <div class='flex-shrink-0'>
                                    <div class='w-2.5 h-2.5 bg-\${config.color}-500 rounded-full animate-pulse' title='Unread'></div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
                notificationsHtml += '</div>';
            }
            
            const dropdownHtml = `
                <div class='p-4 border-b border-gray-200 dark:border-gray-700'>
                    <div class='flex items-center justify-between mb-2'>
                        <h3 class='text-lg font-semibold text-gray-900 dark:text-white'>Notifications</h3>
                        <span class='text-sm text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full'>
                            \${this.unreadCount} unread
                        </span>
                    </div>
                    \${this.unreadCount > 0 ? `
                    <button onclick='notificationSystem.markAllAsRead()' 
                            class='text-xs text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium flex items-center transition-colors duration-200'>
                        <i class='fas fa-check-double mr-1'></i>
                        Mark all as read
                    </button>
                    ` : ''}
                </div>
                \${notificationsHtml}
                <div class='p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800'>
                    <a href='notifications.php' class='text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 text-sm font-medium flex items-center justify-center transition-colors duration-200'>
                        View all notifications
                        <i class='fas fa-arrow-right ml-2 text-xs'></i>
                    </a>
                </div>
            `;
            
            dropdown.innerHTML = dropdownHtml;
        }
        
        closeNotificationDropdown() {
            const dropdown = document.getElementById('notification-dropdown');
            if (dropdown) {
                dropdown.remove();
                this.dropdownOpen = false;
                document.removeEventListener('click', this.closeDropdownOnOutsideClick, true);
            }
        }
        
        closeDropdownOnOutsideClick = (e) => {
            const dropdown = document.getElementById('notification-dropdown');
            const bell = document.getElementById('notification-bell');
            
            if (dropdown && !dropdown.contains(e.target) && !bell.contains(e.target)) {
                this.closeNotificationDropdown();
            }
        }
        
        getNotificationConfig(type) {
            const configs = {
                'critical': { color: 'red', icon: 'fa-exclamation-triangle' },
                'high': { color: 'orange', icon: 'fa-exclamation-circle' },
                'warning': { color: 'yellow', icon: 'fa-exclamation-circle' },
                'medium': { color: 'yellow', icon: 'fa-info-circle' },
                'low': { color: 'blue', icon: 'fa-check-circle' },
                'info': { color: 'blue', icon: 'fa-info-circle' },
                'success': { color: 'green', icon: 'fa-check-circle' }
            };
            return configs[type] || { color: 'gray', icon: 'fa-bell' };
        }
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        markAsRead(notificationId) {
            // Find the notification
            const notification = this.notifications.find(n => n.id === notificationId);
            if (!notification || notification.read) return;
            
            // In a real implementation, this would make an AJAX call
            console.log('Marking notification as read:', notificationId);
            
            // Update notification object
            notification.read = true;
            
            // Update UI with smooth transition
            const item = document.querySelector(`[data-notification-id=\"\${notificationId}\"]`);
            if (item) {
                item.classList.add('opacity-75');
                
                setTimeout(() => {
                    item.classList.remove('bg-white', 'dark:bg-gray-800');
                    item.classList.add('bg-gray-50', 'dark:bg-gray-700');
                    
                    // Remove unread indicator with fade
                    const indicator = item.querySelector('.w-2\\\\.5');
                    if (indicator) {
                        indicator.style.transition = 'opacity 0.3s';
                        indicator.style.opacity = '0';
                        setTimeout(() => indicator.remove(), 300);
                    }
                    
                    item.classList.remove('opacity-75');
                }, 150);
            }
            
            // Update unread count
            this.unreadCount = Math.max(0, this.unreadCount - 1);
            this.updateBellCount();
            
            // Show subtle feedback
            this.showFeedback('Notification marked as read', 'success');
        }
        
        showFeedback(message, type = 'info') {
            // Create a small feedback toast
            const feedback = document.createElement('div');
            feedback.className = 'fixed bottom-4 right-4 z-50 bg-gray-900 text-white text-sm px-4 py-2 rounded-lg shadow-lg opacity-0 transition-opacity duration-300';
            feedback.textContent = message;
            
            document.body.appendChild(feedback);
            
            setTimeout(() => feedback.classList.add('opacity-100'), 10);
            
            setTimeout(() => {
                feedback.classList.remove('opacity-100');
                setTimeout(() => feedback.remove(), 300);
            }, 2000);
        }
        
        updateBellCount() {
            const bell = document.getElementById('notification-bell');
            const countElement = bell.querySelector('span');
            
            if (this.unreadCount > 0) {
                if (countElement) {
                    countElement.textContent = this.unreadCount > 9 ? '9+' : this.unreadCount;
                } else {
                    bell.insertAdjacentHTML('beforeend', `
                        <span class='absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium'>
                            \${this.unreadCount > 9 ? '9+' : this.unreadCount}
                        </span>
                    `);
                }
            } else {
                if (countElement) {
                    countElement.remove();
                }
            }
        }
        
        async markAllAsRead() {
            if (this.unreadCount === 0) return;
            
            try {
                // Make API call to mark all as read
                const response = await fetch('includes/notification_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json'
                    },
                    body: 'action=mark_all_as_read'
                });
                
                if (!response.ok) {
                    throw new Error('Failed to mark all as read');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Update all notifications to read
                    this.notifications.forEach(notification => {
                        notification.read = true;
                    });
                    
                    // Update unread count
                    this.unreadCount = 0;
                    this.updateBellCount();
                    
                    // Update UI - remove all unread indicators
                    const unreadIndicators = document.querySelectorAll('.notification-item .w-2\\\\.5');
                    unreadIndicators.forEach(indicator => {
                        indicator.style.transition = 'opacity 0.3s';
                        indicator.style.opacity = '0';
                        setTimeout(() => indicator.remove(), 300);
                    });
                    
                    // Update all notification items background
                    const notificationItems = document.querySelectorAll('.notification-item');
                    notificationItems.forEach(item => {
                        item.classList.remove('bg-white', 'dark:bg-gray-800');
                        item.classList.add('bg-gray-50', 'dark:bg-gray-700');
                    });
                    
                    // Re-render dropdown to update header
                    this.renderNotificationDropdown();
                    
                    // Show success feedback
                    this.showFeedback('All notifications marked as read', 'success');
                } else {
                    throw new Error(data.message || 'Failed to mark all as read');
                }
            } catch (error) {
                console.error('Error marking all as read:', error);
                this.showFeedback('Failed to mark all as read', 'error');
            }
        }
        
        getTimeAgo(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diffInSeconds = Math.floor((now - time) / 1000);
            
            if (diffInSeconds < 60) {
                return 'Just now';
            } else if (diffInSeconds < 3600) {
                const minutes = Math.floor(diffInSeconds / 60);
                return minutes + ' minute' + (minutes > 1 ? 's' : '') + ' ago';
            } else if (diffInSeconds < 86400) {
                const hours = Math.floor(diffInSeconds / 3600);
                return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
            } else if (diffInSeconds < 2592000) {
                const days = Math.floor(diffInSeconds / 86400);
                return days + ' day' + (days > 1 ? 's' : '') + ' ago';
            } else {
                return time.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            }
        }
    }
    
    // Initialize notification system when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
        window.notificationSystem = new NotificationSystem();
    });
    </script>
    <style>
        /* Notification dropdown scrollbar styling */
        #notification-dropdown .overflow-y-auto::-webkit-scrollbar {
            width: 8px;
        }
        
        #notification-dropdown .overflow-y-auto::-webkit-scrollbar-track {
            background: rgba(243, 244, 246, 0.5);
            border-radius: 4px;
        }
        
        #notification-dropdown .overflow-y-auto::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.6);
            border-radius: 4px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }
        
        #notification-dropdown .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: rgba(107, 114, 128, 0.8);
            background-clip: padding-box;
        }
        
        /* Dark mode scrollbar */
        .dark #notification-dropdown .overflow-y-auto::-webkit-scrollbar-track {
            background: rgba(31, 41, 55, 0.5);
        }
        
        .dark #notification-dropdown .overflow-y-auto::-webkit-scrollbar-thumb {
            background: rgba(75, 85, 99, 0.6);
            background-clip: padding-box;
        }
        
        .dark #notification-dropdown .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: rgba(107, 114, 128, 0.8);
            background-clip: padding-box;
        }
        
        /* Smooth scrolling */
        #notification-dropdown .overflow-y-auto {
            scroll-behavior: smooth;
            overscroll-behavior: contain;
        }
        
        /* Firefox scrollbar */
        #notification-dropdown .overflow-y-auto {
            scrollbar-width: thin;
            scrollbar-color: rgba(156, 163, 175, 0.6) rgba(243, 244, 246, 0.5);
        }
        
        .dark #notification-dropdown .overflow-y-auto {
            scrollbar-color: rgba(75, 85, 99, 0.6) rgba(31, 41, 55, 0.5);
        }
    </style>";
}
