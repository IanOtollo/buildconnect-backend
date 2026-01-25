<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// Require authentication
$user = requireAuth();

if ($method === 'GET') {
    // Get notifications for current user
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$user['user_id']]);
        $notifications = $stmt->fetchAll();
        
        // Get unread count
        $stmt = $db->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user['user_id']]);
        $unreadCount = $stmt->fetch()['unread_count'];
        
        jsonResponse([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Failed to fetch notifications'], 500);
    }
    
} elseif ($method === 'POST') {
    // Mark notification(s) as read
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $db = getDBConnection();
        
        if (isset($data['notification_id'])) {
            // Mark single notification as read
            $notificationId = intval($data['notification_id']);
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$notificationId, $user['user_id']]);
        } else {
            // Mark all as read
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
        }
        
        jsonResponse(['message' => 'Notifications marked as read']);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Failed to update notifications'], 500);
    }
    
} else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}
