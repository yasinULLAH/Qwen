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
        case 'get_settings':
            $stmt = dbQuery("SELECT * FROM settings");
            $settings = dbFetchAll($stmt);
            $settings_array = [];
            foreach ($settings as $setting) {
                $settings_array[$setting['setting_key']] = $setting['setting_value'];
            }
            echo json_response(['success' => true, 'settings' => $settings_array]);
            break;

        case 'update_setting':
            $key = sanitize_input($_POST['setting_key']);
            $value = sanitize_input($_POST['setting_value']);
            
            $existing = dbQuery("SELECT id FROM settings WHERE setting_key = ?", [$key]);
            if (dbFetchOne($existing)) {
                dbQuery("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
            } else {
                dbQuery("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)", [$key, $value]);
            }
            
            audit_log(get_current_user()['id'], 'update_setting', "Updated setting: $key");
            echo json_response(['success' => true]);
            break;

        case 'backup_database':
            check_permission(['Admin']);
            
            $tables = [];
            $stmt = dbQuery("SHOW TABLES");
            while ($row = dbFetchOne($stmt, PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            $sql = "-- FeastFlow Pro Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                $stmt = dbQuery("SHOW CREATE TABLE `$table`");
                $create = dbFetchOne($stmt);
                $sql .= $create['Create Table'] . ";\n\n";
                
                $stmt = dbQuery("SELECT * FROM `$table`");
                $rows = dbFetchAll($stmt);
                
                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $values = array_map(function($val) {
                            return $val === null ? 'NULL' : "'" . addslashes($val) . "'";
                        }, array_values($row));
                        $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            }
            
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            header('Content-Type: text/sql');
            header('Content-Disposition: attachment; filename="feastflow_backup_' . date('Y-m-d_H-i-s') . '.sql"');
            echo $sql;
            exit;

        case 'restore_database':
            check_permission(['Admin']);
            
            if (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_response(['success' => false, 'message' => 'No file uploaded']);
                break;
            }
            
            $file_content = file_get_contents($_FILES['sql_file']['tmp_name']);
            
            if (strpos($file_content, 'FeastFlow Pro') === false && strpos($file_content, 'CREATE TABLE') === false) {
                echo json_response(['success' => false, 'message' => 'Invalid SQL file']);
                break;
            }
            
            $statements = array_filter(array_map('trim', explode(';', $file_content)));
            
            dbBeginTransaction();
            
            try {
                foreach ($statements as $statement) {
                    if (empty($statement) || strpos($statement, '--') === 0) continue;
                    dbQuery($statement);
                }
                dbCommit();
                audit_log(get_current_user()['id'], 'restore_database', "Database restored from backup");
                echo json_response(['success' => true, 'message' => 'Database restored successfully']);
            } catch (Exception $e) {
                dbRollback();
                throw $e;
            }
            break;

        default:
            echo json_response(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        dbRollback();
    }
    error_log("Settings Action Error: " . $e->getMessage());
    echo json_response(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
