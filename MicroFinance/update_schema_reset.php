<?php
require('includes/db_connect.php');

// Add reset columns if they don't exist
$sql = "ALTER TABLE users 
ADD COLUMN IF NOT EXISTS reset_token_hash VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS reset_token_expires_at DATETIME NULL";

if ($conn->query($sql) === TRUE) {
    echo "Database updated successfully: Added reset_token columns.";
} else {
    echo "Error updating database: " . $conn->error;
}
?>
