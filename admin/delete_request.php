<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET['id'])) {
    $request_id = $_GET['id'];

    // Fetch the request to get subadmin_id
    $fetch_query = "SELECT subadmin_id FROM requests WHERE request_id = '$request_id'";
    $fetch_result = mysqli_query($conn, $fetch_query);
    if (!$fetch_result || mysqli_num_rows($fetch_result) == 0) {
        die("Request not found: " . mysqli_error($conn));
    }
    $row = mysqli_fetch_assoc($fetch_result);
    $subadmin_id = $row['subadmin_id'];

    // Delete the request
    $delete_query = "DELETE FROM requests WHERE request_id = '$request_id'";
    if (mysqli_query($conn, $delete_query)) {
        // Insert notification for subadmin
        $title = "Election Request Deleted";
        $message = "Your election request (ID: $request_id) has been deleted by an admin.";
        $notification_query = "INSERT INTO notifications (user_id, title, message, status) 
                               VALUES ('$subadmin_id', '$title', '$message', 'unread')";
        if (!mysqli_query($conn, $notification_query)) {
            echo "Error inserting notification: " . mysqli_error($conn);
        }
        header("Location: view_requested_elections.php?message=Request%20deleted%20successfully");
    } else {
        echo "Error deleting request: " . mysqli_error($conn);
    }
} else {
    header("Location: view_requested_elections.php?message=Invalid%20request%20ID");
}

mysqli_close($conn);
?>