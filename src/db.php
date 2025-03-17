<?php
$host = "db"; // The service name in docker-compose
$username = "user";
$password = "password";
$dbname = "user_management";
$port = 3307;

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
