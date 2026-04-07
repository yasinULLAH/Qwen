<?php
/**
 * Authentication AJAX Actions
 * Handles login, logout, CAPTCHA refresh
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure JSON response
header('Content-Type: application/json');

// Get action
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    
    case 'logout':
        handleLogout();
        break;
    
    case 'refresh_captcha':
        handleRefreshCaptcha();
        break;
    
    default:
        json_response(false, 'Invalid action');
}

/**
 * Handle user login
 */
function handleLogin() {
    // Validate CSRF token
    if (!validate_csrf_token()) {
        json_response(false, 'Invalid security token. Please refresh and try again.');
    }
    
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $pincode = isset($_POST['pincode']) ? trim($_POST['pincode']) : '';
    $captcha_answer = isset($_POST['captcha_answer']) ? intval($_POST['captcha_answer']) : 0;
    $captcha_hash = isset($_POST['captcha_hash']) ? $_POST['captcha_hash'] : '';
    
    // Validate input
    if (empty($username)) {
        json_response(false, 'Username is required');
    }
    
    // Validate CAPTCHA
    if (!validate_captcha($captcha_answer, $captcha_hash)) {
        json_response(false, 'Incorrect CAPTCHA answer. Please try again.');
    }
    
    // Check if username or pincode is provided
    if (empty($username) && empty($pincode)) {
        json_response(false, 'Please provide username or PIN code');
    }
    
    try {
        $user = null;
        
        // Try login with pincode first if provided
        if (!empty($pincode)) {
            $stmt = dbQuery("SELECT * FROM users WHERE pincode = ? AND pincode IS NOT NULL LIMIT 1", [$pincode]);
            $user = dbFetchOne($stmt);
        }
        
        // If pincode didn't work or wasn't provided, try username/password
        if (!$user && !empty($username)) {
            $stmt = dbQuery("SELECT * FROM users WHERE username = ? LIMIT 1", [$username]);
            $user = dbFetchOne($stmt);
            
            if ($user && !verify_password($password, $user['password'])) {
                audit_log($user['id'], 'Failed Login', 'Invalid password attempt for username: ' . $username);
                json_response(false, 'Invalid password');
            }
        }
        
        if (!$user) {
            json_response(false, 'Invalid credentials. User not found.');
        }
        
        // Check if user is active (you can add an 'active' column later if needed)
        // For now, all users are considered active
        
        // Perform login
        login_user($user);
        
        // Log successful login
        audit_log($user['id'], 'Login', 'User logged in successfully from IP: ' . get_client_ip());
        
        // Regenerate CSRF token for security
        regenerate_csrf_token();
        
        json_response(true, 'Login successful', [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'redirect_url' => APP_URL . '?page=dashboard'
        ]);
        
    } catch (Exception $e) {
        json_response(false, 'An error occurred. Please try again.');
    }
}

/**
 * Handle user logout
 */
function handleLogout() {
    if (is_logged_in()) {
        $user = get_current_user();
        audit_log($user['id'], 'Logout', 'User logged out');
    }
    
    logout_user();
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        json_response(true, 'Logged out successfully');
    } else {
        header('Location: ' . APP_URL . '?page=login&logout=1');
        exit;
    }
}

/**
 * Refresh CAPTCHA
 */
function handleRefreshCaptcha() {
    $captcha = generate_math_captcha();
    
    json_response(true, 'CAPTCHA refreshed', [
        'question' => $captcha['question'],
        'hash' => $captcha['hash']
    ]);
}

/**
 * Get client IP address
 */
function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ipaddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    return $ipaddress;
}
