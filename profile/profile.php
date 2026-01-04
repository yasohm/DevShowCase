<?php
/**
 * DevShowcase - Profile Management
 * Display and update user profile information
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/helpers.php';

// Require user to be logged in
requireLogin('../auth/login.php');

$pdo = getDBConnection();
$errors = [];
$success = false;
$userData = null;

// Get current user data
try {
    $stmt = $pdo->prepare("
        SELECT id, username, email, first_name, last_name, bio, github_url, 
               profile_photo, job_title, skills, created_at
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([getCurrentUserId()]);
    $userData = $stmt->fetch();
    
    if (!$userData) {
        setErrorMessage('User not found.');
        redirect('../auth/login.php');
    }
    
    // User found, set success to true for GET requests
    $success = true;
} catch (PDOException $e) {
    error_log("Profile Fetch Error: " . $e->getMessage());
    setErrorMessage('Failed to load profile data.');
}

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_profile') {
        // Update basic profile information
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        $bio = sanitizeTextarea($_POST['bio'] ?? '');
        $githubUrl = sanitizeInput($_POST['github_url'] ?? '');
        $jobTitle = sanitizeInput($_POST['job_title'] ?? '');
        
        // Validate GitHub URL if provided
        if (!empty($githubUrl) && !validateURL($githubUrl)) {
            $errors[] = 'Invalid GitHub URL format.';
        }
        
        // Update profile photo if uploaded
        $profilePhotoPath = $userData['profile_photo'];
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $validation = validateFileUpload($_FILES['profile_photo'], ALLOWED_IMAGE_TYPES, MAX_PROFILE_IMAGE_SIZE);
            
            if (!$validation['valid']) {
                $errors[] = $validation['error'];
            } else {
                $uploadResult = uploadFile($_FILES['profile_photo'], UPLOAD_DIR_PROFILE, 'profile');
                
                if (!$uploadResult['success']) {
                    $errors[] = $uploadResult['error'];
                } else {
                    // Delete old profile photo if exists
                    if ($profilePhotoPath && file_exists($profilePhotoPath)) {
                        deleteFile($profilePhotoPath);
                    }
                    $profilePhotoPath = $uploadResult['file_path'];
                }
            }
        }
        
        // Update database if no errors
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, bio = ?, github_url = ?, 
                        profile_photo = ?, job_title = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $success = $stmt->execute([
                    $firstName ?: null,
                    $lastName ?: null,
                    $bio ?: null,
                    $githubUrl ?: null,
                    $profilePhotoPath ?: null,
                    $jobTitle ?: null,
                    getCurrentUserId()
                ]);
                
                if ($success) {
                    // Update session data
                    $_SESSION['first_name'] = $firstName;
                    $_SESSION['last_name'] = $lastName;
                    $_SESSION['bio'] = $bio;
                    $_SESSION['github_url'] = $githubUrl;
                    $_SESSION['profile_photo'] = $profilePhotoPath;
                    $_SESSION['job_title'] = $jobTitle;
                    
                    setSuccessMessage('Profile updated successfully!');
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("
                        SELECT id, username, email, first_name, last_name, bio, github_url, 
                               profile_photo, job_title, skills, created_at
                        FROM users 
                        WHERE id = ?
                    ");
                    $stmt->execute([getCurrentUserId()]);
                    $userData = $stmt->fetch();
                }
                
            } catch (PDOException $e) {
                error_log("Profile Update Error: " . $e->getMessage());
                $errors[] = 'Failed to update profile. Please try again.';
            }
        }
        
    } elseif ($action === 'update_skills') {
        // Update skills
        $skills = $_POST['skills'] ?? '';
        
        // Validate skills (expect JSON array)
        $skillsArray = [];
        if (!empty($skills)) {
            if (is_array($skills)) {
                $skillsArray = $skills;
            } elseif (is_string($skills)) {
                // Try to decode JSON
                $decoded = json_decode($skills, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $skillsArray = $decoded;
                } else {
                    // Comma-separated list
                    $skillsArray = array_map('trim', explode(',', $skills));
                }
            }
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET skills = ? WHERE id = ?");
            $success = $stmt->execute([json_encode($skillsArray), getCurrentUserId()]);
            
            if ($success) {
                $_SESSION['skills'] = json_encode($skillsArray);
                setSuccessMessage('Skills updated successfully!');
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT skills FROM users WHERE id = ?");
                $stmt->execute([getCurrentUserId()]);
                $userData['skills'] = $stmt->fetchColumn();
            }
            
        } catch (PDOException $e) {
            error_log("Skills Update Error: " . $e->getMessage());
            $errors[] = 'Failed to update skills. Please try again.';
        }
    }
}

// Parse skills if exists
$skillsArray = [];
if (!empty($userData['skills'])) {
    $decoded = json_decode($userData['skills'], true);
    if (is_array($decoded)) {
        $skillsArray = $decoded;
    }
}

// [Removed duplicate AJAX handler]

// Convert absolute file paths to relative URLs for web access
if (!empty($userData['profile_photo'])) {
    // Convert absolute path to relative URL
    // Handle /opt/lampp/htdocs/DevShowcase/ paths
    if (strpos($userData['profile_photo'], '/opt/lampp/htdocs/DevShowcase/') !== false) {
        $userData['profile_photo'] = str_replace('/opt/lampp/htdocs/DevShowcase/', '', $userData['profile_photo']);
    }
    
    // Handle config/../ pattern (resolve parent directory)
    $userData['profile_photo'] = str_replace('config/../', '', $userData['profile_photo']);
    
    // Clean up any double slashes
    $userData['profile_photo'] = preg_replace('#/+#', '/', $userData['profile_photo']);
    
    // Ensure it starts with uploads/ for web access
    // Extract just the part after uploads/ if it exists in the path
    if (preg_match('#uploads/(.+)$#', $userData['profile_photo'], $matches)) {
        $userData['profile_photo'] = 'uploads/' . $matches[1];
    }
}

// Check for AJAX request (fetch/XMLHttpRequest)
// fetch() doesn't always send X-Requested-With, so check multiple indicators
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
    || (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

// Also check if request method is GET and no referer is set (likely AJAX)
if (!$isAjax && $_SERVER['REQUEST_METHOD'] === 'GET' && empty($_SERVER['HTTP_REFERER'])) {
    // If no referer and GET request, assume it's a direct access, redirect
    header('Location: ../profile.html');
    exit();
}

// Return JSON for AJAX requests or if it's a GET request with referer (likely from page)
if ($isAjax || ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_SERVER['HTTP_REFERER']))) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'errors' => $errors,
        'user' => $userData,
        'skills' => $skillsArray
    ]);
    exit();
}

// Default: redirect to HTML page
header('Location: ../profile.html');
exit();

