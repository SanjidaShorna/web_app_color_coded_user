<?php
session_start();
require 'db.php';

// Check if user is admin
$query = "SELECT roles.role_name FROM users 
          JOIN roles ON users.role_id = roles.id 
          WHERE users.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$role = $result->fetch_assoc()['role_name'];

if ($role !== 'Admin') {
    http_response_code(403);
    exit('Unauthorized');
}

// Get all users
$query = "SELECT users.username, roles.role_name as role, users.created_at 
          FROM users 
          JOIN roles ON users.role_id = roles.id 
          ORDER BY users.created_at DESC";
$result = $conn->query($query);

$users = array();
while ($row = $result->fetch_assoc()) {
    $users[] = array(
        'username' => htmlspecialchars($row['username']),
        'role' => htmlspecialchars($row['role']),
        'created_at' => date('Y-m-d', strtotime($row['created_at']))
    );
}

header('Content-Type: application/json');
echo json_encode($users);
?>