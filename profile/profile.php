<?php
/**
 * DevShowcase - Profile Management
 * Display and update user profile information
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/helpers.php';

$pdo = getDBConnection();
$errors = [];
$success = false;
$userData = null;

// Convert absolute file paths to relative URLs for web access
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
    || (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

// Get user ID - prioritize 'id' from GET for public viewing
$profileUserId = isset($_GET['id']) ? (int)$_GET['id'] : getCurrentUserId();

// If no ID is provided and user is NOT logged in, require login
if (!$profileUserId && !isset($_GET['id'])) {
    if ($isAjax) {
        jsonResponse(false, 'Login required');
    } else {
        requireLogin('../auth/login.php');
    }
}

// Get user data
try {
    $stmt = $pdo->prepare("
        SELECT id, username, email, first_name, last_name, bio, github_url, 
               profile_photo, cv_path, job_title, skills, created_at
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$profileUserId]);
    $userData = $stmt->fetch();
    
    if (!$userData) {
        if ($isAjax) {
            jsonResponse(false, 'User not found.');
        } else {
            setErrorMessage('User not found.');
            redirect('../auth/login.php');
        }
    }
    
    // Check if this is the current user viewing their own profile
    $isOwner = ($profileUserId && $profileUserId == getCurrentUserId());
    $success = true;
} catch (PDOException $e) {
    error_log("Profile Fetch Error: " . $e->getMessage());
    setErrorMessage('Failed to load profile data.');
}

// Handle form submission for profile update (requires owner status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$isOwner) {
        jsonResponse(false, 'Unauthorized. You can only edit your own profile.');
    }

    $action = $_POST['action'];
    
    if ($action === 'update_profile') {
        // Update basic profile information
        $firstName = isset($_POST['first_name']) ? sanitizeInput($_POST['first_name']) : $userData['first_name'];
        $lastName = isset($_POST['last_name']) ? sanitizeInput($_POST['last_name']) : $userData['last_name'];
        $bio = isset($_POST['bio']) ? sanitizeTextarea($_POST['bio']) : $userData['bio'];
        $githubUrl = isset($_POST['github_url']) ? sanitizeInput($_POST['github_url']) : $userData['github_url'];
        $jobTitle = isset($_POST['job_title']) ? sanitizeInput($_POST['job_title']) : $userData['job_title'];
        
        // Validate GitHub URL
        if (!empty($githubUrl) && !validateURL($githubUrl)) {
            $errors[] = 'Invalid GitHub URL format.';
        }
        
        // Update profile photo
        $profilePhotoPath = $userData['profile_photo'];
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $validation = validateFileUpload($_FILES['profile_photo'], ALLOWED_IMAGE_TYPES, MAX_PROFILE_IMAGE_SIZE);
            
            if (!$validation['valid']) {
                $errors[] = $validation['error'];
            } else {
                $uploadResult = uploadFile($_FILES['profile_photo'], UPLOAD_DIR_PROFILE, 'profile');
                if ($uploadResult['success']) {
                    if ($profilePhotoPath && file_exists($profilePhotoPath)) {
                        deleteFile($profilePhotoPath);
                    }
                    $profilePhotoPath = $uploadResult['file_path'];
                } else {
                    $errors[] = $uploadResult['error'];
                }
            }
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, bio = ?, github_url = ?, 
                        profile_photo = ?, job_title = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $success = $stmt->execute([$firstName, $lastName, $bio, $githubUrl, $profilePhotoPath, $jobTitle, $profileUserId]);
                if ($success) {
                    setSuccessMessage('Profile updated successfully!');
                    $userData['first_name'] = $firstName;
                    $userData['last_name'] = $lastName;
                    $userData['bio'] = $bio;
                    $userData['github_url'] = $githubUrl;
                    $userData['profile_photo'] = $profilePhotoPath;
                    $userData['job_title'] = $jobTitle;
                }
            } catch (PDOException $e) {
                error_log("Profile Update Error: " . $e->getMessage());
                $errors[] = 'Failed to update profile.';
            }
        }
    } elseif ($action === 'update_skills') {
        $skills = $_POST['skills'] ?? '';
        $skillsArray = is_array($skills) ? $skills : array_map('trim', explode(',', $skills));
        try {
            $stmt = $pdo->prepare("UPDATE users SET skills = ? WHERE id = ?");
            if ($stmt->execute([json_encode($skillsArray), $profileUserId])) {
                $userData['skills'] = json_encode($skillsArray);
                setSuccessMessage('Skills updated successfully!');
            }
        } catch (PDOException $e) {
            $errors[] = 'Failed to update skills.';
        }
    } elseif ($action === 'upload_cv') {
        if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
            $validation = validateFileUpload($_FILES['cv_file'], ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], MAX_FILE_SIZE);
            if ($validation['valid']) {
                $uploadResult = uploadFile($_FILES['cv_file'], UPLOAD_DIR_CV, 'cv');
                if ($uploadResult['success']) {
                    $oldCv = $userData['cv_path'];
                    $stmt = $pdo->prepare("UPDATE users SET cv_path = ? WHERE id = ?");
                    if ($stmt->execute([$uploadResult['file_path'], $profileUserId])) {
                        if ($oldCv && file_exists($oldCv)) deleteFile($oldCv);
                        $userData['cv_path'] = $uploadResult['file_path'];
                        setSuccessMessage('CV uploaded successfully!');
                    }
                } else {
                    $errors[] = $uploadResult['error'];
                }
            } else {
                $errors[] = $validation['error'];
            }
        }
    }
}

// Format data for response
$skillsArray = [];
if (!empty($userData['skills'])) {
    $decoded = json_decode($userData['skills'], true);
    if (is_array($decoded)) $skillsArray = $decoded;
}

if (!empty($userData['profile_photo'])) {
    $userData['profile_photo'] = getRelativeUrlPath($userData['profile_photo']);
}
if (!empty($userData['cv_path'])) {
    $userData['cv_path'] = getRelativeUrlPath($userData['cv_path']);
}

// Return JSON response
if ($isAjax || (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'view.html') !== false)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'errors' => $errors,
        'user' => $userData,
        'skills' => $skillsArray,
        'is_owner' => $isOwner
    ]);
    exit();
}

// If not AJAX, redirect back to profile page
header('Location: ../profile.html');
exit();
