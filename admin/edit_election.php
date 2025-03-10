<?php
// Include database connection
include 'db.php';
// if (!isset($_SESSION['user_id'])) {
//     header("Location: admin_login.php");
//     exit();
// }

// Ensure 'id' is provided instead of 'election_id'
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No election ID provided.");
}

$election_id = intval($_GET['id']); // Convert to integer for safety

// Escape election_id for safety
$election_id = mysqli_real_escape_string($conn, $election_id);

// Fetch election details
$query = "SELECT * FROM elections WHERE election_id = '$election_id'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching election: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) == 0) {
    die("Error: Election does not exist.");
}

$election = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Election</title>
    <link rel="stylesheet" href="css/edit_elections.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-check-label {
            margin-left: 8px;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Edit Election</h2>

        <!-- HTML form to edit election -->
        <form action="update_election.php" method="POST">
            <input type="hidden" name="election_id" value="<?php echo $election['election_id']; ?>">
            <div class="mb-3">
                <label for="election_name" class="form-label">Election Name:</label>
                <input type="text" class="form-control" name="election_name" id="election_name" value="<?php echo htmlspecialchars($election['election_name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="start_date" class="form-label">Start Date:</label>
                <input type="datetime-local" class="form-control" name="start_date" id="start_date" value="<?php echo date('Y-m-d\TH:i', strtotime($election['start_date'])); ?>" required>
            </div>
            <div class="mb-3">
                <label for="end_date" class="form-label">End Date:</label>
                <input type="datetime-local" class="form-control" name="end_date" id="end_date" value="<?php echo date('Y-m-d\TH:i', strtotime($election['end_date'])); ?>" required>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="requires_code" id="requires_code" value="1" <?php echo $election['requires_code'] ? 'checked' : ''; ?>>
                <label for="requires_code" class="form-check-label">Requires Voter Code</label>
            </div>
            <button type="submit" class="btn btn-primary">Update Election</button>
        </form>

        <a href="election_control.php" class="btn btn-secondary mt-3">Back</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>