<?php
/**
 * Core Utility Functions
 * Restaurant POS & Business Management SaaS
 * 
 * Contains: CSRF protection, CAPTCHA, Authentication, Authorization,
 * Audit Logging, Notifications, and other helper functions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout in seconds (30 minutes)
define('SESSION_TIMEOUT', 1800);

/**
 * ============================================
 * CSRF TOKEN FUNCTIONS
 * ============================================
 */

/**
 * Generate a new CSRF token and store it in session
 * @return string The generated CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token against the session token
 * @param string $token The token to validate
 * @return bool True if valid, false otherwise
 */
function validate_csrf($token) {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerate CSRF token (use after form submission)
 * @return string New CSRF token
 */
function regenerate_csrf_token() {
    unset($_SESSION['csrf_token']);
    return generate_csrf_token();
}

/**
 * Get CSRF token as hidden input field
 * @return string HTML hidden input field
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * ============================================
 * MATH CAPTCHA FUNCTIONS
 * ============================================
 */

/**
 * Generate a math CAPTCHA challenge and store answer in session
 * @return array ['question' => string, 'hash' => string]
 */
function generate_math_captcha() {
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $operators = ['+', '-', '*'];
    $operator = $operators[array_rand($operators)];
    
    // Calculate correct answer
    switch ($operator) {
        case '+':
            $answer = $num1 + $num2;
            $question = "$num1 + $num2 = ?";
            break;
        case '-':
            // Ensure positive result
            if ($num1 < $num2) {
                $temp = $num1;
                $num1 = $num2;
                $num2 = $temp;
            }
            $answer = $num1 - $num2;
            $question = "$num1 - $num2 = ?";
            break;
        case '*':
            $num1 = rand(1, 5);
            $num2 = rand(1, 5);
            $answer = $num1 * $num2;
            $question = "$num1 × $num2 = ?";
            break;
    }
    
    // Store hashed answer in session
    $_SESSION['captcha_answer'] = password_hash((string)$answer, PASSWORD_DEFAULT);
    $_SESSION['captcha_time'] = time();
    
    return [
        'question' => $question,
        'hash' => $_SESSION['captcha_answer']
    ];
}

/**
 * Validate CAPTCHA answer
 * @param string $answer The user's answer
 * @return bool True if correct, false otherwise
 */
function validate_captcha($answer) {
    if (empty($_SESSION['captcha_answer']) || empty($answer)) {
        return false;
    }
    
    // Check if captcha has expired (5 minutes)
    if (isset($_SESSION['captcha_time']) && (time() - $_SESSION['captcha_time']) > 300) {
        unset($_SESSION['captcha_answer']);
        unset($_SESSION['captcha_time']);
        return false;
    }
    
    $valid = password_verify((string)$answer, $_SESSION['captcha_answer']);
    
    // Clear captcha after validation attempt
    unset($_SESSION['captcha_answer']);
    unset($_SESSION['captcha_time']);
    
    return $valid;
}

/**
 * ============================================
 * AUTHENTICATION FUNCTIONS
 * ============================================
 */

/**
 * Check if user is logged in
 * @return bool True if logged in, false otherwise
 */
function is_logged_in() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Get current logged-in user ID
 * @return int|false User ID or false if not logged in
 */
function get_current_user_id() {
    return is_logged_in() ? $_SESSION['user_id'] : false;
}

/**
 * Get current logged-in user role
 * @return string|false User role or false if not logged in
 */
function get_current_user_role() {
    return is_logged_in() ? $_SESSION['user_role'] : false;
}

/**
 * Get current logged-in user info
 * @return array|false User data or false if not logged in
 */
function get_current_user() {
    if (!is_logged_in()) {
        return false;
    }
    
    require_once __DIR__ . '/../config/database.php';
    
    $sql = "SELECT id, name, username, role, pincode, is_active FROM users WHERE id = ?";
    $user = dbFetchOne($sql, [$_SESSION['user_id']]);
    
    if (!$user || !$user['is_active']) {
        logout_user();
        return false;
    }
    
    return $user;
}

/**
 * Login user with username and password
 * @param string $username Username
 * @param string $password Plain text password
 * @return array ['success' => bool, 'message' => string, 'role' => string|null]
 */
function login_user($username, $password) {
    require_once __DIR__ . '/../config/database.php';
    
    $sql = "SELECT id, name, username, password, role, pincode, is_active FROM users WHERE username = ?";
    $user = dbFetchOne($sql, [$username]);
    
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    if (!$user['is_active']) {
        return ['success' => false, 'message' => 'Account is deactivated. Contact administrator.'];
    }
    
    if (!password_verify($password, $user['password'])) {
        // Log failed login attempt
        audit_log($user['id'], 'Failed Login', 'Failed login attempt for username: ' . $username);
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['last_activity'] = time();
    $_SESSION['login_time'] = time();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Update last login timestamp
    $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
    dbQuery($updateSql, [$user['id']]);
    
    // Log successful login
    audit_log($user['id'], 'Login', 'User logged in successfully');
    
    return ['success' => true, 'message' => 'Login successful', 'role' => $user['role']];
}

/**
 * Logout current user
 */
function logout_user() {
    if (is_logged_in()) {
        audit_log($_SESSION['user_id'], 'Logout', 'User logged out');
    }
    
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * ============================================
 * AUTHORIZATION / RBAC FUNCTIONS
 * ============================================
 */

/**
 * Check if current user has permission (role-based)
 * @param string|array $roles Single role or array of allowed roles
 * @return bool True if authorized, false otherwise
 */
function check_permission($roles) {
    if (!is_logged_in()) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'];
    
    // Admin has access to everything
    if ($userRole === 'Admin') {
        return true;
    }
    
    // Convert single role to array
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    return in_array($userRole, $roles);
}

/**
 * Require specific permission or die
 * @param string|array $roles Allowed roles
 */
function require_permission($roles) {
    if (!check_permission($roles)) {
        http_response_code(403);
        if (is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => 'Access denied. Insufficient permissions.']);
        } else {
            echo '<div class="alert alert-danger">Access Denied: You do not have permission to access this page.</div>';
        }
        exit;
    }
}

/**
 * Require user to be logged in or redirect
 * @param string $redirect_url URL to redirect if not logged in
 */
function require_login($redirect_url = '/views/login.php') {
    if (!is_logged_in()) {
        if (is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
            exit;
        }
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Role hierarchy (higher number = more permissions)
 * @return array Role hierarchy
 */
function get_role_hierarchy() {
    return [
        'Kitchen' => 1,
        'Server' => 2,
        'Counter' => 3,
        'Accountant' => 4,
        'Admin' => 5
    ];
}

/**
 * Check if user role can access another role's features
 * @param string $targetRole The role to check access for
 * @return bool True if accessible
 */
function can_access_role($targetRole) {
    if (!is_logged_in()) {
        return false;
    }
    
    $hierarchy = get_role_hierarchy();
    $userRole = $_SESSION['user_role'];
    
    if (!isset($hierarchy[$userRole]) || !isset($hierarchy[$targetRole])) {
        return false;
    }
    
    return $hierarchy[$userRole] >= $hierarchy[$targetRole];
}

/**
 * ============================================
 * AUDIT LOGGING FUNCTIONS
 * ============================================
 */

/**
 * Log an action to the audit log
 * @param int|null $userId User ID (null for system actions)
 * @param string $action Action performed
 * @param string $details Additional details
 * @return int|false Insert ID or false on failure
 */
function audit_log($userId, $action, $details = '') {
    require_once __DIR__ . '/../config/database.php';
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $sql = "INSERT INTO auditlogs (userid, action, details, ipaddress, useragent) VALUES (?, ?, ?, ?, ?)";
    
    $stmt = dbQuery($sql, [$userId, $action, $details, $ipAddress, $userAgent]);
    
    if ($stmt) {
        return dbLastInsertId();
    }
    
    return false;
}

/**
 * Get recent audit logs
 * @param int $limit Number of records to fetch
 * @param int|null $userId Filter by user ID
 * @return array Array of audit log entries
 */
function get_audit_logs($limit = 50, $userId = null) {
    require_once __DIR__ . '/../config/database.php';
    
    if ($userId) {
        $sql = "SELECT a.*, u.name as username FROM auditlogs a 
                LEFT JOIN users u ON a.userid = u.id 
                WHERE a.userid = ? 
                ORDER BY a.timestamp DESC LIMIT ?";
        return dbFetchAll($sql, [$userId, $limit]);
    }
    
    $sql = "SELECT a.*, u.name as username FROM auditlogs a 
            LEFT JOIN users u ON a.userid = u.id 
            ORDER BY a.timestamp DESC LIMIT ?";
    return dbFetchAll($sql, [$limit]);
}

/**
 * ============================================
 * NOTIFICATION FUNCTIONS
 * ============================================
 */

/**
 * Push a notification to users
 * @param string $recipientRole Target role ('all' for everyone)
 * @param string $message Notification message
 * @param int|null $relatedId Related record ID
 * @param string $relatedType Type of related record (order, customer, etc.)
 * @param string $type Notification type (info, warning, success, error)
 * @param int|null $recipientUserId Specific user ID (optional)
 * @return int|false Insert ID or false on failure
 */
function push_notification($recipientRole, $message, $relatedId = null, $relatedType = null, $type = 'info', $recipientUserId = null) {
    require_once __DIR__ . '/../config/database.php';
    
    $sql = "INSERT INTO notifications (recipientrole, recipientuserid, message, relatedid, relatedtype, type) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = dbQuery($sql, [$recipientRole, $recipientUserId, $message, $relatedId, $relatedType, $type]);
    
    if ($stmt) {
        return dbLastInsertId();
    }
    
    return false;
}

/**
 * Get unread notifications for current user
 * @return array Array of notifications
 */
function get_unread_notifications() {
    require_once __DIR__ . '/../config/database.php';
    
    if (!is_logged_in()) {
        return [];
    }
    
    $userRole = $_SESSION['user_role'];
    $userId = $_SESSION['user_id'];
    
    // Get notifications for specific user or role or all
    $sql = "SELECT * FROM notifications 
            WHERE isread = 0 
            AND (recipientuserid = ? OR recipientrole = ? OR recipientrole = 'all') 
            ORDER BY created_at DESC 
            LIMIT 50";
    
    return dbFetchAll($sql, [$userId, $userRole]);
}

/**
 * Mark notification as read
 * @param int $notificationId Notification ID
 * @return bool Success status
 */
function mark_notification_read($notificationId) {
    require_once __DIR__ . '/../config/database.php';
    
    $sql = "UPDATE notifications SET isread = 1 WHERE id = ?";
    $stmt = dbQuery($sql, [$notificationId]);
    
    return $stmt !== false;
}

/**
 * Mark all notifications as read for current user
 * @return bool Success status
 */
function mark_all_notifications_read() {
    require_once __DIR__ . '/../config/database.php';
    
    if (!is_logged_in()) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'];
    $userId = $_SESSION['user_id'];
    
    $sql = "UPDATE notifications SET isread = 1 
            WHERE (recipientuserid = ? OR recipientrole = ? OR recipientrole = 'all') 
            AND isread = 0";
    
    $stmt = dbQuery($sql, [$userId, $userRole]);
    
    return $stmt !== false;
}

/**
 * Get notification count
 * @return int Unread notification count
 */
function get_notification_count() {
    require_once __DIR__ . '/../config/database.php';
    
    if (!is_logged_in()) {
        return 0;
    }
    
    $userRole = $_SESSION['user_role'];
    $userId = $_SESSION['user_id'];
    
    $sql = "SELECT COUNT(*) as count FROM notifications 
            WHERE isread = 0 
            AND (recipientuserid = ? OR recipientrole = ? OR recipientrole = 'all')";
    
    $result = dbFetchOne($sql, [$userId, $userRole]);
    
    return $result ? (int)$result['count'] : 0;
}

/**
 * ============================================
 * HELPER / UTILITY FUNCTIONS
 * ============================================
 */

/**
 * Check if request is AJAX
 * @return bool True if AJAX request
 */
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Sanitize input data
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Format currency amount
 * @param float $amount Amount to format
 * @return string Formatted currency string
 */
function format_currency($amount) {
    require_once __DIR__ . '/../config/database.php';
    
    // Get currency symbol from settings
    $setting = dbFetchOne("SELECT setting_value FROM settings WHERE setting_key = 'currency_symbol'");
    $symbol = $setting ? $setting['setting_value'] : '$';
    
    return $symbol . ' ' . number_format($amount, 2);
}

/**
 * Format date for display
 * @param string $date Date string
 * @param string $format Output format
 * @return string Formatted date
 */
function format_date($date, $format = 'M d, Y h:i A') {
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Generate unique order number
 * @return string Order number
 */
function generate_order_number() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Calculate age from birthdate
 * @param string $birthdate Birthdate in Y-m-d format
 * @return int Age in years
 */
function calculate_age($birthdate) {
    $birth = new DateTime($birthdate);
    $today = new DateTime();
    $diff = $today->diff($birth);
    return $diff->y;
}

/**
 * Get setting value by key
 * @param string $key Setting key
 * @param mixed $default Default value if not found
 * @return mixed Setting value or default
 */
function get_setting($key, $default = null) {
    require_once __DIR__ . '/../config/database.php';
    
    $sql = "SELECT setting_value FROM settings WHERE setting_key = ?";
    $result = dbFetchOne($sql, [$key]);
    
    return $result ? $result['setting_value'] : $default;
}

/**
 * Update setting value
 * @param string $key Setting key
 * @param string $value New value
 * @return bool Success status
 */
function update_setting($key, $value) {
    require_once __DIR__ . '/../config/database.php';
    
    // Check if exists
    $exists = dbFetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
    
    if ($exists) {
        $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
        $stmt = dbQuery($sql, [$value, $key]);
    } else {
        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)";
        $stmt = dbQuery($sql, [$key, $value]);
    }
    
    return $stmt !== false;
}

/**
 * Redirect with message
 * @param string $url URL to redirect to
 * @param string $message Message to display
 * @param string $type Message type (success, error, warning, info)
 */
function redirect_with_message($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header('Location: ' . $url);
    exit;
}

/**
 * Get and clear flash message
 * @return array|null Flash message array or null
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return $message;
    }
    return null;
}

/**
 * JSON response helper for AJAX
 * @param bool $success Success status
 * @param string $message Response message
 * @param mixed $data Additional data
 */
function json_response($success, $message = '', $data = null) {
    header('Content-Type: application/json');
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Validate email format
 * @param string $email Email to validate
 * @return bool True if valid
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (basic validation)
 * @param string $phone Phone number to validate
 * @return bool True if valid
 */
function is_valid_phone($phone) {
    // Remove common separators
    $clean = preg_replace('/[\s\-\(\)]/', '', $phone);
    // Check if it contains only digits and optionally + at start
    return preg_match('/^\+?\d{7,15}$/', $clean) === 1;
}

/**
 * Generate QR code data URL (simple implementation)
 * Note: For production, use a proper QR code library like phpqrcode
 * @param string $data Data to encode
 * @return string Placeholder message
 */
function generate_qr_code($data) {
    // This is a placeholder - implement with actual QR library
    // Recommended: https://github.com/chillerlan/php-qrcode
    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
}

/**
 * Get client IP address
 * @return string IP address
 */
function get_client_ip() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    return $ip;
}

/**
 * Secure password hash
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 * @param string $password Plain text password
 * @param string $hash Password hash
 * @return bool True if matches
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}
?>
