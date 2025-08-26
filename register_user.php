<?php
require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Function to generate random display names
function generateRandomNames($count = 10) {
    $adjectives = ['Cool', 'Chill', 'Epic', 'Mighty', 'Sly', 'Brave', 'Witty', 'Noble', 'Swift', 'Bold'];
    $nouns = ['Tiger', 'Eagle', 'Shark', 'Dragon', 'Phoenix', 'Wolf', 'Falcon', 'Lion', 'Bear', 'Hawk'];
    $names = [];
    for ($i = 0; $i < $count; $i++) {
        $names[] = $adjectives[array_rand($adjectives)] . ' ' . $nouns[array_rand($nouns)];
    }
    return $names;
}

// Function to generate random profile picture URLs
function generateRandomAvatars($count = 10) {
    $avatars = [];
    for ($i = 1; $i <= $count; $i++) {
        $avatars[] = "https://i.pravatar.cc/150?img=$i";
    }
    return $avatars;
}

// Check if the user is registering
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['selected_name']) || !isset($input['selected_avatar'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$selected_name = trim($input['selected_name']);
$selected_avatar = trim($input['selected_avatar']);

// Validate inputs
if (empty($selected_name) || empty($selected_avatar)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid display name or avatar']);
    exit;
}

try {
    // Generate unique anon_id
    $anon_id = bin2hex(random_bytes(16)); // Generate a 32-character unique ID
    $stmt = $pdo->prepare("INSERT INTO users (anon_id, display_name, profile_picture) VALUES (?, ?, ?)");
    $stmt->execute([$anon_id, $selected_name, $selected_avatar]);
    echo json_encode([
        'success' => true,
        'anon_id' => $anon_id
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>