<?php
// includes/footer.php
$is_sidebar_page = isset($page_has_sidebar) && $page_has_sidebar;
?>
    <footer style="<?= $is_sidebar_page ? 'margin-left: var(--sidebar-width);' : '' ?>">
        <div class="container">
            <p style="font-weight: 600; color: white; margin-bottom: 12px;">Developed by:</p>
            <p style="font-size: 0.9rem;">
                Ahmed Mohsen (S2024266221), Shan Ahmed (S2024266154),<br>
                Maryam Hasnat (S2024266194), Fajr Asim (S2024266030)
            </p>
            <p style="margin-top: 24px; font-size: 0.85rem; color: rgba(255,255,255,0.5);">&copy; 2026 MicroFinance Bank. All rights reserved.</p>
        </div>
    </footer>
    <?php if (basename($_SERVER['PHP_SELF']) !== 'login.php' && function_exists('isAdmin') && isAdmin()): ?>
        <script src="../assets/js/admin_notifications.js"></script>
    <?php endif; ?>
    <!-- Global Auto-Refresh Script -->
    <script src="../assets/js/live_updates.js"></script>
</body>
</html>
