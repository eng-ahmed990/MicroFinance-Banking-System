<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireLogin();

$user_id = $_SESSION['user_id'];
requireVerification($conn); // Enforce KYC
$user_name = $_SESSION['user_name'];
$unread_count = getUnreadCount($conn, $user_id);

// Fetch User Data for Profile Pic
$stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res_user = $stmt->get_result();
$u_data = $res_user->fetch_assoc();
$profile_pic = $u_data['profile_pic'] ?? null;
$stmt->close();

// Fetch Active Loan
$stmt = $conn->prepare("SELECT * FROM loans WHERE user_id = ? AND status = 'approved' ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_loan = $stmt->get_result();
$stmt->close(); // Store result first then close if needed, but get_result() returns object independent of stmt if stmt not closed too early? Actually mysqli_stmt_get_result returns a result object. We can close statement.

$active_loan_amount = 0;
$next_payment = 0;
// Default values
$loan_status = "No Active Loan";

if ($result_loan->num_rows > 0) {
    $loan = $result_loan->fetch_assoc();
    $summary = getLoanSummary($conn, $loan['id']);
    $active_loan_amount = $summary['remaining']; 
    $total_paid = $summary['paid'];
    $loan_status = "Active";
    // Simplified calculation for next payment
    $next_payment = $summary['availed'] / $loan['duration']; 
}

// Recent Activity
$stmt = $conn->prepare("SELECT * FROM loans WHERE user_id = ? ORDER BY applied_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_activity = $stmt->get_result();
$stmt->close();

$page_title = "Dashboard";
?>
<!DOCTYPE html>
<html lang="en">

<?php require('../includes/header_head.php'); ?>

<body class="user-page-body">

    <div class="dashboard-container">
        <?php include('../includes/sidebar_user.php'); ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php include('../includes/topbar.php'); ?>

            <!-- Modern Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card-modern">
                    <div class="stat-info">
                        <h4>Remaining Balance</h4>
                        <div>PKR <span id="stat-balance"><?php echo number_format($active_loan_amount, 2); ?></span></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                </div>
                <div class="stat-card-modern success">
                    <div class="stat-info">
                        <h4>Next Payment</h4>
                        <div>PKR <span id="stat-next-payment"><?php echo number_format($next_payment, 2); ?></span></div>
                        <small style="color: var(--text-muted); font-size: 0.8rem;">Due Next Month</small>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                </div>
                <div class="stat-card-modern info">
                    <div class="stat-info">
                        <h4>Loan Status</h4>
                        <div style="font-size: 1.5rem;"><span id="stat-status"><?php echo $loan_status; ?></span></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                </div>
                <div class="stat-card-modern warning">
                    <div class="stat-info">
                        <h4>Amount Paid</h4>
                        <div style="color: var(--success);">PKR <span id="stat-total-paid"><?php echo number_format($total_paid ?? 0, 2); ?></span></div>
                    </div>
                    <div class="stat-icon" style="background: var(--secondary-gradient); box-shadow: 0 8px 20px rgba(17, 153, 142, 0.3);">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card" style="margin-bottom: 32px; padding: 24px 28px;">
                <h3 style="margin: 0 0 20px; font-size: 1.1rem; color: var(--text-color);"><i class="fas fa-bolt" style="color: var(--warning); margin-right: 10px;"></i>Quick Actions</h3>
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <a href="loan_application.php" class="btn btn-primary btn-small" style="border-radius: var(--radius-full);">
                        <i class="fas fa-plus"></i> New Loan
                    </a>
                    <a href="make_repayment.php" class="btn btn-secondary btn-small" style="border-radius: var(--radius-full);">
                        <i class="fas fa-credit-card"></i> Make Payment
                    </a>
                    <a href="loan_calculator.php" class="btn btn-outline btn-small" style="border-radius: var(--radius-full);">
                        <i class="fas fa-calculator"></i> Calculator
                    </a>
                    <a href="loan_status.php" class="btn btn-outline btn-small" style="border-radius: var(--radius-full);">
                        <i class="fas fa-eye"></i> View Status
                    </a>
                </div>
            </div>

            <!-- Recent Loans Table -->
            <div class="card" style="border: none; box-shadow: var(--shadow-card);">
                <div class="flex-between" style="padding-bottom: 20px; border-bottom: 1px solid var(--border-color); margin-bottom: 16px;">
                    <h3 style="margin: 0; color: var(--text-color); font-size: 1.1rem;">
                        <i class="fas fa-clock" style="color: var(--primary-color); margin-right: 10px;"></i>Recent Loans
                    </h3>
                    <a href="loan_history.php" class="btn btn-outline btn-small" style="border-radius: var(--radius-full);">View All</a>
                </div>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_activity->num_rows > 0): ?>
                            <?php while($row = $result_activity->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date("M d, Y", strtotime($row['applied_at'])); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 36px; height: 36px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-file-invoice-dollar" style="color: var(--primary-color);"></i>
                                        </div>
                                        <span>Loan Application</span>
                                    </div>
                                </td>
                                <td style="font-weight: 700;">PKR <?php echo number_format($row['amount'], 2); ?></td>
                                <td><span class="status-pill status-<?php echo strtolower($row['status']); ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 12px; display: block; opacity: 0.5;"></i>
                                    No recent activity found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <?php 
    $page_has_sidebar = true;
    include('../includes/footer.php'); 
    ?>
