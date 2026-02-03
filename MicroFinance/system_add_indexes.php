<?php
require('includes/db_connect.php');

echo "<h2>Applying Database Indexes...</h2>";

$indexes = [
    "ALTER TABLE users ADD INDEX idx_role (role)",
    "ALTER TABLE loans ADD INDEX idx_user_id (user_id)",
    "ALTER TABLE loans ADD INDEX idx_status (status)",
    "ALTER TABLE repayments ADD INDEX idx_loan_id (loan_id)",
    "ALTER TABLE repayments ADD INDEX idx_user_id (user_id)",
    "ALTER TABLE repayments ADD INDEX idx_status (status)",
    "ALTER TABLE notifications ADD INDEX idx_user_id (user_id)",
    "ALTER TABLE notifications ADD INDEX idx_is_read (is_read)"
];

foreach ($indexes as $sql) {
    try {
        if ($conn->query($sql) === TRUE) {
            echo "<p style='color:green;'>Success: $sql</p>";
        } else {
            // Check if error is "Duplicate key name" (Error 1061)
            if ($conn->errno == 1061) {
                echo "<p style='color:orange;'>Skipped (Already exists): $sql</p>";
            } else {
                echo "<p style='color:red;'>Error: " . $conn->error . " (Query: $sql)</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>Exception: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Optimization Complete.</h3>";
?>
