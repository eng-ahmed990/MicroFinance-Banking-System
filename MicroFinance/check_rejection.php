<?php
require('includes/db_connect.php');

header('Content-Type: application/json');

if (isset($_GET['email'])) {
    $email = sanitize($conn, $_GET['email']);
    
    // Check rejected_registrations table
    $stmt = $conn->prepare("SELECT reason, rejected_at FROM rejected_registrations WHERE email = ? ORDER BY rejected_at DESC LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'rejected' => true,
            'reason' => $row['reason'],
            'date' => date("d M Y", strtotime($row['rejected_at']))
        ]);
    } else {
        echo json_encode(['rejected' => false]);
    }
    $stmt->close();
} else {
    echo json_encode(['error' => 'No email provided']);
}

function sanitize($conn, $input) {
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($input))));
}
?>
