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
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $image = null;
} else {
    // JSON submission (for backward compatibility)
    $input = json_decode(file_get_contents('php://input'), true);
    $anon_id = isset($input['anon_id']) ? trim($input['anon_id']) : '';
    $content = isset($input['content']) ? trim($input['content']) : '';
    $image = null;
}

// Validate input
if (empty($anon_id) || empty($content)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: anon_id, content']);
    exit;
}

// Validate content length
if (strlen($content) > 1000) {
    http_response_code(400);
    echo json_encode(['error' => 'Post content must be less than 1000 characters']);
    exit;
}

// Handle file upload if present
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/posts/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];
    if (!in_array($_FILES['image']['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, GIF, and MP4 are allowed.']);
        exit;
    }
    
    // Validate file size (5MB max)
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File size must be less than 5MB']);
        exit;
    }
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
        $image = $filePath;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload file']);
        exit;
    }
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
    
    // Insert post
    $stmt = $pdo->prepare("INSERT INTO posts (anon_id, content, image) VALUES (?, ?, ?)");
    $stmt->execute([$anon_id, $content, $image]);
    
    $post_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'post_id' => $post_id,
        'message' => 'Post submitted successfully'
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>