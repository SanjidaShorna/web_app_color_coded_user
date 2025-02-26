<?php
$servername = "your_remote_server";
$username = "1234";
$password = "1234";
$dbname = "admin";

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error;
}

$conn->select_db($dbname);

$sql = file_get_contents('path/to/admin.sql');

if ($conn->multi_query($sql) === TRUE) {
    echo "Database setup successfully<br>";
} else {
    echo "Error setting up database: " . $conn->error;
}

$conn->close();
?>