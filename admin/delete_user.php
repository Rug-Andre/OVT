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

// Prevent deleting the current logged-in user (optional, for safety)
if ($user_id === $_SESSION['user_id']) {
    header("Location: view_users.php?error=cannot_delete_self");
    exit();
}

// Escape the user_id for safety
$user_id = mysqli_real_escape_string($conn, $user_id);

// Delete the user
$query = "DELETE FROM users WHERE user_id = '$user_id'";
if (mysqli_query($conn, $query)) {
    $message = "User deleted successfully!";
    $message_type = "success";
} else {
    $message = "Error deleting user: " . mysqli_error($conn);
    $message_type = "error";
}

// Close the connection
mysqli_close($conn);

// Redirect back to view_users.php with a message
header("Location: view_users.php?message=" . urlencode($message) . "&message_type=" . urlencode($message_type));
exit();
?>

