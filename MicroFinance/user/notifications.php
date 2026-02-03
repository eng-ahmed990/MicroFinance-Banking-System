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

// Handle Mark All as Read
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
    header("Location: notifications.php");
    exit();
}

// Fetch all notifications for list
$sql = "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC";
$result = $conn->query($sql);

$page_title = "Notifications";
?>
<!DOCTYPE html>
<html lang="en">

<?php require('../includes/header_head.php'); ?>

<body class="user-page-body">

    <div class="dashboard-container">
        <?php include('../includes/sidebar_user.php'); ?>

        <main class="main-content">
            <?php include('../includes/topbar.php'); ?>

            <div class="notification-container">
                <div class="notification-header" style="margin-bottom: 40px;">
                    <div>
                        <h2 style="margin: 0; display: flex; align-items: center; gap: 12px;">
                            <div style="width: 48px; height: 48px; background: var(--primary-gradient); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);">
                                <i class="fas fa-bell" style="color: white; font-size: 1.25rem;"></i>
                            </div>
                            Notifications
                        </h2>
                        <?php if ($unread_count > 0): ?>
                        <p style="margin: 8px 0 0 60px; color: rgba(255,255,255,0.7); font-size: 0.9rem;">You have <strong style="color: white;"><?php echo $unread_count; ?></strong> unread notification<?php echo $unread_count > 1 ? 's' : ''; ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($unread_count > 0): ?>
                    <form action="notifications.php" method="POST">
                        <input type="hidden" name="mark_all_read" value="1">
                        <button type="submit" class="mark-read-btn">
                            <i class="fas fa-check-double"></i> Mark all as read
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <div class="notification-list animate-fade">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $icon_class = "icon-info";
                            $icon = "fa-info-circle";
                            $title = "Update";
                            
                            if (strpos(strtolower($row['message']), 'approved') !== false) {
                                $icon_class = "icon-success";
                                $icon = "fa-check-circle";
                                $title = "Approved";
                            } elseif (strpos(strtolower($row['message']), 'rejected') !== false) {
                                $icon_class = "icon-danger";
                                $icon = "fa-times-circle";
                                $title = "Rejected";
                            } elseif (strpos(strtolower($row['message']), 'due') !== false || strpos(strtolower($row['message']), 'reminder') !== false) {
                                $icon_class = "icon-warning";
                                $icon = "fa-exclamation-triangle";
                                $title = "Reminder";
                            } elseif (strpos(strtolower($row['message']), 'payment') !== false) {
                                $icon_class = "icon-success";
                                $icon = "fa-receipt";
                                $title = "Payment";
                            }
                        ?>
                        <div class="notification-item <?php echo $row['is_read'] ? '' : 'unread'; ?>">
                            <div class="notification-icon <?php echo $icon_class; ?>">
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title"><?php echo $title; ?></div>
                                <div class="notification-message"><?php echo htmlspecialchars($row['message']); ?></div>
                                <div class="notification-time">
                                    <i class="far fa-clock" style="margin-right: 4px;"></i>
                                    <?php echo date("M d, Y", strtotime($row['created_at'])); ?> at <?php echo date("h:i A", strtotime($row['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="card" style="text-align: center; padding: 60px 40px;">
                            <div style="width: 100px; height: 100px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                                <i class="fas fa-bell-slash" style="font-size: 2.5rem; color: var(--primary-color); opacity: 0.5;"></i>
                            </div>
                            <h3 style="color: var(--text-color); margin-bottom: 8px;">No Notifications</h3>
                            <p style="color: var(--text-muted); margin: 0;">You're all caught up! Check back later for updates.</p>
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
</body>
</html>
