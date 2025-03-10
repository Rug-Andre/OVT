<?php
include 'db.php';
session_start();

if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    $query = "SELECT user_id, expires FROM password_resets WHERE token = '$token'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if (strtotime($row['expires']) > time()) {
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $user_id = $row['user_id'];
                $update_query = "UPDATE admin SET password = '$new_password' WHERE user_id = '$user_id'";
                if (mysqli_query($conn, $update_query)) {
                    mysqli_query($conn, "DELETE FROM password_resets WHERE token = '$token'");
                    $message = "Password updated successfully. <a href='admin_login.php'>Login</a>";
                } else {
                    $error = "Error updating password: " . mysqli_error($conn);
                }
            }
        } else {
            $error = "This reset link has expired.";
        }
    } else {
        $error = "Invalid reset token.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="css/admin_login.css">
</head>
<body>
    <div class="container">
        <div class="form login">
            <?php if (isset($message)): ?>
                <p><?php echo $message; ?></p>
            <?php elseif (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php else: ?>
                <form method="POST">
                    <h2>Reset Password</h2>
                    <label for="new_password">New Password</label>
                    <input type="password" name="new_password" id="new_password" required>
                    <button type="submit" class="submit">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>