<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

try {
    // Get anon_id from query parameters
    $anon_id = isset($_GET['anon_id']) ? trim($_GET['anon_id']) : '';
    if (empty($anon_id)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized: Missing anon_id']);
        exit;
    }

    // Verify user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE anon_id = ?');
    $stmt->execute([$anon_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized: Invalid anon_id']);
        exit;
    }

    // Fetch posts with like and comment counts
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

    // Fetch comments and replies for each post
    foreach ($posts as &$post) {
        // Get comments for this post
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
        
        // Get replies for each comment
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
    }

    echo json_encode([
        'success' => true,
        'posts' => $posts
    ]);
} catch (Exception $e) {
    error_log('Error in fetch_posts.php: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>