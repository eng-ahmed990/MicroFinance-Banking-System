<?php
require('includes/db_connect.php');

// Disable foreign key checks to allow flexible deletion order if needed
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

echo "<h2>Starting System Reset...</h2>";

// 1. Clean Admins (Keep Main Admin)
$main_email = 'admin@microfinance.com';
$stmt = $conn->prepare("DELETE FROM admins WHERE email != ?");
$stmt->bind_param("s", $main_email);
if ($stmt->execute()) {
    echo "<p style='color:green'>Deleted all admins except $main_email.</p>";
} else {
    echo "<p style='color:red'>Error deleting admins: " . $conn->error . "</p>";
}
$stmt->close();

// 2. Clean Users (All Customers)
// Cascades will handle: loans, repayments, notifications, verification_logs(related to user)
if ($conn->query("TRUNCATE TABLE users")) {
    echo "<p style='color:green'>Deleted all users (and cascaded data).</p>";
} else {
    // If truncate fails due to FK (sometimes happens even with checks off if simple truncate), try DELETE
    if ($conn->query("DELETE FROM users")) {
        echo "<p style='color:green'>Deleted all users via DELETE.</p>";
    } else {
        echo "<p style='color:red'>Error deleting users: " . $conn->error . "</p>";
    }
}

// 3. Clean Logs (Explicitly clear any remaining logs if not cascaded)
if ($conn->query("TRUNCATE TABLE verification_logs")) {
    echo "<p style='color:green'>Cleared all verification logs.</p>";
}

// Re-enable FK
$conn->query("SET FOREIGN_KEY_CHECKS = 1");


// 4. Clean Files
echo "<h3>Cleaning File Storage...</h3>";
function cleanDirectory($dir) {
    if (!is_dir($dir)) return;
    $files = glob($dir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            echo "Deleted file: " . basename($file) . "<br>";
        }
    }
}

cleanDirectory('assets/uploads/');
cleanDirectory('assets/image_users/');

echo "<h2>System Reset Complete. Only Main Admin remains.</h2>";
?>
