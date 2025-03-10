<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Set timezone to ensure NOW() matches your local time
date_default_timezone_set('Africa/Nairobi'); // Adjust to your timezone

ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('max_execution_time', '60');

$user_id = $_SESSION['user_id'];
$message = '';
$message_class = '';

// Fetch user image
$query = "SELECT image FROM admin WHERE user_id = " . mysqli_real_escape_string($conn, $user_id);
$result = mysqli_query($conn, $query);
if ($result) {
    $user = mysqli_fetch_assoc($result) ?? ['image' => null];
} else {
    $user = ['image' => null];
}

// Handle image upload (only if no image exists)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['admin_image']) && !$user['image']) {
    $image = $_FILES['admin_image']['tmp_name'];
    if (!empty($image) && $_FILES['admin_image']['error'] == UPLOAD_ERR_OK) {
        $imgContent = file_get_contents($image);
        if ($imgContent === false) {
            $message = "Error: Failed to read the uploaded file.";
            $message_class = 'error';
        } else {
            $imageSize = strlen($imgContent);
            $imgContent = mysqli_real_escape_string($conn, $imgContent); // Escape binary data
            $update_query = "UPDATE admin SET image = '$imgContent' WHERE user_id = " . mysqli_real_escape_string($conn, $user_id);
            if (mysqli_query($conn, $update_query)) {
                $user['image'] = $imgContent;
                $message = "Image uploaded successfully! Size: " . round($imageSize / 1024 / 1024, 2) . " MB";
                $message_class = 'success';
            } else {
                $message = "Image upload failed: " . mysqli_error($conn);
                $message_class = 'error';
            }
        }
    } else {
        $message = "Error: No image selected or upload error (Code: " . $_FILES['admin_image']['error'] . ").";
        $message_class = 'error';
    }
}

// Fetch total voters
$totalVoters = 0;
$totalVotersQuery = "SELECT COUNT(*) as total FROM users";
$totalVotersResult = mysqli_query($conn, $totalVotersQuery);
if ($totalVotersResult) {
    $totalVoters = mysqli_fetch_assoc($totalVotersResult)['total'];
}

// Fetch total election types
$totalElectionTypes = 0;
$totalElectionTypesQuery = "SELECT COUNT(DISTINCT election_name) as total FROM elections";
$totalElectionTypesResult = mysqli_query($conn, $totalElectionTypesQuery);
if ($totalElectionTypesResult) {
    $totalElectionTypes = mysqli_fetch_assoc($totalElectionTypesResult)['total'];
}

// Fetch total candidates
$totalCandidates = 0;
$totalCandidatesQuery = "SELECT COUNT(*) as total FROM candidates";
$totalCandidatesResult = mysqli_query($conn, $totalCandidatesQuery);
if ($totalCandidatesResult) {
    $totalCandidates = mysqli_fetch_assoc($totalCandidatesResult)['total'];
}

// Fetch total active elections
$totalActiveElections = 0;
$totalActiveElectionsQuery = "SELECT COUNT(*) as total FROM elections WHERE status = 1 AND NOW() BETWEEN start_date AND end_date";
$totalActiveElectionsResult = mysqli_query($conn, $totalActiveElectionsQuery);
if ($totalActiveElectionsResult) {
    $totalActiveElections = mysqli_fetch_assoc($totalActiveElectionsResult)['total'];
}

// Fetch active elections for the notification div
$sql_elections = "SELECT * FROM elections WHERE status = 1 AND NOW() BETWEEN start_date AND end_date";
$result_elections = mysqli_query($conn, $sql_elections);
if (!$result_elections) {
    die("Error fetching elections: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/admin_dashboard.css">
</head>
<body>
    <nav class="top_nav">
        <div class="logo">
            <img src="https://www.citypng.com/public/uploads/preview/png-green-vote-word-704081694605369jh3qz1gntg.png" alt="Voting Platform" style="width: 40px; height: 40px;">
            <button id="sidebarToggle">☰</button>
        </div>
    </nav>

    <nav class="sidebar">
        <div class="admin_img <?php echo !$user['image'] ? 'upload-enabled' : ''; ?>" style="<?php echo $user['image'] ? 'background-image: url(data:image/jpeg;base64,' . base64_encode($user['image']) . ');' : ''; ?>">
            <?php if (!$user['image']): ?>
                <form method="POST" enctype="multipart/form-data" id="imageUploadForm">
                    <input type="file" id="admin_image" name="admin_image" accept="image/*">
                </form>
            <?php endif; ?>
        </div>
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_class; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <ul>
            <li><a href="#"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="election_control.php"><i class="fas fa-calendar"></i> Elections</a></li>
            <li><a href="#"><i class="fas fa-vote-yea"></i> Voting System</a></li>
            <li><a href="#"><i class="fas fa-box"></i> Ballot Box</a></li>
            <li><a href="manage_candidates.php"><i class="fas fa-user-tie"></i> Candidates</a></li>
            <li><a href="view_users.php"><i class="fas fa-users"></i> Voters</a></li>
            <li><a href="view_requests.php"><i class="fas fa-envelope"></i> Subadmin Requests</a></li> <!-- New link added -->
            <li><a href="settings.php"><i class="fas fa-user-cog"></i> User Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="dashboard">
            <h1>Admin Dashboard</h1>
            <div class="user-info">Logged in as: <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
            <div class="row">
                <div class="card">
                    <h3><i class="fas fa-users"></i> Total Voters/Users</h3>
                    <p><?php echo $totalVoters; ?></p>
                </div>
                <div class="card">
                    <h3><i class="fas fa-calendar"></i> Total Election Types</h3>
                    <p><?php echo $totalElectionTypes; ?></p>
                </div>
                <div class="card">
                    <h3><i class="fas fa-user-tie"></i> Total Candidates</h3>
                    <p><?php echo $totalCandidates; ?></p>
                </div>
                <div class="card">
                    <h3><i class="fas fa-check-circle"></i> Total Active Elections</h3>
                    <p><?php echo $totalActiveElections; ?></p>
                </div>
            </div>
            <h4 class="tasks"><i class="fas fa-tasks"></i> Make your Task</h4>
            <div class="grid-container">
                <div class="grid-item"><i class="fas fa-calendar"></i><?php echo "<a href='election_management.php'>Add Election</a>" ?></div>
                <div class="grid-item"><i class="fas fa-users"></i><?php echo "<a href='add_candidates.php'>Add Candidates</a>" ?></div>
                <div class="grid-item"><i class="fas fa-user-edit"></i><?php echo "<a href='manage_candidates.php'>Manage Candidates</a>" ?></div>
                <div class="grid-item" id="election-report">
                    <i class="fas fa-chart-pie"></i>
                    <a href="#" onclick="showResultsNotification(); return false;">Election Report</a>
                </div>
                <div class="grid-item"><i class="fas fa-cogs"></i><?php echo "<a href='election_control.php'>Election Management</a>" ?></div>
                <div class="grid-item"><i class="fas fa-cogs"></i><?php echo "<a href='generate_codes.php'>Generate election codes</a>" ?></div>
                <div class="grid-item"><i class="fas fa-cogs"></i><?php echo "<a href='view_voter_codes.php'>View voter codes</a>" ?></div>
                <div class="grid-item"><i class="fas fa-history"></i><?php echo "<a href='voting_history.php'>Voting History</a>" ?></div>
                <div class="grid-item"><i class="fas fa-users"></i><?php echo "<a href='view_users.php'>View users</a>" ?></div>
                <div class="grid-item"><i class="fas fa-sign-out-alt"></i><?php echo "<a href='logout.php'>Logout</a>" ?></div>
            </div>
            <div class="footer" id="realTimeFooter"></div>
        </div>
    </div>

    <!-- Results Notification -->
    <div id="results-overlay" class="overlay"></div>
    <div id="results-notification" class="notification">
        <h3>Choose an Election to View Results</h3>
        <?php
        mysqli_data_seek($result_elections, 0); // Reset pointer for reuse
        if (mysqli_num_rows($result_elections) > 0):
            while ($election = mysqli_fetch_assoc($result_elections)):
        ?>
                <div class="election-card">
                    <a href="results.php?election_id=<?php echo $election['election_id']; ?>">
                        <?php echo htmlspecialchars($election['election_name']); ?>
                    </a>
                </div>
        <?php endwhile; else: ?>
            <p>No active elections available at this time.</p>
        <?php endif; ?>
        <button id="results-cancel-btn" class="cancel-btn">Cancel</button>
    </div>

    <script>
        function updateRealTime() {
            const footer = document.getElementById('realTimeFooter');
            const now = new Date();
            const time = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            const date = now.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
            footer.textContent = `${time} | ${date}`;
        }
        updateRealTime();
        setInterval(updateRealTime, 1000);

        const sidebar = document.querySelector('.sidebar');
        const toggleButton = document.getElementById('sidebarToggle');
        const mainContent = document.querySelector('.main-content');
        const topNav = document.querySelector('.top_nav');
        const adminImg = document.querySelector('.admin_img');
        const fileInput = document.getElementById('admin_image');

        toggleButton.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            if (sidebar.classList.contains('active')) {
                if (window.innerWidth > 768) {
                    mainContent.classList.add('active');
                    topNav.style.width = 'calc(100% - 200px)';
                    topNav.style.left = '200px';
                }
            } else {
                mainContent.classList.remove('active');
                topNav.style.width = '100%';
                topNav.style.left = '0';
            }
        });

        adminImg.addEventListener('click', () => {
            <?php if (!$user['image']): ?>
                fileInput.click();
            <?php else: ?>
                window.location.href = 'settings.php';
            <?php endif; ?>
        });

        if (fileInput) {
            fileInput.addEventListener('change', () => {
                document.getElementById('imageUploadForm').submit();
            });
        }

        // Functions to show/hide the election report notification
        const resultsOverlay = document.getElementById('results-overlay');
        const resultsNotification = document.getElementById('results-notification');
        const resultsCancelBtn = document.getElementById('results-cancel-btn');

        function showResultsNotification() {
            resultsOverlay.style.display = 'block';
            resultsNotification.style.display = 'block';
            document.body.classList.add('overlay-active');
        }

        function hideResultsNotification() {
            resultsOverlay.style.display = 'none';
            resultsNotification.style.display = 'none';
            document.body.classList.remove('overlay-active');
        }

        resultsCancelBtn.addEventListener('click', hideResultsNotification);
        resultsOverlay.addEventListener('click', hideResultsNotification);
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>