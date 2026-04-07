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
        case 'get_products':
            $category_id = $_GET['category_id'] ?? null;
            if ($category_id) {
                $stmt = dbQuery("SELECT p.*, c.name as category_name 
                                FROM products p 
                                LEFT JOIN categories c ON p.categoryid = c.id 
                                WHERE p.categoryid = ? 
                                ORDER BY p.name", [$category_id]);
            } else {
                $stmt = dbQuery("SELECT p.*, c.name as category_name 
                                FROM products p 
                                LEFT JOIN categories c ON p.categoryid = c.id 
                                ORDER BY c.name, p.name");
            }
            $products = dbFetchAll($stmt);
            echo json_response(['success' => true, 'products' => $products]);
            break;

        case 'get_categories':
            $stmt = dbQuery("SELECT * FROM categories ORDER BY name");
            $categories = dbFetchAll($stmt);
            echo json_response(['success' => true, 'categories' => $categories]);
            break;

        case 'add_to_cart':
            $product_id = (int)$_POST['product_id'];
            $qty = (int)$_POST['qty'];
            $price = (float)$_POST['price'];
            $notes = sanitize_input($_POST['notes'] ?? '');
            $course = sanitize_input($_POST['course'] ?? 'Main');
            
            $stmt = dbQuery("SELECT * FROM products WHERE id = ?", [$product_id]);
            $product = dbFetchOne($stmt);
            
            if (!$product) {
                echo json_response(['success' => false, 'message' => 'Product not found']);
                break;
            }
            
            $final_price = $price * $qty;
            echo json_response([
                'success' => true, 
                'item' => [
                    'product_id' => $product_id,
                    'name' => $product['name'],
                    'qty' => $qty,
                    'price' => $price,
                    'final_price' => $final_price,
                    'notes' => $notes,
                    'course' => $course
                ]
            ]);
            break;

        case 'create_order':
            $seating_area_id = $_POST['seating_area_id'] ?: null;
            $customer_id = $_POST['customer_id'] ?: null;
            $total_amount = (float)$_POST['total_amount'];
            $discount_percentage = (float)($_POST['discount_percentage'] ?? 0);
            $discount_amount = (float)($_POST['discount_amount'] ?? 0);
            $net_amount = (float)$_POST['net_amount'];
            $paid_amount = (float)$_POST['paid_amount'];
            $payment_method = sanitize_input($_POST['payment_method'] ?? 'Cash');
            $is_qr_order = (int)($_POST['is_qr_order'] ?? 0);
            $is_online_order = (int)($_POST['is_online_order'] ?? 0);
            $items = json_decode($_POST['items'], true);
            
            if (empty($items)) {
                echo json_response(['success' => false, 'message' => 'No items in order']);
                break;
            }
            
            $status = 'Pending';
            if ($paid_amount >= $net_amount) {
                $status = 'Paid';
            } elseif ($paid_amount > 0) {
                $status = 'Credit';
            }
            
            dbBeginTransaction();
            
            $stmt = dbQuery("INSERT INTO orders (seatingareaid, customerid, userid, totalamount, discountpercentage, discountamount, netamount, paidamount, paymentmethod, status, isqrorder, isonlineorder, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$seating_area_id, $customer_id, get_current_user()['id'], $total_amount, $discount_percentage, $discount_amount, $net_amount, $paid_amount, $payment_method, $status, $is_qr_order, $is_online_order]);
            
            $order_id = dbLastInsertId();
            
            foreach ($items as $item) {
                dbQuery("INSERT INTO orderitems (orderid, productid, qty, price, finalprice, course, notes, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')",
                    [$order_id, $item['product_id'], $item['qty'], $item['price'], $item['final_price'], $item['course'] ?? 'Main', $item['notes'] ?? '']);
            }
            
            if ($status === 'Paid') {
                dbQuery("INSERT INTO transactions (type, category, amount, description, referenceid, date) 
                        VALUES ('sale', 'POS Sale', ?, 'Order #' . ?, ?, NOW())",
                    [$net_amount, $order_id, $order_id]);
                
                if ($customer_id) {
                    $loyalty_points = floor($net_amount / 100);
                    if ($loyalty_points > 0) {
                        dbQuery("UPDATE customers SET loyaltypoints = loyaltypoints + ? WHERE id = ?", [$loyalty_points, $customer_id]);
                        push_notification('Admin', "Customer earned $loyalty_points loyalty points on Order #$order_id", $order_id, 'loyalty');
                    }
                }
            } elseif ($status === 'Credit' && $customer_id) {
                $balance_due = $net_amount - $paid_amount;
                dbQuery("UPDATE customers SET balance = balance + ? WHERE id = ?", [$balance_due, $customer_id]);
            }
            
            dbCommit();
            
            audit_log(get_current_user()['id'], 'create_order', "Created order #$order_id for $net_amount");
            push_notification('Kitchen', "New Order #$order_id received with " . count($items) . " items", $order_id, 'order');
            
            echo json_response(['success' => true, 'order_id' => $order_id, 'status' => $status]);
            break;

        case 'update_order_status':
            $order_id = (int)$_POST['order_id'];
            $status = sanitize_input($_POST['status']);
            
            $valid_statuses = ['Pending', 'Cooking', 'Served', 'Paid', 'Cancelled', 'Credit'];
            if (!in_array($status, $valid_statuses)) {
                echo json_response(['success' => false, 'message' => 'Invalid status']);
                break;
            }
            
            dbQuery("UPDATE orders SET status = ? WHERE id = ?", [$status, $order_id]);
            audit_log(get_current_user()['id'], 'update_order_status', "Updated order #$order_id status to $status");
            
            echo json_response(['success' => true]);
            break;

        case 'get_order_details':
            $order_id = (int)$_GET['order_id'];
            $stmt = dbQuery("SELECT o.*, s.name as seating_name, c.name as customer_name 
                            FROM orders o 
                            LEFT JOIN seatingareas s ON o.seatingareaid = s.id 
                            LEFT JOIN customers c ON o.customerid = c.id 
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

        case 'apply_discount':
            $product_id = (int)$_POST['product_id'];
            $stmt = dbQuery("SELECT * FROM productdiscounts 
                            WHERE productid = ? 
                            AND startdate <= CURDATE() 
                            AND enddate >= CURDATE() 
                            ORDER BY discountpercentage DESC LIMIT 1", [$product_id]);
            $discount = dbFetchOne($stmt);
            
            if ($discount) {
                echo json_response(['success' => true, 'discount' => $discount]);
            } else {
                echo json_response(['success' => true, 'discount' => null]);
            }
            break;

        case 'redeem_loyalty':
            $customer_id = (int)$_POST['customer_id'];
            $points_to_redeem = (int)$_POST['points_to_redeem'];
            
            $stmt = dbQuery("SELECT loyaltypoints FROM customers WHERE id = ?", [$customer_id]);
            $customer = dbFetchOne($stmt);
            
            if (!$customer || $customer['loyaltypoints'] < $points_to_redeem) {
                echo json_response(['success' => false, 'message' => 'Insufficient loyalty points']);
                break;
            }
            
            $discount_amount = $points_to_redeem;
            dbQuery("UPDATE customers SET loyaltypoints = loyaltypoints - ? WHERE id = ?", [$points_to_redeem, $customer_id]);
            dbQuery("INSERT INTO transactions (type, category, amount, description, referenceid, date) 
                    VALUES ('loyaltyredemption', 'Loyalty Points', ?, 'Redeemed ' . ? . ' points', ?, NOW())",
                [$discount_amount, $points_to_redeem, $customer_id]);
            
            echo json_response(['success' => true, 'discount_amount' => $discount_amount]);
            break;

        default:
            echo json_response(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        dbRollback();
    }
    error_log("POS Action Error: " . $e->getMessage());
    echo json_response(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
