<?php
session_start();
include 'db.php';

$message = '';
$message_class = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT * FROM subadmins WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        $subadmin = mysqli_fetch_assoc($result);
        if (password_verify($password, $subadmin['password'])) {
            $_SESSION['subadmin_id'] = $subadmin['id'];
            $_SESSION['subadmin_email'] = $subadmin['email'];
            header("Location: subadmin_dashboard.php");
            exit();
        } else {
            $message = "Error: Incorrect password.";
            $message_class = 'error';
        }
    } else {
        $message = "Error: Email not found.";
        $message_class = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subadmin Login</title>
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
        <h2>Subadmin Login</h2>
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
            <button type="submit">Login</button>
        </form>
        <a href="subadmin_register.php">Don't have an account? Register here</a>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>