<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$success = $error = "";
$positions = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['candidate_name'])) {
    $candidate_name = trim($_POST['candidate_name']);
    $position = trim($_POST['position']);
    $election_id = isset($_POST['election_id']) && $_POST['election_id'] !== "" ? intval($_POST['election_id']) : 0;
    $candidate_info = trim($_POST['candidate_info']);
    
    // Handle image upload
    $image_path = "";
    if (isset($_FILES['candidate_image']) && $_FILES['candidate_image']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["candidate_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $check = getimagesize($_FILES["candidate_image"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["candidate_image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            } else {
                $error = "Sorry, there was an error uploading your image.";
            }
        } else {
            $error = "File is not an image.";
        }
    }

    if (!empty($candidate_name) && !empty($position) && $election_id > 0) {
        // Prepare and escape the query using mysqli
        $candidate_name = mysqli_real_escape_string($conn, $candidate_name);
        $position = mysqli_real_escape_string($conn, $position);
        $candidate_info = mysqli_real_escape_string($conn, $candidate_info);
        $image_path = mysqli_real_escape_string($conn, $image_path);

        $sql = "INSERT INTO candidates (election_id, candidate_name, position, candidate_info, candidate_image) 
                VALUES ($election_id, '$candidate_name', '$position', '$candidate_info', '$image_path')";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Candidate added successfully!";
        } else {
            $error = "Error adding candidate: " . mysqli_error($conn);
        }
    } else {
        $error = "Please fill in all fields and select an election.";
    }
}

// Fetch all active elections (status = 1), regardless of date
$electionQuery = "SELECT election_id, election_name, start_date, end_date FROM elections WHERE status = 1";
$electionResult = mysqli_query($conn, $electionQuery);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Candidate</title>
    <link rel="stylesheet" href="css/add_candidates.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Add Candidate</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php elseif (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (mysqli_num_rows($electionResult) == 0): ?>
                    <div class="alert alert-danger">No active elections available to add candidates.</div>
                <?php else: ?>
                    <form action="" method="POST" id="candidateForm" enctype="multipart/form-data">
                        <div>
                            <label class="form-label">Candidate Name</label>
                            <input type="text" name="candidate_name" class="form-control" required>
                        </div>

                        <div>
                            <label class="form-label">Select Election</label>
                            <select name="election_id" class="form-control" id="electionSelect" required>
                                <option value="" disabled selected>-- Choose Election --</option>
                                <?php 
                                while ($row = mysqli_fetch_assoc($electionResult)): ?>
                                    <option value="<?php echo $row['election_id']; ?>">
                                        <?php echo htmlspecialchars($row['election_name']) . " (" . $row['start_date'] . " to " . $row['end_date'] . ")"; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Select Position</label>
                            <select name="position" class="form-control" id="positionSelect" required>
                                <option value="">-- Choose Position --</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Candidate Information</label>
                            <textarea name="candidate_info" class="form-control" rows="4" required></textarea>
                        </div>

                        <div>
                            <label class="form-label">Candidate Image</label>
                            <input type="file" name="candidate_image" class="form-control" accept="image/*" required>
                        </div>

                        <button type="submit" class="btn btn-success">Add Candidate</button>
                    </form>
                <?php endif; ?>

                <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script>
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