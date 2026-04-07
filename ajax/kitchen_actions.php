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
        case 'get_pending_orders':
            $stmt = dbQuery("SELECT o.*, s.name as seating_name, u.name as server_name 
                            FROM orders o 
                            LEFT JOIN seatingareas s ON o.seatingareaid = s.id 
                            LEFT JOIN users u ON o.userid = u.id 
                            WHERE o.status IN ('Pending', 'Cooking') 
                            ORDER BY o.created_at ASC");
            $orders = dbFetchAll($stmt);
            
            $result = [];
            foreach ($orders as $order) {
                $stmt = dbQuery("SELECT oi.*, p.name as product_name 
                                FROM orderitems oi 
                                JOIN products p ON oi.productid = p.id 
                                WHERE oi.orderid = ? AND oi.status != 'Prepared'", [$order['id']]);
                $items = dbFetchAll($stmt);
                $order['items'] = $items;
                $result[] = $order;
            }
            
            echo json_response(['success' => true, 'orders' => $result]);
            break;

        case 'update_item_status':
            $item_id = (int)$_POST['item_id'];
            $status = sanitize_input($_POST['status']);
            
            if (!in_array($status, ['Pending', 'Cooking', 'Prepared', 'Ready'])) {
                echo json_response(['success' => false, 'message' => 'Invalid status']);
                break;
            }
            
            dbQuery("UPDATE orderitems SET status = ? WHERE id = ?", [$status, $item_id]);
            
            $stmt = dbQuery("SELECT orderid FROM orderitems WHERE id = ?", [$item_id]);
            $item = dbFetchOne($stmt);
            
            if ($status === 'Prepared') {
                $all_prepared = dbQuery("SELECT COUNT(*) as total FROM orderitems WHERE orderid = ? AND status != 'Prepared'", [$item['orderid']]);
                $count = dbFetchOne($all_prepared);
                
                if ($count['total'] == 0) {
                    dbQuery("UPDATE orders SET status = 'Served' WHERE id = ?", [$item['orderid']]);
                    push_notification('Server', "Order #" . $item['orderid'] . " is ready for serving", $item['orderid'], 'order');
                }
            }
            
            audit_log(get_current_user()['id'], 'update_item_status', "Updated item #$item_id to $status");
            echo json_response(['success' => true]);
            break;

        case 'get_kds_stats':
            $pending = dbQuery("SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'");
            $cooking = dbQuery("SELECT COUNT(*) as count FROM orders WHERE status = 'Cooking'");
            $ready = dbQuery("SELECT COUNT(*) as count FROM orderitems WHERE status = 'Prepared'");
            
            echo json_response([
                'success' => true,
                'stats' => [
                    'pending' => dbFetchOne($pending)['count'],
                    'cooking' => dbFetchOne($cooking)['count'],
                    'ready' => dbFetchOne($ready)['count']
                ]
            ]);
            break;

        default:
            echo json_response(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Kitchen Action Error: " . $e->getMessage());
    echo json_response(['success' => false, 'message' => 'Server error']);
}
