<?php
// Render environment database connection
$host = getenv("DATABASE_HOST") ?: "db"; // Get from environment variable, fallback to "db"
$username = getenv("DATABASE_USERNAME") ?: "user";
$password = getenv("DATABASE_PASSWORD") ?: "password";
$dbname = getenv("DATABASE_NAME") ?: "user_management";
$port = getenv("DATABASE_PORT") ?: 3307;

$conn = new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
