<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db.php';
$user_id = $_SESSION['user_id'];

// Set timezone to ensure date displays correctly
date_default_timezone_set('Africa/Nairobi'); // Adjust to your timezone

// Fetch voting history including candidate image
$user_id = mysqli_real_escape_string($conn, $user_id);
$sql = "SELECT elections.election_name AS title, candidates.candidate_name AS name, candidates.candidate_image AS image, votes.vote_timestamp AS vote_date, candidates.candidate_info AS info 
        FROM votes 
        JOIN candidates ON votes.candidate_id = candidates.candidate_id 
        JOIN elections ON votes.election_id = elections.election_id 
        WHERE votes.user_id = '$user_id'";

$result = mysqli_query($conn, $sql);

if (!$result) {
    $error_message = "Error fetching voting history: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting History</title>
    <!-- Uncomment if you want to use Bootstrap -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link rel="stylesheet" href="css/voting_history.css">
    <style>
        .candidate-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .error-message {
            color: red;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Your Voting History</h2>
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Election</th>
                        <th>Image</th>
                        <th>Candidate</th>
                        <th>Info</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td>
                                    <?php if ($row['image']): ?>
                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="candidate-image">
                                    <?php else: ?>
                                        <span>No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['info']); ?></td>
                                <td class="vote-date"><?php echo htmlspecialchars(date('F j, Y, g:i A', strtotime($row['vote_date']))); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center no-records">No voting history found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <a href="admin_dashboard.php" class="btn btn-secondary">Back</a>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>