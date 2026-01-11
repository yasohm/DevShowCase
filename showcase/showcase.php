<?php
/**
 * DevShowcase - Community Showcase Backend
 * Fetch projects, documents, and profiles from all users
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/helpers.php';

$pdo = getDBConnection();
$response = [
    'success' => false,
    'members' => [],
    'projects' => [],
    'documents' => []
];

try {
    // 1. Fetch Users (Members)
    $stmt = $pdo->prepare("
        SELECT id, username, first_name, last_name, profile_photo, job_title 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 12
    ");
    $stmt->execute();
    $members = $stmt->fetchAll();
    
    foreach ($members as &$member) {
        if ($member['profile_photo']) {
            $member['profile_photo'] = getRelativeUrlPath($member['profile_photo']);
        }
        $member['full_name'] = trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?: $member['username'];
    }
    unset($member);

    // 2. Fetch Projects
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.first_name, u.last_name 
        FROM projects p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC 
        LIMIT 9
    ");
    $stmt->execute();
    $projects = $stmt->fetchAll();
    
    foreach ($projects as &$project) {
        if (!empty($project['screenshot'])) {
            if (strpos($project['screenshot'], 'http') === 0) {
                $project['screenshot_url'] = $project['screenshot'];
            } else {
                $project['screenshot_url'] = getRelativeUrlPath($project['screenshot']);
            }
        }
        $project['author_name'] = trim(($project['first_name'] ?? '') . ' ' . ($project['last_name'] ?? '')) ?: $project['username'];
    }
    unset($project);

    // 3. Fetch Documents
    $stmt = $pdo->prepare("
        SELECT d.*, u.username, u.first_name, u.last_name 
        FROM documents d
        JOIN users u ON d.user_id = u.id
        ORDER BY d.created_at DESC 
        LIMIT 9
    ");
    $stmt->execute();
    $documents = $stmt->fetchAll();
    
    foreach ($documents as &$doc) {
        $doc['file_size_formatted'] = formatFileSize($doc['file_size']);
        $doc['file_url'] = getRelativeUrlPath($doc['file_path']); // Used for preview if image
        $doc['file_extension'] = getFileExtension($doc['file_path']);
        $doc['author_name'] = trim(($doc['first_name'] ?? '') . ' ' . ($doc['last_name'] ?? '')) ?: $doc['username'];
    }
    unset($doc);

    // 4. Get Total Members Count
    $countStmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalMembers = $countStmt->fetchColumn();

    // Final response
    $response = [
        'success' => true,
        'members' => $members,
        'projects' => $projects,
        'documents' => $documents,
        'total_members' => $totalMembers,
        'is_logged_in' => isLoggedIn()
    ];

} catch (PDOException $e) {
    error_log("Showcase Fetch Error: " . $e->getMessage());
    $response['message'] = "Error loading community data.";
}

header('Content-Type: application/json');
echo json_encode($response);
exit();
