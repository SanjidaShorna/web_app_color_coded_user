<?php
session_start();
require 'db.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Get user role
    $query = "SELECT roles.role_name FROM users 
              JOIN roles ON users.role_id = roles.id 
              WHERE users.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $role = $result->fetch_assoc()['role_name'] ?? null;
    
    // Redirect based on role
    if ($role === 'Admin') {
        header('Location: admin_page.php');
    } else {
        header('Location: dashboard.php');  // Redirect all users to dashboard
    }
} else {
    // Redirect to login page
    header('Location: login.php');
}
exit;
?>