<!-- filepath: e:\CLASS MATERIALS\DBMS\PROJECT\web_app_color_coded_user\src\send_message.php -->
<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    $user_id = $_SESSION['user_id'] ?? 'anonymous';

    if (!empty($message)) {
        try {
            $query = "INSERT INTO anon_chat (user_id, message, created_at) VALUES (:user_id, :message, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->execute(['user_id' => $user_id, 'message' => $message]);
        } catch (PDOException $e) {
            // Handle error
        }
    }
}

header('Location: anon_chat.php');
exit;
?>