<?php
require 'db.php';

// Drop tables if they exist (to avoid errors)
$conn->query("DROP TABLE IF EXISTS role_permissions");
$conn->query("DROP TABLE IF EXISTS users");
$conn->query("DROP TABLE IF EXISTS permissions");
$conn->query("DROP TABLE IF EXISTS roles");

// Create tables in the correct order
$conn->query("CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL
)");

$conn->query("CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE
)");

$conn->query("CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT,
    FOREIGN KEY (role_id) REFERENCES roles(id)
)");

$conn->query("CREATE TABLE role_permissions (
    role_id INT,
    permission_id INT,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id)
)");

// Insert roles
$conn->query("INSERT INTO roles (role_name) VALUES ('Admin'), ('User'), ('Guest')");

// Insert permissions
$conn->query("INSERT INTO permissions (permission_name) VALUES 
    ('manage_users'), ('edit_profile'), ('view_dashboard')");

// Assign permissions to roles
$conn->query("INSERT INTO role_permissions (role_id, permission_id) 
    SELECT r.id, p.id FROM roles r, permissions p 
    WHERE r.role_name = 'Admin' AND p.permission_name IN ('manage_users', 'edit_profile', 'view_dashboard')");

$conn->query("INSERT INTO role_permissions (role_id, permission_id) 
    SELECT r.id, p.id FROM roles r, permissions p 
    WHERE r.role_name = 'User' AND p.permission_name IN ('edit_profile', 'view_dashboard')");

$conn->query("INSERT INTO role_permissions (role_id, permission_id) 
    SELECT r.id, p.id FROM roles r, permissions p 
    WHERE r.role_name = 'Guest' AND p.permission_name IN ('view_dashboard')");

echo "Database initialized successfully!<br>";
echo "<a href='create_admin.php'>Create Admin User</a>";
?>