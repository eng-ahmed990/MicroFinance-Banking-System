<?php
require('includes/db_connect.php');

echo "<h2>Updating Database Schema for Rejected Registrations...</h2>";

$sql = "CREATE TABLE IF NOT EXISTS rejected_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    cnic VARCHAR(20),
    name VARCHAR(100),
    reason TEXT,
    rejected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (email)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>Table rejected_registrations created or exists.</p>";
} else {
    echo "<p style='color:red;'>Error creating rejected_registrations: " . $conn->error . "</p>";
}

echo "<h3>Schema Update Complete.</h3>";
?>
