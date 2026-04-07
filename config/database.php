<?php
/**
 * Database Configuration
 * PDO Connection Setup for Restaurant POS & Business Management SaaS
 */

// Database credentials - Update these for your environment
define('DB_HOST', 'localhost');
define('DB_NAME', 'restaurant_pos');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// PDO Options for security and error handling
$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Default fetch as associative array
    PDO::ATTR_EMULATE_PREPARES   => false,                   // Use native prepared statements
    PDO::ATTR_PERSISTENT         => false                    // Non-persistent connections
];

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
} catch (PDOException $e) {
    // Log error but don't expose details in production
    error_log("Database Connection Error: " . $e->getMessage());
    
    // In development, show the error
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        die("Database Connection Failed: " . htmlspecialchars($e->getMessage()));
    }
    
    // In production, show generic message
    die("Database connection failed. Please check configuration.");
}

/**
 * Get database connection instance
 * @return PDO Database connection
 */
function getDBConnection() {
    global $pdo;
    return $pdo;
}

/**
 * Execute a prepared statement with parameters
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return PDOStatement|false Executed statement or false on failure
 */
function dbQuery($sql, $params = []) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("DB Query Error: " . $e->getMessage() . " | SQL: " . $sql);
        return false;
    }
}

/**
 * Fetch single row
 * @param string $sql SQL query
 * @param array $params Parameters to bind
 * @return array|false Single row or false if not found
 */
function dbFetchOne($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    if ($stmt) {
        return $stmt->fetch();
    }
    return false;
}

/**
 * Fetch all rows
 * @param string $sql SQL query
 * @param array $params Parameters to bind
 * @return array Array of rows
 */
function dbFetchAll($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    if ($stmt) {
        return $stmt->fetchAll();
    }
    return [];
}

/**
 * Get last insert ID
 * @return int|false Last insert ID or false on failure
 */
function dbLastInsertId() {
    try {
        $pdo = getDBConnection();
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("DB Last Insert ID Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Begin transaction
 * @return bool Success status
 */
function dbBeginTransaction() {
    try {
        $pdo = getDBConnection();
        return $pdo->beginTransaction();
    } catch (PDOException $e) {
        error_log("DB Transaction Begin Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Commit transaction
 * @return bool Success status
 */
function dbCommit() {
    try {
        $pdo = getDBConnection();
        return $pdo->commit();
    } catch (PDOException $e) {
        error_log("DB Transaction Commit Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Rollback transaction
 * @return bool Success status
 */
function dbRollback() {
    try {
        $pdo = getDBConnection();
        return $pdo->rollBack();
    } catch (PDOException $e) {
        error_log("DB Transaction Rollback Error: " . $e->getMessage());
        return false;
    }
}
?>
