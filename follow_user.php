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
if (!isset($input['follower_id']) || !isset($input['following_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: follower_id, following_id']);
    exit;
}

$follower_id = $input['follower_id'];
$following_id = $input['following_id'];

try {
    // Check if follow relationship already exists
    $stmt = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$follower_id, $following_id]);
    $existing_follow = $stmt->fetch();
    
    if ($existing_follow) {
        // Unfollow the user
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$follower_id, $following_id]);
        
        echo json_encode([
            'success' => true,
            'action' => 'unfollowed',
            'message' => 'User unfollowed successfully'
        ]);
    } else {
        // Follow the user
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$follower_id, $following_id]);
        
        // Create notification for the followed user
        $stmt = $pdo->prepare("
            INSERT INTO notifications (anon_id, type, source_id) 
            VALUES (?, 'follow', ?)
        ");
        $stmt->execute([$following_id, $follower_id]);
        
        echo json_encode([
            'success' => true,
            'action' => 'followed',
            'message' => 'User followed successfully'
        ]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
