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

// Check if id is set and is a valid integer
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_candidates.php?error=Invalid candidate ID.");
    exit();
}

$candidate_id = intval($_GET['id']);

// Escape candidate_id for safety
$candidate_id = mysqli_real_escape_string($conn, $candidate_id);

// Fetch the candidate details
$sql = "SELECT * FROM candidates WHERE candidate_id = '$candidate_id'";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error fetching candidate: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) === 0) {
    header("Location: manage_candidates.php?error=Candidate not found.");
    exit();
}

$candidate = mysqli_fetch_assoc($result);

$success = $error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['candidate_name'])) {
    $candidate_name = trim($_POST['candidate_name']);
    $position = trim($_POST['position']);
    $election_id = isset($_POST['election_id']) && $_POST['election_id'] !== "" ? intval($_POST['election_id']) : 0;
    $candidate_info = trim($_POST['candidate_info']);
    
    // Handle image upload
    $image_path = $candidate['candidate_image']; // Keep existing image if no new upload
    if (isset($_FILES['candidate_image']) && $_FILES['candidate_image']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["candidate_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is an actual image
        $check = getimagesize($_FILES["candidate_image"]["tmp_name"]);
        if ($check !== false) {
            // Move the uploaded file
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
        // Escape inputs for safety
        $candidate_name = mysqli_real_escape_string($conn, $candidate_name);
        $position = mysqli_real_escape_string($conn, $position);
        $election_id = mysqli_real_escape_string($conn, $election_id);
        $candidate_info = mysqli_real_escape_string($conn, $candidate_info);
        $image_path = mysqli_real_escape_string($conn, $image_path);

        $sql = "UPDATE candidates SET election_id = '$election_id', candidate_name = '$candidate_name', position = '$position', candidate_info = '$candidate_info', candidate_image = '$image_path' WHERE candidate_id = '$candidate_id'";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Candidate updated successfully!";
        } else {
            $error = "Error updating candidate: " . mysqli_error($conn);
        }
    } else {
        $error = "Please fill in all fields and select an election.";
    }
}

// Fetch elections for dropdown
$electionQuery = "SELECT election_id, election_name, start_date, end_date FROM elections";
$electionResult = mysqli_query($conn, $electionQuery);

if (!$electionResult) {
    die("Error fetching elections: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Candidate</title>
    <link rel="stylesheet" href="css/edit_candidates.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Edit Candidate</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php elseif (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST" id="candidateForm" enctype="multipart/form-data">
                    <div>
                        <label class="form-label">Candidate Name</label>
                        <input type="text" name="candidate_name" class="form-control" value="<?php echo htmlspecialchars($candidate['candidate_name']); ?>" required>
                    </div>

                    <div>
                        <label class="form-label">Select Election</label>
                        <select name="election_id" class="form-control" id="electionSelect" required>
                            <option value="" disabled>-- Choose Election --</option>
                            <?php while ($row = mysqli_fetch_assoc($electionResult)): ?>
                                <option value="<?php echo $row['election_id']; ?>" <?php echo ($row['election_id'] == $candidate['election_id']) ? 'selected' : ''; ?>>
                                    <?php echo $row['election_name'] . " (" . $row['start_date'] . " to " . $row['end_date'] . ")"; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Select Position</label>
                        <select name="position" class="form-control" id="positionSelect" required>
                            <option value="<?php echo htmlspecialchars($candidate['position']); ?>" selected><?php echo htmlspecialchars($candidate['position']); ?></option>
                            <!-- Positions will be populated dynamically if needed -->
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Candidate Information</label>
                        <textarea name="candidate_info" class="form-control" rows="4" required><?php echo htmlspecialchars($candidate['candidate_info']); ?></textarea>
                    </div>

                    <div>
                        <label class="form-label">Candidate Image</label>
                        <input type="file" name="candidate_image" class="form-control" accept="image/*">
                        <small>Current image: <?php echo htmlspecialchars($candidate['candidate_image']); ?> (Leave blank to keep current image)</small>
                    </div>

                    <button type="submit" class="btn btn-success">Update Candidate</button>
                </form>

                <a href="manage_candidates.php" class="btn btn-secondary">Back to Candidates</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('electionSelect').addEventListener('change', function () {
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

        // Pre-select the current position
        window.onload = function() {
            var currentPosition = "<?php echo htmlspecialchars($candidate['position']); ?>";
            document.getElementById('positionSelect').value = currentPosition;
        };
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>