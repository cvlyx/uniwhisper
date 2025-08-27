<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$post_id = $input['post_id'] ?? null;
$anon_id = $input['anon_id'] ?? null;
$reason = $input['reason'] ?? '';

if (empty($post_id) || empty($anon_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Check if the post exists
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
        exit;
    }

    // Check if the user exists
    $stmt = $pdo->prepare("SELECT anon_id FROM users WHERE anon_id = ?");
    $stmt->execute([$anon_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Insert the report into the database
    $stmt = $pdo->prepare("INSERT INTO reports (post_id, reported_by_anon_id, reason) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $anon_id, $reason]);

    echo json_encode(['success' => true, 'message' => 'Post reported successfully']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

