<?php
/**
 * FeastFlow Pro - Main Router
 * Restaurant, POS, and Business Management SaaS
 * 
 * @version 1.0.0
 * @author FeastFlow Team
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path
define('BASE_PATH', __DIR__);
define('APP_URL', '/'); // Adjust based on your installation

// Load configuration
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Get current page/view from query parameter
$page = isset($_GET['page']) ? trim($_GET['page']) : 'dashboard';

// Public pages (no auth required)
$public_pages = ['login', 'kiosk_login', 'kiosk_view', 'qr_menu'];

// Check if user is logged in
$is_logged_in = is_logged_in();

// Redirect to login if not authenticated and not a public page
if (!$is_logged_in && !in_array($page, $public_pages)) {
    header('Location: ' . APP_URL . '?page=login');
    exit;
}

// Redirect to dashboard if already logged in and trying to access login
if ($is_logged_in && $page === 'login') {
    header('Location: ' . APP_URL . '?page=dashboard');
    exit;
}

// Determine which view to load
$view_file = BASE_PATH . '/views/' . $page . '.php';

// Handle AJAX requests separately
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // This is an AJAX request - handle in ajax/ directory
    $ajax_file = BASE_PATH . '/ajax/' . $page . '_actions.php';
    if (file_exists($ajax_file)) {
        require_once $ajax_file;
        exit;
    }
}

// For non-AJAX requests, render the full page
if (!in_array($page, $public_pages)) {
    // Include header and sidebar for authenticated pages
    require_once BASE_PATH . '/includes/header.php';
    require_once BASE_PATH . '/includes/sidebar.php';
    
    // Load the requested view
    if (file_exists($view_file)) {
        require_once $view_file;
    } else {
        // Show 404 error
        echo '<div class="container-fluid mt-4">';
        echo '<div class="alert alert-danger">';
        echo '<h4><i class="fas fa-exclamation-triangle"></i> Page Not Found</h4>';
        echo '<p>The requested page "' . htmlspecialchars($page) . '" does not exist.</p>';
        echo '</div></div>';
    }
    
    require_once BASE_PATH . '/includes/footer.php';
} else {
    // For public pages (login, kiosk, etc.), just load the view
    if (file_exists($view_file)) {
        require_once $view_file;
    } else {
        // Show 404 error
        echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body>';
        echo '<h1>Page Not Found</h1>';
        echo '<p>The requested page "' . htmlspecialchars($page) . '" does not exist.</p>';
        echo '</body></html>';
    }
}
