<?php
require_once 'config.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Validate input
if (!isset($_GET['anon_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameter: anon_id']);
    exit;
}

$anon_id = $_GET['anon_id'];

try {
    // Get user profile
    $stmt = $pdo->prepare("
        SELECT 
            anon_id,
            display_name,
            profile_picture,
            points,
            created_at
        FROM users 
        WHERE anon_id = ?
    ");
    $stmt->execute([$anon_id]);
    $user_profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_profile) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Get user stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT p.id) as post_count,
            COUNT(DISTINCT f1.id) as following_count,
            COUNT(DISTINCT f2.id) as followers_count
        FROM users u
        LEFT JOIN posts p ON u.anon_id = p.anon_id
        LEFT JOIN follows f1 ON u.anon_id = f1.follower_id
        LEFT JOIN follows f2 ON u.anon_id = f2.following_id
        WHERE u.anon_id = ?
        GROUP BY u.anon_id
    ");
    $stmt->execute([$anon_id]);
    $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get user's posts
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.content,
            p.image,
            p.created_at,
            COUNT(DISTINCT l.id) as like_count,
            COUNT(DISTINCT c.id) as comment_count
        FROM posts p
        LEFT JOIN likes l ON p.id = l.post_id
        LEFT JOIN comments c ON p.id = c.post_id
        WHERE p.anon_id = ?
        GROUP BY p.id, p.content, p.image, p.created_at
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$anon_id]);
    $user_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'profile' => $user_profile,
        'stats' => $user_stats,
        'posts' => $user_posts
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
