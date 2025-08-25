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
if (!isset($input['anon_id']) || !isset($input['post_id']) || !isset($input['content'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: anon_id, post_id, content']);
    exit;
}

$anon_id = trim($input['anon_id']);
$post_id = intval($input['post_id']);
$content = trim($input['content']);

// Validate content length
if (empty($content) || strlen($content) > 500) {
    http_response_code(400);
    echo json_encode(['error' => 'Comment must be between 1 and 500 characters']);
    exit;
}

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
    
    // Insert comment
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, anon_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $anon_id, $content]);
    
    $comment_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'comment_id' => $comment_id,
        'message' => 'Comment added successfully'
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>