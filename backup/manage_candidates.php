<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch elections and their candidates
$sql_elections = "SELECT * FROM elections";
$result_elections = $conn->query($sql_elections);

$success = $error = "";

// Handle delete request
if (isset($_GET['delete'])) {
    $candidate_id = intval($_GET['delete']);
    $delete_sql = "DELETE FROM candidates WHERE candidate_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $candidate_id);
    
    if ($stmt->execute()) {
        $success = "Candidate deleted successfully.";
    } else {
        $error = "Error deleting candidate.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white text-center">
                <h2>Manage Candidates</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"> <?php echo $success; ?> </div>
                <?php elseif (!empty($error)): ?>
                    <div class="alert alert-danger"> <?php echo $error; ?> </div>
                <?php endif; ?>

                <?php while ($election = $result_elections->fetch_assoc()): ?>
                    <div class="mb-4 p-3 border rounded bg-white">
                        <h3 class="text-success"> <?php echo htmlspecialchars($election['election_name']); ?> </h3>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $election_id = $election['election_id'];
                                $sql_candidates = "SELECT * FROM candidates WHERE election_id = ?";
                                $stmt = $conn->prepare($sql_candidates);
                                $stmt->bind_param("i", $election_id);
                                $stmt->execute();
                                $result_candidates = $stmt->get_result();
                                
                                while ($candidate = $result_candidates->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $candidate['candidate_id']; ?></td>
                                        <td><?php echo htmlspecialchars($candidate['candidate_name']); ?></td>
                                        <td><?php echo htmlspecialchars($candidate['position']); ?></td>
                                        <td>
                                            <a href="edit_candidate.php?id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                            <a href="manage_candidates.php?delete=<?php echo $candidate['candidate_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this candidate?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endwhile; ?>
                <a href="admin_dashboard.php" class="btn btn-secondary w-100">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
