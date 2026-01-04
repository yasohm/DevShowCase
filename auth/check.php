<?php
/**
 * DevShowcase - Authentication Check Endpoint
 * Returns current authentication status (useful for frontend)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/helpers.php';

// Return JSON response with authentication status
header('Content-Type: application/json');

if (isLoggedIn()) {
    $user = getCurrentUser();
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ]
    ]);
} else {
    echo json_encode([
        'logged_in' => false,
        'user' => null
    ]);
}

