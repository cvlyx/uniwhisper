<?php
require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Generate unique anonymous ID
    $anon_id = 'anon_' . uniqid() . '_' . bin2hex(random_bytes(8));
    
    // Insert user into database
    $stmt = $pdo->prepare("INSERT INTO users (anon_id) VALUES (?)");
    $stmt->execute([$anon_id]);
    
    // Return the anonymous ID
    echo json_encode([
        'success' => true,
        'anon_id' => $anon_id
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

