<?php
require_once 'config.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get posts with like counts in reverse chronological order
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.content,
            p.created_at,
            COUNT(DISTINCT l.id) as like_count
        FROM posts p
        LEFT JOIN likes l ON p.id = l.post_id
        GROUP BY p.id, p.content, p.created_at
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get comments for each post
    foreach ($posts as &$post) {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                content,
                created_at
            FROM comments 
            WHERE post_id = ? 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$post['id']]);
        $post['comments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $post['comment_count'] = count($post['comments']);
    }
    
    echo json_encode([
        'success' => true,
        'posts' => $posts
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>