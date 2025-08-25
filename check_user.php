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
    // Check if user exists
    $stmt = $pdo->prepare("SELECT anon_id FROM users WHERE anon_id = ?");
    $stmt->execute([$anon_id]);
    $user = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'exists' => $user ? true : false
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>