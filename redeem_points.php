<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$anon_id = $input['anon_id'] ?? null;
$feature = $input['feature'] ?? null;

if (empty($anon_id) || empty($feature)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$feature_costs = [
    'group_chat_access' => 100,
    'enhanced_profile_customization' => 50,
    'content_promotion' => 200,
    'exclusive_content_access' => 150
];

if (!isset($feature_costs[$feature])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid feature']);
    exit;
}

$cost = $feature_costs[$feature];

try {
    $pdo->beginTransaction();

    // Check user's current points
    $stmt = $pdo->prepare("SELECT points FROM users WHERE anon_id = ? FOR UPDATE");
    $stmt->execute([$anon_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    if ($user['points'] < $cost) {
        $pdo->rollBack();
        http_response_code(402);
        echo json_encode(['error' => 'Insufficient points']);
        exit;
    }

    // Deduct points
    $stmt = $pdo->prepare("UPDATE users SET points = points - ? WHERE anon_id = ?");
    $stmt->execute([$cost, $anon_id]);

    // Log the transaction (optional, but good for auditing)
    $stmt = $pdo->prepare("INSERT INTO point_transactions (anon_id, type, amount, feature) VALUES (?, 'redeem', ?, ?)");
    $stmt->execute([$anon_id, $cost, $feature]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Feature unlocked successfully', 'new_points' => $user['points'] - $cost]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

