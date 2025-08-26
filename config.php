<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration from environment variables
$host = $_ENV['DATABASE_HOST'] ?? 'dpg-d2n2ufqli9vc73chmqa0-a.oregon-postgres.render.com';
$dbname = $_ENV['DATABASE_NAME'] ?? 'uniwhisper_db';
$username = $_ENV['DATABASE_USER'] ?? 'uniwhisper_db';
$password = $_ENV['DATABASE_PASSWORD'] ?? 'YRdLcCPKjWtHvx9EUCSf8Kpasr0qTzXk';

// Create connection
try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname;port=5432", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Enable CORS for all origins
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}
?>