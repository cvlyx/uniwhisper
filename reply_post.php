<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$anon_id = trim($input['anon_id'] ?? '');
$comment_id = intval($input['comment_id'] ?? 0);
$content = trim($input['content'] ?? '');
$media = null;

if (empty($anon_id) || empty($comment_id) || empty($content)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

if (strlen($content) > 500) {
    http_response_code(400);
    echo json_encode(['error' => 'Reply too long']);
    exit;
}

if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '/tmp/uploads/replies/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
    $fileExtension = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];
    if (!in_array($_FILES['media']['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }
    if ($_FILES['media']['size'] > 3 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File size too large']);
        exit;
    }
    if (move_uploaded_file($_FILES['media']['tmp_name'], $filePath)) {
        $media = $filePath;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Upload failed']);
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

    $stmt = $pdo->prepare("SELECT c.anon_id as comment_owner_id, p.anon_id as post_owner_id FROM comments c JOIN posts p ON c.post_id = p.id WHERE c.id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$comment) {
        http_response_code(404);
        echo json_encode(['error' => 'Comment not found']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO replies (comment_id, anon_id, content, media) VALUES (?, ?, ?, ?)");
    $stmt->execute([$comment_id, $anon_id, $content, $media]);
    $reply_id = $pdo->lastInsertId();

    if ($comment['comment_owner_id'] !== $anon_id) {
        $stmt = $pdo->prepare("INSERT INTO notifications (anon_id, type, source_id, post_id, comment_id) VALUES (?, 'reply', ?, ?, ?)");
        $stmt->execute([$comment['comment_owner_id'], $anon_id, $comment['post_id'], $comment_id]);
    }

    $stmt = $pdo->prepare("SELECT display_name, profile_picture FROM users WHERE anon_id = ?");
    $stmt->execute([$anon_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

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
        'message' => 'Reply added'
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>