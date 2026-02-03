<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
        <!-- Sidebar -->
        <aside class="sidebar">
            <div style="text-align: center; padding: 0 24px 24px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 24px;">
                <div style="font-size: 1.35rem; font-weight: 800; color: white; font-family: 'Poppins', sans-serif;">
                    Micro<span style="color: #38ef7d;">Credit</span>
                </div>
                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5); margin-top: 4px; text-transform: uppercase; letter-spacing: 1px;">Banking Portal</div>
            </div>
            <ul>
                <li><a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                <li><a href="profile.php" class="<?= $current_page == 'profile.php' ? 'active' : '' ?>"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
                <li><a href="loan_application.php" class="<?= $current_page == 'loan_application.php' ? 'active' : '' ?>"><i class="fas fa-file-invoice-dollar"></i> <span>Apply for Loan</span></a></li>
                <li><a href="loan_status.php" class="<?= $current_page == 'loan_status.php' ? 'active' : '' ?>"><i class="fas fa-tasks"></i> <span>Loan Status</span></a></li>
                <li><a href="repayments.php" class="<?= $current_page == 'repayments.php' ? 'active' : '' ?>"><i class="fas fa-calendar-alt"></i> <span>Repayments</span></a></li>
                <li><a href="loan_history.php" class="<?= $current_page == 'loan_history.php' ? 'active' : '' ?>"><i class="fas fa-history"></i> <span>Loan History</span></a></li>
                <li><a href="loan_calculator.php" class="<?= $current_page == 'loan_calculator.php' ? 'active' : '' ?>"><i class="fas fa-calculator"></i> <span>Calculator</span></a></li>
                <li><a href="notifications.php" class="<?= $current_page == 'notifications.php' ? 'active' : '' ?>"><i class="fas fa-bell"></i> <span>Notifications</span>
                <?php if (isset($unread_count) && $unread_count > 0): ?>
                    <span class="badge badge-danger" style="margin-left: auto; background: var(--danger); padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;"><?php echo $unread_count; ?></span>
                <?php endif; ?>
                </a></li>
                <li style="margin-top: 24px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.1);">
                    <a href="../logout.php" style="color: rgba(255,255,255,0.5);"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
                </li>
            </ul>
        </aside>
