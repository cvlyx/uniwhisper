<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

try {
    $anon_id = isset($_GET['anon_id']) ? trim($_GET['anon_id']) : '';
    if (empty($anon_id)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized: Missing anon_id']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE anon_id = ?');
    $stmt->execute([$anon_id]);
    if (!$stmt->fetch()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized: Invalid anon_id']);
        exit;
    }

    $stmt = $pdo->prepare('
        SELECT 
            p.id, p.content, p.image, p.created_at,
            u.display_name, u.profile_picture,
            COALESCE((SELECT COUNT(*) FROM likes WHERE post_id = p.id), 0) as like_count,
            COALESCE((SELECT COUNT(*) FROM comments WHERE post_id = p.id), 0) as comment_count,
            COALESCE((SELECT 1 FROM likes WHERE post_id = p.id AND anon_id = ? LIMIT 1), 0) as liked
        FROM posts p
        LEFT JOIN users u ON p.anon_id = u.anon_id
        ORDER BY p.created_at DESC
        LIMIT 50
    ');
    $stmt->execute([$anon_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($posts as &$post) {
        $stmt = $pdo->prepare('
            SELECT c.id, c.content, c.media, c.tags, c.created_at,
                   u.display_name, u.profile_picture
            FROM comments c
            LEFT JOIN users u ON c.anon_id = u.anon_id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ');
        $stmt->execute([$post['id']]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($comments as &$comment) {
            $stmt = $pdo->prepare('
                SELECT r.id, r.content, r.media, r.created_at,
                       u.display_name, u.profile_picture
                FROM replies r
                LEFT JOIN users u ON r.anon_id = u.anon_id
                WHERE r.comment_id = ?
                ORDER BY r.created_at ASC
            ');
            $stmt->execute([$comment['id']]);
            $comment['replies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $post['comments'] = $comments;
        $post['timestamp'] = (new DateTime($post['created_at']))->format('M d, Y h:ia');
    }

    echo json_encode([
        'success' => true,
        'posts' => $posts
    ]);
} catch (Exception $e) {
    error_log('Error in fetch_posts.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>