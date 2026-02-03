<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireSuperAdmin(); // Strict Access Control

// Handle Delete Action
if (isset($_POST['delete_admin_id'])) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed");
    }

    $delete_id = (int)$_POST['delete_admin_id'];
    
    // Prevent self-deletion
    if ($delete_id == $_SESSION['admin_id']) {
        $error_msg = "You cannot delete your own account.";
    } else {
        // Prevent deleting the main super admin by email hardcheck if needed, though ID check usually suffices.
        // Let's get email to be safe
        $check = $conn->query("SELECT email FROM admins WHERE id=$delete_id");
        $target_email = ($check->num_rows > 0) ? $check->fetch_assoc()['email'] : '';
        
        if ($target_email === 'admin@microfinance.com') {
             $error_msg = "The Master Administrator cannot be deleted.";
        } else {
            $stmt = $conn->prepare("DELETE FROM admins WHERE id=?");
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                $success_msg = "Admin account deleted successfully.";
            } else {
                $error_msg = "Error deleting admin: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch Admins
$result = $conn->query("SELECT * FROM admins ORDER BY id ASC");
$page_title = "Manage Admin Accounts";
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
            
            <div class="flex-between">
                <h2>Admin Accounts</h2>
                <a href="register.php" class="btn btn-primary btn-small">
                    <i class="fas fa-plus"></i> Create New Admin
                </a>
            </div>

            <?php if (isset($success_msg)): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="card" style="padding: 0; overflow: hidden; margin-top: 20px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #edf2f7; text-align: left;">
                            <th style="padding: 16px 24px;">ID</th>
                            <th style="padding: 16px 24px;">Admin Name</th>
                            <th style="padding: 16px 24px;">Email</th>
                            <th style="padding: 16px 24px;">Password</th>
                            <th style="padding: 16px 24px;">Contact</th>
                            <th style="padding: 16px 24px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #edf2f7;">
                            <td style="padding: 16px 24px; color: #718096;">#<?php echo $row['id']; ?></td>
                            <td style="padding: 16px 24px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 32px; height: 32px; background: var(--primary-gradient); border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;">
                                        <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                    </div>
                                    <span style="font-weight: 600; color: var(--text-color);"><?php echo htmlspecialchars($row['name']); ?></span>
                                </div>
                            </td>
                            <td style="padding: 16px 24px;"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td style="padding: 16px 24px;">
                                <span style="font-family: monospace; letter-spacing: 2px; color: var(--text-muted);">••••••••</span>
                            </td>
                            <td style="padding: 16px 24px;"><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td style="padding: 16px 24px;">
                                <div style="display: flex; gap: 8px;">
                                    <a href="edit_admin.php?id=<?php echo $row['id']; ?>" class="btn btn-outline btn-small" style="padding: 6px 12px;">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="edit_admin.php?id=<?php echo $row['id']; ?>" class="btn btn-outline btn-small" style="padding: 6px 12px; border-color: #cbd5e0; color: #4a5568;">
                                        <i class="fas fa-key"></i> Reset
                                    </a>
                                    
                                    <?php if ($row['email'] !== 'admin@microfinance.com' && $row['id'] != $_SESSION['admin_id']): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this admin account?');" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="delete_admin_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-secondary btn-small" style="padding: 6px 12px; background: #fff2f2; color: var(--danger); border: 1px solid var(--danger);">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <button disabled class="btn btn-small" style="opacity: 0.5; cursor: not-allowed; background: #eee; color: #999;">Locked</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
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
