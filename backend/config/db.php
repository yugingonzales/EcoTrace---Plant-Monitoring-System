<?php
/**
 * Database Configuration File
 * Connects to MySQL database for EcoTrace
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ecotrace_db');

// Try to connect
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die(json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $conn->connect_error
        ]));
    }
    
    // Set charset
    $conn->set_charset("utf8");
    
} catch (Exception $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Connection error: ' . $e->getMessage()
    ]));
}

// API base URL (adjust based on your XAMPP setup)
define('API_BASE_URL', 'http://localhost/repos/EcoTrace/backend/api');

// JWT Secret Key
define('JWT_SECRET', 'ecotrace_super_secret_key_2024');

// Token expiration (7 days in seconds)
define('TOKEN_EXPIRATION', 7 * 24 * 60 * 60);

// Reservation lock duration (7 days in seconds)
define('RESERVATION_LOCK_DURATION', 7 * 24 * 60 * 60);

// Upload settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);