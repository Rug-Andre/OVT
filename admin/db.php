<?php
$host = "localhost";
$user = "root"; // Change if needed
$pass = ""; // Change if needed
$db = "online_platform";

// Create MySQLi connection
$conn = mysqli_connect($host, $user, $pass, $db);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>