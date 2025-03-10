<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Update the SQL query to match the table structure
    $email = mysqli_real_escape_string($conn, $email);
    $query = "SELECT user_id, username, password FROM admin WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Error executing query: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $id = $row['user_id'];
        $username = $row['username'];
        $hashed_password = $row['password'];
        
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;  // Set username
            // Remove the role assignment if it's not in the database
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid credentials.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/admin_login.css">
</head>
<body>
    <div class="container">
        <div class="form login" id="form">
            <form method="POST">
                <h2>ADMIN LOGIN</h2>
                <label for="email">Enter Email</label>
                <input type="email" name="email" required id="email">
                <label for="password">Enter Password</label>
                <input type="password" name="password" required id="password">
                <button type="submit" class="submit">Login</button>
                <?php if (isset($error)): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                 
            </form>
            <p class="login-link"><a href="admin_register.php">You don't have an account? Register</a></p>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>