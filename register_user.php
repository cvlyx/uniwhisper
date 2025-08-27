<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

function generateRandomNames($count = 10) {
    $adjectives = ['Cool', 'Chill', 'Epic', 'Mighty', 'Sly', 'Brave', 'Witty', 'Noble', 'Swift', 'Bold'];
    $nouns = ['Tiger', 'Eagle', 'Shark', 'Dragon', 'Phoenix', 'Wolf', 'Falcon', 'Lion', 'Bear', 'Hawk'];
    $names = [];
    for ($i = 0; $i < $count; $i++) {
        $names[] = $adjectives[array_rand($adjectives)] . ' ' . $nouns[array_rand($nouns)];
    }
    return $names;
}

function generateRandomAvatars($count = 10) {
    $colors = ['FF6B6B', '4ECDC4', '45B7D1', 'FFA07A', '98D8C8', 'F7DC6F', 'BB8FCE', '85C1E9', 'F8C471', 'A3E4D7'];
    $avatars = [];
    for ($i = 1; $i <= $count; $i++) {
        $color = $colors[array_rand($colors)];
        $initials = chr(65 + rand(0, 25)) . chr(65 + rand(0, 25)); // Random initials
        $avatars[] = "https://via.placeholder.com/100x100/{$color}/FFFFFF?text={$initials}";
    }
    return $avatars;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['selected_name']) || !isset($input['selected_avatar'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$selected_name = trim($input['selected_name']);
$selected_avatar = trim($input['selected_avatar']);

if (empty($selected_name) || empty($selected_avatar)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid display name or avatar']);
    exit;
}

try {
    $anon_id = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("INSERT INTO users (anon_id, display_name, profile_picture, points) VALUES (?, ?, ?, 50)");
    $stmt->execute([$anon_id, $selected_name, $selected_avatar]);
    echo json_encode([
        'success' => true,
        'anon_id' => $anon_id,
        'suggested_names' => generateRandomNames(5),
        'suggested_avatars' => generateRandomAvatars(5)
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>