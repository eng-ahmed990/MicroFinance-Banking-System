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

$sql = "SELECT * FROM loans WHERE user_id = $user_id ORDER BY applied_at DESC";
$result = $conn->query($sql);

$page_title = "Loan History";
?>
<!DOCTYPE html>
<html lang="en">

<?php require('../includes/header_head.php'); ?>

<body class="user-page-body">

    <div class="dashboard-container">
        <?php include('../includes/sidebar_user.php'); ?>

        <main class="main-content">
            <?php include('../includes/topbar.php'); ?>

            <h2 style="margin-bottom: 24px;">Loan Application History</h2>

            <!-- Modern Table Layout -->
            <div style="background: white; border-radius: var(--radius-lg); padding: 20px; box-shadow: var(--shadow-sm);">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Loan ID</th>
                            <th>Amount</th>
                            <th>Applied On</th>
                            <th>Last Update</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><span style="font-weight: 500;">#LN-<?php echo $row['id']; ?></span></td>
                                    <td style="font-weight: bold; font-size: 1.05rem; color: var(--primary-color);">PKR <?php echo number_format($row['amount'], 2); ?></td>
                                    <td><?php echo date("M d, Y", strtotime($row['applied_at'])); ?></td>
                                    <td>
                                        <?php if ($row['updated_at']): ?>
                                            <?php echo date("M d, Y", strtotime($row['updated_at'])); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-pill status-<?php echo strtolower($row['status']); ?>">
                                            <?php if($row['status'] == 'paid') echo '<i class="fas fa-check-circle" style="margin-right:4px;"></i>'; ?>
                                            <?php if($row['status'] == 'active') echo '<i class="fas fa-spinner fa-spin" style="margin-right:4px;"></i>'; ?>
                                            <?php if($row['status'] == 'pending') echo '<i class="fas fa-clock" style="margin-right:4px;"></i>'; ?>
                                            <?php if($row['status'] == 'rejected') echo '<i class="fas fa-times-circle" style="margin-right:4px;"></i>'; ?>
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="loan_status.php?id=<?php echo $row['id']; ?>" class="btn btn-outline btn-small"
                                            style="border-radius: 20px; padding: 5px 15px; text-decoration: none;"><i class="fas fa-eye"></i>
                                            Details</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #777;">
                                    <i class="fas fa-folder-open" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i><br>
                                    No loan history found.
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
</body>
</html>
