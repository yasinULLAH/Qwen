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
        case 'get_customers_with_balance':
            $stmt = dbQuery("SELECT * FROM customers WHERE balance != 0 ORDER BY balance DESC");
            $customers = dbFetchAll($stmt);
            echo json_response(['success' => true, 'customers' => $customers]);
            break;

        case 'get_customer_ledger':
            $customer_id = (int)$_GET['customer_id'];
            
            $stmt = dbQuery("SELECT * FROM customers WHERE id = ?", [$customer_id]);
            $customer = dbFetchOne($stmt);
            
            if (!$customer) {
                echo json_response(['success' => false, 'message' => 'Customer not found']);
                break;
            }
            
            $transactions = [];
            
            $stmt = dbQuery("SELECT o.id, o.totalamount, o.paidamount, o.netamount, o.created_at, o.status 
                            FROM orders o 
                            WHERE o.customerid = ? AND o.status IN ('Credit', 'Paid') 
                            ORDER BY o.created_at ASC", [$customer_id]);
            $orders = dbFetchAll($stmt);
            
            $running_balance = 0;
            foreach ($orders as $order) {
                if ($order['status'] === 'Credit') {
                    $balance_due = $order['netamount'] - $order['paidamount'];
                    $running_balance += $balance_due;
                    $transactions[] = [
                        'type' => 'order',
                        'description' => 'Order #' . $order['id'],
                        'amount' => $balance_due,
                        'balance' => $running_balance,
                        'date' => $order['created_at'],
                        'reference' => 'order_' . $order['id']
                    ];
                } else {
                    $transactions[] = [
                        'type' => 'payment',
                        'description' => 'Order #' . $order['id'] . ' (Paid in full)',
                        'amount' => 0,
                        'balance' => $running_balance,
                        'date' => $order['created_at'],
                        'reference' => 'order_' . $order['id']
                    ];
                }
            }
            
            $stmt = dbQuery("SELECT t.* FROM transactions t 
                            WHERE t.type = 'debtpayment' AND t.referenceid = ? 
                            ORDER BY t.date ASC", [$customer_id]);
            $payments = dbFetchAll($stmt);
            
            foreach ($payments as $payment) {
                $running_balance -= $payment['amount'];
                $transactions[] = [
                    'type' => 'payment',
                    'description' => 'Debt Payment',
                    'amount' => -$payment['amount'],
                    'balance' => $running_balance,
                    'date' => $payment['date'],
                    'reference' => 'payment_' . $payment['id']
                ];
            }
            
            usort($transactions, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });
            
            $running_balance = 0;
            foreach ($transactions as &$transaction) {
                if ($transaction['type'] === 'order') {
                    $running_balance += $transaction['amount'];
                } else {
                    $running_balance += $transaction['amount'];
                }
                $transaction['balance'] = $running_balance;
            }
            
            echo json_response([
                'success' => true, 
                'customer' => $customer,
                'transactions' => $transactions,
                'current_balance' => $customer['balance']
            ]);
            break;

        case 'make_payment':
            $customer_id = (int)$_POST['customer_id'];
            $amount = (float)$_POST['amount'];
            $payment_method = sanitize_input($_POST['payment_method'] ?? 'Cash');
            $notes = sanitize_input($_POST['notes'] ?? '');
            
            if ($amount <= 0) {
                echo json_response(['success' => false, 'message' => 'Invalid amount']);
                break;
            }
            
            $stmt = dbQuery("SELECT balance FROM customers WHERE id = ?", [$customer_id]);
            $customer = dbFetchOne($stmt);
            
            if (!$customer || $customer['balance'] <= 0) {
                echo json_response(['success' => false, 'message' => 'No outstanding balance']);
                break;
            }
            
            $payment_amount = min($amount, $customer['balance']);
            
            dbBeginTransaction();
            
            dbQuery("UPDATE customers SET balance = balance - ? WHERE id = ?", [$payment_amount, $customer_id]);
            
            dbQuery("INSERT INTO transactions (type, category, amount, description, referenceid, date) 
                    VALUES ('debtpayment', 'Customer Payment', ?, ?, ?, NOW())",
                [$payment_amount, $notes . ' - Khata Payment', $customer_id]);
            
            dbCommit();
            
            audit_log(get_current_user()['id'], 'khata_payment', "Received payment of $payment_amount from customer #$customer_id");
            
            echo json_response(['success' => true, 'amount_received' => $payment_amount, 'remaining_balance' => $customer['balance'] - $payment_amount]);
            break;

        case 'add_opening_balance':
            $customer_id = (int)$_POST['customer_id'];
            $amount = (float)$_POST['amount'];
            $notes = sanitize_input($_POST['notes'] ?? 'Opening balance');
            
            dbBeginTransaction();
            
            dbQuery("UPDATE customers SET balance = balance + ? WHERE id = ?", [$amount, $customer_id]);
            
            dbQuery("INSERT INTO transactions (type, category, amount, description, referenceid, date) 
                    VALUES ('expense', 'Opening Balance', ?, ?, ?, NOW())",
                [$amount, $notes, $customer_id]);
            
            dbCommit();
            
            audit_log(get_current_user()['id'], 'opening_balance', "Added opening balance of $amount for customer #$customer_id");
            
            echo json_response(['success' => true]);
            break;

        default:
            echo json_response(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        dbRollback();
    }
    error_log("Khata Action Error: " . $e->getMessage());
    echo json_response(['success' => false, 'message' => 'Server error']);
}
