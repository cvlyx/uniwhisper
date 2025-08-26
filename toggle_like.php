<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['anon_id']) || !isset($input['post_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters: anon_id, post_id']);
    exit;
}

$anon_id = trim($input['anon_id']);
$post_id = $input['post_id'];

if (empty($anon_id) || empty($post_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND anon_id = ?");
    $stmt->execute([$post_id, $anon_id]);
    $like = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($like) {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE id = ?");
        $stmt->execute([$like['id']]);
        $liked = false;
    } else {
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, anon_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$post_id, $anon_id]);
        $liked = true;

        // Create notification for post owner
        $stmt = $pdo->prepare("SELECT anon_id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($post && $post['anon_id'] !== $anon_id) {
            $stmt = $pdo->prepare("INSERT INTO notifications (anon_id, type, source_id, post_id, created_at) VALUES (?, 'like', ?, ?, NOW())");
            $stmt->execute([$post['anon_id'], $anon_id, $post_id]);
        }
    }

    echo json_encode([
        'success' => true,
        'liked' => $liked
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>