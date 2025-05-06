CREATE SCHEMA IF NOT EXISTS user_management;

CREATE TABLE IF NOT EXISTS user_management.roles (
    id SERIAL PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS user_management.permissions (
    id SERIAL PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS user_management.users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INTEGER,
    FOREIGN KEY (role_id) REFERENCES user_management.roles(id)
);

CREATE TABLE IF NOT EXISTS user_management.role_permissions (
    role_id INTEGER,
    permission_id INTEGER,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES user_management.roles(id),
    FOREIGN KEY (permission_id) REFERENCES user_management.permissions(id)
);
CREATE TABLE anon_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS group_chats (
    id SERIAL PRIMARY KEY,
    group_name VARCHAR(100) NOT NULL,
    created_by VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS group_members (
    group_id INTEGER REFERENCES group_chats(id),
    user_id VARCHAR(255) NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (group_id, user_id)
);
INSERT INTO user_management.roles (id, role_name) VALUES 
    (1, 'Admin'), 
    (2, 'User'), 
    (3, 'Guest');

INSERT INTO user_management.permissions (id, permission_name) VALUES 
    (1, 'manage_users'), 
    (2, 'edit_profile'), 
    (3, 'view_dashboard');

INSERT INTO user_management.role_permissions (role_id, permission_id) VALUES 
    (1, 1), (1, 2), (1, 3),
    (2, 2), (2, 3),
    (3, 3);

ALTER SEQUENCE user_management.roles_id_seq RESTART WITH 4;
ALTER SEQUENCE user_management.permissions_id_seq RESTART WITH 4;