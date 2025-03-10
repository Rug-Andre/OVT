<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET['id'])) {
    $election_id = intval($_GET['id']);
    
    // Prepare the query using mysqli
    $query = "UPDATE elections SET status = 1 WHERE election_id = " . mysqli_real_escape_string($conn, $election_id);
    
    // Execute the query
    if (mysqli_query($conn, $query)) {
        echo "Election $election_id activated successfully.<br>";
        header("Location: election_control.php?message=activated");
        exit();
    } else {
        echo "Error activating election: " . mysqli_error($conn);
    }
} else {
    echo "No election ID provided.";
}

// Close the connection
mysqli_close($conn);
?>