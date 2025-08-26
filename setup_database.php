<?php
// Load environment variables and database configuration
require_once 'config.php';

// Function to execute SQL queries
function executeQuery($pdo, $sql) {
    try {
        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        echo "Query failed: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Drop existing tables if they exist (for reset)
$dropTables = <<<EOT
DROP TABLE IF EXISTS comments CASCADE;
DROP TABLE IF EXISTS posts CASCADE;
DROP TABLE IF EXISTS users CASCADE;
EOT;

if (executeQuery($pdo, $dropTables)) {
    echo "Dropped existing tables successfully.<br>";
} else {
    echo "Failed to drop tables.<br>";
    exit;
}

// Create tables
$createTables = <<<EOT
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    anon_id VARCHAR(32) NOT NULL,
    display_name VARCHAR(50) NOT NULL DEFAULT 'Anonymous User',
    profile_picture VARCHAR(255) DEFAULT 'https://via.placeholder.com/100x100/F3F4F6/6B7280?text=A',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id SERIAL PRIMARY KEY,
    anon_id VARCHAR(32) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    like_count INTEGER DEFAULT 0,
    comment_count INTEGER DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tags TEXT DEFAULT NULL,
    FOREIGN KEY (anon_id) REFERENCES users(anon_id) ON DELETE CASCADE
);

CREATE TABLE comments (
    id SERIAL PRIMARY KEY,
    post_id INTEGER NOT NULL,
    anon_id VARCHAR(32) NOT NULL,
    content TEXT NOT NULL,
    media VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tags TEXT DEFAULT NULL,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (anon_id) REFERENCES users(anon_id) ON DELETE CASCADE
);
EOT;

if (executeQuery($pdo, $createTables)) {
    echo "Created tables successfully.<br>";
} else {
    echo "Failed to create tables.<br>";
    exit;
}

// Insert initial data
$insertData = <<<EOT
INSERT INTO users (anon_id, display_name) VALUES
('user1_anon', 'Campus Whisperer'),
('user2_anon', 'Silent Observer');

INSERT INTO posts (anon_id, content) VALUES
('user1_anon', 'First whisper of the semester! #campuslife'),
('user2_anon', 'Heard a rumor about the library! #rumors');

INSERT INTO comments (post_id, anon_id, content) VALUES
((SELECT id FROM posts WHERE anon_id = 'user1_anon' LIMIT 1), 'user2_anon', 'Interesting! #discussion'),
((SELECT id FROM posts WHERE anon_id = 'user2_anon' LIMIT 1), 'user1_anon', 'Tell me more! #curious');
EOT;

if (executeQuery($pdo, $insertData)) {
    echo "Inserted initial data successfully.<br>";
} else {
    echo "Failed to insert initial data.<br>";
    exit;
}

echo "Database setup and data import completed for Postgres.";
?>