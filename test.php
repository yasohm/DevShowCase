<?php
/**
 * DevShowcase - Quick Test/Debug File
 * Access via: http://localhost/DevShowcase/test.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>DevShowcase System Check</h2>";

// Test 1: PHP Version
echo "<h3>1. PHP Version</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "✓ PHP is working<br><br>";

// Test 2: Config File
echo "<h3>2. Config File</h3>";
try {
    require_once __DIR__ . '/config/config.php';
    echo "✓ Config file loaded successfully<br>";
    echo "Database Host: " . DB_HOST . "<br>";
    echo "Database Name: " . DB_NAME . "<br>";
    echo "Database User: " . DB_USER . "<br>";
    echo "Base URL: " . BASE_URL . "<br><br>";
} catch (Exception $e) {
    echo "✗ Config Error: " . $e->getMessage() . "<br><br>";
    exit;
}

// Test 3: Database Connection
echo "<h3>3. Database Connection</h3>";
try {
    $pdo = getDBConnection();
    echo "✓ Database connection successful!<br><br>";
} catch (Exception $e) {
    echo "✗ Database Error: " . $e->getMessage() . "<br>";
    echo "Check your database credentials in config/config.php<br><br>";
}

// Test 4: Session
echo "<h3>4. Session</h3>";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Not Active") . "<br>";
echo "Session ID: " . session_id() . "<br><br>";

// Test 5: Database Tables
echo "<h3>5. Database Tables</h3>";
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "✓ Found " . count($tables) . " table(s):<br>";
        foreach ($tables as $table) {
            echo "- $table<br>";
        }
    } else {
        echo "⚠ No tables found. Run database.sql to create tables.<br>";
    }
    echo "<br>";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br><br>";
}

// Test 6: Upload Directories
echo "<h3>6. Upload Directories</h3>";
$dirs = [
    'Profile' => UPLOAD_DIR_PROFILE,
    'Documents' => UPLOAD_DIR_DOCUMENTS,
    'Projects' => UPLOAD_DIR_PROJECTS
];

foreach ($dirs as $name => $dir) {
    if (file_exists($dir) && is_dir($dir)) {
        $writable = is_writable($dir) ? "✓" : "✗";
        echo "$writable $name: $dir<br>";
    } else {
        echo "✗ $name: $dir (does not exist)<br>";
    }
}
echo "<br>";

// Test 7: Helper Functions
echo "<h3>7. Helper Functions</h3>";
try {
    require_once __DIR__ . '/helpers/helpers.php';
    echo "✓ Helper functions loaded<br><br>";
} catch (Exception $e) {
    echo "✗ Helper Error: " . $e->getMessage() . "<br><br>";
}

// Test 8: File Permissions
echo "<h3>8. File Permissions</h3>";
echo "Config file: " . substr(sprintf('%o', fileperms(__DIR__ . '/config/config.php')), -4) . "<br>";
echo "Upload dir (profiles): " . (file_exists(UPLOAD_DIR_PROFILE) ? substr(sprintf('%o', fileperms(UPLOAD_DIR_PROFILE)), -4) : "N/A") . "<br><br>";

echo "<hr>";
echo "<h3>Summary</h3>";
echo "If all tests show ✓, your setup should be working!<br>";
echo "If you see ✗, fix those issues first.<br>";
echo "<br>";
echo "<a href='index.html'>Go to Application</a> | ";
echo "<a href='auth/check.php'>Check Auth Status</a>";

?>

