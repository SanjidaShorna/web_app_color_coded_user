<?php
session_start();
require 'db.php'; // Include database connection

// Function to get user role
function getUserRole($user_id, $conn) {
    $query = "SELECT roles.role_name FROM users 
              JOIN roles ON users.role_id = roles.id 
              WHERE users.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['role_name'] ?? null;
}

// Function to check if a role has a permission
function hasPermission($role, $permission, $conn) {
    $query = "SELECT COUNT(*) as count FROM role_permissions 
              JOIN roles ON role_permissions.role_id = roles.id 
              JOIN permissions ON role_permissions.permission_id = permissions.id 
              WHERE roles.role_name = ? AND permissions.permission_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $role, $permission);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['count'] > 0;
}

// Restrict access based on permission
function requirePermission($permission) {
    global $conn;
    if (!isset($_SESSION['user_id'])) {
        die("Access denied: Please log in.");
    }
    
    $role = getUserRole($_SESSION['user_id'], $conn);
    if (!hasPermission($role, $permission, $conn)) {
        die("Access denied: You do not have permission to view this page.");
    }
}

// Example: Restrict a page to only admins
requirePermission('manage_users');
?>