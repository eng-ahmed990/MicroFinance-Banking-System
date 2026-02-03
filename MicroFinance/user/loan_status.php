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

// Fetch all loans for this user
$sql = "SELECT * FROM loans WHERE user_id = $user_id ORDER BY applied_at DESC";
$result = $conn->query($sql);

$page_title = "Loan Status";
?>
<!DOCTYPE html>
<html lang="en">

<?php require('../includes/header_head.php'); ?>

<body class="user-page-body">

    <div class="dashboard-container">
        <?php include('../includes/sidebar_user.php'); ?>

        <main class="main-content">
            <?php include('../includes/topbar.php'); ?>

            <div style="display: grid; gap: 24px;">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="modern-form-card" style="border-left: 5px solid <?php echo ($row['status'] == 'pending') ? 'var(--warning)' : (($row['status'] == 'approved' || $row['status'] == 'paid') ? 'var(--success)' : 'var(--danger)'); ?>; padding: 24px; box-shadow: var(--shadow-md); border-radius: var(--radius-lg); background: white;">
                        <div class="flex-between" style="margin-bottom: 20px;">
                            <h3 style="margin: 0; font-size: 1.25rem;">Loan #LN-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></h3>
                            <span class="status-pill status-<?php echo strtolower($row['status']); ?>"><?php echo ucfirst($row['status']); ?></span>
                        </div>
                        
                        <div class="flex-between" style="color: var(--text-muted); margin-bottom: 12px;">
                            <span>Applied Date</span>
                            <span style="font-weight: 500; color: var(--text-color);"><?php echo date("M d, Y", strtotime($row['applied_at'])); ?></span>
                        </div>
                        
                        <div class="flex-between" style="color: var(--text-muted); margin-bottom: 12px;">
                            <span>Amount</span>
                            <span style="font-weight: 700; color: var(--primary-color); font-size: 1.1rem;">PKR <?php echo number_format($row['amount'], 2); ?></span>
                        </div>
                        
                        <div class="flex-between" style="color: var(--text-muted); margin-bottom: 12px;">
                            <span>Duration</span>
                            <span style="font-weight: 500; color: var(--text-color);"><?php echo $row['duration']; ?> Months</span>
                        </div>

                        <?php if ($row['status'] == 'rejected'): ?>
                            <div style="margin-top: 16px; padding: 12px; background: #fee2e2; color: #b91c1c; border-radius: 8px; font-size: 0.9rem;">
                                <i class="fas fa-exclamation-circle" style="margin-right: 6px;"></i>
                                Your application was not approved. Please contact support for more details.
                            </div>
                        <?php endif; ?>

                        <?php if ($row['status'] == 'approved'): ?>
                            <?php 
                            $summary = getLoanSummary($conn, $row['id']);
                            $percent = ($summary['availed'] > 0) ? ($summary['paid'] / $summary['availed']) * 100 : 0;
                            ?>
                            <div style="margin-top: 24px;">
                                <div class="flex-between" style="font-size: 0.85rem; margin-bottom: 8px;">
                                    <span>Repayment Progress</span>
                                    <span><?php echo number_format($percent, 0); ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div>
                                </div>
                                <div class="flex-between" style="margin-top: 12px; font-size: 0.9rem;">
                                    <span>Paid: <span style="color: var(--success); font-weight: 600;">PKR <?php echo number_format($summary['paid']); ?></span></span>
                                    <span>Remaining: <span style="color: var(--danger); font-weight: 600;">PKR <?php echo number_format($summary['remaining']); ?></span></span>
                                </div>
                                <div style="margin-top: 20px; display: flex; gap: 12px;">
                                    <a href="make_repayment.php?loan_id=<?php echo $row['id']; ?>" class="btn btn-primary btn-block btn-small">Make Repayment</a>
                                    <a href="repayments.php" class="btn btn-outline btn-block btn-small">View Schedule</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px; background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-card);">
                        <i class="fas fa-folder-open" style="font-size: 3rem; color: var(--text-light); margin-bottom: 16px;"></i>
                        <h3 style="color: var(--text-muted);">No Loan History</h3>
                        <p style="color: var(--text-light); margin-bottom: 24px;">You haven't applied for any loans yet.</p>
                        <a href="loan_application.php" class="btn btn-primary">Apply Now</a>
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
