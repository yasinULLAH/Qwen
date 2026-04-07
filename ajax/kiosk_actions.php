<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'kiosk_login':
            $kiosk_name = sanitize_input($_POST['kiosk_name']);
            $password = $_POST['password'];
            
            $stmt = dbQuery("SELECT * FROM kioskdevices WHERE name = ?", [$kiosk_name]);
            $kiosk = dbFetchOne($stmt);
            
            if (!$kiosk || !password_verify($password, $kiosk['passwordhash'])) {
                echo json_response(['success' => false, 'message' => 'Invalid credentials']);
                break;
            }
            
            $_SESSION['kiosk_id'] = $kiosk['id'];
            $_SESSION['kiosk_seating_area_id'] = $kiosk['seatingareaid'];
            $_SESSION['is_kiosk'] = true;
            
            audit_log($kiosk['id'], 'kiosk_login', "Kiosk {$kiosk['name']} logged in");
            
            echo json_response(['success' => true, 'seating_area_id' => $kiosk['seatingareaid']]);
            break;

        case 'kiosk_logout':
            unset($_SESSION['kiosk_id']);
            unset($_SESSION['kiosk_seating_area_id']);
            unset($_SESSION['is_kiosk']);
            echo json_response(['success' => true]);
            break;

        case 'kiosk_create_order':
            if (!isset($_SESSION['is_kiosk'])) {
                echo json_response(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            
            $seating_area_id = (int)$_SESSION['kiosk_seating_area_id'];
            $total_amount = (float)$_POST['total_amount'];
            $net_amount = (float)$_POST['net_amount'];
            $paid_amount = (float)$_POST['paid_amount'];
            $payment_method = sanitize_input($_POST['payment_method'] ?? 'Cash');
            $items = json_decode($_POST['items'], true);
            
            if (empty($items)) {
                echo json_response(['success' => false, 'message' => 'No items in order']);
                break;
            }
            
            $status = ($paid_amount >= $net_amount) ? 'Paid' : 'Pending';
            
            dbBeginTransaction();
            
            $stmt = dbQuery("INSERT INTO orders (seatingareaid, userid, totalamount, netamount, paidamount, paymentmethod, status, isqrorder, created_at) 
                            VALUES (?, NULL, ?, ?, ?, ?, ?, 0, NOW())",
                [$seating_area_id, $total_amount, $net_amount, $paid_amount, $payment_method, $status]);
            
            $order_id = dbLastInsertId();
            
            foreach ($items as $item) {
                dbQuery("INSERT INTO orderitems (orderid, productid, qty, price, finalprice, status) 
                        VALUES (?, ?, ?, ?, ?, 'Pending')",
                    [$order_id, $item['product_id'], $item['qty'], $item['price'], $item['final_price']]);
            }
            
            if ($status === 'Paid') {
                dbQuery("INSERT INTO transactions (type, category, amount, description, referenceid, date) 
                        VALUES ('sale', 'Kiosk Sale', ?, 'Kiosk Order #' . ?, ?, NOW())",
                    [$net_amount, $order_id, $order_id]);
            }
            
            dbCommit();
            
            push_notification('Kitchen', "New Kiosk Order #$order_id received", $order_id, 'order');
            
            echo json_response(['success' => true, 'order_id' => $order_id]);
            break;

        case 'get_kiosk_seating':
            $stmt = dbQuery("SELECT id, name, type, capacity FROM seatingareas WHERE status = 'Free' ORDER BY name");
            $areas = dbFetchAll($stmt);
            echo json_response(['success' => true, 'areas' => $areas]);
            break;

        default:
            echo json_response(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        dbRollback();
    }
    error_log("Kiosk Action Error: " . $e->getMessage());
    echo json_response(['success' => false, 'message' => 'Server error']);
}
