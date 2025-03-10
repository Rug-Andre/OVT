<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';

$sql = "SELECT r.request_id, s.email AS subadmin_email, r.election_name, r.start_date, r.end_date, r.positions, r.notes, r.status, r.created_at 
        FROM requests r 
        JOIN subadmins s ON r.subadmin_id = s.id";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - View Subadmin Requests</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* General Styles */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            /* overflow-x: hidden; */
        }

        .container {
            max-width: 1200px;
            width: 100%;
            margin: 0 ;
            display: flex;
        }

        /* Top Navigation */
        .top_nav {
            background: linear-gradient(90deg, #2980b9, #3498db);
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

        /* Sidebar */
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
            transition: background 0.3s;
            white-space: nowrap;
        }

        .sidebar ul li a i {
            margin-right: 15px;
            font-size: 18px;
            width: 20px;
        }
a{
    font-size: 15px;
}
.logout{
        font-size: 22px;
        border: none;
        cursor: pointer;
        margin-left: 90%;
        position: fixed;
        top: 20px;
        background:rgb(207, 207, 207);
        color: blue;
        padding: 7px 8px;
        border-radius: 50%;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        transition: transform 0.3s ease, background 0.3s;
        z-index: 1001;
        left:100px;
    } 
        .sidebar ul li a:hover, .sidebar ul li a.active {
            background: #3498db;
        }

        .sidebar:not(.expanded) ul li a span {
            display: none;
        }

        /* Main Content */
        .main-content {
            margin-left: 60px;
            padding: 80px 20px;
            flex: 1;
            transition: margin-left 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 200px;
        }

        h1 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            background-color: #2ecc71;
            color: white;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
           
        }

        th, td {
            padding: 4px;
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

        a {
            color: #3498db;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .action-links a {
            margin-right: 10px;
        }

        .status-approved { color: #2ecc71; font-weight: bold; }
        .status-rejected { color: #e74c3c; font-weight: bold; }
        .status-pending { color: #f39c12; font-weight: bold; }

        /* Delete Button */
        .delete-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s;
        }

        .delete-btn:hover {
            background-color: #c0392b;
            text-decoration: none;
        }

        /* Toggle Button */
        #sidebarToggle {
            background: none;
            color: white;
            font-size: 22px;
            border: none;
            cursor: pointer;
            margin-left: 0;
        }

        /* Responsive Design */
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

            table, th, td {
                font-size: 12px;
                padding: 8px;
            }

            .delete-btn {
                padding: 4px 8px;
                font-size: 11px;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 22px;
            }

            table {
                font-size: 11px;
            }

            .action-links a, .delete-btn {
                display: block;
                margin: 5px 0;
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
            <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
            <li><a href="view_requested_elections.php" class="active"><i class="fas fa-list"></i><span>View Requests</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="main-content">
            <h1>Subadmin Election Requests</h1>
            <?php if (!empty($message)): ?>
                <div class="message">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <table>
                <tr>
                    <th>Request ID</th>
                    <th>Subadmin Email</th>
                    <th>Election Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Positions</th>
                    <th>Notes</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?php echo $row['request_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['subadmin_email']); ?></td>
                        <td><?php echo htmlspecialchars($row['election_name']); ?></td>
                        <td><?php echo $row['start_date']; ?></td>
                        <td><?php echo $row['end_date']; ?></td>
                        <td><?php echo htmlspecialchars($row['positions']); ?></td>
                        <td><?php echo htmlspecialchars($row['notes'] ?? 'N/A'); ?></td>
                        <td>
                            <?php 
                            if ($row['status'] == 0) {
                                echo '<span class="status-pending">Pending</span>';
                            } elseif ($row['status'] == 1) {
                                echo '<span class="status-approved">Approved</span>';
                            } else {
                                echo '<span class="status-rejected">Rejected</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo $row['created_at']; ?></td>
                        <td class="action-links">
                            <?php if ($row['status'] == 0) { ?>
                                <a href="approve_request.php?id=<?php echo $row['request_id']; ?>" 
                                   onclick="return confirm('Are you sure you want to approve this request?');">Approve</a> 
                                <a href="reject_request.php?id=<?php echo $row['request_id']; ?>" 
                                   onclick="return confirm('Are you sure you want to reject this request?');">Reject</a> 
                            <?php } ?>
                            <button class="delete-btn" 
                                    onclick="if(confirm('Are you sure you want to delete this request?')) { window.location.href='delete_request.php?id=<?php echo $row['request_id']; ?>'; }">
                                Delete
                            </button>
                        </td>
                    </tr>
                <?php } ?>
            </table>
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
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>