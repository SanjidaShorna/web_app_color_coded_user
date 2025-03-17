<?php
require 'access_control.php';

// Only admins can access this page
requirePermission('manage_users');

// Function to generate a random email based on username
function generateEmail($username) {
    $domains = ['example.com', 'testmail.org', 'company.net', 'domain.io'];
    $randomDomain = $domains[array_rand($domains)];
    return strtolower($username) . '@' . $randomDomain;
}

// Get users from database
function getUsers($conn) {
    $query = "SELECT users.id, users.username, roles.role_name 
              FROM users 
              JOIN roles ON users.role_id = roles.id";
    $result = $conn->query($query);
    
    $users = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['email'] = generateEmail($row['username']);
            $users[] = $row;
        }
    }
    return $users;
}

$users = getUsers($conn);

echo "Welcome, Admin! You have access to manage users.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            display: flex;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .sidebar {
            width: 250px;
            background: #333;
            color: white;
            padding: 20px;
            height: 100vh;
            position: fixed;
        }
        .sidebar h2 {
            text-align: center;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar ul li {
            padding: 10px;
            margin: 10px 0;
            background: #444;
            cursor: pointer;
        }
        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
        }
        .main-content {
            margin-left: 270px;
            padding: 20px;
            width: calc(100% - 270px);
        }
        header {
            background: #007BFF;
            color: white;
            padding: 15px;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }
        table, th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #007BFF;
            color: white;
        }
        button {
            padding: 5px 10px;
            background: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background: #218838;
        }
        section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .edit-btn {
            background: #ffc107;
            color: black;
        }
        .edit-btn:hover {
            background: #e0a800;
        }
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            z-index: 1000;
            width: 300px;
        }
        .popup input {
            display: block;
            margin-bottom: 10px;
            padding: 5px;
            width: 100%;
        }
        .popup .close-btn {
            background: #dc3545;
            margin-top: 10px;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
    </style>
</head>
<body>
    <div class="overlay" id="overlay"></div>
    <div class="popup" id="popup">
        <h2>Edit User</h2>
        <input type="text" id="username" placeholder="Name">
        <input type="email" id="useremail" placeholder="Email">
        <input type="text" id="userrole" placeholder="Role">
        <button onclick="closePopup()">Save</button>
        <button class="close-btn" onclick="closePopup()">Cancel</button>
    </div>

    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="#users"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="#account"><i class="fas fa-user-cog"></i> Account Management</a></li>
            <li><a href="#activity"><i class="fas fa-chart-line"></i> Activity Monitoring</a></li>
            <li><a href="#reports"><i class="fas fa-file-alt"></i> Reports</a></li>
        </ul>
    </div>
    <div class="main-content">
        <header>
            <h1>Admin Dashboard</h1>
        </header>
        <section id="users">
            <h2>Users List</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>Active</td>
                            <td><button onclick="openPopup('<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['role_name']); ?>')">Manage</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No users found</td>
                    </tr>
                <?php endif; ?>
            </table>
        </section>
        <section id="account">
            <h2>Account Management</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                            <td><button class="edit-btn" onclick="openPopup('<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['role_name']); ?>')">Edit</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No users found</td>
                    </tr>
                <?php endif; ?>
            </table>
        </section>
        <section id="activity">
            <h2>Activity Monitoring</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Timestamp</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>John Doe</td>
                    <td>Logged in</td>
                    <td>2025-03-16 12:30:45</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Jane Smith</td>
                    <td>Sent a message</td>
                    <td>2025-03-16 12:32:10</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>John Doe</td>
                    <td>Logged out</td>
                    <td>2025-03-16 12:40:15</td>
                </tr>
            </table>
        </section>
        <section id="reports">
            <h2>Usage Reports</h2>
            <canvas id="usageChart" width="400" height="200"></canvas>
            <script>
                var ctx = document.getElementById('usageChart').getContext('2d');
                var usageChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Logins', 'Messages Sent', 'Files Uploaded', 'New Users'],
                        datasets: [{
                            label: 'Activity Count',
                            data: [45, 120, 30, 10],
                            backgroundColor: ['#007BFF', '#28a745', '#ffc107', '#dc3545']
                        }]
                    }
                });
            </script>
        </section>
    </div>
    <script>
    function openPopup(name, email, role) {
        document.getElementById('username').value = name;
        document.getElementById('useremail').value = email;
        document.getElementById('userrole').value = role;
        document.getElementById('popup').style.display = 'block';
        document.getElementById('overlay').style.display = 'block';
    }
    
    function closePopup() {
        document.getElementById('popup').style.display = 'none';
        document.getElementById('overlay').style.display = 'none';
    }
    </script>
</body>
</html>