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
    'warning' => ['priority' => 2, 'color' => 'yellow', 'icon' => 'fa-exclamation-circle'],
    'info' => ['priority' => 3, 'color' => 'blue', 'icon' => 'fa-info-circle'],
    'success' => ['priority' => 4, 'color' => 'green', 'icon' => 'fa-check-circle']
]);

/**
 * Get sample notifications for demonstration
 */
function getSampleNotifications()
{
    return [
        [
            'id' => 1,
            'type' => 'critical',
            'title' => 'Critical Pest Alert',
            'message' => 'Severe aphid infestation detected in Greenhouse A. Immediate action required.',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
            'read' => false,
            'action_url' => 'pest_detection.php',
            'action_text' => 'View Details'
        ],
        [
            'id' => 2,
            'type' => 'warning',
            'title' => 'Sensor Offline',
            'message' => 'Temperature sensor in Field B has been offline for 2 hours.',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'read' => false,
            'action_url' => 'sensors.php',
            'action_text' => 'Check Sensors'
        ],
        [
            'id' => 3,
            'type' => 'info',
            'title' => 'Daily Report Ready',
            'message' => 'Your daily farm monitoring report is now available for download.',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-4 hours')),
            'read' => true,
            'action_url' => 'reports.php',
            'action_text' => 'View Report'
        ],
        [
            'id' => 4,
            'type' => 'success',
            'title' => 'System Update Complete',
            'message' => 'All sensors have been successfully updated to the latest firmware.',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'read' => true,
            'action_url' => null,
            'action_text' => null
        ],
        [
            'id' => 5,
            'type' => 'warning',
            'title' => 'Low Soil Moisture',
            'message' => 'Soil moisture levels in Zone C have dropped below optimal range.',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
            'read' => false,
            'action_url' => 'sensors.php',
            'action_text' => 'View Sensors'
        ]
    ];
}

/**
 * Get notifications with priority sorting
 */
function getNotifications($limit = null, $unreadOnly = false)
{
    $notifications = getSampleNotifications();

    // Filter unread only if requested
    if ($unreadOnly) {
        $notifications = array_filter($notifications, function ($notification) {
            return !$notification['read'];
        });
    }

    // Sort by priority (critical first) then by timestamp (newest first)
    usort($notifications, function ($a, $b) {
        $priorityA = NOTIFICATION_TYPES[$a['type']]['priority'];
        $priorityB = NOTIFICATION_TYPES[$b['type']]['priority'];

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
 * Get unread notification count
 */
function getUnreadNotificationCount()
{
    $notifications = getSampleNotifications();
    return count(array_filter($notifications, function ($notification) {
        return !$notification['read'];
    }));
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
    $unreadCount = getUnreadNotificationCount();

    return "
    <div class='relative'>
        <button id='notification-bell' class='relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-lg transition-colors duration-200'>
            <i class='fas fa-bell text-xl'></i>
            " . ($unreadCount > 0 ? "
            <span class='absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium'>
                " . ($unreadCount > 9 ? '9+' : $unreadCount) . "
            </span>
            " : "") . "
        </button>
    </div>";
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
            this.init();
        }
        
        init() {
            this.setupEventListeners();
            this.loadNotifications();
            
            // Only auto-show critical notifications on dashboard or if explicitly requested
            // This prevents toasts from showing on every page refresh
            const currentPage = window.location.pathname.split('/').pop().replace('.php', '');
            if (currentPage === 'dashboard' || currentPage === 'test_notifications') {
                // Check if we should show notifications (only if not shown recently)
                this.checkAndShowNewNotifications();
            }
        }
        
        setupEventListeners() {
            // Notification bell click
            const bell = document.getElementById('notification-bell');
            if (bell) {
                bell.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleNotificationDropdown();
                });
            }
            
            // Close toast notifications
            document.addEventListener('click', (e) => {
                if (e.target.closest('.toast-close')) {
                    const toast = e.target.closest('.toast-notification');
                    this.closeToast(toast);
                }
            });
            
            // Mark notifications as read when clicked
            document.addEventListener('click', (e) => {
                if (e.target.closest('.notification-item')) {
                    const item = e.target.closest('.notification-item');
                    const notificationId = item.dataset.notificationId;
                    this.markAsRead(notificationId);
                }
            });
        }
        
        loadNotifications() {
            // In a real implementation, this would fetch from the server
            // For now, we'll use the static data
        }
        
        checkAndShowNewNotifications() {
            // Check if notifications have been shown recently to avoid spam
            const lastShown = localStorage.getItem('lastNotificationShow');
            const now = Date.now();
            const fiveMinutes = 5 * 60 * 1000; // 5 minutes in milliseconds
            
            // Only show notifications if they haven't been shown in the last 5 minutes
            if (!lastShown || (now - parseInt(lastShown)) > fiveMinutes) {
                this.showCriticalNotifications();
                localStorage.setItem('lastNotificationShow', now.toString());
            }
        }
        
        showCriticalNotifications() {
            // Show critical and warning notifications as toasts
            const criticalNotifications = " . json_encode(getNotifications(3, true)) . ";
            
            criticalNotifications.forEach((notification, index) => {
                if (notification.type === 'critical' || notification.type === 'warning') {
                    setTimeout(() => {
                        this.showToast(notification);
                    }, index * 1000); // Stagger the notifications
                }
            });
        }
        
        // Method to manually trigger notifications (for testing)
        showTestNotifications() {
            this.showCriticalNotifications();
        }
        
        // Method to reset notification timing (for testing or admin purposes)
        resetNotificationTiming() {
            localStorage.removeItem('lastNotificationShow');
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
                dropdown.remove();
                return;
            }
            
            // Create dropdown
            const notifications = " . json_encode(getNotifications(10)) . ";
            let notificationsHtml = '';
            
            if (notifications.length === 0) {
                notificationsHtml = `
                    <div class='p-6 text-center'>
                        <i class='fas fa-bell-slash text-gray-400 text-3xl mb-3'></i>
                        <p class='text-gray-500 text-sm'>No notifications</p>
                    </div>
                `;
            } else {
                notificationsHtml = '<div class=\"max-h-96 overflow-y-auto\">';
                notifications.forEach(notification => {
                    const config = {
                        'critical': { color: 'red', icon: 'fa-exclamation-triangle' },
                        'warning': { color: 'yellow', icon: 'fa-exclamation-circle' },
                        'info': { color: 'blue', icon: 'fa-info-circle' },
                        'success': { color: 'green', icon: 'fa-check-circle' }
                    }[notification.type] || { color: 'gray', icon: 'fa-bell' };
                    
                    const timeAgo = this.getTimeAgo(notification.timestamp);
                    const readClass = notification.read ? 'bg-gray-50' : 'bg-white';
                    
                    notificationsHtml += `
                        <div class='notification-item \${readClass} hover:bg-gray-100 border-b border-gray-200 p-4 cursor-pointer transition-colors duration-200'
                             data-notification-id='\${notification.id}'>
                            <div class='flex items-start space-x-3'>
                                <div class='flex-shrink-0'>
                                    <div class='w-8 h-8 bg-\${config.color}-100 rounded-full flex items-center justify-center'>
                                        <i class='fas \${config.icon} text-\${config.color}-600 text-sm'></i>
                                    </div>
                                </div>
                                <div class='flex-1 min-w-0'>
                                    <p class='text-sm font-medium text-gray-900'>
                                        \${notification.title}
                                    </p>
                                    <p class='text-sm text-gray-600 mt-1'>
                                        \${notification.message}
                                    </p>
                                    <p class='text-xs text-gray-400 mt-2'>
                                        \${timeAgo}
                                    </p>
                                </div>
                                \${!notification.read ? `
                                <div class='flex-shrink-0'>
                                    <div class='w-2 h-2 bg-\${config.color}-500 rounded-full'></div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
                notificationsHtml += '</div>';
            }
            
            const dropdownHtml = `
                <div id='notification-dropdown' class='absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50 animate-fade-in'>
                    <div class='p-4 border-b border-gray-200'>
                        <div class='flex items-center justify-between'>
                            <h3 class='text-lg font-semibold text-gray-900'>Notifications</h3>
                            <span class='text-sm text-gray-500'>\${this.unreadCount} unread</span>
                        </div>
                    </div>
                    \${notificationsHtml}
                    <div class='p-4 border-t border-gray-200'>
                        <a href='notifications.php' class='text-primary-600 hover:text-primary-700 text-sm font-medium'>
                            View all notifications →
                        </a>
                    </div>
                </div>
            `;
            
            const bell = document.getElementById('notification-bell');
            bell.parentElement.insertAdjacentHTML('beforeend', dropdownHtml);
            
            // Close dropdown when clicking outside
            setTimeout(() => {
                document.addEventListener('click', this.closeDropdownOnOutsideClick, true);
            }, 100);
        }
        
        closeDropdownOnOutsideClick = (e) => {
            const dropdown = document.getElementById('notification-dropdown');
            const bell = document.getElementById('notification-bell');
            
            if (dropdown && !dropdown.contains(e.target) && !bell.contains(e.target)) {
                dropdown.remove();
                document.removeEventListener('click', this.closeDropdownOnOutsideClick, true);
            }
        }
        
        markAsRead(notificationId) {
            // In a real implementation, this would make an AJAX call
            console.log('Marking notification as read:', notificationId);
            
            // Update UI
            const item = document.querySelector(`[data-notification-id=\"\${notificationId}\"]`);
            if (item) {
                item.classList.remove('bg-white');
                item.classList.add('bg-gray-50');
                
                // Remove unread indicator
                const indicator = item.querySelector('.w-2.h-2');
                if (indicator) {
                    indicator.remove();
                }
            }
            
            // Update unread count
            this.unreadCount = Math.max(0, this.unreadCount - 1);
            this.updateBellCount();
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
    </script>";
}
