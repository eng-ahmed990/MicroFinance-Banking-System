<?php
// api/poll_updates.php
require('../includes/db_connect.php');
require('../includes/auth_session.php');

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$response = [];

// 1. Unread Notifications Count
$stmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$unread = $res->fetch_assoc()['unread'];
$stmt->close();

$response['unread_count'] = $unread;

// 2. Dashboard Stats (Only if needed, but cheap to fetch)
// Fetch Active Loan Status & Balance
$stmt = $conn->prepare("SELECT id, status, amount, duration FROM loans WHERE user_id = ? AND status = 'approved' ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res_loan = $stmt->get_result();

if ($res_loan->num_rows > 0) {
    $loan = $res_loan->fetch_assoc();
    $summary = getLoanSummary($conn, $loan['id']);
    
    $response['loan_status'] = 'Active';
    $response['loan_balance'] = $summary['remaining'];
    $response['next_payment'] = $summary['availed'] / $loan['duration']; // Simplified logic matching dashboard
    $response['total_paid'] = $summary['paid'];
} else {
    $response['loan_status'] = 'No Active Loan';
    $response['loan_balance'] = 0;
    $response['next_payment'] = 0;
    $response['total_paid'] = 0;
}

echo json_encode($response);
?>
