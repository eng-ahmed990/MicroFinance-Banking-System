<?php
require('../../includes/db_connect.php');
require('../../includes/auth_session.php');

header('Content-Type: application/json');

// Ensure only Admins can access
if (!isAdmin()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// Count pending verification requests
$sql = "SELECT COUNT(*) as count FROM users WHERE verification_status = 'pending'";
$result = $conn->query($sql);
$count = 0;

if ($result) {
    $row = $result->fetch_assoc();
    $count = (int)$row['count'];
}

echo json_encode(['status' => 'success', 'count' => $count]);
?>
