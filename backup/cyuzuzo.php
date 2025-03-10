<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            overflow-x: hidden;
        }

        /* Fixed Top Navbar */
        .top_nav {
            width: calc(100% - 250px);
            height: 60px;
            background-color: white;
            position: fixed;
            top: 0;
            left: 250px;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            border-radius: 5px;
            margin-top: 10px;
            margin-right: 10px;
        }
        .logo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            height: 100%;
        }
        .logo h2 {
            margin: 0;
        }
        .logo button {
            background-color: #000000;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
        }

        /* Fixed Sidebar */
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #212529;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 20px;
            z-index: 999;
        }
        .admin_img {
            width: 90%;
            height: 180px;
            background-color: white;
            margin: 0 auto 20px;
            border-radius: 5px;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar ul li {
            padding: 15px;
            border-bottom: 1px solid rgb(221, 238, 255);
        }
        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
        }
        .sidebar ul li a:hover {
            background: #34495e;
        }

        /* Scrollable Main Content */
        .main-content {
            margin-left: 250px;
            padding: 80px 20px 20px;
            overflow-y: auto;
            height: 100vh;
            box-sizing: border-box;
        }

        /* Dashboard Styles */
        .dashboard {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
            width: 100%;
            max-width: 1300px;
            margin-bottom: 20px;
        }
        h1 {
            color: rgb(230, 175, 24);
            margin-bottom: 20px;
            font-size: 40px;
            font-weight: bold;
        }
        .user-info {
            color: #666;
            margin-bottom: 20px;
        }
        .username {
            color: red;
            font-weight: bold;
            padding-left: 20px;
        }

        /* Cards Row */
        .row {
            margin-bottom: 50px;
            box-shadow: 0 0 5px rgba(235, 19, 19, 0.2);
            padding: 40px;
            border-radius: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            flex: 1;
            min-width: 200px;
        }
        .card h3 {
            margin: 0;
            font-size: 20px;
            color: #333;
        }
        .card p {
            font-size: 24px;
            color: #2980b9;
            font-weight: bold;
            margin-top: 10px;
        }
        .tasks {
            font-weight: bold;
            text-align: left;
            padding-bottom: 30px;
        }

        /* Grid Container */
        .grid-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            padding: 40px;
        }
        .grid-item {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 5px rgba(0,0,0,0.2);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            transition: transform 0.3s;
        }
        .grid-item a {
            text-decoration: none;
            color: black;
        }
        .grid-item:hover {
            transform: scale(1.05);
        }
        .grid-item i {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .grid-item:nth-child(1) i { color: #ff00ff; }
        .grid-item:nth-child(2) i { color: #00ff00; }
        .grid-item:nth-child(3) i { color: #000000; }
        .grid-item:nth-child(4) i { color: #ff00ff; }
        .grid-item:nth-child(5) i { color: #00ffff; }
        .grid-item:nth-child(6) i { color: #ffff00; }
        .grid-item:nth-child(7) i { color: #0000ff; }
        .grid-item:nth-child(8) i { color: #ff8000; }
        .grid-item:nth-child(9) i { color: #ff0000; }

        .footer {
            color: #666;
            font-size: 14px;
            margin-top: 100px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Fixed Top Navbar -->
    <nav class="top_nav">
        <div class="logo">
            <h2>LOGO</h2>
            <button>☰</button>
        </div>
    </nav>

    <!-- Fixed Sidebar -->
    <nav class="sidebar">
        <div class="admin_img"></div>
        <ul>
            <li><a href="#">Dashboard</a></li>
            <li><a href="#">Elections</a></li>
            <li><a href="#">Voting System</a></li>
            <li><a href="#">Ballot Box</a></li>
            <li><a href="#">Candidates</a></li>
            <li><a href="#">Voters</a></li>
            <li><a href="#">Change Password</a></li>
            <li><a href="#">Logout</a></li>
        </ul>
    </nav>

    <!-- Scrollable Main Content -->
    <div class="main-content">
        <div class="dashboard">
            <h1>Admin Dashboard</h1>
            <div class="user-info">Logged in as: <span class="username"><?php echo $_SESSION['username']; ?></span></div>
            <div class="row">
                <div class="card">
                    <h3>Total Voters</h3>
                    <p>2</p>
                </div>
                <div class="card">
                    <h3>Total Election Type</h3>
                    <p>2</p>
                </div>
                <div class="card">
                    <h3>Total Candidates</h3>
                    <p>5</p>
                </div>
                <div class="card">
                    <h3>Total Election Area</h3>
                    <p>2</p>
                </div>
            </div>
            <h4 class="tasks">Make your Task</h4>
            <div class="grid-container">
                <div class="grid-item"><i class="fas fa-calendar"></i><?php echo "<a href='election_management.php'>Add Election</a>" ?></div>
                <div class="grid-item"><i class="fas fa-users"></i><?php echo "<a href='add_candidates.php'>Add Candidates</a>" ?></div>
                <div class="grid-item"><i class="fas fa-skull-crossbones"></i><?php echo "<a href='manage_candidates.php'>Manage Candidates</a>" ?></div>
                <div class="grid-item"><i class="fas fa-chart-pie"></i><?php echo "<a href='results.php'>Election report</a>" ?></div>
                <div class="grid-item"><i class="fas fa-edit"></i><?php echo "<a href='vote.php'>Vote Now</a>" ?></div>
                <div class="grid-item"><i class="fas fa-cogs"></i><?php echo "<a href='election_control.php'>Election Management</a>" ?></div>
                <div class="grid-item"><i class="fas fa-user"></i><?php echo "<a href='vote.php'>Vote Now</a>" ?></div>
                <div class="grid-item"><i class="fas fa-history"></i><?php echo "<a href='voting_history.php'>Voting History</a>" ?></div>
                <div class="grid-item"><i class="fas fa-sign-out-alt"></i><?php echo "<a href='vote.php'>Vote Now</a>" ?></div>
            </div>
            <div class="footer">6:02 PM | Feb 26, 2025</div>
        </div>
    </div>
</body>
</html>