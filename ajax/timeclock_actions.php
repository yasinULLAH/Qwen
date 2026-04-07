<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'clock_in':
            $pincode = sanitize_input($_POST['pincode']);
            
            $stmt = dbQuery("SELECT * FROM users WHERE pincode = ? AND role != 'Admin'", [$pincode]);
            $user = dbFetchOne($stmt);
            
            if (!$user) {
                echo json_response(['success' => false, 'message' => 'Invalid PIN code']);
                break;
            }
            
            $stmt = dbQuery("SELECT * FROM timeclock WHERE userid = ? AND status = 'Clocked In' ORDER BY clockin DESC LIMIT 1", [$user['id']]);
            $existing = dbFetchOne($stmt);
            
            if ($existing) {
                echo json_response(['success' => false, 'message' => 'Already clocked in']);
                break;
            }
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            
            dbQuery("INSERT INTO timeclock (userid, clockin, ipaddress, status) VALUES (?, NOW(), ?, 'Clocked In')", 
                [$user['id'], $ip_address]);
            
            audit_log($user['id'], 'clock_in', "Clocked in");
            
            echo json_response(['success' => true, 'user' => $user]);
            break;

        case 'clock_out':
            $pincode = sanitize_input($_POST['pincode']);
            
            $stmt = dbQuery("SELECT * FROM users WHERE pincode = ?", [$pincode]);
            $user = dbFetchOne($stmt);
            
            if (!$user) {
                echo json_response(['success' => false, 'message' => 'Invalid PIN code']);
                break;
            }
            
            $stmt = dbQuery("SELECT * FROM timeclock WHERE userid = ? AND status = 'Clocked In' ORDER BY clockin DESC LIMIT 1", [$user['id']]);
            $clock_in = dbFetchOne($stmt);
            
            if (!$clock_in) {
                echo json_response(['success' => false, 'message' => 'Not clocked in']);
                break;
            }
            
            dbQuery("UPDATE timeclock SET clockout = NOW(), status = 'Clocked Out' WHERE id = ?", [$clock_in['id']]);
            
            audit_log($user['id'], 'clock_out', "Clocked out");
            
            echo json_response(['success' => true]);
            break;

        case 'get_attendance':
            $user_id = (int)($_GET['user_id'] ?? 0);
            $start_date = sanitize_input($_GET['start_date'] ?? date('Y-m-01'));
            $end_date = sanitize_input($_GET['end_date'] ?? date('Y-m-d'));
            
            if ($user_id) {
                $stmt = dbQuery("SELECT t.*, u.name as user_name 
                                FROM timeclock t 
                                JOIN users u ON t.userid = u.id 
                                WHERE t.userid = ? AND DATE(t.clockin) BETWEEN ? AND ? 
                                ORDER BY t.clockin DESC", [$user_id, $start_date, $end_date]);
            } else {
                $stmt = dbQuery("SELECT t.*, u.name as user_name 
                                FROM timeclock t 
                                JOIN users u ON t.userid = u.id 
                                WHERE DATE(t.clockin) BETWEEN ? AND ? 
                                ORDER BY t.clockin DESC", [$start_date, $end_date]);
            }
            
            $records = dbFetchAll($stmt);
            echo json_response(['success' => true, 'records' => $records]);
            break;

        case 'get_current_status':
            $user_id = (int)$_GET['user_id'];
            
            $stmt = dbQuery("SELECT * FROM timeclock WHERE userid = ? AND status = 'Clocked In' ORDER BY clockin DESC LIMIT 1", [$user_id]);
            $current = dbFetchOne($stmt);
            
            if ($current) {
                echo json_response(['success' => true, 'status' => 'Clocked In', 'clock_in_time' => $current['clockin']]);
            } else {
                echo json_response(['success' => true, 'status' => 'Clocked Out']);
            }
            break;

        default:
            echo json_response(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Timeclock Action Error: " . $e->getMessage());
    echo json_response(['success' => false, 'message' => 'Server error']);
}
