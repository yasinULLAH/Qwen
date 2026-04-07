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
        case 'get_transactions':
            $type = sanitize_input($_GET['type'] ?? '');
            $start_date = sanitize_input($_GET['start_date'] ?? date('Y-m-01'));
            $end_date = sanitize_input($_GET['end_date'] ?? date('Y-m-d'));
            
            $sql = "SELECT * FROM transactions WHERE 1=1";
            $params = [];
            
            if ($type) {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            
            $sql .= " AND DATE(date) BETWEEN ? AND ? ORDER BY date DESC";
            $params[] = $start_date;
            $params[] = $end_date;
            
            $stmt = dbQuery($sql, $params);
            $transactions = dbFetchAll($stmt);
            echo json_response(['success' => true, 'transactions' => $transactions]);
            break;

        case 'add_transaction':
            $type = sanitize_input($_POST['type']);
            $category = sanitize_input($_POST['category']);
            $amount = (float)$_POST['amount'];
            $description = sanitize_input($_POST['description']);
            $reference_id = $_POST['referenceid'] ?: null;
            
            if (!in_array($type, ['income', 'expense', 'salary', 'debtpayment', 'sale', 'loyaltyredemption', 'refund'])) {
                echo json_response(['success' => false, 'message' => 'Invalid transaction type']);
                break;
            }
            
            dbQuery("INSERT INTO transactions (type, category, amount, description, referenceid, date) 
                    VALUES (?, ?, ?, ?, ?, NOW())",
                [$type, $category, $amount, $description, $reference_id]);
            
            $transaction_id = dbLastInsertId();
            audit_log(get_current_user()['id'], 'add_transaction', "Added $type transaction of $amount");
            
            echo json_response(['success' => true, 'transaction_id' => $transaction_id]);
            break;

        case 'get_employees':
            $stmt = dbQuery("SELECT * FROM employees ORDER BY name");
            $employees = dbFetchAll($stmt);
            echo json_response(['success' => true, 'employees' => $employees]);
            break;

        case 'process_salary':
            $employee_id = (int)$_POST['employee_id'];
            $amount = (float)$_POST['amount'];
            $month_year = sanitize_input($_POST['month_year']);
            
            $stmt = dbQuery("SELECT COUNT(*) as count FROM salarypayments WHERE employeeid = ? AND monthyear = ?", 
                [$employee_id, $month_year]);
            $existing = dbFetchOne($stmt);
            
            if ($existing['count'] > 0) {
                echo json_response(['success' => false, 'message' => 'Salary already paid for this month']);
                break;
            }
            
            dbBeginTransaction();
            
            dbQuery("INSERT INTO salarypayments (employeeid, amount, monthyear, date) 
                    VALUES (?, ?, ?, NOW())", [$employee_id, $amount, $month_year]);
            
            dbQuery("INSERT INTO transactions (type, category, amount, description, referenceid, date) 
                    VALUES ('salary', 'Employee Salary', ?, ?, ?, NOW())",
                [$amount, 'Salary payment for ' . $month_year, $employee_id]);
            
            dbCommit();
            
            audit_log(get_current_user()['id'], 'process_salary', "Processed salary of $amount for employee #$employee_id");
            
            echo json_response(['success' => true]);
            break;

        case 'get_salary_history':
            $employee_id = (int)$_GET['employee_id'] ?? null;
            
            if ($employee_id) {
                $stmt = dbQuery("SELECT sp.*, e.name as employee_name 
                                FROM salarypayments sp 
                                JOIN employees e ON sp.employeeid = e.id 
                                WHERE sp.employeeid = ? 
                                ORDER BY sp.date DESC", [$employee_id]);
            } else {
                $stmt = dbQuery("SELECT sp.*, e.name as employee_name 
                                FROM salarypayments sp 
                                JOIN employees e ON sp.employeeid = e.id 
                                ORDER BY sp.date DESC");
            }
            
            $payments = dbFetchAll($stmt);
            echo json_response(['success' => true, 'payments' => $payments]);
            break;

        case 'get_financial_summary':
            $start_date = sanitize_input($_GET['start_date'] ?? date('Y-m-01'));
            $end_date = sanitize_input($_GET['end_date'] ?? date('Y-m-d'));
            
            $income = dbQuery("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type IN ('income', 'sale') AND DATE(date) BETWEEN ? AND ?", [$start_date, $end_date]);
            $expense = dbQuery("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type IN ('expense', 'salary', 'refund') AND DATE(date) BETWEEN ? AND ?", [$start_date, $end_date]);
            $loyalty = dbQuery("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type = 'loyaltyredemption' AND DATE(date) BETWEEN ? AND ?", [$start_date, $end_date]);
            
            $income_total = dbFetchOne($income)['total'];
            $expense_total = dbFetchOne($expense)['total'];
            $loyalty_total = dbFetchOne($loyalty)['total'];
            
            echo json_response([
                'success' => true,
                'summary' => [
                    'total_income' => $income_total,
                    'total_expense' => $expense_total,
                    'loyalty_redeemed' => $loyalty_total,
                    'net_profit' => $income_total - $expense_total - $loyalty_total
                ]
            ]);
            break;

        default:
            echo json_response(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        dbRollback();
    }
    error_log("Finance Action Error: " . $e->getMessage());
    echo json_response(['success' => false, 'message' => 'Server error']);
}
