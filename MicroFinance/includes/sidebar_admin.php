<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
        <aside class="sidebar">
            <div style="text-align: center; padding: 0 24px 24px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 24px;">
                <div style="font-size: 1.35rem; font-weight: 800; font-family: 'Poppins', sans-serif;">
                    <span style="color: #fbbf24;">ADMIN</span> <span style="color: white;">PANEL</span>
                </div>
                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5); margin-top: 4px; text-transform: uppercase; letter-spacing: 1px;">Management System</div>
            </div>
            <ul>
                <li><a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-th-large"></i> <span>Overview</span></a></li>
                <li><a href="users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> <span>Manage Users</span></a></li>
                <li><a href="verification_requests.php" class="<?= $current_page == 'verification_requests.php' ? 'active' : '' ?>"><i class="fas fa-user-check"></i> <span>Verification Requests</span> <span id="kyc-badge" class="badge badge-warning" style="display:none; background: #f59e0b; color: #000; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-left: auto;"></span></a></li>
                <li><a href="loans.php" class="<?= $current_page == 'loans.php' ? 'active' : '' ?>"><i class="fas fa-file-invoice-dollar"></i> <span>Manage Loans</span>
                    <?php if (isset($pending_apps) && $pending_apps > 0): ?>
                    <span class="badge badge-warning" style="margin-left: auto; background: var(--warning); color: #000; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;"><?php echo $pending_apps; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="repayments.php" class="<?= $current_page == 'repayments.php' ? 'active' : '' ?>"><i class="fas fa-money-bill-wave"></i> <span>Repayments</span></a></li>
                <?php if (isSuperAdmin()): ?>
                <li><a href="manage_admins.php" class="<?= $current_page == 'manage_admins.php' ? 'active' : '' ?>"><i class="fas fa-user-shield"></i> <span>Manage Admin Accounts</span></a></li>
                <?php endif; ?>
                <li style="margin-top: 24px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.1);">
                    <a href="../logout.php" style="color: rgba(255,255,255,0.5);"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
                </li>
            </ul>
        </aside>
