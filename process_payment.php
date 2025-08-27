<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$anon_id = $input['anon_id'] ?? null;
$amount = $input['amount'] ?? null;
$currency = $input['currency'] ?? 'USD';
$description = $input['description'] ?? 'Points purchase';

if (empty($anon_id) || empty($amount) || !is_numeric($amount) || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid required fields']);
    exit;
}

// Simulate PayChangu API call
// In a real application, you would integrate with PayChangu's SDK or API here.
// This is a placeholder for demonstration purposes.

// For demonstration, assume payment is always successful.
$transaction_id = 'PAYCHG_' . uniqid();
$points_awarded = floor($amount * 100); // Example: 1 USD = 100 points

try {
    $pdo->beginTransaction();

    // Record the transaction
    $stmt = $pdo->prepare("INSERT INTO transactions (anon_id, transaction_id, amount, currency, description, points_awarded) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$anon_id, $transaction_id, $amount, $currency, $description, $points_awarded]);

    // Update user's points
    $stmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE anon_id = ?");
    $stmt->execute([$points_awarded, $anon_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Payment processed and points awarded successfully!',
        'transaction_id' => $transaction_id,
        'points_awarded' => $points_awarded
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

