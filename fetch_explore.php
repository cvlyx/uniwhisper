<?php
require_once 'config.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get trending posts (most liked in last 24 hours)
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.content,
            p.image,
            p.created_at,
            u.display_name,
            u.profile_picture,
            COUNT(DISTINCT l.id) as like_count,
            COUNT(DISTINCT c.id) as comment_count
        FROM posts p
        LEFT JOIN likes l ON p.id = l.post_id
        LEFT JOIN comments c ON p.id = c.post_id
        LEFT JOIN users u ON p.anon_id = u.anon_id
        WHERE p.created_at >= NOW() - INTERVAL '24 hours'
        GROUP BY p.id, p.content, p.image, p.created_at, u.display_name, u.profile_picture
        ORDER BY like_count DESC, comment_count DESC
        LIMIT 20
    ");
    $stmt->execute();
    $trending_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get popular tags (from post content)
    $stmt = $pdo->prepare("
        SELECT 
            LOWER(REGEXP_REPLACE(SUBSTRING(content FROM '#[a-zA-Z0-9_]+'), '^#', '')) as tag_name,
            COUNT(*) as tag_count
        FROM posts
        WHERE content ~ '#[a-zA-Z0-9_]+'
        GROUP BY tag_name
        ORDER BY tag_count DESC
        LIMIT 10
    ");
    $stmt->execute();
    $popular_tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get active users (most posts in last week)
    $stmt = $pdo->prepare("
        SELECT 
            u.anon_id,
            u.display_name,
            u.profile_picture,
            COUNT(p.id) as post_count
        FROM users u
        LEFT JOIN posts p ON u.anon_id = p.anon_id AND p.created_at >= NOW() - INTERVAL '7 days'
        GROUP BY u.anon_id, u.display_name, u.profile_picture
        ORDER BY post_count DESC
        LIMIT 10
    ");
    $stmt->execute();
    $active_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'trending_posts' => $trending_posts,
        'popular_tags' => $popular_tags,
        'active_users' => $active_users
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>