<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch all elections created by the logged-in admin
$elections_query = "SELECT election_id, election_name FROM elections WHERE created_by = '{$_SESSION['user_id']}' ORDER BY election_name";
$elections_result = mysqli_query($conn, $elections_query);

if (!$elections_result) {
    die("Error fetching elections: " . mysqli_error($conn));
}

// Handle election selection
$selected_election_id = isset($_GET['election_id']) ? intval($_GET['election_id']) : null;
$codes = [];
if ($selected_election_id) {
    $selected_election_id = mysqli_real_escape_string($conn, $selected_election_id);
    $codes_query = "SELECT voter_code, is_used, created_at 
                    FROM voter_codes 
                    WHERE election_id = '$selected_election_id' 
                    ORDER BY created_at DESC";
    $codes_result = mysqli_query($conn, $codes_query);

    if ($codes_result) {
        while ($row = mysqli_fetch_assoc($codes_result)) {
            $codes[] = $row;
        }
    } else {
        $message = "Error fetching voter codes: " . mysqli_error($conn);
    }
}

// Handle deletion of all codes
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_all_codes']) && $selected_election_id) {
    $delete_query = "DELETE FROM voter_codes WHERE election_id = '$selected_election_id'";
    if (mysqli_query($conn, $delete_query)) {
        $message = "All voter codes for this election have been deleted successfully!";
        $codes = []; // Clear the codes array after deletion
    } else {
        $message = "Error deleting voter codes: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Voter Codes</title>
    <link rel="stylesheet" href="css/view_voter_codes.css">
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-container {
            margin-top: 20px;
        }
        .status-used {
            color: red;
        }
        .status-unused {
            color: green;
        }
        .btn-danger {
            background-color: #e74c3c;
            color: #fff;
            margin-left: 10px;
        }
        .btn-danger:hover {
            background-color: #c0392b;
        }
        .btn-secondary {
            background-color: #7f8c8d;
            color: #fff;
            margin-top: 20px;
        }
        .btn-secondary:hover {
            background-color: #6c757d;
        }
        .btn i {
            margin-right: 8px;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 15px;
            }
            th, td {
                font-size: 14px;
                padding: 10px;
            }
            .btn {
                width: 100%;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-key"></i> View Voter Codes</h2>
        <?php if (isset($message)) echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> $message</div>"; ?>

        <!-- Election Selection Dropdown -->
        <form method="GET" class="mb-4">
            <div class="form-group">
                <label for="election_id" class="form-label">Select Election</label>
                <select name="election_id" id="election_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Select an Election --</option>
                    <?php while ($election = mysqli_fetch_assoc($elections_result)): ?>
                        <option value="<?php echo $election['election_id']; ?>" 
                                <?php echo $selected_election_id == $election['election_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($election['election_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>

        <!-- Voter Codes Table -->
        <?php if ($selected_election_id && !empty($codes)): ?>
            <div class="table-container">
                <h4>Voter Codes for Selected Election</h4>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Voter Code</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($codes as $code): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($code['voter_code']); ?></td>
                                <td class="<?php echo $code['is_used'] ? 'status-used' : 'status-unused'; ?>">
                                    <?php echo $code['is_used'] ? 'Used' : 'Unused'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($code['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($selected_election_id): ?>
            <div class="alert alert-info">No voter codes have been generated for this election yet.</div>
        <?php endif; ?>

        <a href="admin_dashboard.php" class="btn btn-secondary mt-3">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    
        <?php if ($selected_election_id): ?>
        <div class="btn btn-zero mt-3">   <form method="POST" onsubmit="return confirm('Are you sure you want to delete all voter codes for this election?');">
                        <input type="hidden" name="delete_all_codes" value="1">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete All Codes
                        </button>
                    </form></div></a>
                    <?php endif; ?>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>