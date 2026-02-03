<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "microfinance_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("System is currently unavailable. Please try again later.");
}
?>
