<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $anon_id = $_GET['anon_id'] ?? '';
    if (!$anon_id) {
        throw new Exception('Invalid anon_id');
    }

    $stmt = $pdo->prepare("SELECT u.*, 
        (SELECT COUNT(*) FROM posts p WHERE p.anon_id = u.anon_id) as post_count,
        (SELECT COUNT(*) FROM comments c WHERE c.anon_id = u.anon_id) as comment_count,
        (SELECT COUNT(*) FROM likes l WHERE l.anon_id = u.anon_id) as received_likes
        FROM users u WHERE u.anon_id = ?");
    $stmt->execute([$anon_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM posts WHERE anon_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$anon_id]);
    $recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'profile' => $profile, 'recent_posts' => $recent_posts]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>