<?php
require_once 'db.php';
require_once 'group_chat.php';

// Only start session if one hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate or retrieve user ID for this session
if (!isset($_SESSION['anon_user_id'])) {
    $_SESSION['anon_user_id'] = uniqid();
}
$user_id = $_SESSION['anon_user_id'];

// Get user color based on user_id (consistent color per user)
function getUserColor($user_id) {
    // Using hash of user_id to generate a consistent color
    $hash = md5($user_id);
    
    // Extract 6 chars for a hex color, but avoid too dark or too light colors
    $r = hexdec(substr($hash, 0, 2)); 
    $g = hexdec(substr($hash, 2, 2));
    $b = hexdec(substr($hash, 4, 2));
    
    // Ensure minimum brightness for readability
    $minBrightness = 50;
    $maxBrightness = 200;
    
    // Adjust RGB values if needed
    $r = max($minBrightness, min($maxBrightness, $r));
    $g = max($minBrightness, min($maxBrightness, $g));
    $b = max($minBrightness, min($maxBrightness, $b));
    
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

// Create database tables if they don't exist
function createTables($conn) {
    try {
        // Check if tables exist
        $checkTable = "SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'anon_chat_rooms'
        )";
        $result = query_safe($conn, $checkTable)->fetchColumn();
        
        if (!$result) {
            // Create rooms table
            $createRoomsTable = "CREATE TABLE anon_chat_rooms (
                id SERIAL PRIMARY KEY,
                room_name VARCHAR(100) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            query_safe($conn, $createRoomsTable);
            
            // Create messages table
            $createMessagesTable = "CREATE TABLE anon_chat_messages (
                id SERIAL PRIMARY KEY,
                room_id INTEGER NOT NULL,
                user_id VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (room_id) REFERENCES anon_chat_rooms(id)
            )";
            query_safe($conn, $createMessagesTable);
            
            // Add some default rooms
            $insertRooms = "INSERT INTO anon_chat_rooms (room_name, description) VALUES 
                ('General', 'General discussion about anything'),
                ('Tech', 'Technology discussions'),
                ('Random', 'Random conversations')";
            query_safe($conn, $insertRooms);
        }
    } catch (PDOException $e) {
        error_log("Error creating tables: " . $e->getMessage());
    }
}

// Create tables if needed
createTables($conn);

// Create a new chat room
function createChatRoom($conn, $roomName, $description) {
    try {
        $query = "INSERT INTO anon_chat_rooms (room_name, description) VALUES (?, ?)";
        query_safe($conn, $query, [$roomName, $description]);
        return $conn->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error creating room: " . $e->getMessage());
        return false;
    }
}

// Get all chat rooms
function getChatRooms($conn) {
    try {
        $query = "SELECT id, room_name, description, created_at FROM anon_chat_rooms ORDER BY created_at DESC";
        $stmt = query_safe($conn, $query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching rooms: " . $e->getMessage());
        return [];
    }
}

// Get chat room by ID
function getChatRoom($conn, $roomId) {
    try {
        $query = "SELECT id, room_name, description FROM anon_chat_rooms WHERE id = ?";
        $stmt = query_safe($conn, $query, [$roomId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching room: " . $e->getMessage());
        return null;
    }
}

// Get messages for a specific room
function getRoomMessages($conn, $roomId) {
    try {
        $query = "SELECT user_id, message, created_at FROM anon_chat_messages WHERE room_id = ? ORDER BY created_at ASC";
        $stmt = query_safe($conn, $query, [$roomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching messages: " . $e->getMessage());
        return [];
    }
}

// Add after your existing functions

// function getAllUsers($conn) {
//     try {
//         $query = "SELECT id, username FROM user_management.users ORDER BY username";
//         $stmt = query_safe($conn, $query);
//         return $stmt->fetchAll(PDO::FETCH_ASSOC);
//     } catch (PDOException $e) {
//         error_log("Error fetching users: " . $e->getMessage());
//         return [];
//     }
// }

// function createGroupChat($conn, $groupName, $createdBy, $selectedUsers) {
//     try {
//         $conn->beginTransaction();

//         // Create group
//         $query = "INSERT INTO group_chats (group_name, created_by) VALUES (?, ?) RETURNING id";
//         $stmt = query_safe($conn, $query, [$groupName, $createdBy]);
//         $groupId = $stmt->fetchColumn();

//         // Add members
//         $query = "INSERT INTO group_members (group_id, user_id) VALUES (?, ?)";
//         foreach ($selectedUsers as $userId) {
//             query_safe($conn, $query, [$groupId, $userId]);
//         }

//         // Add creator as member
//         query_safe($conn, $query, [$groupId, $createdBy]);

//         $conn->commit();
//         return $groupId;
//     } catch (PDOException $e) {
//         $conn->rollBack();
//         error_log("Error creating group chat: " . $e->getMessage());
//         return false;
//     }
// }

// Add a new message to a room
function addMessage($conn, $roomId, $userId, $message) {
    try {
        $query = "INSERT INTO anon_chat_messages (room_id, user_id, message) VALUES (?, ?, ?)";
        query_safe($conn, $query, [$roomId, $userId, $message]);
        return true;
    } catch (PDOException $e) {
        error_log("Error adding message: " . $e->getMessage());
        return false;
    }
}
// Add after your existing functions
function deleteRoom($conn, $roomId) {
    try {
        $conn->beginTransaction();
        
        // Delete messages first due to foreign key constraint
        $deleteMessages = "DELETE FROM anon_chat_messages WHERE room_id = ?";
        query_safe($conn, $deleteMessages, [$roomId]);
        
        // Then delete the room
        $deleteRoom = "DELETE FROM anon_chat_rooms WHERE id = ?";
        query_safe($conn, $deleteRoom, [$roomId]);
        
        $conn->commit();
        return true;
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error deleting room: " . $e->getMessage());
        return false;
    }
}
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new room
    if (isset($_POST['create_room']) && isset($_POST['room_name']) && isset($_POST['room_description'])) {
        $roomName = trim($_POST['room_name']);
        $roomDescription = trim($_POST['room_description']);
        
        if (!empty($roomName)) {
            $roomId = createChatRoom($conn, $roomName, $roomDescription);
            if ($roomId) {
                header('Location: ' . $_SERVER['PHP_SELF'] . '?room=' . $roomId);
                exit;
            }
        }
    }
    
    // Send message
    if (isset($_POST['send_message']) && isset($_POST['message']) && isset($_POST['room_id'])) {
        $message = trim($_POST['message']);
        $roomId = intval($_POST['room_id']);
        
        if (!empty($message) && $roomId > 0) {
            addMessage($conn, $roomId, $user_id, $message);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?room=' . $roomId);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_group']) && isset($_POST['group_name'])) {
        $groupName = trim($_POST['group_name']);
        $selectedUsers = isset($_POST['selected_users']) ? $_POST['selected_users'] : [];
        
        if (!empty($groupName) && !empty($selectedUsers)) {
            $groupId = createGroupChat($conn, $groupName, $user_id, $selectedUsers);
            if ($groupId) {
                header('Location: ' . $_SERVER['PHP_SELF'] . '?room=' . $groupId);
                exit;
            }
        }
    }
}


    // Handle room deletion
if (isset($_POST['delete_room']) && isset($_POST['room_id'])) {
        $roomId = intval($_POST['room_id']);
        if (deleteRoom($conn, $roomId)) {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }



// Determine if we're in a room or at the room list
$inRoom = false;
$currentRoom = null;
$messages = [];
$rooms = getChatRooms($conn);

if (isset($_GET['room']) && is_numeric($_GET['room'])) {
    $roomId = intval($_GET['room']);
    $currentRoom = getChatRoom($conn, $roomId);
    
    if ($currentRoom) {
        $inRoom = true;
        $messages = getRoomMessages($conn, $roomId);
    }
}

// Track colors for all users in the current room
$userColors = [];
if ($inRoom && !empty($messages)) {
    foreach ($messages as $msg) {
        if (!isset($userColors[$msg['user_id']])) {
            $userColors[$msg['user_id']] = getUserColor($msg['user_id']);
        }
    }
}

// Check if it's an AJAX request for real-time updates
if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && $inRoom) {
    // Return only the messages HTML
    foreach ($messages as $message) {
        $color = $userColors[$message['user_id']];
        $isCurrentUser = ($message['user_id'] === $user_id);
        $alignClass = $isCurrentUser ? 'self-message' : 'other-message';
        
        echo '<div class="message-container ' . $alignClass . '">';
        echo '<div class="message" style="background-color: ' . $color . ';">';
        echo '<p class="message-text">' . htmlspecialchars($message['message']) . '</p>';
        echo '<span class="message-time">' . date('H:i', strtotime($message['created_at'])) . '</span>';
        echo '</div></div>';
    }
    exit;
}
// Add to your existing POST handler

if (isset($_POST['create_group']) && isset($_POST['group_name']) && isset($_POST['selected_users'])) {
    $groupName = trim($_POST['group_name']);
    $selectedUsers = $_POST['selected_users'];
    
    if (!empty($groupName) && !empty($selectedUsers)) {
        $groupId = createGroupChat($conn, $groupName, $user_id, $selectedUsers);
        if ($groupId) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?room=' . $groupId);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $inRoom ? htmlspecialchars($currentRoom['room_name']) : 'Anonymous Chat Rooms'; ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            max-width: 1000px;
            margin: 20px auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            background-color: #fff;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        
        header {
            background-color: #4a76a8;
            color: white;
            padding: 15px 20px;
            font-size: 20px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        header a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            background-color: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        header a:hover {
            background-color: rgba(255,255,255,0.3);
        }
        
        .room-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .room-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            border: 1px solid #eee;
        }
        
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .room-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #4a76a8;
        }
        
        .room-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .room-created {
            font-size: 12px;
            color: #999;
        }
        
        .new-room-form {
            background-color: transparent;
            padding: 0;
            border-top: none;
        }
        
        .form-title {
            font-size: 18px;
            margin-bottom: 15px;
            color: #4a76a8;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
        
        .btn {
            background-color: #4a76a8;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #3a5a78;
        }
        
        /* Chat room styles */
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            height: calc(100vh - 180px);
            scroll-behavior: smooth;
            position: relative;
         }
        
        .message-container {
            display: flex;
            margin-bottom: 10px;
        }
        
        .self-message {
            justify-content: flex-end;
        }
        
        .other-message {
            justify-content: flex-start;
        }
        
        .message {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            position: relative;
            color: white;
            word-wrap: break-word;
        }
        
        .self-message .message {
            border-bottom-right-radius: 5px;
        }
        
        .other-message .message {
            border-bottom-left-radius: 5px;
        }
        
        .message-text {
            margin-bottom: 15px;
        }
        
        .message-time {
            position: absolute;
            bottom: 5px;
            right: 10px;
            font-size: 12px;
            opacity: 0.8;
        }
        
        .input-area {
            display: flex;
            padding: 15px;
            background-color: #fff;
            border-top: 1px solid #eee;
        }
        
        #message-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            outline: none;
            font-size: 16px;
        }
        
        .send-btn {
            margin-left: 10px;
            background-color: #4a76a8;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 0 20px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        
        .send-btn:hover {
            background-color: #3a5a78;
        }
        
        .color-info {
            padding: 10px 20px;
            background-color: #f9f9f9;
            font-size: 14px;
            border-bottom: 1px solid #eee;
        }
        
        .user-indicator {
            background-color: <?php echo getUserColor($user_id); ?>;
            color: white;
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
            vertical-align: middle;
        }
        
        .room-info {
            padding: 10px 20px;
            background-color: #f9f9f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }
        
        .room-title {
            font-size: 16px;
            font-weight: bold;
        }
        
        .room-desc {
            font-size: 14px;
            color: #666;
        }
        header .dashboard-btn {
            color: white;
            text-decoration: none;
            font-size: 16px;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
            margin-left: 10px;
       }

       header .dashboard-btn:hover {
           background-color: rgba(255, 255, 255, 0.3);
       }

       .create-group-chat {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        margin: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .group-chat-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .user-selection {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #ddd;
        padding: 10px;
        border-radius: 5px;
        background: white;
    }

    .user-checkbox {
        display: block;
        padding: 5px 0;
    }

    .user-checkbox input[type="checkbox"] {
        margin-right: 10px;
    }

        
        .header-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }
    
        .create-btn {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }
    
        .create-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
    
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow: auto;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .close {
            position: absolute;
            right: 15px;
            top: 10px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            color: #333;
        }

        .create-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
        }

        .create-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
    
        .scroll-down-btn {
    position: fixed;
    bottom: 100px;
    right: 30px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #4a76a8;
    color: white;
    border: none;
    font-size: 24px;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    z-index: 1000;
    outline: none;
}

        .scroll-down-btn:hover {
    background-color: #3a5a78;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.scroll-down-btn:active {
    transform: translateY(0);
}

       
            
            .room-name {
                font-size: 16px;
            }
            
            .room-description, .room-created {
                font-size: 12px;
            }
        
  
              
            .room-card {
                position: relative;
                padding-right: 40px; /* Make space for delete button */
            }
        
            .room-link {
                text-decoration: none;
                color: inherit;
                display: block;
            }
        
            .delete-form {
                position: absolute;
                top: 10px;
                right: 10px;
            }
        
            .delete-btn {
                background-color: #ff4444;
                color: white;
                border: none;
                border-radius: 50%;
                width: 24px;
                height: 24px;
                font-size: 18px;
                line-height: 1;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0;
                transition: background-color 0.3s;
            }
        
            .delete-btn:hover {
                background-color: #cc0000;
            }

    </style>
</head>
<body>
    <div class="container">
        <header>
            <?php if ($inRoom): ?>
                <span><?php echo htmlspecialchars($currentRoom['room_name']); ?></span>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>">Back to Rooms</a>
            <?php else: ?>
                <div class="header-content">
                    <span>Anonymous Chat Rooms</span>
                    <button id="createGroupBtn" class="create-btn" title="Create New Room">+</button>
                </div>
                <a href="dashboard.php" class="dashboard-btn">Dashboard</a>
            <?php endif; ?>
        </header>

        <!-- <div class="create-group-chat">
    <h3>Create Group Chat</h3>
    <form method="post" class="group-chat-form">
        <div class="form-group">
            <label for="group_name">Group Name:</label>
            <input type="text" id="group_name" name="group_name" required>
        </div>
        <div class="form-group">
            <label>Select Users:</label>
            <div class="user-selection">
                <?php
                $users = getAllUsers($conn);
                if (!empty($users)): 
                    foreach ($users as $usr): ?>
                        <label class="user-checkbox">
                            <input type="checkbox" name="selected_users[]" 
                                   value="<?php echo htmlspecialchars($usr['id']); ?>">
                            <?php echo htmlspecialchars($usr['username']); ?>
                        </label>
                    <?php endforeach; 
                else: ?>
                    <p>No users available</p>
                <?php endif; ?>
            </div>
        </div>
        <button type="submit" name="create_group" class="btn">Create Group Chat</button>
    </form>
</div>     -->
        
        <?php if ($inRoom): ?>
            <div class="color-info">
                Your color: <span class="user-indicator"></span>
            </div>
            
            <div class="room-info">
                <div>
                    <div class="room-title"><?php echo htmlspecialchars($currentRoom['room_name']); ?></div>
                    <div class="room-desc"><?php echo htmlspecialchars($currentRoom['description']); ?></div>
                </div>
            </div>
           
            
            <div class="messages-container" id="messages">
                <?php foreach ($messages as $message): ?>
                    <?php 
                        $color = $userColors[$message['user_id']];
                        $isCurrentUser = ($message['user_id'] === $user_id);
                        $alignClass = $isCurrentUser ? 'self-message' : 'other-message';
                    ?>
                    <div class="message-container <?php echo $alignClass; ?>">
                        <div class="message" style="background-color: <?php echo $color; ?>;">
                            <p class="message-text"><?php echo htmlspecialchars($message['message']); ?></p>
                            <span class="message-time"><?php echo date('H:i', strtotime($message['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button id="scrollDownBtn" class="scroll-down-btn" title="Scroll to bottom">↓</button>
            
            <!-- Add this right after the messages-container div -->
            <button id="scrollDownBtn" class="scroll-down-btn">↓</button>

            <form method="post" class="input-area">
                <input type="hidden" name="room_id" value="<?php echo $currentRoom['id']; ?>">
                <input type="text" id="message-input" name="message" placeholder="Type your message..." autocomplete="off" autofocus required>
                <button type="submit" name="send_message" class="send-btn">Send</button>
            </form>
            
            <script>
document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.getElementById('messages');
    const scrollDownBtn = document.getElementById('scrollDownBtn');
    
    if (messagesContainer && scrollDownBtn) {
        // Function to check if we're near bottom
        function isNearBottom() {
            const threshold = 100;
            const position = messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight;
            return position < threshold;
        }

        // Function to update scroll button visibility
        function updateScrollButtonVisibility() {
            if (isNearBottom()) {
                scrollDownBtn.style.display = 'none';
            } else {
                scrollDownBtn.style.display = 'flex';
            }
        }

        // Initial scroll to bottom and button state
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        updateScrollButtonVisibility();

        // Handle scroll events
        messagesContainer.addEventListener('scroll', updateScrollButtonVisibility);

        // Handle scroll button click
        scrollDownBtn.addEventListener('click', () => {
            messagesContainer.scrollTo({
                top: messagesContainer.scrollHeight,
                behavior: 'smooth'
            });
        });

        // Handle new messages (polling)
        setInterval(function() {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?room=<?php echo $currentRoom['id']; ?>&ajax=1')
                .then(response => response.text())
                .then(html => {
                    const wasNearBottom = isNearBottom();
                    messagesContainer.innerHTML = html;
                    
                    if (wasNearBottom) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        scrollDownBtn.style.display = 'none';
                    } else {
                        scrollDownBtn.style.display = 'flex';
                    }
                })
                .catch(error => console.error('Error fetching messages:', error));
        }, 3000);
    }
});
</script>
        <?php else: ?>
            <!-- Room list page -->
                        <!-- Replace the existing room card HTML in the room-list div -->
            <div class="room-list">
                <?php foreach ($rooms as $room): ?>
                <div class="room-card">
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?room=<?php echo $room['id']; ?>" class="room-link">
                        <div class="room-name"><?php echo htmlspecialchars($room['room_name']); ?></div>
                        <div class="room-description"><?php echo htmlspecialchars($room['description']); ?></div>
                        <div class="room-created">Created: <?php echo date('M j, Y', strtotime($room['created_at'])); ?></div>
                    </a>
                    <form method="post" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this room? This action cannot be undone.');">
                        <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                        <button type="submit" name="delete_room" class="delete-btn" title="Delete Room">×</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>


                <div class="create-group-chat">
                    <h3>Create Group Chat</h3>
                    <form method="post" class="group-chat-form">
                            <div class="form-group">
                                <label for="group_name">Group Name:</label>
                                <input type="text" id="group_name" name="group_name" required>
                </div>
                <div class="form-group">
                    <label>Select Users:</label>
                    <div class="user-selection">
                         <?php
                // Fetch all users
                        $users = getAllUsers($conn);
                        foreach ($users as $user): ?>
                            <label class="user-checkbox">
                                 <input type="checkbox" name="selected_users[]" value="<?php echo htmlspecialchars($user['id']); ?>">
                                 <?php echo htmlspecialchars($user['username']); ?>
                            </label>
                        <?php endforeach; ?>
                 </div>
        </div>
        <button type="submit" name="create_group" class="btn">Create Group Chat</button>
    </form>
</div>
            </div>
        <?php endif; ?>
    </div>
        <div id="groupChatModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="new-room-form">
            <div class="form-title">Create New Chat Room</div>
            <form method="post">
                <div class="form-group">
                    <label for="modal_room_name">Room Name</label>
                    <input type="text" id="modal_room_name" name="room_name" required>
                </div>
                <div class="form-group">
                    <label for="modal_room_description">Description</label>
                    <textarea id="modal_room_description" name="room_description"></textarea>
                </div>
                <button type="submit" name="create_room" class="btn">Create Room</button>
            </form>
        </div>
    </div>
</div>
    <script>
        // Get modal elements
        const modal = document.getElementById('groupChatModal');
        const btn = document.getElementById('createGroupBtn');
        const span = document.getElementsByClassName('close')[0];

        if (btn && modal && span) {
            // Open modal when + button is clicked
            btn.onclick = function() {
                modal.style.display = "block";
            }

            // Close modal when × is clicked
            span.onclick = function() {
                modal.style.display = "none";
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        }
    </script>
</body>
</html>