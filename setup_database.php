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
    (3, 3, '6b3bb6309db9dc91c43336694c130689', 'ZAPA EASY', NULL, '[]', '2025-08-26 15:16:17'),
    (4, 5, '6b3bb6309db9dc91c43336694c130689', 'AWA', 'uploads/comments/68ade10597c4c.jpg', '[]', '2025-08-26 16:29:57'),
    (5, 6, '6b3bb6309db9dc91c43336694c130689', 'NFFD', NULL, '[]', '2025-08-26 16:33:17'),
    (6, 7, '6b3bb6309db9dc91c43336694c130689', '66', 'uploads/comments/68ade7ea4e1d3.jpg', '[]', '2025-08-26 16:59:22');

    -- Table structure for table follows
    CREATE TABLE follows (
        id SERIAL PRIMARY KEY,
        follower_id VARCHAR(32) NOT NULL,
        following_id VARCHAR(32) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );

    -- Table structure for table likes
    CREATE TABLE likes (
        id SERIAL PRIMARY KEY,
        post_id INTEGER NOT NULL,
        anon_id VARCHAR(32) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );

    INSERT INTO likes (id, post_id, anon_id, created_at) VALUES
    (2, 3, '68549ac5db63ea5e71f644f1bf7afe24', '2025-08-26 15:05:35'),
    (3, 3, '6b3bb6309db9dc91c43336694c130689', '2025-08-26 15:08:43'),
    (4, 4, '6b3bb6309db9dc91c43336694c130689', '2025-08-26 15:10:48'),
    (5, 4, '68549ac5db63ea5e71f644f1bf7afe24', '2025-08-26 15:11:22'),
    (6, 2, '68549ac5db63ea5e71f644f1bf7afe24', '2025-08-26 15:11:38'),
    (7, 1, '68549ac5db63ea5e71f644f1bf7afe24', '2025-08-26 15:18:15'),
    (8, 5, '6b3bb6309db9dc91c43336694c130689', '2025-08-26 16:29:38'),
    (10, 7, '6b3bb6309db9dc91c43336694c130689', '2025-08-26 16:58:57'),
    (11, 6, '6b3bb6309db9dc91c43336694c130689', '2025-08-26 17:01:06'),
    (12, 7, '96da938b77560b6a5a69bcdb0afe74a4', '2025-08-26 18:33:58'),
    (13, 5, '96da938b77560b6a5a69bcdb0afe74a4', '2025-08-26 18:34:19'),
    (14, 6, '96da938b77560b6a5a69bcdb0afe74a4', '2025-08-26 18:34:22'),
    (15, 4, '96da938b77560b6a5a69bcdb0afe74a4', '2025-08-26 18:34:25');

    -- Table structure for table notifications
    CREATE TABLE notifications (
        id SERIAL PRIMARY KEY,
        anon_id VARCHAR(32) NOT NULL,
        type VARCHAR(10) CHECK (type IN ('like','comment','mention','follow')) NOT NULL,
        source_id VARCHAR(32) NOT NULL,
        post_id INTEGER DEFAULT NULL,
        comment_id INTEGER DEFAULT NULL,
        is_read BOOLEAN DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );

    INSERT INTO notifications (id, anon_id, type, source_id, post_id, comment_id, is_read, created_at) VALUES
    (1, '68549ac5db63ea5e71f644f1bf7afe24', 'like', '6b3bb6309db9dc91c43336694c130689', 3, NULL, 0, '2025-08-26 15:08:43'),
    (2, '6b3bb6309db9dc91c43336694c130689', 'like', '68549ac5db63ea5e71f644f1bf7afe24', 4, NULL, 0, '2025-08-26 15:11:22'),
    (3, '6b3bb6309db9dc91c43336694c130689', 'comment', '68549ac5db63ea5e71f644f1bf7afe24', 4, 1, 0, '2025-08-26 15:14:40'),
    (4, '6b3bb6309db9dc91c43336694c130689', 'comment', '68549ac5db63ea5e71f644f1bf7afe24', 4, 2, 0, '2025-08-26 15:15:22'),
    (5, '68549ac5db63ea5e71f644f1bf7afe24', 'comment', '6b3bb6309db9dc91c43336694c130689', 3, 3, 0, '2025-08-26 15:16:17'),
    (6, '68549ac5db63ea5e71f644f1bf7afe24', 'like', '6b3bb6309db9dc91c43336694c130689', 5, NULL, 0, '2025-08-26 16:29:38'),
    (7, '68549ac5db63ea5e71f644f1bf7afe24', 'comment', '6b3bb6309db9dc91c43336694c130689', 5, 4, 0, '2025-08-26 16:29:57'),
    (8, '6b3bb6309db9dc91c43336694c130689', 'like', '96da938b77560b6a5a69bcdb0afe74a4', 7, NULL, 0, '2025-08-26 18:33:58'),
    (9, '68549ac5db63ea5e71f644f1bf7afe24', 'like', '96da938b77560b6a5a69bcdb0afe74a4', 5, NULL, 0, '2025-08-26 18:34:19'),
    (10, '6b3bb6309db9dc91c43336694c130689', 'like', '96da938b77560b6a5a69bcdb0afe74a4', 6, NULL, 0, '2025-08-26 18:34:22'),
    (11, '6b3bb6309db9dc91c43336694c130689', 'like', '96da938b77560b6a5a69bcdb0afe74a4', 4, NULL, 0, '2025-08-26 18:34:25');

    -- Table structure for table posts
    CREATE TABLE posts (
        id SERIAL PRIMARY KEY,
        anon_id VARCHAR(32) NOT NULL,
        content TEXT NOT NULL,
        image VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );

    INSERT INTO posts (id, anon_id, content, image, created_at) VALUES
    (1, '68549ac5db63ea5e71f644f1bf7afe24', '2492992', NULL, '2025-08-26 15:03:59'),
    (2, '68549ac5db63ea5e71f644f1bf7afe24', 'WETEWTTW', NULL, '2025-08-26 15:04:50'),
    (3, '68549ac5db63ea5e71f644f1bf7afe24', '3RR@R3', 'uploads/posts/68adcd319c9ba.PNG', '2025-08-26 15:05:21'),
    (4, '6b3bb6309db9dc91c43336694c130689', 'ADA AWA NDAZOBA', 'uploads/posts/68adce6ea59ad.jpg', '2025-08-26 15:10:38'),
    (5, '68549ac5db63ea5e71f644f1bf7afe24', 'FFSDF', NULL, '2025-08-26 15:34:40'),
    (6, '6b3bb6309db9dc91c43336694c130689', 'AKA', 'uploads/posts/68ade1adc69c3.mp4', '2025-08-26 16:32:45'),
    (7, '6b3bb6309db9dc91c43336694c130689', '999008855442', NULL, '2025-08-26 16:58:53');

    -- Table structure for table replies
    CREATE TABLE replies (
        id SERIAL PRIMARY KEY,
        comment_id INTEGER NOT NULL,
        anon_id VARCHAR(32) NOT NULL,
        content TEXT NOT NULL,
        media VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );

    -- Table structure for table saved_posts
    CREATE TABLE saved_posts (
        id SERIAL PRIMARY KEY,
        anon_id VARCHAR(32) NOT NULL,
        post_id INTEGER NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );

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
    (2, '6b3bb6309db9dc91c43336694c130689', 'Anonymous User', 'https://via.placeholder.com/100/F3F4F6/6B7280?TEXT=A', '2025-08-26 13:41:33'),
    (3, '5c0a2bceab2ff0bee0ce725f233704c8', 'Anonymous User', 'https://via.placeholder.com/100x100/F3F4F6/6B7280?TEXT=A', '2025-08-26 16:50:01'),
    (4, '26fbacdf2b4e2cf1ec396ec3740c2190', 'Anonymous User', 'https://via.placeholder.com/100x100/F3F4F6/6B7280?TEXT=A', '2025-08-26 18:10:32'),
    (5, '96da938b77560b6a5a69bcdb0afe74a4', 'Anonymous User', 'https://via.placeholder.com/100x100/F3F4F6/6B7280?TEXT=A', '2025-08-26 18:33:52'),
    (6, '7fde40cd6209ab861b1c9dff0a31c6da', 'Anonymous User', 'https://via.placeholder.com/100x100/F3F4F6/6B7280?TEXT=A', '2025-08-26 18:49:00'),
    (7, '7c97143f6c7f0a3553713821e8278a40', 'Anonymous User', 'https://via.placeholder.com/100x100/F3F4F6/6B7280?TEXT=A', '2025-08-26 19:33:27');

    -- Indexes for dumped tables
    ALTER TABLE comments ADD PRIMARY KEY (id), ADD KEY idx_post_id (post_id), ADD KEY idx_anon_id (anon_id);
    ALTER TABLE follows ADD PRIMARY KEY (id), ADD UNIQUE KEY unique_follow (follower_id, following_id), ADD KEY idx_follower_id (follower_id), ADD KEY idx_following_id (following_id);
    ALTER TABLE likes ADD PRIMARY KEY (id), ADD UNIQUE KEY unique_like (post_id, anon_id), ADD KEY idx_post_id (post_id), ADD KEY idx_anon_id (anon_id);
    ALTER TABLE notifications ADD PRIMARY KEY (id), ADD KEY source_id (source_id), ADD KEY post_id (post_id), ADD KEY comment_id (comment_id), ADD KEY idx_anon_id (anon_id), ADD KEY idx_is_read (is_read);
    ALTER TABLE posts ADD PRIMARY KEY (id), ADD KEY idx_anon_id (anon_id), ADD KEY idx_created_at (created_at);
    ALTER TABLE replies ADD PRIMARY KEY (id), ADD KEY idx_comment_id (comment_id), ADD KEY idx_anon_id (anon_id);
    ALTER TABLE saved_posts ADD PRIMARY KEY (id), ADD UNIQUE KEY unique_save (anon_id, post_id), ADD KEY post_id (post_id), ADD KEY idx_anon_id (anon_id);
    ALTER TABLE users ADD PRIMARY KEY (id), ADD UNIQUE KEY anon_id (anon_id), ADD KEY idx_anon_id (anon_id);

    -- Constraints for dumped tables
    ALTER TABLE comments ADD CONSTRAINT comments_ibfk_1 FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE, ADD CONSTRAINT comments_ibfk_2 FOREIGN KEY (anon_id) REFERENCES users (anon_id) ON DELETE CASCADE;
    ALTER TABLE follows ADD CONSTRAINT follows_ibfk_1 FOREIGN KEY (follower_id) REFERENCES users (anon_id) ON DELETE CASCADE, ADD CONSTRAINT follows_ibfk_2 FOREIGN KEY (following_id) REFERENCES users (anon_id) ON DELETE CASCADE;
    ALTER TABLE likes ADD CONSTRAINT likes_ibfk_1 FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE, ADD CONSTRAINT likes_ibfk_2 FOREIGN KEY (anon_id) REFERENCES users (anon_id) ON DELETE CASCADE;
    ALTER TABLE notifications ADD CONSTRAINT notifications_ibfk_1 FOREIGN KEY (anon_id) REFERENCES users (anon_id) ON DELETE CASCADE, ADD CONSTRAINT notifications_ibfk_2 FOREIGN KEY (source_id) REFERENCES users (anon_id) ON DELETE CASCADE, ADD CONSTRAINT notifications_ibfk_3 FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE SET NULL, ADD CONSTRAINT notifications_ibfk_4 FOREIGN KEY (comment_id) REFERENCES comments (id) ON DELETE SET NULL;
    ALTER TABLE posts ADD CONSTRAINT posts_ibfk_1 FOREIGN KEY (anon_id) REFERENCES users (anon_id) ON DELETE CASCADE;
    ALTER TABLE replies ADD CONSTRAINT replies_ibfk_1 FOREIGN KEY (comment_id) REFERENCES comments (id) ON DELETE CASCADE, ADD CONSTRAINT replies_ibfk_2 FOREIGN KEY (anon_id) REFERENCES users (anon_id) ON DELETE CASCADE;
    ALTER TABLE saved_posts ADD CONSTRAINT saved_posts_ibfk_1 FOREIGN KEY (anon_id) REFERENCES users (anon_id) ON DELETE CASCADE, ADD CONSTRAINT saved_posts_ibfk_2 FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE;
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