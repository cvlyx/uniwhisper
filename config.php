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
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

header("Access-Control-Allow-Origin: https://uniwhisper.onrender.com");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Set upload limits
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('max_execution_time', 30);
?>