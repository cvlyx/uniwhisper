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
    $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $media = null;
} else {
    // JSON submission
    $input = json_decode(file_get_contents('php://input'), true);
    $anon_id = isset($input['anon_id']) ? trim($input['anon_id']) : '';
    $comment_id = isset($input['comment_id']) ? intval($input['comment_id']) : 0;
    $content = isset($input['content']) ? trim($input['content']) : '';
    $media = null;
}

// Validate input
if (empty($anon_id) || empty($comment_id) || empty($content)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: anon_id, comment_id, content']);
    exit;
}

// Validate content length
if (strlen($content) > 500) {
    http_response_code(400);
    echo json_encode(['error' => 'Reply must be less than 500 characters']);
    exit;
}

// Handle file upload if present
if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/replies/';
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
    
    // Validate file size (3MB max for replies)
    if ($_FILES['media']['size'] > 3 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File size must be less than 3MB']);
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
    
    // Check if comment exists and get post info
    $stmt = $pdo->prepare("
        SELECT c.id, c.post_id, c.anon_id as comment_owner_id, p.anon_id as post_owner_id 
        FROM comments c 
        JOIN posts p ON c.post_id = p.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$comment) {
        http_response_code(404);
        echo json_encode(['error' => 'Comment not found']);
        exit;
    }
    
    // Insert reply
    $stmt = $pdo->prepare("INSERT INTO replies (comment_id, anon_id, content, media) VALUES (?, ?, ?, ?)");
    $stmt->execute([$comment_id, $anon_id, $content, $media]);
    
    $reply_id = $pdo->lastInsertId();
    
    // Create notification for the comment owner (unless the replier is the comment owner)
    if ($comment['comment_owner_id'] !== $anon_id) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (anon_id, type, source_id, post_id, comment_id) 
            VALUES (?, 'reply', ?, ?, ?)
        ");
        $stmt->execute([$comment['comment_owner_id'], $anon_id, $comment['post_id'], $comment_id]);
    }
    
    // Return reply data with user info
    echo json_encode([
        'success' => true,
        'reply' => [
            'id' => $reply_id,
            'comment_id' => $comment_id,
            'anon_id' => $anon_id,
            'content' => $content,
            'media' => $media,
            'created_at' => date('Y-m-d H:i:s'),
            'display_name' => $user['display_name'],
            'profile_picture' => $user['profile_picture']
        ],
        'message' => 'Reply added successfully'
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>