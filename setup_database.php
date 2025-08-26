<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration
$host = $_ENV['DATABASE_HOST'] ?? 'dpg-d2n2ufqli9vc73chmqa0-a.oregon-postgres.render.com';
$dbname = $_ENV['DATABASE_NAME'] ?? 'uniwhisper_db';
$username = $_ENV['DATABASE_USER'] ?? 'uniwhisper_db';
$password = $_ENV['DATABASE_PASSWORD'] ?? 'YRdLcCPKjWtHvx9EUCSf8Kpasr0qTzXk';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname;port=5432", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create tables for Postgres
    $sql = "CREATE TABLE IF NOT EXISTS users (
        anon_id VARCHAR(255) PRIMARY KEY,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Database setup completed for Postgres.";
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
?>