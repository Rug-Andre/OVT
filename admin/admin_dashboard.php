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
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$user_id = $_SESSION['user_id'];
$message = '';
$message_class = '';
$election_message = ''; // Message for election form
$candidate_success = $candidate_error = ''; // Messages for candidate form

// Fetch user image
$query = "SELECT image FROM admin WHERE user_id = " . mysqli_real_escape_string($conn, $user_id);
$result = mysqli_query($conn, $query);
if ($result) {
    $user = mysqli_fetch_assoc($result) ?? ['image' => null];
} else {
    $user = ['image' => null];
}

// Handle image upload (admin profile)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['admin_image']) && !$user['image']) {
    $image = $_FILES['admin_image']['tmp_name'];
    if (!empty($image) && $_FILES['admin_image']['error'] == UPLOAD_ERR_OK) {
        $imgContent = file_get_contents($image);
        if ($imgContent === false) {
            $message = "Error: Failed to read the uploaded file.";
            $message_class = 'error';
        } else {
            $imageSize = strlen($imgContent);
            $imgContent = mysqli_real_escape_string($conn, $imgContent);
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

// Handle election form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['title'])) {
    $title = $_POST['title'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $positions = trim($_POST['positions']);
    $requires_code = isset($_POST['requires_code']) ? 1 : 0;
    $created_by = $_SESSION['user_id'];

    // Check if the admin exists in the admin table
    $user_check = $conn->prepare("SELECT user_id FROM admin WHERE user_id = ?");
    $user_check->bind_param("i", $created_by);
    $user_check->execute();
    $result = $user_check->get_result();

    if ($result->num_rows == 0) {
        $election_message = "Error: Invalid admin ID. Admin does not exist.";
    } elseif (strtotime($end_date) <= strtotime($start_date)) {
        $election_message = "Error: End date must be after start date.";
    } elseif (empty($positions)) {
        $election_message = "Error: Please enter at least one position.";
    } else {
        $stmt = $conn->prepare("INSERT INTO elections (election_name, start_date, end_date, created_by, positions, requires_code) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisi", $title, $start_date, $end_date, $created_by, $positions, $requires_code);

        if ($stmt->execute()) {
            $election_message = "Election added successfully!";
        } else {
            $election_message = "Error: " . $conn->error;
        }
        $stmt->close();
    }
    $user_check->close();
}

// Handle candidate form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['candidate_name'])) {
    $candidate_name = trim($_POST['candidate_name']);
    $position = trim($_POST['position']);
    $election_id = isset($_POST['election_id']) && $_POST['election_id'] !== "" ? intval($_POST['election_id']) : 0;
    $candidate_info = trim($_POST['candidate_info']);
    
    $image_path = "";
    if (isset($_FILES['candidate_image']) && $_FILES['candidate_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $target_file = $target_dir . basename($_FILES["candidate_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $check = getimagesize($_FILES["candidate_image"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["candidate_image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            } else {
                $candidate_error = "Sorry, there was an error uploading your image.";
            }
        } else {
            $candidate_error = "File is not an image.";
        }
    }

    if (!empty($candidate_name) && !empty($position) && $election_id > 0) {
        $candidate_name = mysqli_real_escape_string($conn, $candidate_name);
        $position = mysqli_real_escape_string($conn, $position);
        $candidate_info = mysqli_real_escape_string($conn, $candidate_info);
        $image_path = mysqli_real_escape_string($conn, $image_path);

        $sql = "INSERT INTO candidates (election_id, candidate_name, position, candidate_info, candidate_image) 
                VALUES ($election_id, '$candidate_name', '$position', '$candidate_info', '$image_path')";
        
        if (mysqli_query($conn, $sql)) {
            $candidate_success = "Candidate added successfully!";
        } else {
            $candidate_error = "Error adding candidate: " . mysqli_error($conn);
        }
    } else {
        $candidate_error = "Please fill in all fields and select an election.";
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

// Fetch active elections for results notification and candidate form
$sql_elections = "SELECT * FROM elections WHERE status = 1 AND NOW() BETWEEN start_date AND end_date";
$result_elections = mysqli_query($conn, $sql_elections);
if (!$result_elections) {
    die("Error fetching elections: " . mysqli_error($conn));
}

$electionQuery = "SELECT election_id, election_name, start_date, end_date FROM elections WHERE status = 1";
$electionResult = mysqli_query($conn, $electionQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
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
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            width: 90%;
            max-width: 500px;
        }
        .popup h2 {
            margin-top: 0;
        }
        .popup .form-group {
            margin-bottom: 15px;
        }
        .popup .form-label {
            display: block;
            margin-bottom: 5px;
        }
        .popup .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .popup textarea.form-control {
            resize: vertical;
        }
        .popup .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        .popup .btn-primary, .popup .btn-success {
            background-color: #007bff;
            color: white;
        }
        .popup .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .alert-info, .alert-success, .alert-danger {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
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
            <li><a href="view_election_requests.php"><i class="fas fa-box"></i>Election Requests</a></li>
            <li><a href="manage_candidates.php"><i class="fas fa-user-tie"></i> Candidates</a></li>
            <li><a href="view_users.php"><i class="fas fa-users"></i> Voters</a></li>
            <li><a href="subadmin_requests.php"><i class="fas fa-envelope"></i> Subadmin Requests</a></li>
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
                <div class="grid-item" id="add-election-btn"><i class="fas fa-calendar"></i><a href="#" onclick="showElectionPopup(); return false;">Add Election</a></div>
                <div class="grid-item" id="add-candidate-btn"><i class="fas fa-users"></i><a href="#" onclick="showCandidatePopup(); return false;">Add Candidates</a></div>
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

    <!-- Election Popup -->
    <div id="election-overlay" class="overlay"></div>
    <div id="election-popup" class="popup">
        <h2><i class="fas fa-vote-yea"></i> Add New Election</h2>
        <?php if (!empty($election_message)): ?>
            <div class="alert-info"><i class="fas fa-info-circle"></i> <?php echo $election_message; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Election Title</label>
                <i class="fas fa-heading form-icon"></i>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Start Date</label>
                <input type="datetime-local" name="start_date" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">End Date</label>
                <input type="datetime-local" name="end_date" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Positions (separate with commas, e.g., Mayor, Deputy Mayor)</label>
                <i class="fas fa-users form-icon"></i>
                <input type="text" name="positions" class="form-control" required placeholder="e.g., Mayor, Deputy Mayor">
            </div>
            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="requires_code" value="1"> Requires Voter Code
                </label>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Election
            </button>
            <button type="button" class="btn btn-secondary" onclick="hideElectionPopup()">
                <i class="fas fa-times"></i> Close
            </button>
        </form>
    </div>

    <!-- Candidate Popup -->
    <div id="candidate-overlay" class="overlay"></div>
    <div id="candidate-popup" class="popup">
        <h2>Add Candidate</h2>
        <?php if (!empty($candidate_success)): ?>
            <div class="alert-success"><?php echo $candidate_success; ?></div>
        <?php elseif (!empty($candidate_error)): ?>
            <div class="alert-danger"><?php echo $candidate_error; ?></div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($electionResult) == 0): ?>
            <div class="alert-danger">No active elections available to add candidates.</div>
            <button type="button" class="btn btn-secondary" onclick="hideCandidatePopup()">Close</button>
        <?php else: ?>
            <form method="POST" id="candidateForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Candidate Name</label>
                    <input type="text" name="candidate_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Select Election</label>
                    <select name="election_id" class="form-control" id="electionSelect" required>
                        <option value="" disabled selected>-- Choose Election --</option>
                        <?php 
                        mysqli_data_seek($electionResult, 0); // Reset pointer
                        while ($row = mysqli_fetch_assoc($electionResult)): ?>
                            <option value="<?php echo $row['election_id']; ?>">
                                <?php echo htmlspecialchars($row['election_name']) . " (" . $row['start_date'] . " to " . $row['end_date'] . ")"; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Select Position</label>
                    <select name="position" class="form-control" id="positionSelect" required>
                        <option value="">-- Choose Position --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Candidate Information</label>
                    <textarea name="candidate_info" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Candidate Image</label>
                    <input type="file" name="candidate_image" class="form-control" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-success">Add Candidate</button>
                <button type="button" class="btn btn-secondary" onclick="hideCandidatePopup()">Close</button>
            </form>
        <?php endif; ?>
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

        // Election Popup Functions
        const electionOverlay = document.getElementById('election-overlay');
        const electionPopup = document.getElementById('election-popup');

        function showElectionPopup() {
            electionOverlay.style.display = 'block';
            electionPopup.style.display = 'block';
            document.body.classList.add('overlay-active');
        }

        function hideElectionPopup() {
            electionOverlay.style.display = 'none';
            electionPopup.style.display = 'none';
            document.body.classList.remove('overlay-active');
        }

        electionOverlay.addEventListener('click', hideElectionPopup);

        // Candidate Popup Functions
        const candidateOverlay = document.getElementById('candidate-overlay');
        const candidatePopup = document.getElementById('candidate-popup');

        function showCandidatePopup() {
            candidateOverlay.style.display = 'block';
            candidatePopup.style.display = 'block';
            document.body.classList.add('overlay-active');
        }

        function hideCandidatePopup() {
            candidateOverlay.style.display = 'none';
            candidatePopup.style.display = 'none';
            document.body.classList.remove('overlay-active');
        }

        candidateOverlay.addEventListener('click', hideCandidatePopup);

        // Results Notification Functions
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

        // Dynamic position fetching for candidate form
        document.getElementById('electionSelect')?.addEventListener('change', function () {
            var electionId = this.value;
            var positionSelect = document.getElementById('positionSelect');
            
            positionSelect.innerHTML = '<option value="">-- Choose Position --</option>';

            if (electionId) {
                fetch('fetch_positions.php?election_id=' + electionId)
                    .then(response => response.json())
                    .then(data => {
                        data.positions.forEach(position => {
                            var option = document.createElement('option');
                            option.value = position;
                            option.textContent = position;
                            positionSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching positions:', error));
            }
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>