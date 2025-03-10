<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $candidate_id = isset($_POST['candidate_id']) ? intval($_POST['candidate_id']) : 0;
    $candidate_name = trim($_POST['candidate_name']);
    $position = trim($_POST['position']);
    $candidate_info = trim($_POST['candidate_info']);

    // Handle image upload
    $image_path = null;
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

    if ($candidate_id > 0 && !empty($candidate_name) && !empty($position) && !empty($candidate_info)) {
        // Escape inputs for safety
        $candidate_id = mysqli_real_escape_string($conn, $candidate_id);
        $candidate_name = mysqli_real_escape_string($conn, $candidate_name);
        $position = mysqli_real_escape_string($conn, $position);
        $candidate_info = mysqli_real_escape_string($conn, $candidate_info);
        $image_path = $image_path ? mysqli_real_escape_string($conn, $image_path) : null;

        // Construct the SQL query dynamically based on whether an image is uploaded
        $sql = "UPDATE candidates SET candidate_name = '$candidate_name', position = '$position', candidate_info = '$candidate_info'";
        if ($image_path) {
            $sql .= ", candidate_image = '$image_path'";
        }
        $sql .= " WHERE candidate_id = '$candidate_id'";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Candidate updated successfully!";
        } else {
            $error = "Error updating candidate: " . mysqli_error($conn);
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Candidate</title>
</head>
<body>
    <div>
        <?php if (!empty($success)): ?>
            <div><?php echo $success; ?></div>
        <?php elseif (!empty($error)): ?>
            <div><?php echo $error; ?></div>
        <?php endif; ?>
        <a href="admin_dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>