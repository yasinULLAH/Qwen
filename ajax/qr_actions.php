<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_menu':
            $seating_id = (int)($_GET['seating_id'] ?? 0);
            
            if (!$seating_id) {
                echo json_response(['success' => false, 'message' => 'Invalid table']);
                break;
            }
            
            $stmt = dbQuery("SELECT c.id, c.name as category_name FROM categories c ORDER BY c.name");
            $categories = dbFetchAll($stmt);
            
            $menu = [];
            foreach ($categories as $category) {
                $stmt = dbQuery("SELECT p.*, 
                                CASE 
                                    WHEN p.stockstatus = 'Out of Stock' THEN 0
                                    ELSE 1
                                END as available
                                FROM products p 
                                WHERE p.categoryid = ? 
                                ORDER BY p.name", [$category['id']]);
                $products = dbFetchAll($stmt);
                
                if (!empty($products)) {
                    $menu[] = [
                        'category_id' => $category['id'],
                        'category_name' => $category['name'],
                        'products' => $products
                    ];
                }
            }
            
            echo json_response(['success' => true, 'menu' => $menu, 'seating_id' => $seating_id]);
            break;

        case 'qr_create_order':
            $seating_area_id = (int)$_POST['seating_area_id'];
            $guest_count = (int)($_POST['guest_count'] ?? 2);
            $total_amount = (float)$_POST['total_amount'];
            $net_amount = (float)$_POST['net_amount'];
            $items = json_decode($_POST['items'], true);
            
            if (empty($items)) {
                echo json_response(['success' => false, 'message' => 'No items in order']);
                break;
            }
            
            dbBeginTransaction();
            
            $stmt = dbQuery("INSERT INTO orders (seatingareaid, guestcount, totalamount, netamount, paidamount, paymentmethod, status, isqrorder, created_at) 
                            VALUES (?, ?, ?, ?, 0, NULL, 'Pending', 1, NOW())",
                [$seating_area_id, $guest_count, $total_amount, $net_amount]);
            
            $order_id = dbLastInsertId();
            
            foreach ($items as $item) {
                dbQuery("INSERT INTO orderitems (orderid, productid, qty, price, finalprice, course, notes, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')",
                    [$order_id, $item['product_id'], $item['qty'], $item['price'], $item['final_price'], $item['course'] ?? 'Main', $item['notes'] ?? '']);
            }
            
            dbCommit();
            
            push_notification('Kitchen', "New QR Order #$order_id from Table", $order_id, 'order');
            
            echo json_response(['success' => true, 'order_id' => $order_id]);
            break;

        case 'get_order_status':
            $order_id = (int)$_GET['order_id'];
            
            $stmt = dbQuery("SELECT o.*, s.name as table_name 
                            FROM orders o 
                            LEFT JOIN seatingareas s ON o.seatingareaid = s.id 
                            WHERE o.id = ?", [$order_id]);
            $order = dbFetchOne($stmt);
            
            if (!$order) {
                echo json_response(['success' => false, 'message' => 'Order not found']);
                break;
            }
            
            $stmt = dbQuery("SELECT oi.*, p.name as product_name 
                            FROM orderitems oi 
                            JOIN products p ON oi.productid = p.id 
                            WHERE oi.orderid = ?", [$order_id]);
            $items = dbFetchAll($stmt);
            
            echo json_response(['success' => true, 'order' => $order, 'items' => $items]);
            break;

        default:
            echo json_response(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        dbRollback();
    }
    error_log("QR Action Error: " . $e->getMessage());
    echo json_response(['success' => false, 'message' => 'Server error']);
}
