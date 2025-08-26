<?php
require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['anon_id']) || !isset($input['post_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: anon_id, post_id']);
    exit;
}

$anon_id = $input['anon_id'];
$post_id = intval($input['post_id']);

try {
    // Check if post is already saved
    $stmt = $pdo->prepare("SELECT id FROM saved_posts WHERE anon_id = ? AND post_id = ?");
    $stmt->execute([$anon_id, $post_id]);
    $existing_save = $stmt->fetch();
    
    if ($existing_save) {
        // Unsave the post
        $stmt = $pdo->prepare("DELETE FROM saved_posts WHERE anon_id = ? AND post_id = ?");
        $stmt->execute([$anon_id, $post_id]);
        
        echo json_encode([
            'success' => true,
            'action' => 'unsaved',
            'message' => 'Post unsaved successfully'
        ]);
    } else {
        // Save the post
        $stmt = $pdo->prepare("INSERT INTO saved_posts (anon_id, post_id) VALUES (?, ?)");
        $stmt->execute([$anon_id, $post_id]);
        
        echo json_encode([
            'success' => true,
            'action' => 'saved',
            'message' => 'Post saved successfully'
        ]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>