<?php
require_once 'config.php';
session_start();

$anon_id = $_POST['anon_id'] ?? '';
if (empty($anon_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing anon_id']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, anon_id FROM users WHERE anon_id = ?');
    $stmt->execute([$anon_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['anon_id'] = $user['anon_id'];
        $_SESSION['user_id'] = $user['id'];
        echo json_encode(['success' => true, 'anon_id' => $user['anon_id']]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid anon_id']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>