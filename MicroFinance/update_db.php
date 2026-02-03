<?php
require('includes/db_connect.php');

$queries = [
    "ALTER TABLE users ADD COLUMN dob DATE NULL AFTER cnic",
    "ALTER TABLE users ADD COLUMN country VARCHAR(50) NULL AFTER dob"
];

foreach ($queries as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Column added successfully: " . $sql . "\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
}
?>
