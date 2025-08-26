<?php
require_once 'config.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get anon_id from query parameter
if (!isset($_GET['anon_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing anon_id parameter']);
    exit;
}

$anon_id = trim($_GET['anon_id']);

try {
    // Get user profile
    $stmt = $pdo->prepare("
        SELECT 
            anon_id,
            display_name,
            profile_picture,
            created_at
        FROM users 
        WHERE anon_id = ?
    ");
    $stmt->execute([$anon_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        http_response_code(404);
        echo json_encode(['error' => 'User  not found']);
        exit;
    }

    // Get user stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT p.id) as post_count,
            COUNT(DISTINCT c.id) as comment_count,
            (SELECT COUNT(DISTINCT l2.id) FROM posts p2 JOIN likes l2 ON p2.id = l2.post_id WHERE p2.anon_id = ?) as received_likes
        FROM users u
        LEFT JOIN posts p ON u.anon_id = p.anon_id
        LEFT JOIN comments c ON u.anon_id = c.anon_id
        WHERE u.anon_id = ?
    ");
    $stmt->execute([$anon_id, $anon_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get user's recent posts
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
    $recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'profile' => $profile,
        'stats' => $stats,
        'recent_posts' => $recent_posts
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
