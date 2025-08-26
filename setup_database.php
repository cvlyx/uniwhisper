<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = $_ENV['DATABASE_HOST'] ?? 'dpg-d2n2ufqli9vc73chmqa0-a.oregon-postgres.render.com';
$dbname = $_ENV['DATABASE_NAME'] ?? 'uniwhisper_db';
$username = $_ENV['DATABASE_USER'] ?? 'uniwhisper_db';
$password = $_ENV['DATABASE_PASSWORD'] ?? 'YRdLcCPKjWtHvx9EUCSf8Kpasr0qTzXk';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname;port=5432", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = <<<EOT
    -- Table structure for table comments
    CREATE TABLE comments (
        id SERIAL PRIMARY KEY,
        post_id INTEGER NOT NULL,
        anon_id VARCHAR(32) NOT NULL,
        content TEXT NOT NULL,
        media VARCHAR(255) DEFAULT NULL,
        tags TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(tags)),
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );

    INSERT INTO comments (id, post_id, anon_id, content, media, tags, created_at) VALUES
    (1, 4, '68549ac5db63ea5e71f644f1bf7afe24', 'WQERE', NULL, '[]', '2025-08-26 15:14:40'),
    (2, 4, '68549ac5db63ea5e71f644f1bf7afe24', 'INDEE', 'uploads/comments/68adcf8a732d5.jpg', '[]', '2025-08-26 15:15:22'),
    ... -- Add all tables (posts, users, etc.) and data here
    -- Table structure for table users
    CREATE TABLE users (
        id SERIAL PRIMARY KEY,
        anon_id VARCHAR(32) NOT NULL,
        display_name VARCHAR(50) NOT NULL DEFAULT 'Anonymous User',
        profile_picture VARCHAR(255) DEFAULT 'https://via.placeholder.com/100/F3F4F6/6B7280?TEXT=A',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );

    INSERT INTO users (id, anon_id, display_name, profile_picture, created_at) VALUES
    (1, '68549ac5db63ea5e71f644f1bf7afe24', 'Anonymous User', 'https://via.placeholder.com/100/F3F4F6/6B7280?TEXT=A', '2025-08-26 13:39:14'),
    ... -- Continue with all remaining INSERTs and constraints
    -- Constraints for table notifications (fixed)
    ALTER TABLE notifications
      ADD CONSTRAINT notifications_ibfk_1 FOREIGN KEY (anon_id) REFERENCES users (anon_id) ON DELETE CASCADE,
      ADD CONSTRAINT notifications_ibfk_2 FOREIGN KEY (source_id) REFERENCES users (anon_id) ON DELETE CASCADE,
      ADD CONSTRAINT notifications_ibfk_3 FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE SET NULL,
      ADD CONSTRAINT notifications_ibfk_4 FOREIGN KEY (comment_id) REFERENCES comments (id) ON DELETE SET NULL;
    EOT;

    foreach (explode(';', $sql) as $statement) {
        $statement = trim($statement);
        if ($statement) {
            $pdo->exec($statement);
        }
    }
    echo "Database setup and data import completed for Postgres.";
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
?>