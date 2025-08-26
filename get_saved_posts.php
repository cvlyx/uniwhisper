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
    // Get saved posts
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.content,
            p.image,
            p.created_at,
            u.display_name,
            u.profile_picture,
            COUNT(DISTINCT l.id) as like_count,
            COUNT(DISTINCT c.id) as comment_count,
            sp.created_at as saved_at
        FROM saved_posts sp
        JOIN posts p ON sp.post_id = p.id
        LEFT JOIN likes l ON p.id = l.post_id
        LEFT JOIN comments c ON p.id = c.post_id
        LEFT JOIN users u ON p.anon_id = u.anon_id
        WHERE sp.anon_id = ?
        GROUP BY p.id, p.content, p.image, p.created_at, u.display_name, u.profile_picture, sp.created_at
        ORDER BY sp.created_at DESC
    ");
    $stmt->execute([$anon_id]);
    $saved_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'saved_posts' => $saved_posts
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
