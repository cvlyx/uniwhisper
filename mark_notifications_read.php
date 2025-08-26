<?php
require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['anon_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required field: anon_id']);
    exit;
}

$anon_id = $input['anon_id'];
$notification_ids = isset($input['notification_ids']) ? $input['notification_ids'] : [];

try {
    if (!empty($notification_ids)) {
        // Mark specific notifications as read
        $placeholders = implode(',', array_fill(0, count($notification_ids), '?'));
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE anon_id = ? AND id IN ($placeholders)
        ");
        $stmt->execute(array_merge([$anon_id], $notification_ids));
    } else {
        // Mark all notifications as read
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE anon_id = ?
        ");
        $stmt->execute([$anon_id]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Notifications marked as read'
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
