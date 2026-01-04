<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/helpers/helpers.php';

function test_login($email) {
    echo "Testing login for: $email\n";
    $pdo = getDBConnection();
    
    // Mimic login.php logic
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "Found User ID: " . $user['id'] . " | Email: " . $user['email'] . "\n";
        $_SESSION['user_id'] = $user['id'];
        echo "Current SESSION user_id: " . $_SESSION['user_id'] . "\n\n";
    } else {
        echo "User NOT found for email: $email\n\n";
    }
}

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

test_login('yassamotostreet@gmail.com');
test_login('benhaidayassine1@gmail.com');
test_login('user1@test.com');
