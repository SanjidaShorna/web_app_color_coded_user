<?php
require 'db.php';

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

echo "Roles and permissions setup complete!";
?>