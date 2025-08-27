<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $anon_id = $_POST['anon_id'] ?? '';
    $content = $_POST['content'] ?? '';
    $image = $_FILES['image'] ?? null;

    if (!$anon_id || !$content || strlen($content) > 1000) {
        throw new Exception('Invalid input');
    }

    $stmt = $pdo->prepare("INSERT INTO posts (anon_id, content, image, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$anon_id, $content, $image ? $image['name'] : null]);
    echo json_encode(['success' => true, 'message' => 'Post submitted successfully!']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>