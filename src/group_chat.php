<?php
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function createGroupTables($conn) {
    try {
        $createGroupChatsTable = "CREATE TABLE IF NOT EXISTS group_chats (
            id SERIAL PRIMARY KEY,
            group_name VARCHAR(100) NOT NULL,
            created_by VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        query_safe($conn, $createGroupChatsTable);

        $createGroupMembersTable = "CREATE TABLE IF NOT EXISTS group_members (
            group_id INTEGER REFERENCES group_chats(id),
            user_id VARCHAR(255) NOT NULL,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (group_id, user_id)
        )";
        query_safe($conn, $createGroupMembersTable);
    } catch (PDOException $e) {
        error_log("Error creating group tables: " . $e->getMessage());
    }
}

function getAllUsers($conn) {
    try {
        $query = "SELECT id, username FROM user_management.users ORDER BY username";
        $stmt = query_safe($conn, $query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching users: " . $e->getMessage());
        return [];
    }
}

function createGroupChat($conn, $groupName, $createdBy, $selectedUsers) {
    try {
        $conn->beginTransaction();
        
        $query = "INSERT INTO group_chats (group_name, created_by) VALUES (?, ?) RETURNING id";
        $stmt = query_safe($conn, $query, [$groupName, $createdBy]);
        $groupId = $stmt->fetchColumn();
        
        $query = "INSERT INTO group_members (group_id, user_id) VALUES (?, ?)";
        foreach ($selectedUsers as $userId) {
            query_safe($conn, $query, [$groupId, $userId]);
        }
        
        if (!in_array($createdBy, $selectedUsers)) {
            query_safe($conn, $query, [$groupId, $createdBy]);
        }
        
        $conn->commit();
        return $groupId;
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error creating group chat: " . $e->getMessage());
        return false;
    }
}