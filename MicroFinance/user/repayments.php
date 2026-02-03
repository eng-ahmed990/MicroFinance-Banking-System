<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$unread_count = getUnreadCount($conn, $user_id);

// Fetch User Data for Profile Pic
$sql_user = "SELECT profile_pic FROM users WHERE id = $user_id";
$res_user = $conn->query($sql_user);
$u_data = $res_user->fetch_assoc();
$profile_pic = $u_data['profile_pic'] ?? null;

// Get active loan
$sql = "SELECT * FROM loans WHERE user_id = $user_id AND status = 'approved'";
$result = $conn->query($sql);
$loan = $result->fetch_assoc();

$page_title = "Repayments";
?>
<!DOCTYPE html>
<html lang="en">

<?php require('../includes/header_head.php'); ?>

<body class="user-page-body">

    <div class="dashboard-container">
        <?php include('../includes/sidebar_user.php'); ?>

        <main class="main-content">
            <?php include('../includes/topbar.php'); ?>

            <div class="flex-between" style="margin-bottom: 24px;">
                <h2 style="margin: 0;">Repayment Schedule</h2>
                <a href="make_repayment.php" class="btn btn-primary">Make a Payment</a>
            </div>

            <div class="card" style="box-shadow: var(--shadow-md); border: none; border-radius: var(--radius-lg);">
                <?php if ($loan): ?>
                    <h4 style="margin-bottom: 20px; color: var(--primary-color); border-bottom: 1px solid #eee; padding-bottom: 10px;">Loan #LN-<?php echo $loan['id']; ?> (Active)</h4>
                    <?php
                        // Logic to generate schedule based on loan start date
                        $amount = $loan['amount'];
                        $duration = $loan['duration'];
                        $monthly_installment = $amount / $duration;
                        $start_date = strtotime($loan['updated_at']); // Assuming updated_at is approval date
                    ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Installment #</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i = 1; $i <= $duration; $i++): 
                                $due_date = date("M d, Y", strtotime("+$i month", $start_date));
                                // Simplified status check: This would usually check against actual payments made
                                $status = "Upcoming"; 
                                $status_class = "info";
                                
                                // Check if we are past due date (Mock logic)
                                if(strtotime($due_date) < time()) {
                                    $status = "Overdue";
                                    $status_class = "danger";
                                }
                            ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td><?php echo $due_date; ?></td>
                                <td>PKR <?php echo number_format($monthly_installment, 2); ?></td>
                                <td><span class="status-pill status-<?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-calendar-check" style="font-size: 3rem; color: #ccc; margin-bottom: 16px;"></i>
                        <p style="color: #777;">No active approved loan found to show schedule.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php 
    $page_has_sidebar = true;
    include('../includes/footer.php'); 
    ?>
</body>
</html>
