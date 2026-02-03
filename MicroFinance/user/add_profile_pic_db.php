<?php
require('../includes/db_connect.php');

echo "<h2>Database Schema Updater - Profile Pic</h2>";

// Add 'profile_pic' column to 'users' table if not exists
$sql = "ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>&#10003; Successfully added 'profile_pic' column.</p>";
} else {
    // Check if error is because column exists
    if (strpos($conn->error, "Duplicate column") !== false) {
         echo "<p style='color: orange;'>Column 'profile_pic' already exists. Skipping.</p>";
    } else {
        echo "<p style='color: red;'>&#10007; Error updating table: " . $conn->error . "</p>";
    }
}

echo "<br><br><a href='profile.php'>Go Back to Profile</a>";
?>
