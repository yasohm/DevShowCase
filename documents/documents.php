<?php
/**
 * DevShowcase - Documents Management
 * CRUD operations for uploaded documents
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/helpers.php';

// Require user to be logged in
requireLogin('../auth/login.php');

$pdo = getDBConnection();
$errors = [];
$success = false;
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Handle different actions
switch ($action) {
    case 'list':
        // Fetch all documents for current user
        getDocuments();
        break;
        
    case 'create':
    case 'upload':
        // Upload new document
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            createDocument();
        } else {
            jsonResponse(false, 'Invalid request method');
        }
        break;
        
    case 'read':
    case 'get':
        // Get single document
        $documentId = $_GET['id'] ?? $_POST['id'] ?? null;
        if ($documentId) {
            getDocument($documentId);
        } else {
            jsonResponse(false, 'Document ID required');
        }
        break;
        
    case 'update':
    case 'edit':
        // Update document info
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documentId = $_POST['id'] ?? null;
            if ($documentId) {
                updateDocument($documentId);
            } else {
                jsonResponse(false, 'Document ID required');
            }
        } else {
            jsonResponse(false, 'Invalid request method');
        }
        break;
        
    case 'delete':
        // Delete document
        $documentId = $_GET['id'] ?? $_POST['id'] ?? null;
        if ($documentId) {
            deleteDocument($documentId);
        } else {
            jsonResponse(false, 'Document ID required');
        }
        break;
        
    case 'download':
        // Download document file
        $documentId = $_GET['id'] ?? null;
        if ($documentId) {
            downloadDocument($documentId);
        } else {
            jsonResponse(false, 'Document ID required');
        }
        break;
        
    default:
        jsonResponse(false, 'Invalid action');
        break;
}

/**
 * Get all documents for current user
 */
function getDocuments() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, description, file_path, file_type, file_size, 
                   created_at, updated_at
            FROM documents 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([getCurrentUserId()]);
        $documents = $stmt->fetchAll();
        
        // Format file sizes and get relative URLs
        foreach ($documents as &$document) {
            $document['file_size_formatted'] = formatFileSize($document['file_size']);
            $document['file_url'] = getRelativeUrlPath($document['file_path']);
            
            // Get file extension for icon display
            $document['file_extension'] = getFileExtension($document['file_path']);
        }
        
        jsonResponse(true, 'Documents retrieved successfully', ['documents' => $documents]);
        
    } catch (PDOException $e) {
        error_log("Get Documents Error: " . $e->getMessage());
        jsonResponse(false, 'Failed to retrieve documents');
    }
}

/**
 * Get single document by ID
 */
function getDocument($documentId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, description, file_path, file_type, file_size, 
                   created_at, updated_at
            FROM documents 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$documentId, getCurrentUserId()]);
        $document = $stmt->fetch();
        
        if (!$document) {
            jsonResponse(false, 'Document not found');
            return;
        }
        
        // Format file size and get relative URL
        $document['file_size_formatted'] = formatFileSize($document['file_size']);
        $document['file_url'] = getRelativeUrlPath($document['file_path']);
        $document['file_extension'] = getFileExtension($document['file_path']);
        
        jsonResponse(true, 'Document retrieved successfully', ['document' => $document]);
        
    } catch (PDOException $e) {
        error_log("Get Document Error: " . $e->getMessage());
        jsonResponse(false, 'Failed to retrieve document');
    }
}

/**
 * Upload and create new document
 */
function createDocument() {
    global $pdo, $errors;
    
    // Sanitize and validate input
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeTextarea($_POST['description'] ?? '');
    $documentType = sanitizeInput($_POST['document_type'] ?? '');
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Document title is required.';
    }
    
    // Validate file upload
    if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please select a file to upload.';
    } else {
        $file = $_FILES['document_file'];
        $validation = validateFileUpload($file, ALLOWED_DOCUMENT_TYPES, MAX_FILE_SIZE);
        
        if (!$validation['valid']) {
            $errors[] = $validation['error'];
        }
    }
    
    // Upload file if validation passed
    $filePath = null;
    $fileType = null;
    $fileSize = null;
    
    if (empty($errors) && isset($_FILES['document_file'])) {
        $file = $_FILES['document_file'];
        $uploadResult = uploadFile($file, UPLOAD_DIR_DOCUMENTS, 'doc');
        
        if (!$uploadResult['success']) {
            $errors[] = $uploadResult['error'];
        } else {
            $filePath = $uploadResult['file_path'];
            $fileType = $documentType ?: getFileExtension($file['name']);
            $fileSize = $file['size'];
        }
    }
    
    // Insert document if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO documents (
                    user_id, title, description, file_path, file_type, file_size
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([
                getCurrentUserId(),
                $title,
                $description ?: null,
                $filePath,
                $fileType,
                $fileSize
            ]);
            
            if ($success) {
                $documentId = $pdo->lastInsertId();
                
                // Fetch created document
                $stmt = $pdo->prepare("
                    SELECT id, title, description, file_path, file_type, file_size, 
                           created_at, updated_at
                    FROM documents 
                    WHERE id = ?
                ");
                $stmt->execute([$documentId]);
                $document = $stmt->fetch();
                
                // Format for response
                $document['file_size_formatted'] = formatFileSize($document['file_size']);
                $document['file_url'] = getRelativeUrlPath($document['file_path']);
                $document['file_extension'] = getFileExtension($document['file_path']);
                
                jsonResponse(true, 'Document uploaded successfully', ['document' => $document]);
            } else {
                // Delete uploaded file if database insert failed
                if ($filePath && file_exists($filePath)) {
                    deleteFile($filePath);
                }
                jsonResponse(false, 'Failed to save document');
            }
            
        } catch (PDOException $e) {
            error_log("Create Document Error: " . $e->getMessage());
            
            // Delete uploaded file if database insert failed
            if ($filePath && file_exists($filePath)) {
                deleteFile($filePath);
            }
            
            jsonResponse(false, 'Failed to upload document. Please try again.');
        }
    } else {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }
}

/**
 * Update document information (metadata only, not the file)
 */
function updateDocument($documentId) {
    global $pdo, $errors;
    
    // Verify document belongs to current user
    try {
        $stmt = $pdo->prepare("SELECT id FROM documents WHERE id = ? AND user_id = ?");
        $stmt->execute([$documentId, getCurrentUserId()]);
        $existingDocument = $stmt->fetch();
        
        if (!$existingDocument) {
            jsonResponse(false, 'Document not found or access denied');
            return;
        }
    } catch (PDOException $e) {
        error_log("Update Document Error: " . $e->getMessage());
        jsonResponse(false, 'Failed to verify document ownership');
        return;
    }
    
    // Sanitize and validate input
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeTextarea($_POST['description'] ?? '');
    $documentType = sanitizeInput($_POST['document_type'] ?? '');
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Document title is required.';
    }
    
    // Update document if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE documents 
                SET title = ?, description = ?, file_type = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            
            $success = $stmt->execute([
                $title,
                $description ?: null,
                $documentType ?: null,
                $documentId,
                getCurrentUserId()
            ]);
            
            if ($success) {
                // Fetch updated document
                $stmt = $pdo->prepare("
                    SELECT id, title, description, file_path, file_type, file_size, 
                           created_at, updated_at
                    FROM documents 
                    WHERE id = ?
                ");
                $stmt->execute([$documentId]);
                $document = $stmt->fetch();
                
                // Format for response
                $document['file_size_formatted'] = formatFileSize($document['file_size']);
                $document['file_url'] = getRelativeUrlPath($document['file_path']);
                $document['file_extension'] = getFileExtension($document['file_path']);
                
                jsonResponse(true, 'Document updated successfully', ['document' => $document]);
            } else {
                jsonResponse(false, 'Failed to update document');
            }
            
        } catch (PDOException $e) {
            error_log("Update Document Error: " . $e->getMessage());
            jsonResponse(false, 'Failed to update document. Please try again.');
        }
    } else {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }
}

/**
 * Delete document
 */
function deleteDocument($documentId) {
    global $pdo;
    
    try {
        // Verify document belongs to current user and get file path
        $stmt = $pdo->prepare("SELECT id, file_path FROM documents WHERE id = ? AND user_id = ?");
        $stmt->execute([$documentId, getCurrentUserId()]);
        $document = $stmt->fetch();
        
        if (!$document) {
            jsonResponse(false, 'Document not found or access denied');
            return;
        }
        
        // Delete document from database
        $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ? AND user_id = ?");
        $success = $stmt->execute([$documentId, getCurrentUserId()]);
        
        if ($success) {
            // Delete file if exists
            if ($document['file_path'] && file_exists($document['file_path'])) {
                deleteFile($document['file_path']);
            }
            
            jsonResponse(true, 'Document deleted successfully');
        } else {
            jsonResponse(false, 'Failed to delete document');
        }
        
    } catch (PDOException $e) {
        error_log("Delete Document Error: " . $e->getMessage());
        jsonResponse(false, 'Failed to delete document. Please try again.');
    }
}

/**
 * Download document file
 */
function downloadDocument($documentId) {
    global $pdo;
    
    try {
        // Verify document belongs to current user and get file info
        $stmt = $pdo->prepare("
            SELECT id, title, file_path, file_type 
            FROM documents 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$documentId, getCurrentUserId()]);
        $document = $stmt->fetch();
        
        if (!$document) {
            header('HTTP/1.1 404 Not Found');
            echo 'Document not found or access denied';
            exit();
        }
        
        $filePath = $document['file_path'];
        
        // Check if file exists
        if (!file_exists($filePath) || !is_file($filePath)) {
            header('HTTP/1.1 404 Not Found');
            echo 'File not found on server';
            exit();
        }
        
        // Set headers for file download
        $filename = $document['title'] . '.' . pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Output file
        readfile($filePath);
        exit();
        
    } catch (PDOException $e) {
        error_log("Download Document Error: " . $e->getMessage());
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Failed to download document';
        exit();
    }
}

// TODO: Frontend integration point
// AJAX endpoints:
// GET  /documents/documents.php?action=list - Get all documents
// GET  /documents/documents.php?action=get&id=1 - Get single document
// POST /documents/documents.php?action=upload - Upload document (with FormData)
// POST /documents/documents.php?action=update - Update document info
// GET  /documents/documents.php?action=delete&id=1 - Delete document
// GET  /documents/documents.php?action=download&id=1 - Download document file

