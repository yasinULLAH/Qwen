<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_response(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$csrf_token = $_POST['csrf_token'] ?? '';

if (!validate_csrf($csrf_token)) {
    echo json_response(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

try {
    switch ($action) {
        case 'get_notifications':
            $user_role = get_current_user()['role'];
            $limit = (int)($_GET['limit'] ?? 20);
            
            $stmt = dbQuery("SELECT * FROM notifications 
                            WHERE recipientrole = ? OR recipientrole = 'All' 
                            ORDER BY created_at DESC 
                            LIMIT ?", [$user_role, $limit]);
            $notifications = dbFetchAll($stmt);
            
            echo json_response(['success' => true, 'notifications' => $notifications]);
            break;

        case 'mark_as_read':
            $notification_id = (int)$_POST['notification_id'];
            
            dbQuery("UPDATE notifications SET isread = 1 WHERE id = ? AND recipientrole = ?", 
                [$notification_id, get_current_user()['role']]);
            
            echo json_response(['success' => true]);
            break;

        case 'mark_all_read':
            $user_role = get_current_user()['role'];
            
            dbQuery("UPDATE notifications SET isread = 1 WHERE recipientrole = ? OR recipientrole = 'All'", [$user_role]);
            
            echo json_response(['success' => true]);
            break;

        case 'get_unread_count':
            $user_role = get_current_user()['role'];
            
            $stmt = dbQuery("SELECT COUNT(*) as count FROM notifications 
                            WHERE (recipientrole = ? OR recipientrole = 'All') AND isread = 0", [$user_role]);
            $count = dbFetchOne($stmt)['count'];
            
            echo json_response(['success' => true, 'count' => $count]);
            break;

        default:
            echo json_response(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Notifications Action Error: " . $e->getMessage());
    echo json_response(['success' => false, 'message' => 'Server error']);
}
