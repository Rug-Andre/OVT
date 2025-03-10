<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('max_execution_time', '60');

$user_id = $_SESSION['user_id'];
$message = '';
$message_class = '';
$code_message = ''; // For voter code-specific messages (voting)
$results_code_message = ''; // For voter code-specific messages (results)

// Fetch user image
$user_id = mysqli_real_escape_string($conn, $user_id);
$query = "SELECT image FROM users WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $query);

if (!$result) {
    $user = ['image' => null]; // Default to null if query fails
} else {
    $user = mysqli_fetch_assoc($result) ?? ['image' => null];
}

// Handle image upload (only if no image exists)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['user_image']) && !$user['image']) {
    $image = $_FILES['user_image']['tmp_name'];
    if (!empty($image) && $_FILES['user_image']['error'] == UPLOAD_ERR_OK) {
        $imgContent = file_get_contents($image);
        if ($imgContent === false) {
            $message = "Error: Failed to read the uploaded file.";
            $message_class = 'error';
        } else {
            $imageSize = strlen($imgContent); // Size in bytes
            $imgContent = mysqli_real_escape_string($conn, $imgContent);
            $update_query = "UPDATE users SET image = '$imgContent' WHERE user_id = '$user_id'";
            
            if (mysqli_query($conn, $update_query)) {
                $user['image'] = $imgContent;
                $message = "Image uploaded successfully! Size: " . round($imageSize / 1024 / 1024, 2) . " MB";
                $message_class = 'success';
            } else {
                $message = "Image upload failed: " . mysqli_error($conn) . " (Size: " . round($imageSize / 1024 / 1024, 2) . " MB)";
                $message_class = 'error';
            }
        }
    } else {
        $message = "Error: No image selected or upload error (Code: " . $_FILES['user_image']['error'] . ").";
        $message_class = 'error';
    }
}

// Fetch active elections with requires_code info
$sql_elections = "SELECT election_id, election_name, requires_code 
                  FROM elections 
                  WHERE status = 1 AND NOW() BETWEEN start_date AND end_date";
$result_elections = mysqli_query($conn, $sql_elections);

if (!$result_elections) {
    die("Error fetching elections: " . mysqli_error($conn));
}

// Handle voter code submission for voting
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['voter_code'], $_POST['election_id']) && !isset($_POST['results'])) {
    $election_id = intval($_POST['election_id']);
    $voter_code = mysqli_real_escape_string($conn, $_POST['voter_code']);
    
    $check_code = "SELECT * FROM voter_codes WHERE election_id = '$election_id' AND voter_code = '$voter_code'";
    $code_result = mysqli_query($conn, $check_code);

    if (mysqli_num_rows($code_result) == 0) {
        $code_message = "<div class='code-alert error'>Voter code does not exist.</div>";
    } else {
        $code_row = mysqli_fetch_assoc($code_result);
        if ($code_row['is_used']) {
            $code_message = "<div class='code-alert error'>This voter code has already been used.</div>";
        } else {
            // Mark the code as used and redirect to vote.php
            $update_code = "UPDATE voter_codes SET is_used = 1 WHERE voter_code = '$voter_code'";
            if (mysqli_query($conn, $update_code)) {
                $_SESSION['validated_code'][$election_id] = $voter_code;
                header("Location: vote.php?election_id=" . urlencode($election_id));
                exit();
            } else {
                $code_message = "<div class='code-alert error'>Error processing code: " . mysqli_error($conn) . "</div>";
            }
        }
    }
}

// Handle voter code submission for viewing results
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['voter_code'], $_POST['election_id'], $_POST['results'])) {
    $election_id = intval($_POST['election_id']);
    $voter_code = mysqli_real_escape_string($conn, $_POST['voter_code']);
    
    $check_code = "SELECT * FROM voter_codes WHERE election_id = '$election_id' AND voter_code = '$voter_code'";
    $code_result = mysqli_query($conn, $check_code);

    if (mysqli_num_rows($code_result) == 0) {
        $results_code_message = "<div class='code-alert error'>Voter code does not exist.</div>";
    } else {
        $code_row = mysqli_fetch_assoc($code_result);
        if (!$code_row['is_used']) {
            $results_code_message = "<div class='code-alert error'>This voter code has not been used to vote in this election.</div>";
        } else {
            // Check if the user has voted with this code (assuming votes table links user_id and election_id)
            $vote_check = "SELECT * FROM votes WHERE user_id = '$user_id' AND election_id = '$election_id'";
            $vote_result = mysqli_query($conn, $vote_check);
            if (mysqli_num_rows($vote_result) > 0) {
                header("Location: results.php?election_id=" . urlencode($election_id));
                exit();
            } else {
                $results_code_message = "<div class='code-alert error'>You did not vote in this election with this code.</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Online Voting Platform</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/user_dashboard.css">
    <style>
        .code-notification, .results-code-notification {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            width: 90%;
            max-width: 400px;
        }
        .code-notification label, .results-code-notification label {
            font-weight: bold;
            margin-bottom: 10px;
            display: block;
            color: #2c3e50;
        }
        .code-notification label i, .results-code-notification label i {
            margin-right: 0.5rem;
        }
        .code-notification input, .results-code-notification input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        .code-notification button, .results-code-notification button {
            background-color: #3498db;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .code-notification button i, .results-code-notification button i {
            margin-right: 0.5rem;
        }
        .code-notification button:hover, .results-code-notification button:hover {
            background-color: #2980b9;
        }
        .code-alert {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        .code-alert.error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .overlay.active + .code-notification, .overlay.active + .results-code-notification {
            display: block;
        }
    </style>
</head>
<body>
   <nav class="navbar">
        <div class="navbar-content">
            <a href="home.php" class="navbar-brand">
                <img src="https://www.citypng.com/public/uploads/preview/png-green-vote-word-704081694605369jh3qz1gntg.png" alt="Voting Platform" style="width: 40px; height: 40px;">
                Voting Platform
            </a>
            <ul class="nav-menu">
                <li class="nav-item"><a href="faq.php"><i class="fas fa-question-circle"></i> FAQ</a></li>
                <li class="nav-item"><a href="terms.php"><i class="fas fa-file-contract"></i> Terms & Conditions</a></li>
                <li class="nav-item"><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>
    </nav>

    <div class="main-container">
        <section class="hero">
            <div class="user_img" style="<?php echo $user['image'] ? 'background-image: url(data:image/jpeg;base64,' . base64_encode($user['image']) . ');' : ''; ?>">
                <?php if (!$user['image']): ?>
                    <form method="POST" enctype="multipart/form-data" id="imageUploadForm">
                        <input type="file" id="user_image" name="user_image" accept="image/*">
                    </form>
                <?php endif; ?>
            </div>
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_class; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <h1><i class="fas fa-vote-yea"></i> Welcome to the Online Voting Platform</h1>
            <p>Your voice matters! Participate in secure, transparent elections and shape the future.</p>
            <div>
                <button id="vote-now-btn" class="btn btn-primary"><i class="fas fa-check"></i> Vote Now</button>
                <button id="view-results-btn" class="btn btn-primary"><i class="fas fa-poll"></i> View Results</button>
            </div>
        </section>

        <section class="features">
            <div class="feature-card">
                <h3><i class="fas fa-lock"></i> Secure Voting</h3>
                <p>Our platform uses advanced encryption to ensure your vote remains confidential and tamper-proof.</p>
            </div>
            <div class="feature-card">
                <h3><i class="fas fa-chart-line"></i> Real-Time Results</h3>
                <p>Get instant updates on election outcomes as votes are cast and tallied securely.</p>
            </div>
            <div class="feature-card">
                <h3><i class="fas fa-globe"></i> Easy Access</h3>
                <p>Vote from anywhere, anytime, using any device with an internet connection.</p>
            </div>
        </section>
    </div>

    <!-- Vote Notification -->
    <div id="vote-overlay" class="overlay"></div>
    <div id="vote-notification" class="notification">
        <h3><i class="fas fa-vote-yea"></i> Choose an Election to Vote</h3>
        <?php
        if (mysqli_num_rows($result_elections) > 0):
            while ($election = mysqli_fetch_assoc($result_elections)):
                $requires_code = $election['requires_code'];
        ?>
                <div class="election-card" data-election-id="<?php echo $election['election_id']; ?>" data-requires-code="<?php echo $requires_code; ?>">
                    <i class="fas fa-ballot-box"></i> <?php echo htmlspecialchars($election['election_name']); ?>
                </div>
        <?php endwhile; else: ?>
            <p><i class="fas fa-exclamation-circle"></i> No active elections available at this time.</p>
        <?php endif; ?>
        <button id="vote-cancel-btn" class="cancel-btn"><i class="fas fa-times"></i> Cancel</button>
    </div>

    <!-- Code Notification for Voting -->
    <div id="code-overlay" class="overlay"></div>
    <div id="code-notification" class="code-notification">
        <form method="POST">
            <label><i class="fas fa-key"></i> Enter Your Voter Code to Vote</label>
            <?php echo $code_message; ?>
            <input type="text" name="voter_code" placeholder="e.g., AB12CD34" required>
            <input type="hidden" name="election_id" id="code-election-id">
            <button type="submit"><i class="fas fa-arrow-right"></i> Submit Code</button>
        </form>
    </div>

    <!-- Results Notification -->
    <div id="results-overlay" class="overlay"></div>
    <div id="results-notification" class="notification">
        <h3><i class="fas fa-poll"></i> Choose an Election to View Results</h3>
        <?php
        mysqli_data_seek($result_elections, 0); // Reset pointer for reuse
        if (mysqli_num_rows($result_elections) > 0):
            while ($election = mysqli_fetch_assoc($result_elections)):
                $requires_code = $election['requires_code'];
        ?>
                <div class="election-card" data-election-id="<?php echo $election['election_id']; ?>" data-requires-code="<?php echo $requires_code; ?>">
                    <i class="fas fa-ballot-box"></i> <?php echo htmlspecialchars($election['election_name']); ?>
                </div>
        <?php endwhile; else: ?>
            <p><i class="fas fa-exclamation-circle"></i> No active elections available at this time.</p>
        <?php endif; ?>
        <button id="results-cancel-btn" class="cancel-btn"><i class="fas fa-times"></i> Cancel</button>
    </div>

    <!-- Code Notification for Results -->
    <div id="results-code-overlay" class="overlay"></div>
    <div id="results-code-notification" class="results-code-notification">
        <form method="POST">
            <label><i class="fas fa-key"></i> Enter Your Voter Code to View Results</label>
            <?php echo $results_code_message; ?>
            <input type="text" name="voter_code" placeholder="e.g., AB12CD34" required>
            <input type="hidden" name="election_id" id="results-code-election-id">
            <input type="hidden" name="results" value="1">
            <button type="submit"><i class="fas fa-arrow-right"></i> Submit Code</button>
        </form>
    </div>

    <footer>
        <p><i class="fas fa-copyright"></i> © 2025 Online Voting Platform. All Rights Reserved.</p>
    </footer>

    <script>
        const userImg = document.querySelector('.user_img');
        const fileInput = document.getElementById('user_image');
        const voteNowBtn = document.getElementById('vote-now-btn');
        const viewResultsBtn = document.getElementById('view-results-btn');
        const voteOverlay = document.getElementById('vote-overlay');
        const voteNotification = document.getElementById('vote-notification');
        const voteCancelBtn = document.getElementById('vote-cancel-btn');
        const codeOverlay = document.getElementById('code-overlay');
        const codeNotification = document.getElementById('code-notification');
        const codeElectionId = document.getElementById('code-election-id');
        const resultsOverlay = document.getElementById('results-overlay');
        const resultsNotification = document.getElementById('results-notification');
        const resultsCancelBtn = document.getElementById('results-cancel-btn');
        const resultsCodeOverlay = document.getElementById('results-code-overlay');
        const resultsCodeNotification = document.getElementById('results-code-notification');
        const resultsCodeElectionId = document.getElementById('results-code-election-id');

        userImg.addEventListener('click', () => {
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

        function showVoteNotification() {
            voteOverlay.style.display = 'block';
            voteNotification.style.display = 'block';
            document.body.classList.add('overlay-active');
        }

        function hideVoteNotification() {
            voteOverlay.style.display = 'none';
            voteNotification.style.display = 'none';
            document.body.classList.remove('overlay-active');
        }

        function showCodeNotification(electionId) {
            codeOverlay.style.display = 'block';
            codeNotification.style.display = 'block';
            codeElectionId.value = electionId;
            document.body.classList.add('overlay-active');
        }

        function hideCodeNotification() {
            codeOverlay.style.display = 'none';
            codeNotification.style.display = 'none';
            document.body.classList.remove('overlay-active');
        }

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

        function showResultsCodeNotification(electionId) {
            resultsCodeOverlay.style.display = 'block';
            resultsCodeNotification.style.display = 'block';
            resultsCodeElectionId.value = electionId;
            document.body.classList.add('overlay-active');
        }

        function hideResultsCodeNotification() {
            resultsCodeOverlay.style.display = 'none';
            resultsCodeNotification.style.display = 'none';
            document.body.classList.remove('overlay-active');
        }

        voteNowBtn.addEventListener('click', showVoteNotification);
        voteCancelBtn.addEventListener('click', hideVoteNotification);
        voteOverlay.addEventListener('click', hideVoteNotification);

        document.querySelectorAll('#vote-notification .election-card').forEach(card => {
            card.addEventListener('click', (e) => {
                const electionId = card.getAttribute('data-election-id');
                const requiresCode = card.getAttribute('data-requires-code') === '1';
                
                if (requiresCode) {
                    e.preventDefault();
                    hideVoteNotification();
                    showCodeNotification(electionId);
                } else {
                    window.location.href = `vote.php?election_id=${electionId}`;
                }
            });
        });

        codeOverlay.addEventListener('click', hideCodeNotification);

        viewResultsBtn.addEventListener('click', showResultsNotification);
        resultsCancelBtn.addEventListener('click', hideResultsNotification);
        resultsOverlay.addEventListener('click', hideResultsNotification);

        document.querySelectorAll('#results-notification .election-card').forEach(card => {
            card.addEventListener('click', (e) => {
                const electionId = card.getAttribute('data-election-id');
                const requiresCode = card.getAttribute('data-requires-code') === '1';
                
                if (requiresCode) {
                    e.preventDefault();
                    hideResultsNotification();
                    showResultsCodeNotification(electionId);
                } else {
                    window.location.href = `results.php?election_id=${electionId}`;
                }
            });
        });

        resultsCodeOverlay.addEventListener('click', hideResultsCodeNotification);
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>