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
        case 'get_sales_report':
            $start_date = sanitize_input($_GET['start_date'] ?? date('Y-m-01'));
            $end_date = sanitize_input($_GET['end_date'] ?? date('Y-m-d'));
            
            $stmt = dbQuery("SELECT DATE(created_at) as date, 
                            COUNT(*) as order_count, 
                            SUM(netamount) as total_sales,
                            SUM(paidamount) as total_paid,
                            SUM(CASE WHEN status = 'Credit' THEN netamount - paidamount ELSE 0 END) as credit_amount
                            FROM orders 
                            WHERE DATE(created_at) BETWEEN ? AND ? 
                            GROUP BY DATE(created_at) 
                            ORDER BY date DESC", [$start_date, $end_date]);
            $report = dbFetchAll($stmt);
            
            echo json_response(['success' => true, 'report' => $report]);
            break;

        case 'get_product_performance':
            $start_date = sanitize_input($_GET['start_date'] ?? date('Y-m-01'));
            $end_date = sanitize_input($_GET['end_date'] ?? date('Y-m-d'));
            
            $stmt = dbQuery("SELECT p.name as product_name, c.name as category_name,
                            SUM(oi.qty) as total_qty,
                            SUM(oi.finalprice) as total_revenue
                            FROM orderitems oi
                            JOIN products p ON oi.productid = p.id
                            LEFT JOIN categories c ON p.categoryid = c.id
                            JOIN orders o ON oi.orderid = o.id
                            WHERE DATE(o.created_at) BETWEEN ? AND ?
                            GROUP BY p.id
                            ORDER BY total_revenue DESC
                            LIMIT 20", [$start_date, $end_date]);
            $products = dbFetchAll($stmt);
            
            echo json_response(['success' => true, 'products' => $products]);
            break;

        case 'get_dashboard_stats':
            $today = date('Y-m-d');
            
            $orders_today = dbQuery("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = ?", [$today]);
            $sales_today = dbQuery("SELECT COALESCE(SUM(netamount), 0) as total FROM orders WHERE DATE(created_at) = ? AND status IN ('Paid', 'Credit')", [$today]);
            $pending_orders = dbQuery("SELECT COUNT(*) as count FROM orders WHERE status IN ('Pending', 'Cooking')");
            $low_stock = dbQuery("SELECT COUNT(*) as count FROM ingredients WHERE stocklevel < 10");
            $customers_with_balance = dbQuery("SELECT COUNT(*) as count FROM customers WHERE balance > 0");
            $total_khata = dbQuery("SELECT COALESCE(SUM(balance), 0) as total FROM customers WHERE balance > 0");
            
            echo json_response([
                'success' => true,
                'stats' => [
                    'orders_today' => dbFetchOne($orders_today)['count'],
                    'sales_today' => dbFetchOne($sales_today)['total'],
                    'pending_orders' => dbFetchOne($pending_orders)['count'],
                    'low_stock_items' => dbFetchOne($low_stock)['count'],
                    'customers_with_balance' => dbFetchOne($customers_with_balance)['count'],
                    'total_khata_amount' => dbFetchOne($total_khata)['total']
                ]
            ]);
            break;

        case 'get_inventory_report':
            $stmt = dbQuery("SELECT i.*, 
                            CASE 
                                WHEN i.stocklevel < 5 THEN 'Critical'
                                WHEN i.stocklevel < 10 THEN 'Low'
                                ELSE 'OK'
                            END as stock_status
                            FROM ingredients i 
                            ORDER BY i.stocklevel ASC");
            $inventory = dbFetchAll($stmt);
            
            echo json_response(['success' => true, 'inventory' => $inventory]);
            break;

        case 'get_wastage_report':
            $start_date = sanitize_input($_GET['start_date'] ?? date('Y-m-01'));
            $end_date = sanitize_input($_GET['end_date'] ?? date('Y-m-d'));
            
            $stmt = dbQuery("SELECT w.*, u.name as logged_by_name,
                            CASE 
                                WHEN w.itemtype = 'ingredient' THEN i.name
                                ELSE p.name
                            END as item_name
                            FROM wastagelog w
                            LEFT JOIN users u ON w.loggedby = u.id
                            LEFT JOIN ingredients i ON w.itemid = i.id AND w.itemtype = 'ingredient'
                            LEFT JOIN products p ON w.itemid = p.id AND w.itemtype = 'product'
                            WHERE DATE(w.timestamp) BETWEEN ? AND ?
                            ORDER BY w.timestamp DESC", [$start_date, $end_date]);
            $wastage = dbFetchAll($stmt);
            
            echo json_response(['success' => true, 'wastage' => $wastage]);
            break;

        default:
            echo json_response(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Reports Action Error: " . $e->getMessage());
    echo json_response(['success' => false, 'message' => 'Server error']);
}
