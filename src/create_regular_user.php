<?php
require 'db.php';

// Check if regular user already exists
$query = "SELECT id FROM user_management.users WHERE username = 'user'";
$result = query_safe($conn, $query);

if ($result->rowCount() === 0) {
    // Create regular user
    $username = 'user';
    $email = 'user@example.com'; // Added email as it's required in the schema
    $password = 'user123'; // You should change this to a secure password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role_id = 2; // User role (not admin)
    
    $query = "INSERT INTO user_management.users (username, email, password, role_id) VALUES (:username, :email, :password, :role_id)";
    $params = [
        ':username' => $username,
        ':email' => $email,
        ':password' => $hashed_password,
        ':role_id' => $role_id
    ];
    
    try {
        query_safe($conn, $query, $params);
        echo "Regular user created successfully!<br>";
        echo "Username: user<br>";
        echo "Password: user123<br>";
        echo "<a href='login.php'>Go to login page</a>";
    } catch (PDOException $e) {
        echo "Failed to create regular user: " . $e->getMessage();
    }
} else {
    echo "Regular user already exists.<br>";
    echo "<a href='login.php'>Go to login page</a>";
}
?>