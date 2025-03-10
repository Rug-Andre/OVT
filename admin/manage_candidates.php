<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch only active elections that are ongoing
$sql_elections = "SELECT * FROM elections WHERE status = 1 AND NOW() BETWEEN start_date AND end_date";
$result_elections = mysqli_query($conn, $sql_elections);

if (!$result_elections) {
    die("Error fetching elections: " . mysqli_error($conn));
}

$success = $error = "";

// Handle delete request
if (isset($_GET['delete'])) {
    $candidate_id = intval($_GET['delete']);
    $candidate_id = mysqli_real_escape_string($conn, $candidate_id);
    $delete_sql = "DELETE FROM candidates WHERE candidate_id = '$candidate_id'";
    
    if (mysqli_query($conn, $delete_sql)) {
        $success = "Candidate deleted successfully.";
    } else {
        $error = "Error deleting candidate: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates</title>
    <link rel="stylesheet" href="css/manage_candidates.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Manage Candidates</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="alert-success"> <?php echo $success; ?> </div>
                <?php elseif (!empty($error)): ?>
                    <div class="alert-danger"> <?php echo $error; ?> </div>
                <?php endif; ?>

                <?php if (mysqli_num_rows($result_elections) == 0): ?>
                    <div class="alert-danger">No ongoing elections at the moment.</div>
                <?php else: ?>
                    <?php while ($election = mysqli_fetch_assoc($result_elections)): ?>
                        <div class="election-section">
                            <h3 class="text-success"> <?php echo htmlspecialchars($election['election_name']); ?> </h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Image</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $election_id = $election['election_id'];
                                    $election_id = mysqli_real_escape_string($conn, $election_id);
                                    $sql_candidates = "SELECT * FROM candidates WHERE election_id = '$election_id'";
                                    $result_candidates = mysqli_query($conn, $sql_candidates);
                                    
                                    if (!$result_candidates) {
                                        die("Error fetching candidates: " . mysqli_error($conn));
                                    }
                                    
                                    while ($candidate = mysqli_fetch_assoc($result_candidates)): ?>
                                        <tr>
                                            <td><?php echo $candidate['candidate_id']; ?></td>
                                            <td>
                                                <img src="<?php echo htmlspecialchars($candidate['candidate_image']); ?>" alt="<?php echo htmlspecialchars($candidate['candidate_name']); ?>" class="candidate-image">
                                                <?php echo htmlspecialchars($candidate['candidate_name']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($candidate['position']); ?></td>
                                            <td></td> <!-- Empty cell to align with table structure -->
                                            <td>
                                                <a href="edit_candidates.php?id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                                <a href="manage_candidates.php?delete=<?php echo $candidate['candidate_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this candidate?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
                <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>