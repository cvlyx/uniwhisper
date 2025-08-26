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
    // Get notifications for user
    $stmt = $pdo->prepare("
        SELECT 
            n.id,
            n.type,
            n.source_id,
            n.post_id,
            n.comment_id,
            n.is_read,
            n.created_at,
            u.display_name as source_display_name,
            u.profile_picture as source_profile_picture,
            p.content as post_content,
            c.content as comment_content
        FROM notifications n
        LEFT JOIN users u ON n.source_id = u.anon_id
        LEFT JOIN posts p ON n.post_id = p.id
        LEFT JOIN comments c ON n.comment_id = c.id
        WHERE n.anon_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$anon_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count unread notifications
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as unread_count 
        FROM notifications 
        WHERE anon_id = ? AND is_read = FALSE
    ");
    $stmt->execute([$anon_id]);
    $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>