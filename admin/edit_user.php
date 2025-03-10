<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if the user is an admin (optional, depending on your role logic)
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

// Check if user_id is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_users.php?error=invalid_id");
    exit();
}

$user_id = (int)$_GET['id'];

// Escape user_id for safety
$user_id = mysqli_real_escape_string($conn, $user_id);

// Fetch the user’s current details
$query = "SELECT user_id, username, email, role FROM users WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching user: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) !== 1) {
    header("Location: view_users.php?error=user_not_found");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Handle form submission to update the user
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);

    // Basic validation
    if (empty($username) || empty($email) || empty($role)) {
        $message = "All fields are required!";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format!";
        $message_type = "error";
    } else {
        // Escape inputs for safety
        $email = mysqli_real_escape_string($conn, $email);
        $username = mysqli_real_escape_string($conn, $username);
        $role = mysqli_real_escape_string($conn, $role);

        // Check if the email is already in use by another user
        $email_check_query = "SELECT user_id FROM users WHERE email = '$email' AND user_id != '$user_id'";
        $email_check_result = mysqli_query($conn, $email_check_query);

        if (!$email_check_result) {
            $message = "Error checking email: " . mysqli_error($conn);
            $message_type = "error";
        } elseif (mysqli_num_rows($email_check_result) > 0) {
            $message = "Email is already in use!";
            $message_type = "error";
        } else {
            // Update the user
            $update_query = "UPDATE users SET username = '$username', email = '$email', role = '$role' WHERE user_id = '$user_id'";
            if (mysqli_query($conn, $update_query)) {
                $message = "User updated successfully!";
                $message_type = "success";
                // Redirect after a successful update
                header("Location: view_users.php?message=" . urlencode($message) . "&message_type=" . urlencode($message_type));
                exit();
            } else {
                $message = "Error updating user: " . mysqli_error($conn);
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Online Voting Platform</title>
    <link rel="stylesheet" href="css/edit_user.css">
</head>
<body>
    <div class="main-container">
        <h1>Edit User</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="edit-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-submit">Update User</button>
            <a href="view_users.php" class="btn btn-cancel">Cancel</a>
        </form>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>