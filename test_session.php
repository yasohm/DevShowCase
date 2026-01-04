<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/helpers/helpers.php';

echo "Session ID: " . session_id() . "\n";
echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";
echo "Username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'NOT SET') . "\n";
echo "Email: " . (isset($_SESSION['email']) ? $_SESSION['email'] : 'NOT SET') . "\n";
echo "\nFull SESSION:\n";
print_r($_SESSION);
