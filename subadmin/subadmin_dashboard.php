<?php
session_start();
include 'db.php';

if (!isset($_SESSION['subadmin_id'])) {
    header("Location: subadmin_login.php");
    exit();
}

$subadmin_id = $_SESSION['subadmin_id'];

// Fetch requests for the logged-in subadmin
$query = "SELECT request_id, election_name, start_date, end_date, positions, notes, status, created_at FROM requests WHERE subadmin_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch unread notifications count
$notification_query = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND status = 'unread'";
$notification_stmt = $conn->prepare($notification_query);
$notification_stmt->bind_param("i", $subadmin_id);
$notification_stmt->execute();
$notification_result = $notification_stmt->get_result();
$notification_count = $notification_result->fetch_assoc()['unread_count'];

// Fetch total approved and rejected requests for summary
$approved_query = "SELECT COUNT(*) AS approved_count FROM requests WHERE subadmin_id = ? AND status = 1";
$approved_stmt = $conn->prepare($approved_query);
$approved_stmt->bind_param("i", $subadmin_id);
$approved_stmt->execute();
$approved_result = $approved_stmt->get_result();
$approved_count = $approved_result->fetch_assoc()['approved_count'];

$rejected_query = "SELECT COUNT(*) AS rejected_count FROM requests WHERE subadmin_id = ? AND status = 2";
$rejected_stmt = $conn->prepare($rejected_query);
$rejected_stmt->bind_param("i", $subadmin_id);
$rejected_stmt->execute();
$rejected_result = $rejected_stmt->get_result();
$rejected_count = $rejected_result->fetch_assoc()['rejected_count'];

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_query = "DELETE FROM requests WHERE request_id = ? AND subadmin_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $delete_id, $subadmin_id);
    if ($delete_stmt->execute()) {
        header("Location: subadmin_dashboard.php?message=Request%20deleted%20successfully");
        exit();
    } else {
        echo "Error deleting request: " . $conn->error;
    }
    $delete_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subadmin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* General Styles */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            margin: 0 100px;
            display: flex;
        }

        .top_nav {
            background: linear-gradient(90deg, rgb(6, 22, 161), #3498db);
            color: white;
            padding: 15px 20px;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo img {
            max-height: 40px;
            margin-right: 15px;
        }

        .sidebar {
            width: 60px;
            background: #2c3e50;
            color: white;
            height: 100vh;
            padding: 20px 0;
            position: fixed;
            top: 0;
            left: 0;
            transition: width 0.3s ease;
            z-index: 999;
            overflow: hidden;
        }

        .sidebar.expanded {
            width: 200px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 60px 0 0;
        }

        .sidebar ul li {
            margin: 10px 0;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            display: flex;
            align-items: center;
            padding: 15px 20px;
            transition: background 0.3s, padding-left 0.3s;
            white-space: nowrap;
        }

        .sidebar ul li a i {
            margin-right: 15px;
            font-size: 18px;
            width: 20px;
        }

        .sidebar ul li a:hover, .sidebar ul li a.active {
            background: #3498db;
        }

        .sidebar:not(.expanded) ul li a span {
            display: none;
        }

        .main-content {
            margin-left: 60px;
            padding: 80px 20px;
            flex: 1;
            transition: margin-left 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 200px;
        }

        .dashboard h1 {
            font-size: 35px;
            color: rgb(37, 185, 61);
            margin-bottom: 10px;
        }

        .email {
            color: red;
            font-weight: bold;
            font-size: 24px;
        }

        .summary-card {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .summary-box {
            flex: 1;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            min-width: 200px;
            transition: transform 0.3s ease;
        }

        .summary-box:hover {
            transform: translateY(-5px);
        }

        .summary-box h3 {
            margin: 0;
            font-size: 16px;
            color: #2c3e50;
        }

        .summary-box p {
            font-size: 22px;
            font-weight: bold;
            margin: 8px 0 0;
        }

        .approved-count { color: #2ecc71; }
        .rejected-count { color: #e74c3c; }
        .pending-count { color: #f39c12; }

        .card {
            background: white;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .card h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #3498db;
            color: white;
            font-size: 13px;
            text-transform: uppercase;
        }

        tr:hover {
            background-color: #f5f5f5;
            transition: background 0.3s;
        }

        .status {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            text-transform: uppercase;
        }

        .pending { background: #f39c12; color: white; }
        .approved { background: #2ecc71; color: white; }
        .rejected { background: #e74c3c; color: white; }

        .delete-btn {
            color: #e74c3c;
            text-decoration: none;
            font-size: 12px;
        }

        .delete-btn:hover {
            text-decoration: underline;
        }

        .notification-icon {
            position: fixed;
            top: 20px;
            right: 80px;
            background: #e74c3c;
            color: white;
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, background 0.3s;
            z-index: 1001;
        }

        .notification-icon:hover {
            background: #c0392b;
            transform: scale(1.1);
        }

        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #2ecc71;
            color: white;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 50%;
        }

        .notifications-container {
            display: none;
            position: fixed;
            top: 70px;
            right: 20px;
            width: 300px;
            max-height: 60vh;
            background: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 8px;
            overflow-y: auto;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }

        .notifications-container.active {
            display: block;
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .notification-item {
            padding: 10px;
            background: #ecf0f1;
            margin-bottom: 8px;
            border-radius: 5px;
            transition: background 0.3s;
            cursor: pointer;
        }

        .notification-item:hover {
            background: #bdc3c7;
        }

        .notification-item strong {
            display: block;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .notification-item small {
            color: #7f8c8d;
            font-size: 11px;
        }

        #sidebarToggle {
            background: none;
            color: white;
            font-size: 30px;
            border: none;
            cursor: pointer;
            margin-left: 10px;
        }

        p {
            margin-bottom: 50px;
            margin-top: 30px;
        }

        .logout {
            font-size: 22px;
            border: none;
            cursor: pointer;
            margin-left: 90%;
            position: fixed;
            top: 20px;
            background: rgb(207, 207, 207);
            color: blue;
            padding: 7px 8px;
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, background 0.3s;
            z-index: 1001;
            left: 100px;
        }

        @media (max-width: 768px) {
            .container {
                max-width: 100%;
                padding: 0 10px;
            }

            .sidebar {
                width: 0;
            }

            .sidebar.expanded {
                width: 150px;
            }

            .main-content {
                margin-left: 0;
            }

            .main-content.expanded {
                margin-left: 150px;
            }

            .summary-card {
                flex-direction: column;
            }

            .notifications-container {
                width: 90%;
                right: 5%;
            }
        }

        @media (max-width: 480px) {
            .dashboard h1 {
                font-size: 22px;
            }

            .card h3 {
                font-size: 18px;
            }

            table, th, td {
                font-size: 12px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <nav class="top_nav">
        <div class="logo">
            <img src="https://www.citypng.com/public/uploads/preview/png-green-vote-word-704081694605369jh3qz1gntg.png" alt="Voting Platform">
        </div>
        <button id="sidebarToggle">☰</button>
        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i></a>
    </nav>

    <nav class="sidebar">
        <ul>
            <li><a href="subadmin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
            <li><a href="request_election.php"><i class="fas fa-calendar-plus"></i><span>Request Election</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="dashboard">
                <h1>Welcome, Subadmin!</h1>
                <p>Email: <span class="email"><?php echo htmlspecialchars($_SESSION['subadmin_email']); ?></span></p>

 картка

                <div class="summary-card">
                    <div class="summary-box">
                        <h3>Approved Requests</h3>
                        <p class="approved-count"><?php echo $approved_count; ?></p>
                    </div>
                    <div class="summary-box">
                        <h3>Rejected Requests</h3>
                        <p class="rejected-count"><?php echo $rejected_count; ?></p>
                    </div>
                    <div class="summary-box">
                        <h3>Pending Requests</h3>
                        <p class="pending-count"><?php echo $result->num_rows - ($approved_count + $rejected_count); ?></p>
                    </div>
                </div>

                <div class="card request-status">
                    <h3>Your Election Requests</h3>
                    <?php if ($result->num_rows > 0): ?>
                        <table>
                            <tr>
                                <th>Request ID</th>
                                <th>Election Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Positions</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                            <?php while ($row = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo $row['request_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['election_name']); ?></td>
                                    <td><?php echo $row['start_date']; ?></td>
                                    <td><?php echo $row['end_date']; ?></td>
                                    <td><?php echo htmlspecialchars($row['positions']); ?></td>
                                    <td><?php echo htmlspecialchars($row['notes'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        if ($row['status'] == 0) {
                                            echo '<span class="status pending">Pending</span>';
                                        } elseif ($row['status'] == 1) {
                                            echo '<span class="status approved">Approved</span>';
                                        } else {
                                            echo '<span class="status rejected">Rejected</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $row['created_at']; ?></td>
                                    <td>
                                        <a href="subadmin_dashboard.php?delete_id=<?php echo $row['request_id']; ?>" 
                                           class="delete-btn" 
                                           onclick="return confirm('Are you sure you want to delete this request?');">Delete</a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    <?php else: ?>
                        <p>No election requests found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="notification-icon" id="notificationIcon">
        <i class="fas fa-bell"></i>
        <div class="notification-count" id="notificationCount"><?php echo $notification_count; ?></div>
    </div>

    <div class="notifications-container" id="notificationsContainer">
        <h3>Notifications</h3>
        <div id="notificationList">
            <?php
            $notification_sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
            $notif_stmt = $conn->prepare($notification_sql);
            $notif_stmt->bind_param("i", $subadmin_id);
            $notif_stmt->execute();
            $notification_res = $notif_stmt->get_result();

            if ($notification_res->num_rows > 0) {
                while ($notification = $notification_res->fetch_assoc()) {
                    echo '<div class="notification-item" data-id="' . $notification['notification_id'] . '">';
                    echo '<strong>' . htmlspecialchars($notification['title']) . '</strong>';
                    echo '<p>' . htmlspecialchars($notification['message']) . '</p>';
                    echo '<small>' . $notification['created_at'] . '</small>';
                    echo '</div>';
                }
            } else {
                echo '<p>No notifications found for user ID: ' . htmlspecialchars($subadmin_id) . '</p>';
            }
            $notif_stmt->close();
            ?>
        </div>
    </div>

    <script>
        const sidebar = document.querySelector('.sidebar');
        const toggleButton = document.getElementById('sidebarToggle');
        const mainContent = document.querySelector('.main-content');

        toggleButton.addEventListener('click', () => {
            sidebar.classList.toggle('expanded');
            mainContent.classList.toggle('expanded');
        });

        const notificationIcon = document.getElementById('notificationIcon');
        const notificationsContainer = document.getElementById('notificationsContainer');
        const notificationList = document.getElementById('notificationList');
        const notificationCount = document.getElementById('notificationCount');

        notificationIcon.addEventListener('click', (e) => {
            e.stopPropagation();
            notificationsContainer.classList.toggle('active');
        });

        function deleteNotifications() {
            fetch('delete_notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    notificationList.innerHTML = '<p>No notifications found.</p>';
                    notificationCount.textContent = '0';
                } else {
                    console.error(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        notificationList.addEventListener('click', (e) => {
            const notificationItem = e.target.closest('.notification-item');
            if (notificationItem) {
                const notificationId = notificationItem.dataset.id;
                fetch('delete_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: notificationId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        notificationItem.remove();
                        const currentCount = parseInt(notificationCount.textContent);
                        notificationCount.textContent = currentCount - 1;
                        if (notificationList.children.length === 0) {
                            notificationList.innerHTML = '<p>No notifications found.</p>';
                        }
                    } else {
                        console.error(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });

        document.addEventListener('click', (e) => {
            if (!notificationsContainer.contains(e.target) && !notificationIcon.contains(e.target) && notificationsContainer.classList.contains('active')) {
                deleteNotifications();
                notificationsContainer.classList.remove('active');
            }
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$notification_stmt->close();
$approved_stmt->close();
$rejected_stmt->close();
mysqli_close($conn);
?>