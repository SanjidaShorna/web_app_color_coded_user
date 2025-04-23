<?php
require 'db.php';

try {
    // Drop tables in reverse order to avoid foreign key conflicts
    query_safe($conn, "DROP TABLE IF EXISTS role_permissions");
    query_safe($conn, "DROP TABLE IF EXISTS users");
    query_safe($conn, "DROP TABLE IF EXISTS permissions");
    query_safe($conn, "DROP TABLE IF EXISTS roles");

    // Create roles table
    query_safe($conn, "CREATE TABLE roles (
        id SERIAL PRIMARY KEY,
        role_name VARCHAR(50) UNIQUE NOT NULL
    )");

    // Create permissions table
    query_safe($conn, "CREATE TABLE permissions (
        id SERIAL PRIMARY KEY,
        permission_name VARCHAR(100) NOT NULL UNIQUE
    )");

    // Create users table
    query_safe($conn, "CREATE TABLE users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role_id INTEGER,
        FOREIGN KEY (role_id) REFERENCES roles(id)
    )");

    // Create role_permissions table
    query_safe($conn, "CREATE TABLE role_permissions (
        role_id INTEGER,
        permission_id INTEGER,
        PRIMARY KEY (role_id, permission_id),
        FOREIGN KEY (role_id) REFERENCES roles(id),
        FOREIGN KEY (permission_id) REFERENCES permissions(id)
    )");

    // Insert roles
    query_safe($conn, "INSERT INTO roles (role_name) VALUES ($1) ON CONFLICT DO NOTHING", ['Admin']);
    query_safe($conn, "INSERT INTO roles (role_name) VALUES ($1) ON CONFLICT DO NOTHING", ['User']);
    query_safe($conn, "INSERT INTO roles (role_name) VALUES ($1) ON CONFLICT DO NOTHING", ['Guest']);

    // Insert permissions
    query_safe($conn, "INSERT INTO permissions (permission_name) VALUES ($1) ON CONFLICT DO NOTHING", ['manage_users']);
    query_safe($conn, "INSERT INTO permissions (permission_name) VALUES ($1) ON CONFLICT DO NOTHING", ['edit_profile']);
    query_safe($conn, "INSERT INTO permissions (permission_name) VALUES ($1) ON CONFLICT DO NOTHING", ['view_dashboard']);

    // Insert role_permissions for Admin
    query_safe($conn, "INSERT INTO role_permissions (role_id, permission_id) 
        SELECT r.id, p.id FROM roles r, permissions p 
        WHERE r.role_name = $1 AND p.permission_name IN ($2, $3, $4)", 
        ['Admin', 'manage_users', 'edit_profile', 'view_dashboard']);

    // Insert role_permissions for User
    query_safe($conn, "INSERT INTO role_permissions (role_id, permission_id) 
        SELECT r.id, p.id FROM roles r, permissions p 
        WHERE r.role_name = $1 AND p.permission_name IN ($2, $3)", 
        ['User', 'edit_profile', 'view_dashboard']);

    // Insert role_permissions for Guest
    query_safe($conn, "INSERT INTO role_permissions (role_id, permission_id) 
        SELECT r.id, p.id FROM roles r, permissions p 
        WHERE r.role_name = $1 AND p.permission_name = $2", 
        ['Guest', 'view_dashboard']);

    echo "Database initialized successfully!<br>";
    echo "<a href='create_admin.php'>Create Admin User</a>";
} catch (Exception $e) {
    echo "Initialization failed: " . $e->getMessage();
}
?>