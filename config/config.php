<?php
/**
 * DevShowcase - Database Configuration
 * PDO MySQL Connection
 * 
 * TODO: Update these credentials with your actual database details
 */

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'devshowcase_db');
define('DB_USER', 'root');          // TODO: Change to your MySQL username
define('DB_PASS', '');              // TODO: Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('BASE_URL', 'http://localhost/DevShowcase/');  // TODO: Update with your base URL
define('UPLOAD_DIR_PROFILE', __DIR__ . '/../uploads/profiles/');
define('UPLOAD_DIR_DOCUMENTS', __DIR__ . '/../uploads/documents/');
define('UPLOAD_DIR_PROJECTS', __DIR__ . '/../uploads/projects/');

// File upload limits
define('MAX_FILE_SIZE', 10 * 1024 * 1024);  // 10MB
define('MAX_PROFILE_IMAGE_SIZE', 5 * 1024 * 1024);  // 5MB for profile images

// Allowed file types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOCUMENT_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
    'image/png',
    'image/jpeg',
    'image/jpg'
]);

/**
 * Get PDO Database Connection
 * 
 * @return PDO Database connection instance
 * @throws PDOException If connection fails
 */
function getDBConnection() {
    static $pdo = null;
    
    // Return existing connection if available (singleton pattern)
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        // Build DSN (Data Source Name)
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        // PDO options for security and error handling
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,  // Use native prepared statements
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        
        // Create PDO instance
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Log error (in production, log to file instead of displaying)
        error_log("Database Connection Error: " . $e->getMessage());
        
        // Don't expose database details to users
        die("Database connection failed. Please contact the administrator.");
    }
}

/**
 * Create upload directories if they don't exist
 */
function createUploadDirectories() {
    $directories = [
        UPLOAD_DIR_PROFILE,
        UPLOAD_DIR_DOCUMENTS,
        UPLOAD_DIR_PROJECTS
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            // Try to create directory with write permissions
            if (!@mkdir($dir, 0777, true)) {
                // If mkdir fails, log error but don't break execution
                // User needs to create directories manually with proper permissions
                error_log("Warning: Could not create upload directory: $dir");
                error_log("Please run: sudo mkdir -p $dir && sudo chmod 777 $dir");
            } else {
                // Set permissions after creation
                @chmod($dir, 0777);
            }
        } else {
            // Ensure directory is writable
            if (!is_writable($dir)) {
                @chmod($dir, 0777);
            }
        }
    }
}

// Create upload directories on config load
createUploadDirectories();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

