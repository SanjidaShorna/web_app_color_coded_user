<?php
require 'db.php';

// Check if admin user already exists
$query = "SELECT id FROM users WHERE username = 'admin'";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    // Create admin user
    $username = 'admin';
    $password = 'admin123'; // You should change this to a secure password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role_id = 1; // Admin role
    
    $query = "INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $username, $hashed_password, $role_id);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
        echo "<a href='login.php'>Go to login page</a>";
    } else {
        echo "Failed to create admin user: " . $conn->error;
    }
} else {
    echo "Admin user already exists.<br>";
    echo "<a href='login.php'>Go to login page</a>";
}
?>