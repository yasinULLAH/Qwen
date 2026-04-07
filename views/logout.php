<?php
/**
 * Logout handler
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Perform logout
logout_user();

// Redirect to login
header('Location: ' . APP_URL . '?page=login&logout=1');
exit;
