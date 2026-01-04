<?php
/**
 * DevShowcase - Projects Management
 * CRUD operations for user projects
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/helpers.php';

// Remove global requireLogin to allow public project viewing
// requireLogin('../auth/login.php');

$pdo = getDBConnection();
$errors = [];
$success = false;
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Handle different actions
switch ($action) {
    case 'list':
        // Fetch all projects for current user
        requireLogin('../auth/login.php');
        getProjects();
        break;
        
    case 'create':
    case 'add':
        // Create new project
        requireLogin('../auth/login.php');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            createProject();
        } else {
            jsonResponse(false, 'Invalid request method');
        }
        break;
        
    case 'read':
    case 'get':
        // Get single project
        $projectId = $_GET['id'] ?? $_POST['id'] ?? null;
        if ($projectId) {
            // Allow public reading of projects
            getProject($projectId, true);
        } else {
            jsonResponse(false, 'Project ID required');
        }
        break;
        
    case 'update':
    case 'edit':
        // Update project
        requireLogin('../auth/login.php');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $projectId = $_POST['id'] ?? null;
            if ($projectId) {
                updateProject($projectId);
            } else {
                jsonResponse(false, 'Project ID required');
            }
        } else {
            jsonResponse(false, 'Invalid request method');
        }
        break;
        
    case 'delete':
        // Delete project
        requireLogin('../auth/login.php');
        $projectId = $_GET['id'] ?? $_POST['id'] ?? null;
        if ($projectId) {
            deleteProject($projectId);
        } else {
            jsonResponse(false, 'Project ID required');
        }
        break;
        
    default:
        jsonResponse(false, 'Invalid action');
        break;
}

/**
 * Get all projects for current user
 */
function getProjects() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, description, technologies, github_url, screenshot, 
                   created_at, updated_at
            FROM projects 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([getCurrentUserId()]);
        $projects = $stmt->fetchAll();
        
        // Parse technologies for each project
        foreach ($projects as &$project) {
            $project['technologies'] = parseTechnologies($project['technologies']);
            
            // Handle screenshot URL - check if it's external URL or local path
            if ($project['screenshot']) {
                // If it's already a full URL, use it directly
                if (filter_var($project['screenshot'], FILTER_VALIDATE_URL)) {
                    $project['screenshot_url'] = $project['screenshot'];
                } else {
                    // It's a local file path, convert to relative URL
                    $project['screenshot_url'] = getRelativeUrlPath($project['screenshot']);
                }
            }
        }
        
        jsonResponse(true, 'Projects retrieved successfully', ['projects' => $projects]);
        
    } catch (PDOException $e) {
        error_log("Get Projects Error: " . $e->getMessage());
        jsonResponse(false, 'Failed to retrieve projects');
    }
}

/**
 * Get single project by ID
 */
function getProject($projectId, $allowPublic = false) {
    global $pdo;
    
    try {
        if ($allowPublic) {
            $stmt = $pdo->prepare("
                SELECT p.id, p.user_id, p.title, p.description, p.technologies, p.github_url, p.screenshot, p.category, 
                       p.created_at, p.updated_at, u.username, u.first_name, u.last_name
                FROM projects p
                JOIN users u ON p.user_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$projectId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id, user_id, title, description, technologies, github_url, screenshot, category, 
                       created_at, updated_at
                FROM projects 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$projectId, getCurrentUserId()]);
        }
        
        $project = $stmt->fetch();
        
        if (!$project) {
            jsonResponse(false, 'Project not found');
            return;
        }
        
        // Check if current user is owner
        $isOwner = ($project['user_id'] == getCurrentUserId());
        
        // Parse technologies
        $project['technologies'] = parseTechnologies($project['technologies']);
        
        // Handle screenshot URL
        if ($project['screenshot']) {
            if (filter_var($project['screenshot'], FILTER_VALIDATE_URL)) {
                $project['screenshot_url'] = $project['screenshot'];
            } else {
                $project['screenshot_url'] = getRelativeUrlPath($project['screenshot']);
            }
        }
        
        jsonResponse(true, 'Project retrieved successfully', [
            'project' => $project,
            'is_owner' => $isOwner
        ]);
        
    } catch (PDOException $e) {
        error_log("Get Project Error: " . $e->getMessage());
        jsonResponse(false, 'Failed to retrieve project');
    }
}

/**
 * Create new project
 */
function createProject() {
    global $pdo, $errors;
    
    // Sanitize and validate input
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeTextarea($_POST['description'] ?? '');
    $technologies = $_POST['technologies'] ?? '';
    $githubUrl = sanitizeInput($_POST['github_url'] ?? '');
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Project title is required.';
    }
    
    if (empty($description)) {
        $errors[] = 'Project description is required.';
    }
    
    if (!empty($githubUrl) && !validateURL($githubUrl)) {
        $errors[] = 'Invalid GitHub URL format.';
    }

    // Capture screenshot URL from input (if provided)
    $screenshotUrl = sanitizeInput($_POST['screenshot_url'] ?? '');
    if (!empty($screenshotUrl) && !validateURL($screenshotUrl)) {
        $errors[] = 'Invalid Screenshot URL format.';
    }
    
    // Handle screenshot upload (optional)
    $screenshotPath = null;
    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
        $validation = validateFileUpload($_FILES['screenshot'], ALLOWED_IMAGE_TYPES, MAX_FILE_SIZE);
        
        if (!$validation['valid']) {
            $errors[] = $validation['error'];
        } else {
            $uploadResult = uploadFile($_FILES['screenshot'], UPLOAD_DIR_PROJECTS, 'project');
            
            if (!$uploadResult['success']) {
                $errors[] = $uploadResult['error'];
            } else {
                $screenshotPath = $uploadResult['file_path'];
            }
        }
    } else if (!empty($screenshotUrl)) {
        // Use provided URL if no file uploaded
        $screenshotPath = $screenshotUrl;
    }
    
    // Process technologies
    $technologiesStr = formatTechnologies($technologies);
    
    // Insert project if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO projects (
                    user_id, title, description, technologies, github_url, screenshot
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([
                getCurrentUserId(),
                $title,
                $description,
                $technologiesStr,
                $githubUrl ?: null,
                $screenshotPath ?: null
            ]);
            
            if ($success) {
                $projectId = $pdo->lastInsertId();
                
                // Fetch created project
                $stmt = $pdo->prepare("
                    SELECT id, title, description, technologies, github_url, screenshot, 
                           created_at, updated_at
                    FROM projects 
                    WHERE id = ?
                ");
                $stmt->execute([$projectId]);
                $project = $stmt->fetch();
                
                // Parse technologies
                $project['technologies'] = parseTechnologies($project['technologies']);
                
                // Get relative URL for screenshot if exists
                if ($project['screenshot']) {
                    $project['screenshot_url'] = getRelativeUrlPath($project['screenshot']);
                }
                
                jsonResponse(true, 'Project created successfully', ['project' => $project]);
            } else {
                // Delete uploaded file if database insert failed
                if ($screenshotPath && file_exists($screenshotPath)) {
                    deleteFile($screenshotPath);
                }
                jsonResponse(false, 'Failed to create project');
            }
            
        } catch (PDOException $e) {
            error_log("Create Project Error: " . $e->getMessage());
            
            // Delete uploaded file if database insert failed
            if ($screenshotPath && file_exists($screenshotPath)) {
                deleteFile($screenshotPath);
            }
            
            jsonResponse(false, 'Failed to create project. Please try again.');
        }
    } else {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }
}

/**
 * Update existing project
 */
function updateProject($projectId) {
    global $pdo, $errors;
    
    // Verify project belongs to current user
    try {
        $stmt = $pdo->prepare("SELECT id, screenshot FROM projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$projectId, getCurrentUserId()]);
        $existingProject = $stmt->fetch();
        
        if (!$existingProject) {
            jsonResponse(false, 'Project not found or access denied');
            return;
        }
    } catch (PDOException $e) {
        error_log("Update Project Error: " . $e->getMessage());
        jsonResponse(false, 'Failed to verify project ownership');
        return;
    }
    
    // Sanitize and validate input
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeTextarea($_POST['description'] ?? '');
    $technologies = $_POST['technologies'] ?? '';
    $githubUrl = sanitizeInput($_POST['github_url'] ?? '');
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Project title is required.';
    }
    
    if (empty($description)) {
        $errors[] = 'Project description is required.';
    }
    
    if (!empty($githubUrl) && !validateURL($githubUrl)) {
        $errors[] = 'Invalid GitHub URL format.';
    }

    // Capture screenshot URL from input (if provided)
    $screenshotUrl = sanitizeInput($_POST['screenshot_url'] ?? '');
    if (!empty($screenshotUrl) && !validateURL($screenshotUrl)) {
        $errors[] = 'Invalid Screenshot URL format.';
    }
    
    // Handle screenshot upload (optional - new or replacement)
    $screenshotPath = $existingProject['screenshot'];
    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
        $validation = validateFileUpload($_FILES['screenshot'], ALLOWED_IMAGE_TYPES, MAX_FILE_SIZE);
        
        if (!$validation['valid']) {
            $errors[] = $validation['error'];
        } else {
            $uploadResult = uploadFile($_FILES['screenshot'], UPLOAD_DIR_PROJECTS, 'project');
            
            if (!$uploadResult['success']) {
                $errors[] = $uploadResult['error'];
            } else {
                // Delete old screenshot if exists
                if ($screenshotPath && file_exists($screenshotPath)) {
                    deleteFile($screenshotPath);
                }
                $screenshotPath = $uploadResult['file_path'];
            }
        }
    } else if (!empty($screenshotUrl)) {
        // Use provided URL if no file uploaded
        // Note: We don't delete old local file if switching to URL, or maybe we should?
        // Let's be safe and only delete if we are SURE. But logic suggests if we replace screenshot, we should clean up.
        // If old screenshot was a local file, delete it.
        if ($screenshotPath && file_exists($screenshotPath) && !filter_var($screenshotPath, FILTER_VALIDATE_URL)) {
             deleteFile($screenshotPath);
        }
        $screenshotPath = $screenshotUrl;
    }
    
    // Process technologies
    $technologiesStr = formatTechnologies($technologies);
    
    // Update project if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE projects 
                SET title = ?, description = ?, technologies = ?, github_url = ?, 
                    screenshot = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            
            $success = $stmt->execute([
                $title,
                $description,
                $technologiesStr,
                $githubUrl ?: null,
                $screenshotPath ?: null,
                $projectId,
                getCurrentUserId()
            ]);
            
            if ($success) {
                // Fetch updated project
                $stmt = $pdo->prepare("
                    SELECT id, title, description, technologies, github_url, screenshot, 
                           created_at, updated_at
                    FROM projects 
                    WHERE id = ?
                ");
                $stmt->execute([$projectId]);
                $project = $stmt->fetch();
                
                // Parse technologies
                $project['technologies'] = parseTechnologies($project['technologies']);
                
                // Get relative URL for screenshot if exists
                if ($project['screenshot']) {
                    $project['screenshot_url'] = getRelativeUrlPath($project['screenshot']);
                }
                
                jsonResponse(true, 'Project updated successfully', ['project' => $project]);
            } else {
                jsonResponse(false, 'Failed to update project');
            }
            
        } catch (PDOException $e) {
            error_log("Update Project Error: " . $e->getMessage());
            jsonResponse(false, 'Failed to update project. Please try again.');
        }
    } else {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }
}

/**
 * Delete project
 */
function deleteProject($projectId) {
    global $pdo;
    
    try {
        // Verify project belongs to current user and get screenshot path
        $stmt = $pdo->prepare("SELECT id, screenshot FROM projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$projectId, getCurrentUserId()]);
        $project = $stmt->fetch();
        
        if (!$project) {
            jsonResponse(false, 'Project not found or access denied');
            return;
        }
        
        // Delete project from database
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
        $success = $stmt->execute([$projectId, getCurrentUserId()]);
        
        if ($success) {
            // Delete screenshot file if exists
            if ($project['screenshot'] && file_exists($project['screenshot'])) {
                deleteFile($project['screenshot']);
            }
            
            jsonResponse(true, 'Project deleted successfully');
        } else {
            jsonResponse(false, 'Failed to delete project');
        }
        
    } catch (PDOException $e) {
        error_log("Delete Project Error: " . $e->getMessage());
        jsonResponse(false, 'Failed to delete project. Please try again.');
    }
}

// TODO: Frontend integration point
// AJAX endpoints:
// GET  /projects/projects.php?action=list - Get all projects
// GET  /projects/projects.php?action=get&id=1 - Get single project
// POST /projects/projects.php?action=add - Create project (with FormData for file upload)
// POST /projects/projects.php?action=update - Update project (with FormData)
// GET  /projects/projects.php?action=delete&id=1 - Delete project

