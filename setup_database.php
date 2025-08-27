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

// Drop existing tables if they exist (for reset, in reverse order to avoid foreign key issues)
$dropTables = <<<EOT
DROP TABLE IF EXISTS saved_posts CASCADE;
DROP TABLE IF EXISTS replies CASCADE;
DROP TABLE IF EXISTS notifications CASCADE;
DROP TABLE IF EXISTS votes CASCADE;
DROP TABLE IF EXISTS follows CASCADE;
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
-- Table structure for table users
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    anon_id VARCHAR(32) NOT NULL UNIQUE,
    display_name VARCHAR(50) NOT NULL DEFAULT 'Anonymous User',
    profile_picture VARCHAR(255) DEFAULT 'https://via.placeholder.com/100x100/F3F4F6/6B7280?text=A',
    points INTEGER DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table posts
CREATE TABLE posts (
    id SERIAL PRIMARY KEY,
    anon_id VARCHAR(32) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table comments
CREATE TABLE comments (
    id SERIAL PRIMARY KEY,
    post_id INTEGER NOT NULL,
    anon_id VARCHAR(32) NOT NULL,
    content TEXT NOT NULL,
    media VARCHAR(255) DEFAULT NULL,
    tags TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table follows
CREATE TABLE follows (
    id SERIAL PRIMARY KEY,
    follower_id VARCHAR(32) NOT NULL,
    following_id VARCHAR(32) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table votes
CREATE TABLE votes (
    id SERIAL PRIMARY KEY,
    post_id INTEGER NOT NULL,
    anon_id VARCHAR(32) NOT NULL,
    vote_type VARCHAR(10) NOT NULL, -- 'upvote' or 'downvote'
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table notifications
CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    anon_id VARCHAR(32) NOT NULL,
    type VARCHAR(10) NOT NULL,
    source_id VARCHAR(32) NOT NULL,
    post_id INTEGER DEFAULT NULL,
    comment_id INTEGER DEFAULT NULL,
    is_read BOOLEAN DEFAULT false,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

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

-- Table structure for table transactions
CREATE TABLE transactions (
    id SERIAL PRIMARY KEY,
    anon_id VARCHAR(32) NOT NULL,
    transaction_id VARCHAR(255) NOT NULL UNIQUE,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    description TEXT,
    points_awarded INTEGER NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
EOT;

if (executeQuery($pdo, $createTables)) {
    echo "Created tables successfully.<br>";
} else {
    echo "Failed to create tables.<br>";
    exit;
}

// Add foreign key constraints after all tables are created
$addConstraints = <<<EOT
-- Indexes and constraints for table comments
ALTER TABLE comments ADD FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE;
ALTER TABLE comments ADD FOREIGN KEY (anon_id) REFERENCES users(anon_id) ON DELETE CASCADE;
ALTER TABLE comments ADD INDEX idx_post_id (post_id);
ALTER TABLE comments ADD INDEX idx_anon_id (anon_id);

-- Indexes and constraints for table follows
ALTER TABLE follows ADD FOREIGN KEY (follower_id) REFERENCES users(anon_id) ON DELETE CASCADE;
ALTER TABLE follows ADD FOREIGN KEY (following_id) REFERENCES users(anon_id) ON DELETE CASCADE;
ALTER TABLE follows ADD UNIQUE (follower_id, following_id);
ALTER TABLE follows ADD INDEX idx_follower_id (follower_id);
ALTER TABLE follows ADD INDEX idx_following_id (following_id);

-- Indexes and constraints for table votes
ALTER TABLE votes ADD FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE;
ALTER TABLE votes ADD FOREIGN KEY (anon_id) REFERENCES users(anon_id) ON DELETE CASCADE;
ALTER TABLE votes ADD UNIQUE (post_id, anon_id);
ALTER TABLE votes ADD INDEX idx_post_id (post_id);
ALTER TABLE votes ADD INDEX idx_anon_id (anon_id);

-- Indexes and constraints for table notifications
ALTER TABLE notifications ADD FOREIGN KEY (anon_id) REFERENCES users(anon_id) ON DELETE CASCADE;
ALTER TABLE notifications ADD FOREIGN KEY (source_id) REFERENCES users(anon_id) ON DELETE CASCADE;
ALTER TABLE notifications ADD FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE SET NULL;
ALTER TABLE notifications ADD FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE SET NULL;
ALTER TABLE notifications ADD INDEX source_id (source_id);
ALTER TABLE notifications ADD INDEX post_id (post_id);
ALTER TABLE notifications ADD INDEX comment_id (comment_id);
ALTER TABLE notifications ADD INDEX idx_anon_id (anon_id);
ALTER TABLE notifications ADD INDEX idx_is_read (is_read);

-- Indexes and constraints for table posts
ALTER TABLE posts ADD FOREIGN KEY (anon_id) REFERENCES users(anon_id) ON DELETE CASCADE;
ALTER TABLE posts ADD INDEX idx_anon_id (anon_id);
ALTER TABLE posts ADD INDEX idx_created_at (created_at);

-- Indexes and constraints for table replies
ALTER TABLE replies ADD FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE;
ALTER TABLE replies ADD FOREIGN KEY (anon_id) REFERENCES users(anon_id) ON DELETE CASCADE;
ALTER TABLE replies ADD INDEX idx_comment_id (comment_id);
ALTER TABLE replies ADD INDEX idx_anon_id (anon_id);

-- Indexes and constraints for table saved_posts
ALTER TABLE saved_posts ADD FOREIGN KEY (anon_id) REFERENCES users(anon_id) ON DELETE CASCADE;
ALTER TABLE saved_posts ADD FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE;
ALTER TABLE saved_posts ADD UNIQUE (anon_id, post_id);
ALTER TABLE saved_posts ADD INDEX post_id (post_id);
ALTER TABLE saved_posts ADD INDEX idx_anon_id (anon_id);

-- Indexes and constraints for table transactions
ALTER TABLE transactions ADD FOREIGN KEY (anon_id) REFERENCES users(anon_id) ON DELETE CASCADE;
ALTER TABLE transactions ADD INDEX idx_anon_id (anon_id);

-- Indexes and constraints for table users
ALTER TABLE users ADD UNIQUE (anon_id);
ALTER TABLE users ADD INDEX idx_anon_id (anon_id);
EOT;

if (executeQuery($pdo, $addConstraints)) {
    echo "Added constraints and indexes successfully.<br>";
} else {
    echo "Failed to add constraints and indexes.<br>";
    exit;
}

// Insert initial data
$insertData = <<<EOT
INSERT INTO users (id, anon_id, display_name, profile_picture, created_at) VALUES
(1, '68549ac5db63ea5e71f644f1bf7afe24', 'Anonymous User', 'https://via.placeholder.com/100x100/F3F4F6/6B7280?text=A', '2025-08-26 13:39:14'),
(2, '6b3bb6309db9dc91c43336694c130689', 'Anonymous User', 'https://via.placeholder.com/100x100/F3F4F6/6B7280?text=A', '2025-08-26 13:41:33'),
(3, '5c0a2bceab2ff0bee0ce725f233704c8', 'Anonymous User', 'https://via.placeholder.com/100x100/F3F4F6/6B7280?text=A', '2025-08-26 16:50:01'),
(4, '26fbacdf2b4e2cf1ec396ec3740c2190', 'Anonymous User', 'https://via.placeholder.com/100x100/F3F4F6/6B7280?text=A', '2025-08-26 18:10:32'),
(5, '96da938b77560b6a5a69bcdb0afe74a4', 'Anonymous User', 'https://via.placeholder.com/100x100/F3F4F6/6B7280?text=A', '2025-08-26 18:33:52'),
(6, '7fde40cd6209ab861b1c9dff0a31c6da', 'Anonymous User', 'https://via.placeholder.com/100x100/F3F4F6/6B7280?text=A', '2025-08-26 18:49:00'),
(7, '7c97143f6c7f0a3553713821e8278a40', 'Anonymous User', 'https://via.placeholder.com/100x100/F3F4F6/6B7280?text=A', '2025-08-26 19:33:27');

INSERT INTO posts (id, anon_id, content, image, created_at) VALUES
(1, '68549ac5db63ea5e71f644f1bf7afe24', '2492992', NULL, '2025-08-26 15:03:59'),
(2, '68549ac5db63ea5e71f644f1bf7afe24', 'WETEWTTW', NULL, '2025-08-26 15:04:50'),
(3, '68549ac5db63ea5e71f644f1bf7afe24', '3RR@R3', 'uploads/posts/68adcd319c9ba.PNG', '2025-08-26 15:05:21'),
(4, '6b3bb6309db9dc91c43336694c130689', 'ADA AWA NDAZOBA', 'uploads/posts/68adce6ea59ad.jpg', '2025-08-26 15:10:38'),
(5, '68549ac5db63ea5e71f644f1bf7afe24', 'FFSDF', NULL, '2025-08-26 15:34:40'),
(6, '6b3bb6309db9dc91c43336694c130689', 'AKA', 'uploads/posts/68ade1adc69c3.mp4', '2025-08-26 16:32:45'),
(7, '6b3bb6309db9dc91c43336694c130689', '999008855442', NULL, '2025-08-26 16:58:53');

INSERT INTO comments (id, post_id, anon_id, content, media, tags, created_at) VALUES
(1, 4, '68549ac5db63ea5e71f644f1bf7afe24', 'WQERE', NULL, '[]', '2025-08-26 15:14:40'),
(2, 4, '68549ac5db63ea5e71f644f1bf7afe24', 'INDEE', 'uploads/comments/68adcf8a732d5.jpg', '[]', '2025-08-26 15:15:22'),
(3, 3, '6b3bb6309db9dc91c43336694c130689', 'ZAPA EASY', NULL, '[]', '2025-08-26 15:16:17'),
(4, 5, '6b3bb6309db9dc91c43336694c130689', 'AWA', 'uploads/comments/68ade10597c4c.jpg', '[]', '2025-08-26 16:29:57'),
(5, 6, '6b3bb6309db9dc91c43336694c130689', 'NFFD', NULL, '[]', '2025-08-26 16:33:17'),
(6, 7, '6b3bb6309db9dc91c43336694c130689', '66', 'uploads/comments/68ade7ea4e1d3.jpg', '[]', '2025-08-26 16:59:22');



INSERT INTO votes (post_id, anon_id, vote_type, created_at) VALUES
(3, '68549ac5db63ea5e71f644f1bf7afe24', 'upvote', '2025-08-26 15:05:35'),
(3, '6b3bb6309db9dc91c43336694c130689', 'upvote', '2025-08-26 15:08:43'),
(4, '6b3bb6309db9dc91c43336694c130689', 'upvote', '2025-08-26 15:10:48'),
(4, '68549ac5db63ea5e71f644f1bf7afe24', 'downvote', '2025-08-26 15:11:22'),
(2, '68549ac5db63ea5e71f644f1bf7afe24', 'upvote', '2025-08-26 15:11:38');

EOT;

if (executeQuery($pdo, $insertData)) {
    echo "Inserted initial data successfully.<br>";
} else {
    echo "Failed to insert initial data.<br>";
    exit;
}

echo "Database setup and data import completed for Postgres.";
?>