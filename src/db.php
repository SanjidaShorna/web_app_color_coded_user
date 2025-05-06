<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$host = "db";
$username = "user";
$password = "password";
$dbname = "user_management";
$port = 5432;

$conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// try {
//     $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
//     $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// } catch (PDOException $e) {
//     die("Connection failed: " . $e->getMessage());
// }

/*function query_safe($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt;
}
// ...existing code...
function query_safe($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt;
}*/
function query_safe($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt;
}
?>