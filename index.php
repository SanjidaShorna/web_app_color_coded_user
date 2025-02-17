<?php
$servername = "localhost";
$username = "username"; // Replace with your MySQL username
$password = "password"; // Replace with your MySQL password
$dbname = "chatdatabase"; // Database name

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) {
  echo "Database created successfully or already exists.<br>";
} else {
  die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Set SQL mode and start transaction
$conn->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
$conn->query("START TRANSACTION");
$conn->query("SET time_zone = '+00:00'");

// SQL to create tables
$sql = "
CREATE TABLE IF NOT EXISTS User1 (
    User1ID INT PRIMARY KEY AUTO_INCREMENT,
    FirstName VARCHAR(50),
    LastName VARCHAR(50),
    Email VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS User2 (
    User2ID INT PRIMARY KEY AUTO_INCREMENT,
    FirstName VARCHAR(50),
    LastName VARCHAR(50),
    Email VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS Texts (
    TextID INT PRIMARY KEY AUTO_INCREMENT,
    User1ID INT,
    User2ID INT,
    Message TEXT,
    Timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (User1ID) REFERENCES User1(User1ID),
    FOREIGN KEY (User2ID) REFERENCES User2(User2ID)
);";

// Execute multi-query
if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            while ($row = $result->fetch_row()) {
                // Process each row if necessary
            }
            $result->free();
        }
        // Check if there are more result sets
       