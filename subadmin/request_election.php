<?php
session_start();
include 'db.php';

if (!isset($_SESSION['subadmin_id'])) {
    header("Location: subadmin_login.php");
    exit();
}

$message = '';
$message_class = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subadmin_id = $_SESSION['subadmin_id'];
    $election_name = mysqli_real_escape_string($conn, $_POST['election_name']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    $positions = mysqli_real_escape_string($conn, $_POST['positions']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');

    // Validate dates
    if (strtotime($end_date) <= strtotime($start_date)) {
        $message = "Error: End date must be after start date.";
        $message_class = 'error';
    } else {
        $query = "INSERT INTO requests (subadmin_id, election_name, start_date, end_date, positions, notes) 
                  VALUES ('$subadmin_id', '$election_name', '$start_date', '$end_date', '$positions', '$notes')";
        if (mysqli_query($conn, $query)) {
            $message = "Election request submitted successfully! Awaiting admin approval.";
            $message_class = 'success';
        } else {
            $message = "Error: " . mysqli_error($conn);
            $message_class = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Election</title>
    <style>
        body { 
            font-family: Arial, sans-serif;
             background-color: #f5f7fa;
              margin: 0; 
              padding: 0;
               display: flex;
             }
        .top_nav {
             background-color: #3498db; 
             color: white; 
             padding: 10px 20px;
              position: fixed; 
              top: 0;
               width: 100%; 
               z-index: 1000;
             }
        .logo { 
            display: flex;
             align-items: center;
             }
        .logo img {
             width: 40px;
              height: 40px;
               margin-right: 10px;
             }
        .sidebar { 
            width: 200px; 
            background-color: #2c3e50;
             color: white; 
             height: 100vh; 
             padding: 20px; 
             position: fixed;
              top: 60px;
             }
        .sidebar ul { 
            list-style: none; 
            padding: 0; 
        }
        .sidebar ul li {
             margin: 20px 0; 
            }
        .sidebar ul li a {
             color: white; 
             text-decoration: none; 
             display: flex;
              align-items: center;
             }
        .sidebar ul li a i {
             margin-right: 10px; 
            }
        .sidebar ul li a:hover { 
            color: #3498db; 
        }
        .main-content {
             margin-left: 200px;
              padding: 80px 20px 20px;
               flex: 1; 
            }
        .form-container {
             background-color: white;
              padding: 20px; 
              border-radius: 5px; 
              box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); 
              max-width: 500px;
               margin: 0 auto;
             }
        .form-group { 
            margin-bottom: 15px; 
        }
        label { 
            display: block;
             margin-bottom: 5px; 
            }
        input, textarea { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ddd; 
            border-radius: 4px;
             box-sizing: border-box; 
            }
        button { width: 100%; 
            padding: 10px;
             background-color: #3498db; 
             color: white;
              border: none;
              border-radius: 4px; 
              cursor: pointer; 
            }
        button:hover {
             background-color: #2980b9;
             }
        .message {
             margin-bottom: 15px;

             padding: 10px; 
             border-radius: 4px; 
             text-align: center;
             }
        .message.success {
             background-color: #2ecc71; 
            color: white;
         }
        .message.error {
             background-color: #e74c3c;
             color: white; 
            }
    </style>
</head>
<body>
    <nav class="top_nav">
        <div class="logo">
            <img src="https://www.citypng.com/public/uploads/preview/png-green-vote-word-704081694605369jh3qz1gntg.png" alt="Voting Platform">
            <button id="sidebarToggle">☰</button>
        </div>
    </nav>

    <nav class="sidebar">
        <ul>
            <li><a href="subadmin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="request_election.php"><i class="fas fa-calendar-plus"></i> Request Election</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="form-container">
            <h2>Request Election</h2>
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_class; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="election_name">Election Name</label>
                    <input type="text" id="election_name" name="election_name" required>
                </div>
                <div class="form-group">
                    <label for="start_date">Start Date and Time</label>
                    <input type="datetime-local" id="start_date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label for="end_date">End Date and Time</label>
                    <input type="datetime-local" id="end_date" name="end_date" required>
                </div>
                <div class="form-group">
                    <label for="positions">Positions (comma-separated, e.g., President, Vice President)</label>
                    <input type="text" id="positions" name="positions" required>
                </div>
                <div class="form-group">
                    <label for="notes">Additional Notes (optional)</label>
                    <textarea id="notes" name="notes" rows="4"></textarea>
                </div>
                <button type="submit">Submit Request</button>
            </form>
        </div>
    </div>

    <script>
        const sidebar = document.querySelector('.sidebar');
        const toggleButton = document.getElementById('sidebarToggle');
        const mainContent = document.querySelector('.main-content');
        const topNav = document.querySelector('.top_nav');

        toggleButton.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            if (sidebar.classList.contains('active')) {
                mainContent.style.marginLeft = '200px';
                topNav.style.width = 'calc(100% - 200px)';
                topNav.style.left = '200px';
            } else {
                mainContent.style.marginLeft = '0';
                topNav.style.width = '100%';
                topNav.style.left = '0';
            }
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>