<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $election_id = intval($_POST['election_id']);
    $election_name = mysqli_real_escape_string($conn, $_POST['election_name']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    $requires_code = isset($_POST['requires_code']) ? 1 : 0; // Checkbox: 1 if checked, 0 if not

    if (strtotime($end_date) <= strtotime($start_date)) {
        $message = "Error: End date must be after start date.";
    } else {
        $query = "UPDATE elections SET 
                  election_name = '$election_name', 
                  start_date = '$start_date', 
                  end_date = '$end_date', 
                  requires_code = '$requires_code' 
                  WHERE election_id = '$election_id'";
        
        if (mysqli_query($conn, $query)) {
            header("Location: election_control.php?message=Election updated successfully");
            exit();
        } else {
            $message = "Error updating election: " . mysqli_error($conn);
        }
    }
    // If there's an error, redirect back to edit page with message
    header("Location: edit_election.php?id=" . urlencode($election_id) . "&message=" . urlencode($message));
    exit();
}

mysqli_close($conn);
?>