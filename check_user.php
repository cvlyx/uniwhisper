<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

try {
    $anon_id = isset($_GET['anon_id']) ? trim($_GET['anon_id']) : '';
    if (empty($anon_id)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized: Missing anon_id']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE anon_id = ?');
    $stmt->execute([$anon_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'exists' => !!$user
    ]);
} catch (Exception $e) {
    error_log('Error in check_user.php: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>
