<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $positions = trim($_POST['positions']);
    $requires_code = isset($_POST['requires_code']) ? 1 : 0;
    $created_by = $_SESSION['user_id'];

    // Validate user exists
    $user_check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $user_check->bind_param("i", $created_by);
    $user_check->execute();
    $result = $user_check->get_result();

    if ($result->num_rows == 0) {
        $message = "Error: Invalid user ID. User does not exist.";
    } elseif (strtotime($end_date) <= strtotime($start_date)) {
        $message = "Error: End date must be after start date.";
    } elseif (empty($positions)) {
        $message = "Error: Please enter at least one position.";
    } else {
        $stmt = $conn->prepare("INSERT INTO elections (election_name, start_date, end_date, created_by, positions, requires_code) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisi", $title, $start_date, $end_date, $created_by, $positions, $requires_code);

        if ($stmt->execute()) {
            $message = "Election added successfully!";
        } else {
            $message = "Error: " . $conn->error;
        }
        $stmt->close();
    }
    $user_check->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Management</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/election_management.css">
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-vote-yea"></i> Add New Election</h2>
        <?php if (!empty($message)) echo "<div class='alert-info'><i class='fas fa-info-circle'></i> $message</div>"; ?>
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
        </form>
        <a href="admin_dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>