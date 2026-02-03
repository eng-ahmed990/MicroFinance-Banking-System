<?php
require('includes/db_connect.php');

echo "Starting Database Update...\n";

// Array of columns to add
$columns_to_add = [
    "monthly_income" => "DECIMAL(15,2) DEFAULT 0.00",
    "employment_status" => "VARCHAR(50) DEFAULT 'Unknown'",
    "guarantor" => "VARCHAR(100) DEFAULT NULL"
];

foreach ($columns_to_add as $col_name => $col_def) {
    // Check if column exists
    $check_query = "SHOW COLUMNS FROM loans LIKE '$col_name'";
    $result = $conn->query($check_query);
    
    if ($result->num_rows == 0) {
        $alter_query = "ALTER TABLE loans ADD COLUMN $col_name $col_def";
        if ($conn->query($alter_query) === TRUE) {
            echo "Successfully added column: $col_name\n";
        } else {
            echo "Error adding column $col_name: " . $conn->error . "\n";
        }
    } else {
        echo "Column already exists: $col_name\n";
    }
}

echo "Database Update Completed.\n";
?>
