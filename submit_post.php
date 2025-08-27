<?php
require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if we're receiving form data or JSON
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$anon_id = isset($input['anon_id']) ? trim($input['anon_id']) : '';
$content = isset($input['content']) ? trim($input['content']) : '';
$action = isset($input['action']) ? trim($input['action']) : 'post'; // Add action for like

// Validate input
if (empty($anon_id) || ($action === 'post' && empty($content))) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: anon_id, content']);
    exit;
}

// Validate content length
if ($action === 'post' && strlen($content) > 1000) {
    http_response_code(400);
    echo json_encode(['error' => 'Post content must be less than 1000 characters']);
    exit;
}

// Handle file upload if present
$image = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/uploads/posts/'; // Use web-accessible directory
    $webPath = 'uploads/posts/'; // Web-accessible path for URLs
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
    $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    $webFilePath = $webPath . $fileName;
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];
    if (!in_array($_FILES['image']['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File size must be less than 5MB']);
        exit;
    }
    if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
        $image = $webFilePath; // Store web-accessible path
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload file']);
        exit;
    }
}

try {
    $stmt = $pdo->prepare("SELECT anon_id FROM users WHERE anon_id = ?");
    $stmt->execute([$anon_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    if ($action === 'post') {
        $stmt = $pdo->prepare("INSERT INTO posts (anon_id, content, image) VALUES (?, ?, ?)");
        $stmt->execute([$anon_id, $content, $image]);
        $post_id = $pdo->lastInsertId();

        // Award points for new post
        $stmt = $pdo->prepare("UPDATE users SET points = points + 10 WHERE anon_id = ?");
        $stmt->execute([$anon_id]);

        echo json_encode(["success" => true, "post_id" => $post_id, "message" => "Post submitted"]);
    } elseif ($action === 'like' && isset($input['post_id'])) {
        $post_id = $input['post_id'];
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, anon_id) VALUES (?, ?) ON CONFLICT DO NOTHING");
        $stmt->execute([$post_id, $anon_id]);
        $stmt = $pdo->prepare("SELECT anon_id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post_owner = $stmt->fetchColumn();
        if ($post_owner && $post_owner !== $anon_id) {
            $stmt = $pdo->prepare("INSERT INTO notifications (anon_id, type, source_id, post_id) VALUES (?, 'like', ?, ?)");
            $stmt->execute([$post_owner, $anon_id, $post_id]);
        }
        echo json_encode(['success' => true, 'message' => 'Post liked']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>