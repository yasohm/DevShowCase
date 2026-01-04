<?php
/**
 * DevShowcase - User Login Handler
 * Handles user authentication with session management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/helpers.php';

// Check if request is AJAX
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

// If already logged in, redirect to profile (only for GET requests)
if (isLoggedIn() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        jsonResponse(true, 'Already logged in', ['redirect' => '../profile/profile.php']);
        exit();
    }
    redirect('../profile/profile.php');
}

$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']) ? true : false;
    
    // Validation
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Invalid email format.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }
    
    // Authenticate user
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            
            // Fetch user by email
            $stmt = $pdo->prepare("
                SELECT id, username, email, password, first_name, last_name, 
                       profile_photo, job_title, bio, github_url, skills
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            // Verify password
            if ($user && password_verify($password, $user['password'])) {
                // Password is correct - create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['profile_photo'] = $user['profile_photo'];
                $_SESSION['job_title'] = $user['job_title'];
                $_SESSION['bio'] = $user['bio'];
                $_SESSION['github_url'] = $user['github_url'];
                $_SESSION['skills'] = $user['skills'];
                $_SESSION['logged_in'] = true;
                
                // Remember me functionality (set cookie)
                if ($rememberMe) {
                    // Generate remember token (simplified - in production, use secure tokens)
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                    
                    // TODO: Store token in database for verification
                    // $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    // $stmt->execute([$token, $user['id']]);
                }
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                setSuccessMessage('Welcome back, ' . ($user['first_name'] ?? $user['username']) . '!');
                $success = true;
                
                // Redirect to profile or requested page
                $redirectUrl = $_SESSION['redirect_after_login'] ?? '../profile.html';
                unset($_SESSION['redirect_after_login']);
                if (!$isAjax) {
                    redirect($redirectUrl);
                }
                
            } else {
                // Invalid credentials
                $errors[] = 'Invalid email or password.';
            }
            
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            $errors[] = 'Login failed. Please try again later.';
        }
    }
}

// Return JSON response if AJAX request
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'errors' => $errors,
        'redirect' => $success ? 'profile.html' : null
    ]);
    exit();
}

// If not AJAX, redirect back to login form with errors
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)) {
    $_SESSION['login_errors'] = $errors;
    redirect('../login.html');
}

// TODO: Frontend integration point
// Update login.html form action to: action="auth/login.php" method="POST"
// Or handle via AJAX in js/script.js

