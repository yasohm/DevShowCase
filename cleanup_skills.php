<?php
/**
 * Cleanup script to fix double-encoded skills data
 */
require_once __DIR__ . '/config/config.php';

$pdo = getDBConnection();

try {
    $stmt = $pdo->query("SELECT id, username, skills FROM users WHERE skills IS NOT NULL AND skills != ''");
    $users = $stmt->fetchAll();
    
    $fixedCount = 0;
    foreach ($users as $user) {
        $skills = $user['skills'];
        $decodedOnce = json_decode($skills, true);
        
        // If it decodes to a string that is ITSELF valid JSON, it was double encoded
        if (is_string($decodedOnce)) {
            $decodedTwice = json_decode($decodedOnce, true);
            if (is_array($decodedTwice)) {
                echo "Fixing double encoding for user: {$user['username']}\n";
                $updateStmt = $pdo->prepare("UPDATE users SET skills = ? WHERE id = ?");
                $updateStmt->execute([json_encode($decodedTwice), $user['id']]);
                $fixedCount++;
            }
        }
    }
    
    echo "Done! Fixed $fixedCount users.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
