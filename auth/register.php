<?php
/**
 * DevShowcase - User Registration Handler
 * Handles user registration with validation and file uploads
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/helpers.php';

// If already logged in, redirect to profile (only for GET requests)
if (isLoggedIn() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../profile/profile.php');
}

$errors = [];
$errors = [];
$success = false;

// Check if request is AJAX
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $bio = sanitizeTextarea($_POST['bio'] ?? '');
    $githubUrl = sanitizeInput($_POST['github_url'] ?? '');
    $jobTitle = sanitizeInput($_POST['job_title'] ?? '');
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Invalid email format.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (!empty($githubUrl) && !validateURL($githubUrl)) {
        $errors[] = 'Invalid GitHub URL format.';
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $errors[] = 'Username or email already exists.';
            }
        } catch (PDOException $e) {
            error_log("Registration Error: " . $e->getMessage());
            $errors[] = 'Registration failed. Please try again later.';
        }
    }
    
    // Handle profile photo upload (optional)
    $profilePhotoPath = null;
    if (empty($errors) && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $validation = validateFileUpload($_FILES['profile_photo'], ALLOWED_IMAGE_TYPES, MAX_PROFILE_IMAGE_SIZE);
        
        if (!$validation['valid']) {
            $errors[] = $validation['error'];
        } else {
            $uploadResult = uploadFile($_FILES['profile_photo'], UPLOAD_DIR_PROFILE, 'profile');
            
            if (!$uploadResult['success']) {
                $errors[] = $uploadResult['error'];
            } else {
                $profilePhotoPath = $uploadResult['file_path'];
            }
        }
    }
    
    // If no errors, create user account
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Prepare SQL statement
            $stmt = $pdo->prepare("
                INSERT INTO users (
                    username, email, password, first_name, last_name, 
                    bio, github_url, profile_photo, job_title
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Execute with parameters
            $success = $stmt->execute([
                $username,
                $email,
                $hashedPassword,
                $firstName ?: null,
                $lastName ?: null,
                $bio ?: null,
                $githubUrl ?: null,
                $profilePhotoPath ?: null,
                $jobTitle ?: null
            ]);
            
            if ($success) {
                setSuccessMessage('Registration successful! Please login to continue.');
                if (!$isAjax) {
                    redirect('login.html');
                }
            }
            
        } catch (PDOException $e) {
            error_log("Registration Error: " . $e->getMessage());
            
            // Delete uploaded file if database insert failed
            if ($profilePhotoPath && file_exists($profilePhotoPath)) {
                deleteFile($profilePhotoPath);
            }
            
            $errors[] = 'Registration failed. Please try again later.';
        }
    }
}

// Return JSON response if AJAX request
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'errors' => $errors,
        'redirect' => $success ? 'login.html' : null
    ]);
    exit();
}

// If not AJAX, redirect back to registration form with errors
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)) {
    $_SESSION['registration_errors'] = $errors;
    $_SESSION['registration_data'] = $_POST; // Preserve form data
    redirect('../register.html');
}

// TODO: Frontend integration point
// This file should be called via AJAX from register.html or form action should point here
// Update register.html form action to: action="auth/register.php" method="POST"

