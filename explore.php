<?php
require_once 'config.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get trending posts (most liked in last 7 days)
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
        WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY p.id, p.content, p.image, p.created_at, u.display_name, u.profile_picture
        ORDER BY like_count DESC, comment_count DESC
        LIMIT 20
    ");
    $stmt->execute();
    $trending_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get suggested users to follow (excluding current user and already followed)
    $current_user = isset($_GET['anon_id']) ? $_GET['anon_id'] : '';
    $follow_exclusion = $current_user ? "AND u.anon_id != :current_user 
        AND u.anon_id NOT IN (
            SELECT following_id FROM follows WHERE follower_id = :current_user
        )" : "";
    
    $stmt = $pdo->prepare("
        SELECT 
            u.anon_id,
            u.display_name,
            u.profile_picture,
            COUNT(DISTINCT p.id) as post_count,
            COUNT(DISTINCT f.id) as follower_count
        FROM users u
        LEFT JOIN posts p ON u.anon_id = p.anon_id
        LEFT JOIN follows f ON u.anon_id = f.following_id
        WHERE 1=1 $follow_exclusion
        GROUP BY u.anon_id, u.display_name, u.profile_picture
        ORDER BY follower_count DESC, post_count DESC
        LIMIT 10
    ");
    
    if ($current_user) {
        $stmt->bindParam(':current_user', $current_user);
    }
    
    $stmt->execute();
    $suggested_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'trending_posts' => $trending_posts,
        'suggested_users' => $suggested_users
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>