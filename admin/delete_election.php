<?php
// Include database connection
include 'db.php';

// Debugging: Print GET request
var_dump($_GET);

// Ensure 'id' is provided instead of 'election_id'
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No election ID provided.");
}

$election_id = intval($_GET['id']); // Convert to integer for safety

// Escape the election_id for safety
$election_id = mysqli_real_escape_string($conn, $election_id);

// Check if election exists
$check_query = "SELECT * FROM elections WHERE election_id = '$election_id'";
$check_result = mysqli_query($conn, $check_query);

if (!$check_result) {
    die("Error checking election: " . mysqli_error($conn));
}

if (mysqli_num_rows($check_result) == 0) {
    die("Error: Election does not exist.");
}

// Delete votes related to this election
$delete_votes_query = "DELETE FROM votes WHERE election_id = '$election_id'";
mysqli_query($conn, $delete_votes_query);

// Delete candidates related to this election
$delete_candidates_query = "DELETE FROM candidates WHERE election_id = '$election_id'";
mysqli_query($conn, $delete_candidates_query);

// Delete the election itself
$delete_election_query = "DELETE FROM elections WHERE election_id = '$election_id'";
if (mysqli_query($conn, $delete_election_query)) {
    header("Location: election_control.php");
} else {
    echo "Error deleting election: " . mysqli_error($conn);
}

// Close the connection
mysqli_close($conn);
?>