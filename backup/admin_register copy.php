<?php
include 'db.php';

// Enable error reporting for debugging (optional, remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Escape inputs to prevent SQL injection
    $username = mysqli_real_escape_string($conn, $username);
    $email = mysqli_real_escape_string($conn, $email);
    $hashed_password = mysqli_real_escape_string($conn, $hashed_password);

    // Check if email already exists in the admin table
    $check_query = "SELECT email FROM admin WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_query);
    if ($check_result === false) {
        die("Error executing email check query: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($check_result) > 0) {
        // Email already exists
        $message = "Email already exists! <a href='admin_login.php'>Login here</a>";
        $alert_class = "alert-error";
    } else {
        // Insert with role set to "Admin"
        $insert_query = "INSERT INTO admin (username, email, password, role) VALUES ('$username', '$email', '$hashed_password', 'Admin')";
        if (mysqli_query($conn, $insert_query)) {
            $message = "Registration successful! <a href='admin_login.php'>Login here</a>";
            $alert_class = "alert-success";
        } else {
            $message = "Error: " . mysqli_error($conn);
            $alert_class = "alert-error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register</title>
    <link rel="stylesheet" href="css/admin_register.css">
</head>
<body>
    <div class="container">
        <div class="form">
            <h2>ADMIN REGISTER</h2>
            <?php if (isset($message)): ?>
                <div class="alert <?php echo $alert_class; ?>"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="POST">
                <label>Username</label>
                <input type="text" name="username" required>
                <label>Email</label>
                <input type="email" name="email" required>
                <label>Password</label>
                <input type="password" name="password" required>
                <div class="checkbox-group">
                    <input type="checkbox" name="terms" id="terms" required>
                    <label for="terms" class="checklabel">I agree to terms and objectives of <span>OVT</span>!</label>
                </div>
                <button type="submit" class="submit">Register</button>
            </form>
            <p class="login-link"><a href="admin_login.php">Already have an account? Login</a></p>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>