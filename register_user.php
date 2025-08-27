<?php
require_once 'config.php';

function generateUniqueAnonId($pdo) {
    do {
        $anon_id = 'uni_' . uniqid();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE anon_id = ?");
        $stmt->execute([$anon_id]);
    } while ($stmt->fetchColumn() > 0);
    return $anon_id;
}

try {
    $anon_id = generateUniqueAnonId($pdo);
    $stmt = $pdo->prepare("INSERT INTO users (anon_id, display_name, profile_picture) VALUES (?, ?, ?)");
    $stmt->execute([$anon_id, 'Anonymous User', 'https://via.placeholder.com/100x100/F3F4F6/6B7280?text=A']);
    echo json_encode(['success' => true, 'anon_id' => $anon_id]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>