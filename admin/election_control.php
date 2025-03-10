<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}
// Handle adding a new election
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_election'])) {
    $title = $_POST['title'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // Escape inputs for safety
    $title = mysqli_real_escape_string($conn, $title);
    $start_date = mysqli_real_escape_string($conn, $start_date);
    $end_date = mysqli_real_escape_string($conn, $end_date);
    $created_by = mysqli_real_escape_string($conn, $created_by);

    $query = "INSERT INTO elections (election_name, start_date, end_date, created_by, status) VALUES ('$title', '$start_date', '$end_date', '$created_by', 0)";
    
    if (mysqli_query($conn, $query)) {
        $message = "Election added successfully!";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}

// Fetch elections
$elections_query = "SELECT election_id, election_name, start_date, end_date, created_by, status FROM elections";
$elections_result = mysqli_query($conn, $elections_query);

if (!$elections_result) {
    die("Error fetching elections: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Management</title>
    <!-- <link rel="stylesheet" href="css/manage_candidates.css"> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mt-5">Manage Elections</h2>
        <?php if (isset($message)): ?>
            <div class="alert <?php echo strpos($message, 'Error') === false ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Created By</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($elections_result) > 0): ?>
                    <?php while ($election = mysqli_fetch_assoc($elections_result)): ?>
                        <tr>
                            <td><?php echo $election['election_id']; ?></td>
                            <td><?php echo $election['election_name']; ?></td>
                            <td><?php echo $election['start_date']; ?></td>
                            <td><?php echo $election['end_date']; ?></td>
                            <td><?php echo $election['created_by']; ?></td>
                            <td>
                                <?php if ($election['status'] == 1): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_election.php?id=<?php echo $election['election_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_election.php?id=<?php echo $election['election_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this election?');">Delete</a>
                                <?php if ($election['status'] == 0): ?>
                                    <a href="activate_election.php?id=<?php echo $election['election_id']; ?>" class="btn btn-info btn-sm">Activate</a>
                                <?php else: ?>
                                    <a href="deactivate_election.php?id=<?php echo $election['election_id']; ?>" class="btn btn-success btn-sm" style="width: auto;">Deactivate</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No elections found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Back</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>