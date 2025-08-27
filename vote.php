<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$post_id = $input['post_id'] ?? null;
$anon_id = $input['anon_id'] ?? null;
$vote_type = $input['vote_type'] ?? null; // 'upvote' or 'downvote'

if (empty($post_id) || empty($anon_id) || !in_array($vote_type, ['upvote', 'downvote'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid required fields']);
    exit;
}

try {
    // Check if the post and user exist
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT anon_id FROM users WHERE anon_id = ?");
    $stmt->execute([$anon_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Check for existing vote
    $stmt = $pdo->prepare("SELECT vote_type FROM votes WHERE post_id = ? AND anon_id = ?");
    $stmt->execute([$post_id, $anon_id]);
    $existing_vote = $stmt->fetchColumn();

    if ($existing_vote) {
        if ($existing_vote === $vote_type) {
            // User is casting the same vote again, so remove the vote
            $stmt = $pdo->prepare("DELETE FROM votes WHERE post_id = ? AND anon_id = ?");
            $stmt->execute([$post_id, $anon_id]);
            $action = 'removed';
        } else {
            // User is changing their vote
            $stmt = $pdo->prepare("UPDATE votes SET vote_type = ? WHERE post_id = ? AND anon_id = ?");
            $stmt->execute([$vote_type, $post_id, $anon_id]);
            $action = 'changed';
        }
    } else {
        // New vote
        $stmt = $pdo->prepare("INSERT INTO votes (post_id, anon_id, vote_type) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $anon_id, $vote_type]);
        $action = 'added';
    }

    // Get updated vote counts
    $stmt = $pdo->prepare("SELECT 
        (SELECT COUNT(*) FROM votes WHERE post_id = ? AND vote_type = 'upvote') as upvotes,
        (SELECT COUNT(*) FROM votes WHERE post_id = ? AND vote_type = 'downvote') as downvotes");
    $stmt->execute([$post_id, $post_id]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'action' => $action, 'upvotes' => $counts['upvotes'], 'downvotes' => $counts['downvotes']]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

