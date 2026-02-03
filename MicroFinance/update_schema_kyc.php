<?php
require('includes/db_connect.php');

echo "<h2>Updating Database Schema for KYC...</h2>";

// 1. Update users table
$columns = [
    "verification_status" => "ENUM('unverified', 'pending', 'verified', 'rejected') DEFAULT 'unverified'",
    "id_front_path" => "VARCHAR(255) DEFAULT NULL",
    "id_back_path" => "VARCHAR(255) DEFAULT NULL",
    "rejection_reason" => "TEXT DEFAULT NULL"
];

foreach ($columns as $col => $def) {
    if (!$conn->query("SHOW COLUMNS FROM users LIKE '$col'")->num_rows) {
        $sql = "ALTER TABLE users ADD $col $def";
        if ($conn->query($sql) === TRUE) {
            echo "<p style='color:green;'>Added column: $col</p>";
        } else {
            echo "<p style='color:red;'>Error adding $col: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:orange;'>Column $col already exists.</p>";
    }
}

// 2. Create verification_logs table
$sql_logs = "CREATE TABLE IF NOT EXISTS verification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    admin_id INT NOT NULL,
    action ENUM('approved', 'rejected', 'overridden') NOT NULL,
    reason TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql_logs) === TRUE) {
    echo "<p style='color:green;'>Table verification_logs created or exists.</p>";
} else {
    echo "<p style='color:red;'>Error creating verification_logs: " . $conn->error . "</p>";
}

echo "<h3>Schema Update Complete.</h3>";
?>
