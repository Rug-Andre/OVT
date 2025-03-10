<?php
include 'db.php';

$message = '';
$message_class = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Securely hash the password

    // Check if email already exists
    $check_query = "SELECT * FROM subadmins WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_query);
    if (mysqli_num_rows($check_result) > 0) {
        $message = "Error: Email already exists.";
        $message_class = 'error';
    } else {
        $insert_query = "INSERT INTO subadmins (email, password) VALUES ('$email', '$password')";
        if (mysqli_query($conn, $insert_query)) {
            $message = "Registration successful! You can now log in.";
            $message_class = 'success';
        } else {
            $message = "Registration failed: " . mysqli_error($conn);
            $message_class = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subadmin Registration</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f7fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); width: 300px; }
        h2 { text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { width: 100%; padding: 10px; background-color: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #2980b9; }
        .message { margin-bottom: 15px; padding: 10px; border-radius: 4px; text-align: center; }
        .message.success { background-color: #2ecc71; color: white; }
        .message.error { background-color: #e74c3c; color: white; }
        a { display: block; text-align: center; margin-top: 10px; color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Subadmin Registration</h2>
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_class; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Register</button>
        </form>
        <a href="subadmin_login.php">Already have an account? Login here</a>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>