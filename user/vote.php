<?php 
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

if (isset($_SESSION['vote_message'])) {
    $message = $_SESSION['vote_message'];
    unset($_SESSION['vote_message']);
}

if (!isset($_GET['election_id']) || !is_numeric($_GET['election_id'])) {
    $message = "<div class='alert alert-warning'>Please select an election from the dashboard.</div>";
    $sql = "SELECT * FROM elections WHERE status = 1 AND NOW() BETWEEN start_date AND end_date LIMIT 0";
} else {
    $election_id = intval($_GET['election_id']);
    $sql = "SELECT * FROM elections WHERE election_id = ? AND status = 1 AND NOW() BETWEEN start_date AND end_date";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $election_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $message = "<div class='alert alert-warning'>The selected election is not available or not ongoing.</div>";
    }
}

// Handle Voting Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['candidate_id'], $_POST['election_id'], $_POST['position'])) {
    $candidate_id = intval($_POST['candidate_id']);
    $election_id = intval($_POST['election_id']);
    $position = $_POST['position'];

    $user_check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $user_check->bind_param("i", $user_id);
    $user_check->execute();
    $user_result = $user_check->get_result();

    if ($user_result->num_rows == 0) {
        $message = "<div class='alert alert-danger'>Error: Your account is not authorized to vote. Contact support.</div>";
    } else {
        $check_stmt = $conn->prepare("SELECT * FROM votes WHERE user_id = ? AND election_id = ? AND position = ?");
        $check_stmt->bind_param("iis", $user_id, $election_id, $position);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = "<div class='alert alert-danger'>You have already voted for the position of " . htmlspecialchars($position) . " in this election!</div>";
        } else {
            $vote_stmt = $conn->prepare("INSERT INTO votes (user_id, election_id, candidate_id, position, vote_timestamp) VALUES (?, ?, ?, ?, NOW())");
            $vote_stmt->bind_param("iiis", $user_id, $election_id, $candidate_id, $position);
            if ($vote_stmt->execute()) {
                $message = "<div class='alert alert-success'>Your vote for " . htmlspecialchars($position) . " has been recorded successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger'>Error casting your vote: " . $conn->error . "</div>";
            }
            $vote_stmt->close();
        }
        $check_stmt->close();
    }
    $user_check->close();

    $_SESSION['vote_message'] = $message;
    header("Location: vote.php?election_id=" . urlencode($election_id));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Now</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/vote.css">
</head>
<body>
    <div class="container">
        <h2 class="text-center text-primary"><i class="fas fa-vote-yea"></i> Vote for Your Candidate</h2>
        <?php echo $message; ?>
        
        <?php if (isset($result) && $row = $result->fetch_assoc()): ?>
            <div class="election-section">
                <h3 class="text-success"><i class="fas fa-ballot-box"></i> <?php echo htmlspecialchars($row['election_name']); ?></h3>
                <?php
                $election_id = $row['election_id'];
                $position_stmt = $conn->prepare("SELECT DISTINCT position FROM candidates WHERE election_id = ?");
                $position_stmt->bind_param("i", $election_id);
                $position_stmt->execute();
                $positions = $position_stmt->get_result();
                ?>

                <?php while ($position_row = $positions->fetch_assoc()): ?>
                    <div class="position-section mb-4">
                        <h4 class="text-info"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($position_row['position']); ?></h4>
                        <div class="row">
                            <?php
                            $candidate_stmt = $conn->prepare("SELECT candidate_id, candidate_name, position, candidate_info, candidate_image 
                                                            FROM candidates 
                                                            WHERE election_id = ? AND position = ?");
                            $candidate_stmt->bind_param("is", $election_id, $position_row['position']);
                            $candidate_stmt->execute();
                            $candidates = $candidate_stmt->get_result();
                            ?>

                            <?php while ($candidate = $candidates->fetch_assoc()): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="candidate-card p-3">
                                        <?php if (!empty($candidate['candidate_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($candidate['candidate_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($candidate['candidate_name']); ?>" 
                                                 class="candidate-image">
                                        <?php else: ?>
                                            <img src="uploads/default.jpg" 
                                                 alt="Default Image" 
                                                 class="candidate-image">
                                        <?php endif; ?>
                                        <h5 class="mt-2"><?php echo htmlspecialchars($candidate['candidate_name']); ?></h5>
                                        <p class="text-muted">Position: <?php echo htmlspecialchars($candidate['position']); ?></p>
                                        <p><?php echo htmlspecialchars($candidate['candidate_info']); ?></p>
                                        <form method="POST">
                                            <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                            <input type="hidden" name="election_id" value="<?php echo $election_id; ?>">
                                            <input type="hidden" name="position" value="<?php echo $candidate['position']; ?>">
                                            <button type="submit" class="vote-button"><i class="fas fa-check"></i> Vote</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            <?php $candidate_stmt->close(); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
                <?php $position_stmt->close(); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($stmt)) $stmt->close(); ?>
        <a href="user_dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>