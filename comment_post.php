<?php
require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if we're receiving form data or JSON
if (!empty($_POST)) {
    // Form data submission
    $anon_id = isset($_POST['anon_id']) ? trim($_POST['anon_id']) : '';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $media = null;
    $tags = isset($_POST['tags']) ? $_POST['tags'] : '[]';
} else {
    // JSON submission (for backward compatibility)
    $input = json_decode(file_get_contents('php://input'), true);
    $anon_id = isset($input['anon_id']) ? trim($input['anon_id']) : '';
    $post_id = isset($input['post_id']) ? intval($input['post_id']) : 0;
    $content = isset($input['content']) ? trim($input['content']) : '';
    $media = null;
    $tags = isset($input['tags']) ? $input['tags'] : '[]';
}

// Validate input
if (empty($anon_id) || empty($post_id) || empty($content)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: anon_id, post_id, content']);
    exit;
}

// Validate content length
if (strlen($content) > 500) {
    http_response_code(400);
    echo json_encode(['error' => 'Comment must be less than 500 characters']);
    exit;
}

// Handle file upload if present
if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/comments/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExtension = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];
    if (!in_array($_FILES['media']['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, GIF, and MP4 are allowed.']);
        exit;
    }
    
    // Validate file size (5MB max)
    if ($_FILES['media']['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File size must be less than 5MB']);
        exit;
    }
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['media']['tmp_name'], $filePath)) {
        $media = $filePath;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload file']);
        exit;
    }
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT anon_id, display_name, profile_picture FROM users WHERE anon_id = ?");
    $stmt->execute([$anon_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Check if post exists
    $stmt = $pdo->prepare("SELECT id, anon_id as post_owner_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
        exit;
    }
    
    // Insert comment
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, anon_id, content, media, tags) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$post_id, $anon_id, $content, $media, $tags]);
    
    $comment_id = $pdo->lastInsertId();
    
    // Create notification for the post owner (unless the commenter is the post owner)
    if ($post['post_owner_id'] !== $anon_id) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (anon_id, type, source_id, post_id, comment_id) 
            VALUES (?, 'comment', ?, ?, ?)
        ");
        $stmt->execute([$post['post_owner_id'], $anon_id, $post_id, $comment_id]);
    }
    
    // Return comment data with user info
    echo json_encode([
        'success' => true,
        'comment' => [
            'id' => $comment_id,
            'post_id' => $post_id,
            'anon_id' => $anon_id,
            'content' => $content,
            'media' => $media,
            'tags' => json_decode($tags, true),
            'created_at' => date('Y-m-d H:i:s'),
            'display_name' => $user['display_name'],
            'profile_picture' => $user['profile_picture']
        ],
        'message' => 'Comment added successfully'
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>