<?php
require 'db.php';

// Check if regular user already exists
$query = "SELECT id FROM users WHERE username = 'user'";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    // Create regular user
    $username = 'user';
    $password = 'user123'; // You should change this to a secure password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role_id = 2; // User role (not admin)
    
    $query = "INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $username, $hashed_password, $role_id);
    
    if ($stmt->execute()) {
        echo "Regular user created successfully!<br>";
        echo "Username: user<br>";
        echo "Password: user123<br>";
        echo "<a href='login.php'>Go to login page</a>";
    } else {
        echo "Failed to create regular user: " . $conn->error;
    }
} else {
    echo "Regular user already exists.<br>";
    echo "<a href='login.php'>Go to login page</a>";
}
?>