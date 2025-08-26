<?php
require_once 'config.php';

session_start();
if (!isset($_SESSION['anon_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$post_id = $_POST['post_id'] ?? 0;
$parent_comment_id = $_POST['parent_comment_id'] ?? null;
$content = $_POST['content'] ?? '';
$media = $_FILES['media'] ?? null;
$tags = $_POST['tags'] ?? '[]';

if (empty($content) && empty($media)) {
    http_response_code(400);
    echo json_encode(['error' => 'Content or media required']);
    exit;
}

$media_path = null;
if ($media && $media['size'] <= MAX_FILE_SIZE) {
    $ext = pathinfo($media['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'mp4'];
    if (in_array(strtolower($ext), $allowed)) {
        $filename = uniqid() . '.' . $ext;
        $destination = UPLOAD_DIR . $filename;
        if (!move_uploaded_file($media['tmp_name'], $destination)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to upload media']);
            exit;
        }
        $media_path = $destination;
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }
}

try {
    $pdo->beginTransaction();
    
    // Insert comment
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, parent_comment_id, media, tags, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$post_id, $_SESSION['user_id'], $content, $parent_comment_id, $media_path, $tags]);
    $comment_id = $pdo->lastInsertId();
    
    // Handle mentions
    preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
    if (!empty($matches[1])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE display_name = ?");
        $insert_mention = $pdo->prepare("INSERT INTO mentions (source_type, source_id, mentioned_user_id) VALUES ('comment', ?, ?)");
        foreach ($matches[1] as $username) {
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user) {
                $insert_mention->execute([$comment_id, $user['id']]);
            }
        }
    }
    
    // Notify post owner or parent comment owner
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, source_type, source_id, created_at) VALUES (?, ?, 'comment', ?, NOW())");
    $target_id = $parent_comment_id 
        ? $pdo->query("SELECT user_id FROM comments WHERE id = $parent_comment_id")->fetchColumn()
        : $pdo->query("SELECT user_id FROM posts WHERE id = $post_id")->fetchColumn();
    $stmt->execute([$target_id, 'comment', $comment_id]);
    
    $pdo->commit();
    echo json_encode(['success' => true, 'comment_id' => $comment_id]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit comment: ' . $e->getMessage()]);
}
?>