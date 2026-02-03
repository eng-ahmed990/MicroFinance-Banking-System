<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireAdmin();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['repayment_id'])) {
    $repayment_id = sanitize($conn, $_POST['repayment_id']);
    // Verify repayment
    $conn->query("UPDATE repayments SET status='verified' WHERE id=$repayment_id");
}

// Fetch repayments
$sql = "SELECT repayments.*, users.name, users.profile_pic, loans.id as loan_ref FROM repayments JOIN users ON repayments.user_id = users.id JOIN loans ON repayments.loan_id = loans.id ORDER BY repayments.created_at DESC";
$result = $conn->query($sql);

$page_title = "Repayments";
$admin_name = $_SESSION['name'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">

<?php require('../includes/header_head.php'); ?>

<body class="user-page-body">

    <div class="dashboard-container">
        <?php include('../includes/sidebar_admin.php'); ?>

        <main class="main-content">
            <?php include('../includes/topbar_admin.php'); ?>

            <div class="flex-between" style="margin-bottom: 24px;">
                <h2 style="margin: 0;">Repayment Ledger</h2>
                <a href="export_repayments.php" class="btn btn-secondary btn-small" style="text-decoration: none;">Download Report</a>
            </div>

            <div class="card"
                style="border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-radius: 15px;">
                <div class="transaction-list">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <div class="transaction-item">
                            <div style="display: flex; align-items: center;">
                                <div class="t-icon" style="<?php echo ($row['status'] == 'pending') ? 'background: #fff8e1; color: var(--warning);' : ''; ?>; overflow: hidden; padding: 0;">
                                    <?php if (!empty($row['profile_pic'])): ?>
                                        <img src="../assets/images/<?php echo $row['profile_pic']; ?>" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="fas fa-university"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="t-details">
                                    <h5><?php echo htmlspecialchars($row['name']); ?></h5>
                                    <span><i class="fas fa-fingerprint"></i> Loan #LN-<?php echo $row['loan_ref']; ?> &bull; <?php echo htmlspecialchars($row['method']); ?></span>
                                </div>
                            </div>
                            <div>
                                <div class="t-amount">PKR <?php echo number_format($row['amount'], 2); ?></div>
                                <div class="t-meta"><?php echo date("M d, Y h:i A", strtotime($row['created_at'])); ?></div>
                                <div style="text-align: right; margin-top: 5px;">
                                    <?php if ($row['status'] == 'pending'): ?>
                                    <form action="repayments.php" method="POST">
                                        <input type="hidden" name="repayment_id" value="<?php echo $row['id']; ?>">
                                        <button class="status-pill status-pending" style="padding: 2px 10px; font-size: 0.75rem; border: none; cursor: pointer;">Verify Now</button>
                                    </form>
                                    <?php else: ?>
                                    <span class="status-pill status-paid" style="padding: 2px 10px; font-size: 0.75rem;">Verified</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; padding: 20px;">No repayments found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php 
    $page_has_sidebar = true;
    include('../includes/footer.php'); 
    ?>
