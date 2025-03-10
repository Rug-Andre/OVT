<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET['id'])) {
    $election_id = intval($_GET['id']);
    
    // Prepare the query with mysqli, escaping the input
    $election_id = mysqli_real_escape_string($conn, $election_id);
    $query = "UPDATE elections SET status = 0 WHERE election_id = '$election_id'";
    
    // Execute the query
    if (mysqli_query($conn, $query)) {
        echo "Election $election_id deactivated successfully.<br>";
        header("Location: election_control.php?message=deactivated");
        exit();
    } else {
        echo "Error deactivating election: " . mysqli_error($conn);
    }
} else {
    echo "No election ID provided.";
}

// Close the connection
mysqli_close($conn);
?>