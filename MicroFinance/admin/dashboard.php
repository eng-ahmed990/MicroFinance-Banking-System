<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireAdmin();

// Total Users
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='user'");
$total_users = $result->fetch_assoc()['total'];

// Active Loans
$result = $conn->query("SELECT COUNT(*) as total FROM loans WHERE status='approved'");
$active_loans = $result->fetch_assoc()['total'];

// Pending Applications
$result = $conn->query("SELECT COUNT(*) as total FROM loans WHERE status='pending'");
$pending_apps = $result->fetch_assoc()['total'];

// Overdue/Repayments (Simulated just total loans amount for now or 0)
// Or sum of disbursed loans
$result = $conn->query("SELECT SUM(amount) as total FROM loans WHERE status='approved'");
$total_disbursed = $result->fetch_assoc()['total'] ?? 0;

// Recent Applications
$recent_loans = $conn->query("SELECT loans.*, users.name FROM loans JOIN users ON loans.user_id = users.id ORDER BY loans.applied_at DESC LIMIT 5");

$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
$page_title = "Admin Dashboard";
?>
<!DOCTYPE html>
<html lang="en">

<?php require('../includes/header_head.php'); ?>

<body class="user-page-body">
    <div class="dashboard-container">
        <?php include('../includes/sidebar_admin.php'); ?>
        
        <main class="main-content">
            <?php include('../includes/topbar_admin.php'); ?>
            
            <!-- Modern Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card-modern">
                    <div class="stat-info">
                        <h4>Total Users</h4>
                        <div><?php echo number_format($total_users); ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
                <div class="stat-card-modern success">
                    <div class="stat-info">
                        <h4>Active Loans</h4>
                        <div><?php echo number_format($active_loans); ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                </div>
                <div class="stat-card-modern warning">
                    <div class="stat-info">
                        <h4>Pending Apps</h4>
                        <div><?php echo number_format($pending_apps); ?></div>
                        <?php if ($pending_apps > 0): ?>
                        <small style="color: var(--warning); font-size: 0.8rem;">Needs Review</small>
                        <?php endif; ?>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                </div>
                <div class="stat-card-modern" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(255, 255, 255, 0.95) 100%);">
                    <div class="stat-info">
                        <h4>Total Disbursed</h4>
                        <div style="color: var(--danger);">PKR <?php echo number_format($total_disbursed); ?></div>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444 0%, #f87171 100%); box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);">
                        <i class="fa-solid fa-money-bill-transfer"></i>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card" style="margin-bottom: 32px; padding: 24px 28px;">
                <h3 style="margin: 0 0 20px; font-size: 1.1rem; color: var(--text-color);"><i class="fas fa-bolt" style="color: var(--warning); margin-right: 10px;"></i>Quick Actions</h3>
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <a href="loans.php" class="btn btn-primary btn-small" style="border-radius: var(--radius-full);">
                        <i class="fas fa-tasks"></i> Review Loans
                    </a>
                    <a href="users.php" class="btn btn-secondary btn-small" style="border-radius: var(--radius-full);">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                    <a href="repayments.php" class="btn btn-outline btn-small" style="border-radius: var(--radius-full);">
                        <i class="fas fa-money-bill-wave"></i> View Repayments
                    </a>
                    <a href="export_repayments.php" class="btn btn-outline btn-small" style="border-radius: var(--radius-full);">
                        <i class="fas fa-download"></i> Export Data
                    </a>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="card" style="border: none; box-shadow: var(--shadow-card);">
                <div class="flex-between" style="margin-bottom: 24px;">
                    <h3 style="margin: 0; color: var(--text-color); font-size: 1.1rem;">
                        <i class="fas fa-clock" style="color: var(--primary-color); margin-right: 10px;"></i>Recent Applications
                    </h3>
                    <a href="loans.php" class="btn btn-outline btn-small" style="border-radius: var(--radius-full);">View All</a>
                </div>

                <div class="transaction-list">
                    <?php if ($recent_loans->num_rows > 0): ?>
                        <?php while($row = $recent_loans->fetch_assoc()): ?>
                        <div class="transaction-item">
                            <div style="display: flex; align-items: center;">
                                <div class="t-icon" style="background: linear-gradient(135deg, rgba(17, 153, 142, 0.1) 0%, rgba(56, 239, 125, 0.1) 100%); color: var(--success);">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </div>
                                <div class="t-details">
                                    <h5><?php echo htmlspecialchars($row['name']); ?></h5>
                                    <span><i class="fas fa-hashtag" style="font-size: 0.75rem;"></i> LN-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <div style="text-align: right;">
                                    <div class="t-amount">PKR <?php echo number_format($row['amount'], 2); ?></div>
                                    <div class="t-meta"><?php echo date("M d, Y", strtotime($row['applied_at'])); ?></div>
                                </div>
                                <a href="loans.php" class="btn btn-primary btn-small" style="border-radius: var(--radius-full); padding: 8px 20px;">
                                    Review
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 48px; color: var(--text-muted);">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 16px; display: block; opacity: 0.3;"></i>
                            <p style="margin: 0;">No recent applications.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <?php 
    $page_has_sidebar = true;
    include('../includes/footer.php'); 
    ?>
