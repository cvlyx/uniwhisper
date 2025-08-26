<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['anon_id']) || !isset($_POST['display_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters: anon_id, display_name']);
    exit;
}

$anon_id = trim($_POST['anon_id']);
$display_name = trim($_POST['display_name']);

if (empty($anon_id) || empty($display_name) || strlen($display_name) > 50) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$profile_picture = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_picture'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if (!in_array($file['type'], $allowed_types) || $file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type or size']);
        exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $upload_dir = 'uploads/';
    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload file']);
        exit;
    }

    $profile_picture = $upload_dir . $filename;
}

try {
    $query = "UPDATE users SET display_name = ?";
    $params = [$display_name];
    if ($profile_picture) {
        $query .= ", profile_picture = ?";
        $params[] = $profile_picture;
    }
    $query .= " WHERE anon_id = ?";
    $params[] = $anon_id;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>