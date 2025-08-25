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

$anon_id = trim($input['anon_id']);
$post_id = intval($input['post_id']);

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT anon_id FROM users WHERE anon_id = ?");
    $stmt->execute([$anon_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Check if post exists
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
        exit;
    }
    
    // Check if user already liked this post
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND anon_id = ?");
    $stmt->execute([$post_id, $anon_id]);
    $existing_like = $stmt->fetch();
    
    if ($existing_like) {
        // Unlike the post
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND anon_id = ?");
        $stmt->execute([$post_id, $anon_id]);
        
        echo json_encode([
            'success' => true,
            'action' => 'unliked',
            'message' => 'Post unliked successfully'
        ]);
    } else {
        // Like the post
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, anon_id) VALUES (?, ?)");
        $stmt->execute([$post_id, $anon_id]);
        
        echo json_encode([
            'success' => true,
            'action' => 'liked',
            'message' => 'Post liked successfully'
        ]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>