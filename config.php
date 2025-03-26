<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'legoscho_logistic');
define('DB_USER', 'legoscho_logisticu');
define('DB_PASS', '1q2w3e4r5t!QA');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME ,
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Use native prepared statements
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// API Settings
define('API_VERSION', 'v1'); // Current version of the API
define('BASE_URL', 'https://https://legoschool.az/api/' . API_VERSION); // Base URL for API

// Security Settings
define('SECRET_KEY', 'f5c3d9f0e8a1b6f9'); // Secret key for generating JWT tokens
define('RATE_LIMIT', 100); // Max requests per user per minute (example for rate limiting)

// Log Settings
define('LOG_FILE', __DIR__ . '/logs/api.log'); // Path to log file
define('ENABLE_LOGGING', true); // Enable or disable logging

//Expire date settings
define('EXPIRE_DATE', '+1 year');

// Response Settings
define('DEFAULT_RESPONSE_FORMAT', 'json'); // Response format, e.g., JSON or XML

// Debug Mode
define('DEBUG_MODE', true); // Set to false in production
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
}

// Helper Functions
function log_message($message) {
    if (ENABLE_LOGGING) {
        file_put_contents(LOG_FILE, date('[Y-m-d H:i:s]') . " " . $message . PHP_EOL, FILE_APPEND);
    }
}
?>