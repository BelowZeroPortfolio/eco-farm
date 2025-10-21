<?php
/**
 * Notification API Endpoint
 * 
 * Handles AJAX requests for real-time notification updates
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/notifications.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_unread_count':
            // Get unread notification count
            $unreadCount = getUnreadNotificationCount();
            
            // Check if there are critical notifications
            $notifications = getNotifications(5, true);
            $hasCritical = false;
            foreach ($notifications as $notification) {
                if ($notification['type'] === 'critical') {
                    $hasCritical = true;
                    break;
                }
            }
            
            echo json_encode([
                'success' => true,
                'unread_count' => $unreadCount,
                'has_critical' => $hasCritical
            ]);
            break;
            
        case 'get_new_critical':
            // Get new critical notifications (last 5 minutes)
            $notifications = getNotifications(10, true);
            $criticalNotifications = [];
            
            $fiveMinutesAgo = time() - 300; // 5 minutes
            
            foreach ($notifications as $notification) {
                if ($notification['type'] === 'critical') {
                    $timestamp = strtotime($notification['timestamp']);
                    if ($timestamp > $fiveMinutesAgo) {
                        $criticalNotifications[] = $notification;
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'notifications' => $criticalNotifications
            ]);
            break;
            
        case 'get_notifications':
            // Get all notifications
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            
            $notifications = getNotifications($limit, $unreadOnly);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => getUnreadNotificationCount()
            ]);
            break;
            
        case 'mark_as_read':
            // Mark notification as read
            $notificationId = $_POST['notification_id'] ?? '';
            
            if (empty($notificationId)) {
                throw new Exception('Notification ID is required');
            }
            
            // For pest alerts
            if (strpos($notificationId, 'pest_') === 0) {
                $alertId = intval(str_replace('pest_', '', $notificationId));
                
                $pdo = getDatabaseConnection();
                $stmt = $pdo->prepare("
                    UPDATE pest_alerts 
                    SET is_read = TRUE, read_at = NOW(), read_by = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $alertId]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
            break;
            
        case 'mark_all_as_read':
            // Mark all notifications as read
            $pdo = getDatabaseConnection();
            $stmt = $pdo->prepare("
                UPDATE pest_alerts 
                SET is_read = TRUE, read_at = NOW(), read_by = ? 
                WHERE is_read = FALSE
            ");
            $stmt->execute([$_SESSION['user_id']]);
            
            $count = $stmt->rowCount();
            
            echo json_encode([
                'success' => true,
                'message' => "{$count} notifications marked as read",
                'count' => $count
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
