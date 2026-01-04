<?php
/**
 * DevShowcase - Helper Functions
 * Reusable utility functions for the application
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Sanitize input data
 * 
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Sanitize textarea content (allows newlines)
 * 
 * @param string $data Textarea content to sanitize
 * @return string Sanitized data
 */
function sanitizeTextarea($data) {
    $data = trim($data);
    $data = stripslashes($data);
    // Convert newlines to <br> for display, but keep original for storage
    return $data;
}

/**
 * Validate email format
 * 
 * @param string $email Email address to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate URL format
 * 
 * @param string $url URL to validate
 * @return bool True if valid, false otherwise
 */
function validateURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require user to be logged in (redirect if not)
 * 
 * @param string $redirectUrl URL to redirect to if not logged in
 */
function requireLogin($redirectUrl = '../auth/login.php') {
    if (!isLoggedIn()) {
        $_SESSION['error_message'] = 'Please login to access this page.';
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * Get current user ID from session
 * 
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data from session
 * 
 * @return array|null User data array or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    // Return session user data if available
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'email' => $_SESSION['email'] ?? null
    ];
}

/**
 * Set success message in session
 * 
 * @param string $message Success message
 */
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Set error message in session
 * 
 * @param string $message Error message
 */
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Get and clear success message from session
 * 
 * @return string|null Success message or null
 */
function getSuccessMessage() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}

/**
 * Get and clear error message from session
 * 
 * @return string|null Error message or null
 */
function getErrorMessage() {
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    return null;
}

/**
 * Validate file upload
 * 
 * @param array $file $_FILES array element
 * @param array $allowedTypes Allowed MIME types
 * @param int $maxSize Maximum file size in bytes
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateFileUpload($file, $allowedTypes = [], $maxSize = MAX_FILE_SIZE) {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
        ];
        
        $error = $errorMessages[$file['error']] ?? 'Unknown upload error.';
        return ['valid' => false, 'error' => $error];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File size exceeds maximum allowed size (' . ($maxSize / 1024 / 1024) . 'MB).'];
    }
    
    // Check file type if allowed types specified
    if (!empty($allowedTypes)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['valid' => false, 'error' => 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes)];
        }
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Upload file securely
 * 
 * @param array $file $_FILES array element
 * @param string $uploadDir Upload directory path
 * @param string $prefix Optional filename prefix
 * @return array ['success' => bool, 'file_path' => string|null, 'error' => string|null]
 */
function uploadFile($file, $uploadDir, $prefix = '') {
    // Validate upload directory
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'file_path' => null, 'error' => 'Failed to create upload directory.'];
        }
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid($prefix . '_', true) . '.' . $extension;
    $filePath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'file_path' => $filePath, 'error' => null];
    } else {
        return ['success' => false, 'file_path' => null, 'error' => 'Failed to move uploaded file.'];
    }
}

/**
 * Delete file from server
 * 
 * @param string $filePath Path to file to delete
 * @return bool True if deleted, false otherwise
 */
function deleteFile($filePath) {
    if (file_exists($filePath) && is_file($filePath)) {
        return unlink($filePath);
    }
    return false;
}

/**
 * Get file extension from filename
 * 
 * @param string $filename Filename
 * @return string File extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Format file size for display
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

/**
 * JSON response helper
 * 
 * @param bool $success Success status
 * @param string $message Response message
 * @param array $data Additional data to include
 */
function jsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

/**
 * Get relative URL path from absolute file path
 * 
 * @param string $filePath Absolute file path
 * @return string Relative URL path
 */
function getRelativeUrlPath($filePath) {
    $basePath = __DIR__ . '/../';
    return str_replace($basePath, '', $filePath);
}

/**
 * Parse technologies string (comma-separated or JSON)
 * 
 * @param string $technologies Technologies string
 * @return array Array of technologies
 */
function parseTechnologies($technologies) {
    if (empty($technologies)) {
        return [];
    }
    
    // Try to decode as JSON first
    $decoded = json_decode($technologies, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }
    
    // Otherwise, split by comma
    return array_map('trim', explode(',', $technologies));
}

/**
 * Format technologies array to string
 * 
 * @param array $technologies Array of technologies
 * @return string Formatted technologies string
 */
function formatTechnologies($technologies) {
    if (is_array($technologies)) {
        return json_encode($technologies);
    }
    return $technologies ?? '';
}

/**
 * Escape output for HTML display
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function escapeHtml($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

