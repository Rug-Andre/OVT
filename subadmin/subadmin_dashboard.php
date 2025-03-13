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

// Fetch historical data for the past 7 days (initial load)
$days = 7;
$historical_data = [
    'approved' => [],
    'rejected' => [],
    'pending' => [],
    'notifications' => []
];
$labels = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('M d', strtotime("-$i days")); // e.g., "Mar 3"

    // Approved requests for each day
    $approved_query = "SELECT COUNT(*) AS count FROM requests WHERE subadmin_id = ? AND status = 1 AND DATE(created_at) = ?";
    $approved_stmt = $conn->prepare($approved_query);
    $approved_stmt->bind_param("is", $subadmin_id, $date);
    $approved_stmt->execute();
    $approved_result = $approved_stmt->get_result();
    $historical_data['approved'][] = $approved_result->fetch_assoc()['count'] ?: 0;

    // Rejected requests for each day
    $rejected_query = "SELECT COUNT(*) AS count FROM requests WHERE subadmin_id = ? AND status = 2 AND DATE(created_at) = ?";
    $rejected_stmt = $conn->prepare($rejected_query);
    $rejected_stmt->bind_param("is", $subadmin_id, $date);
    $rejected_stmt->execute();
    $rejected_result = $rejected_stmt->get_result();
    $historical_data['rejected'][] = $rejected_result->fetch_assoc()['count'] ?: 0;

    // Pending requests for each day (status = 0)
    $pending_query = "SELECT COUNT(*) AS count FROM requests WHERE subadmin_id = ? AND status = 0 AND DATE(created_at) = ?";
    $pending_stmt = $conn->prepare($pending_query);
    $pending_stmt->bind_param("is", $subadmin_id, $date);
    $pending_stmt->execute();
    $pending_result = $pending_stmt->get_result();
    $historical_data['pending'][] = $pending_result->fetch_assoc()['count'] ?: 0;

    // Notifications for each day
    $notification_query = "SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND DATE(created_at) = ?";
    $notification_stmt = $conn->prepare($notification_query);
    $notification_stmt->bind_param("is", $subadmin_id, $date);
    $notification_stmt->execute();
    $notification_result = $notification_stmt->get_result();
    $historical_data['notifications'][] = $notification_result->fetch_assoc()['count'] ?: 0;
}

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
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f7fc;
            overflow-y: auto; /* Allow vertical scrolling on the body */
        }

        /* Wrapper to contain the layout */
        .wrapper {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Sidebar */
        .sidebar {
            width: 70px;
            background: #1a2238;
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            transition: width 0.3s ease;
            z-index: 1000;
            overflow: hidden;
        }

        .sidebar.expanded {
            width: 200px;
        }

        .sidebar .logo {
            padding: 20px;
            text-align: center;
        }

        .sidebar .logo i {
            font-size: 24px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }

        .sidebar ul li a {
            color: #a4a6b3;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 15px 20px;
            transition: background 0.3s;
        }

        .sidebar ul li a i {
            margin-right: 15px;
            font-size: 18px;
            width: 20px;
        }

        .sidebar ul li a:hover, .sidebar ul li a.active {
            background: #293256;
            color: white;
        }

        .sidebar:not(.expanded) ul li a span {
            display: none;
        }

        /* Top Navigation */
        .top-nav {
            background: white;
            padding: 15px 20px;
            position: fixed;
            top: 0;
            left: 70px;
            right: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 999;
            transition: left 0.3s ease;
        }

        .top-nav.expanded {
            left: 200px;
        }

        .top-nav .menu-icon {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            display: block; /* Ensure it remains visible */
        }

        .top-nav .search-bar {
            display: flex;
            align-items: center;
            background: #f4f7fc;
            padding: 8px 15px;
            border-radius: 20px;
            flex: 1;
            margin: 0 20px;
            position: relative;
        }

        .top-nav .search-bar input {
            border: none;
            background: none;
            outline: none;
            width: 100%;
        }

        .top-nav .search-bar .clear-search {
            position: absolute;
            right: 15px;
            color: #6c757d;
            cursor: pointer;
            font-size: 14px;
            display: none;
        }

        .top-nav .search-bar.has-text .clear-search {
            display: block;
        }

        .top-nav .profile {
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
        }

        .top-nav .profile i {
            font-size: 18px;
            color: #6c757d;
            cursor: pointer;
        }

        .notification-icon {
            position: relative;
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
            position: absolute;
            top: 40px;
            right: 0;
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

        /* Main Content */
        .main-content {
            margin-left: 70px;
            margin-right: 250px;
            margin-top: 60px; /* Offset for the fixed top nav */
            padding: 20px;
            flex: 1;
            overflow-y: auto; /* Enable vertical scrolling */
            min-height: calc(100vh - 60px); /* Ensure it takes full height minus top nav */
            transition: margin-left 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 200px;
        }

        .main-content h1 {
            font-size: 24px;
            color: #1a2238;
            margin-bottom: 20px;
        }

        /* Highlighted Search Match */
        .highlight {
            background-color: #ffeb3b;
        }

        /* Chart Controls */
        .chart-controls {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
        }

        .chart-controls button {
            padding: 5px 10px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .chart-controls button:hover {
            background: #0056b3;
        }

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .summary-card i {
            font-size: 24px;
            color: #007bff;
        }

        .summary-card .info h3 {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .summary-card .info p {
            font-size: 24px;
            font-weight: 600;
            color: #1a2238;
        }

        /* Charts */
        .charts {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .chart-card h3 {
            font-size: 16px;
            color: #1a2238;
            margin-bottom: 15px;
        }

        /* Table */
        .table-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .table-card h3 {
            font-size: 16px;
            color: #1a2238;
            margin-bottom: 15px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background: #f4f7fc;
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
        }

        td {
            color: #1a2238;
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

        /* Right Sidebar */
        .right-sidebar {
            width: 250px;
            background: white;
            padding: 20px;
            position: fixed;
            top: 60px;
            right: 0;
            height: calc(100vh - 60px);
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.05);
            overflow-y: auto;
        }

        .right-sidebar h3 {
            font-size: 16px;
            color: #1a2238;
            margin-bottom: 15px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .activity-item img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .activity-item p {
            font-size: 14px;
            color: #6c757d;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .main-content {
                margin-right: 0;
            }

            .right-sidebar {
                display: none;
            }
            table, th, td {
                font-size: 10px;
                padding: 8px;
            }
        }

        @media (max-width: 992px) {
            .summary-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .charts {
                grid-template-columns: 1fr;
            }
            table, th, td {
                font-size: 10px;
                padding: 8px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
            }

            .sidebar.expanded {
                width: 200px;
            }
            table, th, td {
                font-size: 10px;
                padding: 8px;
            }
            .top-nav {
                left: 0;
            }

            .top-nav.expanded {
                left: 200px;
            }

            .main-content {
                margin-left: 0;
            }

            .main-content.expanded {
                margin-left: 200px;
            }

            .notifications-container {
                width: 90%;
                right: 5%;
            }
        }

        @media (max-width: 576px) {
            .summary-cards {
                grid-template-columns: 1fr;
            }

            .main-content h1 {
                font-size: 20px;
            }

            table, th, td {
                font-size: 9px;
                padding: 8px;
            }

            .chart-controls {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="logo">
                <i class="fas fa-tachometer-alt"></i>
            </div>
            <ul>
                <li><a href="subadmin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                <li><a href="request_election.php"><i class="fas fa-calendar-plus"></i><span>Request Election</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
            </ul>
        </nav>

        <!-- Top Navigation -->
        <nav class="top-nav">
            <button id="sidebarToggle" class="menu-icon">
                <i class="fas fa-bars"></i>
            </button>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search page...">
                <i class="fas fa-times clear-search" id="clearSearch"></i>
            </div>
            <div class="profile">
                <div class="notification-icon" id="notificationIcon">
                    <i class="fas fa-bell"></i>
                    <div class="notification-count" id="notificationCount"><?php echo $notification_count; ?></div>
                </div>
                <a href="logout.php"><i class="fas fa-user-circle"></i></a>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <h1>Dashboard</h1>

            <!-- Summary Cards -->
            <div class="summary-cards searchable">
                <div class="summary-card">
                    <i class="fas fa-users"></i>
                    <div class="info">
                        <h3>Approved Requests</h3>
                        <p><?php echo $approved_count; ?></p>
                    </div>
                </div>
                <div class="summary-card">
                    <i class="fas fa-times-circle"></i>
                    <div class="info">
                        <h3>Rejected Requests</h3>
                        <p><?php echo $rejected_count; ?></p>
                    </div>
                </div>
                <div class="summary-card">
                    <i class="fas fa-clock"></i>
                    <div class="info">
                        <h3>Pending Requests</h3>
                        <p><?php echo $result->num_rows - ($approved_count + $rejected_count); ?></p>
                    </div>
                </div>
                <div class="summary-card">
                    <i class="fas fa-bell"></i>
                    <div class="info">
                        <h3>Notifications</h3>
                        <p><?php echo $notification_count; ?></p>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts searchable">
                <div class="chart-card">
                    <h3>Request and Notification Trends</h3>
                    <div class="chart-controls">
                        <button id="prevData">Previous</button>
                        <button id="nextData">Next</button>
                        <span id="currentDateRange"></span>
                    </div>
                    <canvas id="requestTrendsChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Status Breakdown</h3>
                    <canvas id="statusBreakdownChart"></canvas>
                </div>
            </div>

            <!-- Table -->
            <div class="table-card searchable">
                <h3>Your Election Requests</h3>
                <?php if ($result->num_rows > 0): ?>
                    <div class="table-wrapper">
                        <table id="requestsTable">
                            <thead>
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
                            </thead>
                            <tbody>
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
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No election requests found.</p>
                <?php endif; ?>
            </div>

            <!-- Add extra space to ensure scrollability for testing -->
            <div style="height: 500px;"></div>
        </div>

        <!-- Right Sidebar -->
        <div class="right-sidebar searchable">
            <h3>Activity Feed</h3>
            <div class="activity-item">
                <img src="https://via.placeholder.com/40" alt="User">
                <p>User requested an election</p>
            </div>
            <div class="activity-item">
                <img src="https://via.placeholder.com/40" alt="User">
                <p>Request approved by admin</p>
            </div>
            <div class="activity-item">
                <img src="https://via.placeholder.com/40" alt="User">
                <p>New notification received</p>
            </div>
        </div>
    </div>

    <!-- Notifications Dropdown -->
    <div class="notifications-container searchable" id="notificationsContainer">
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
        // Sidebar Toggle
        const sidebar = document.querySelector('.sidebar');
        const topNav = document.querySelector('.top-nav');
        const toggleButton = document.getElementById('sidebarToggle');
        const mainContent = document.querySelector('.main-content');

        toggleButton.addEventListener('click', () => {
            sidebar.classList.toggle('expanded');
            topNav.classList.toggle('expanded');
            mainContent.classList.toggle('expanded');
        });

        // Notification Handling
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

        // Enhanced Search Functionality
        const searchInput = document.getElementById('searchInput');
        const clearSearch = document.getElementById('clearSearch');
        const searchables = document.querySelectorAll('.searchable');

        function highlightText(element, searchTerm) {
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            const textNodes = getTextNodes(element);
            textNodes.forEach(node => {
                if (node.parentElement.tagName.toLowerCase() !== 'script' && node.parentElement.tagName.toLowerCase() !== 'style') {
                    const text = node.textContent;
                    if (text.toLowerCase().includes(searchTerm.toLowerCase())) {
                        const newHTML = text.replace(regex, '<span class="highlight">$1</span>');
                        const wrapper = document.createElement('span');
                        wrapper.innerHTML = newHTML;
                        node.replaceWith(...wrapper.childNodes);
                    }
                }
            });
        }

        function removeHighlights() {
            const highlights = document.querySelectorAll('.highlight');
            highlights.forEach(highlight => {
                const parent = highlight.parentNode;
                parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
                parent.normalize();
            });
        }

        function getTextNodes(element) {
            const textNodes = [];
            const walker = document.createTreeWalker(
                element,
                NodeFilter.SHOW_TEXT,
                null,
                false
            );
            let node;
            while (node = walker.nextNode()) {
                textNodes.push(node);
            }
            return textNodes;
        }

        function searchPage() {
            const searchTerm = searchInput.value.trim();
            removeHighlights();

            if (searchTerm.length === 0) {
                searchables.forEach(section => {
                    section.style.display = '';
                    section.querySelectorAll('*').forEach(child => {
                        child.style.display = '';
                    });
                });
                searchInput.parentElement.classList.remove('has-text');
                return;
            }

            searchables.forEach(section => {
                let sectionMatch = false;
                const textContent = section.textContent.toLowerCase();
                if (textContent.includes(searchTerm.toLowerCase())) {
                    sectionMatch = true;
                    highlightText(section, searchTerm);
                    section.style.display = '';

                    // Handle table rows specifically
                    if (section.classList.contains('table-card')) {
                        const rows = section.querySelectorAll('#requestsTable tbody tr');
                        rows.forEach(row => {
                            const rowText = row.textContent.toLowerCase();
                            row.style.display = rowText.includes(searchTerm.toLowerCase()) ? '' : 'none';
                        });
                    }
                } else {
                    section.style.display = 'none';
                }

                // Ensure the section is visible if it contains matches
                section.style.display = sectionMatch ? '' : 'none';
            });

            searchInput.parentElement.classList.toggle('has-text', searchTerm.length > 0);
        }

        searchInput.addEventListener('input', searchPage);

        clearSearch.addEventListener('click', () => {
            searchInput.value = '';
            searchPage();
            searchInput.focus();
        });

        // Dynamic Line Chart with History (Request and Notification Trends)
        const ctx1 = document.getElementById('requestTrendsChart').getContext('2d');
        let history1 = [<?php echo json_encode(['labels' => $labels, 'datasets' => [
            ['label' => 'Approved Requests', 'data' => $historical_data['approved'], 'borderColor' => '#2ecc71', 'backgroundColor' => 'rgba(46, 204, 113, 0.2)', 'fill' => true, 'tension' => 0.4],
            ['label' => 'Rejected Requests', 'data' => $historical_data['rejected'], 'borderColor' => '#e74c3c', 'backgroundColor' => 'rgba(231, 76, 60, 0.2)', 'fill' => true, 'tension' => 0.4],
            ['label' => 'Pending Requests', 'data' => $historical_data['pending'], 'borderColor' => '#f39c12', 'backgroundColor' => 'rgba(243, 156, 18, 0.2)', 'fill' => true, 'tension' => 0.4],
            ['label' => 'Notifications', 'data' => $historical_data['notifications'], 'borderColor' => '#007bff', 'backgroundColor' => 'rgba(0, 123, 255, 0.2)', 'fill' => true, 'tension' => 0.4]
        ]]); ?>];
        let currentIndex1 = 0;
        const maxHistory = 144; // Approximately 24 hours with 60-second intervals

        const requestTrendsChart = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: history1[currentIndex1].labels,
                datasets: history1[currentIndex1].datasets
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Count'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        function updateChart1(index) {
            if (index >= 0 && index < history1.length) {
                currentIndex1 = index;
                requestTrendsChart.data.labels = history1[currentIndex1].labels;
                requestTrendsChart.data.datasets = history1[currentIndex1].datasets;
                requestTrendsChart.update();
                document.getElementById('currentDateRange').textContent = `Showing data from ${history1[currentIndex1].labels[0]} to ${history1[currentIndex1].labels[history1[currentIndex1].labels.length - 1]}`;
            }
        }

        document.getElementById('prevData').addEventListener('click', () => updateChart1(currentIndex1 - 1));
        document.getElementById('nextData').addEventListener('click', () => updateChart1(currentIndex1 + 1));

        // Dynamic Line Chart for Status Breakdown
        const ctx2 = document.getElementById('statusBreakdownChart').getContext('2d');
        let history2 = [<?php echo json_encode(['labels' => $labels, 'datasets' => [
            ['label' => 'Approved', 'data' => $historical_data['approved'], 'borderColor' => '#2ecc71', 'backgroundColor' => 'rgba(46, 204, 113, 0.2)', 'fill' => true, 'tension' => 0.4],
            ['label' => 'Rejected', 'data' => $historical_data['rejected'], 'borderColor' => '#e74c3c', 'backgroundColor' => 'rgba(231, 76, 60, 0.2)', 'fill' => true, 'tension' => 0.4],
            ['label' => 'Pending', 'data' => $historical_data['pending'], 'borderColor' => '#f39c12', 'backgroundColor' => 'rgba(243, 156, 18, 0.2)', 'fill' => true, 'tension' => 0.4]
        ]]); ?>];
        let currentIndex2 = 0;

        const statusBreakdownChart = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: history2[currentIndex2].labels,
                datasets: history2[currentIndex2].datasets
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Count'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        function updateChart2(index) {
            if (index >= 0 && index < history2.length) {
                currentIndex2 = index;
                statusBreakdownChart.data.labels = history2[currentIndex2].labels;
                statusBreakdownChart.data.datasets = history2[currentIndex2].datasets;
                statusBreakdownChart.update();
            }
        }

        // Auto-refresh and store history for both charts
        setInterval(() => {
            fetch('get_historical_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ subadmin_id: <?php echo $subadmin_id; ?> })
            })
            .then(response => response.json())
            .then(data => {
                if (data.labels && data.datasets) {
                    const newData1 = {
                        labels: data.labels,
                        datasets: [
                            { label: 'Approved Requests', data: data.datasets[0].data, borderColor: '#2ecc71', backgroundColor: 'rgba(46, 204, 113, 0.2)', fill: true, tension: 0.4 },
                            { label: 'Rejected Requests', data: data.datasets[1].data, borderColor: '#e74c3c', backgroundColor: 'rgba(231, 76, 60, 0.2)', fill: true, tension: 0.4 },
                            { label: 'Pending Requests', data: data.datasets[2].data, borderColor: '#f39c12', backgroundColor: 'rgba(243, 156, 18, 0.2)', fill: true, tension: 0.4 },
                            { label: 'Notifications', data: data.datasets[3].data, borderColor: '#007bff', backgroundColor: 'rgba(0, 123, 255, 0.2)', fill: true, tension: 0.4 }
                        ]
                    };
                    history1.unshift(newData1);
                    if (history1.length > maxHistory) history1.pop();
                    updateChart1(0);

                    const newData2 = {
                        labels: data.labels,
                        datasets: [
                            { label: 'Approved', data: data.datasets[0].data, borderColor: '#2ecc71', backgroundColor: 'rgba(46, 204, 113, 0.2)', fill: true, tension: 0.4 },
                            { label: 'Rejected', data: data.datasets[1].data, borderColor: '#e74c3c', backgroundColor: 'rgba(231, 76, 60, 0.2)', fill: true, tension: 0.4 },
                            { label: 'Pending', data: data.datasets[2].data, borderColor: '#f39c12', backgroundColor: 'rgba(243, 156, 18, 0.2)', fill: true, tension: 0.4 }
                        ]
                    };
                    history2.unshift(newData2);
                    if (history2.length > maxHistory) history2.pop();
                    updateChart2(0);
                }
            })
            .catch(error => console.error('Error refreshing chart data:', error));
        }, 60000); // Refresh every 60 seconds
    </script>
</body>
</html>

<?php
$stmt->close();
$notification_stmt->close();
$approved_stmt->close();
$rejected_stmt->close();
$approved_stmt = null; // Reset for historical queries
$rejected_stmt = null;
$pending_stmt = null;
$notification_stmt = null;
mysqli_close($conn);
?>