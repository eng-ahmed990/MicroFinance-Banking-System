<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireAdmin();

// Fetch repayments
$sql = "SELECT repayments.id, users.name, loans.id as loan_ref, repayments.amount, repayments.payment_date, repayments.method, repayments.status 
        FROM repayments 
        JOIN users ON repayments.user_id = users.id 
        JOIN loans ON repayments.loan_id = loans.id 
        ORDER BY repayments.created_at DESC";
$result = $conn->query($sql);

// Set headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="repayment_report_' . date('Y-m-d') . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output column headings
fputcsv($output, array('Repayment ID', 'User Name', 'Loan Reference', 'Amount', 'Date', 'Method', 'Status'));

// Fetch and output data rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, array(
            '#REP-' . $row['id'], 
            $row['name'], 
            '#LN-' . $row['loan_ref'], 
            'PKR ' . number_format($row['amount'], 2), 
            $row['payment_date'], 
            $row['method'], 
            ucfirst($row['status'])
        ));
    }
}

fclose($output);
exit();
?>
