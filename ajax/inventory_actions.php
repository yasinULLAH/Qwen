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
        case 'get_ingredients':
            $stmt = dbQuery("SELECT * FROM ingredients ORDER BY name");
            $ingredients = dbFetchAll($stmt);
            echo json_response(['success' => true, 'ingredients' => $ingredients]);
            break;

        case 'get_low_stock':
            $threshold = (int)($_GET['threshold'] ?? 10);
            $stmt = dbQuery("SELECT * FROM ingredients WHERE stocklevel < ? ORDER BY stocklevel ASC", [$threshold]);
            $ingredients = dbFetchAll($stmt);
            echo json_response(['success' => true, 'ingredients' => $ingredients]);
            break;

        case 'update_stock':
            $ingredient_id = (int)$_POST['ingredient_id'];
            $new_stock = (float)$_POST['stock_level'];
            
            dbQuery("UPDATE ingredients SET stocklevel = ? WHERE id = ?", [$new_stock, $ingredient_id]);
            audit_log(get_current_user()['id'], 'update_stock', "Updated ingredient #$ingredient_id stock to $new_stock");
            
            echo json_response(['success' => true]);
            break;

        case 'log_wastage':
            $item_type = sanitize_input($_POST['item_type']);
            $item_id = (int)$_POST['item_id'];
            $quantity = (float)$_POST['quantity'];
            $reason = sanitize_input($_POST['reason']);
            
            if (!in_array($item_type, ['product', 'ingredient'])) {
                echo json_response(['success' => false, 'message' => 'Invalid item type']);
                break;
            }
            
            dbBeginTransaction();
            
            dbQuery("INSERT INTO wastagelog (itemtype, itemid, quantity, reason, loggedby, timestamp) 
                    VALUES (?, ?, ?, ?, ?, NOW())",
                [$item_type, $item_id, $quantity, $reason, get_current_user()['id']]);
            
            if ($item_type === 'ingredient') {
                dbQuery("UPDATE ingredients SET stocklevel = stocklevel - ? WHERE id = ?", [$quantity, $item_id]);
            }
            
            dbCommit();
            
            audit_log(get_current_user()['id'], 'log_wastage', "Logged wastage: $quantity of $item_type #$item_id");
            
            echo json_response(['success' => true]);
            break;

        case 'get_product_recipe':
            $product_id = (int)$_GET['product_id'];
            
            $stmt = dbQuery("SELECT pi.*, i.name as ingredient_name, i.unitofmeasure, i.stocklevel 
                            FROM productingredients pi 
                            JOIN ingredients i ON pi.ingredientid = i.id 
                            WHERE pi.productid = ?", [$product_id]);
            $ingredients = dbFetchAll($stmt);
            
            echo json_response(['success' => true, 'ingredients' => $ingredients]);
            break;

        case 'deduct_inventory':
            $order_id = (int)$_POST['order_id'];
            
            $stmt = dbQuery("SELECT oi.productid, oi.qty FROM orderitems oi WHERE oi.orderid = ?", [$order_id]);
            $items = dbFetchAll($stmt);
            
            dbBeginTransaction();
            
            foreach ($items as $item) {
                $stmt = dbQuery("SELECT pi.ingredientid, pi.quantityneeded FROM productingredients pi WHERE pi.productid = ?", [$item['productid']]);
                $recipe = dbFetchAll($stmt);
                
                foreach ($recipe as $ingredient) {
                    $total_needed = $ingredient['quantityneeded'] * $item['qty'];
                    dbQuery("UPDATE ingredients SET stocklevel = stocklevel - ? WHERE id = ?", [$total_needed, $ingredient['ingredientid']]);
                    
                    $stmt = dbQuery("SELECT stocklevel FROM ingredients WHERE id = ?", [$ingredient['ingredientid']]);
                    $current_stock = dbFetchOne($stmt);
                    
                    if ($current_stock['stocklevel'] < 5) {
                        push_notification('Admin', "Low stock alert: Ingredient #" . $ingredient['ingredientid'] . " is below threshold", $ingredient['ingredientid'], 'inventory');
                    }
                }
            }
            
            dbCommit();
            
            echo json_response(['success' => true]);
            break;

        default:
            echo json_response(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        dbRollback();
    }
    error_log("Inventory Action Error: " . $e->getMessage());
    echo json_response(['success' => false, 'message' => 'Server error']);
}
