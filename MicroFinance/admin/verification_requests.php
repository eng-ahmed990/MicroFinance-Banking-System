<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireAdmin();

// Prevent Caching to avoid role state confusion
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$admin_id = $_SESSION['admin_id'];
// Strict check: Ensure variable is boolean and strictly tied to specific email
$is_super_admin = (isSuperAdmin() && isset($_SESSION['email']) && $_SESSION['email'] === 'admin@microfinance.com');

// Handle Actions (Approve/Reject)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed");
    }

    $target_user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;
    $log_action = $action;

    // Fetch current status and details for email
    $stmt_check = $conn->prepare("SELECT name, email, verification_status FROM users WHERE id = ?");
    $stmt_check->bind_param("i", $target_user_id);
    $stmt_check->execute();
    $user_res = $stmt_check->get_result()->fetch_assoc();
    $current_status = $user_res['verification_status'];
    $user_email = $user_res['email'];
    $user_name = $user_res['name'];
    $stmt_check->close();

    // Permission Check
    // Regular admins can only act on 'pending'
    if (!$is_super_admin && $current_status !== 'pending') {
        $error_msg = "You do not have permission to modify verified/rejected users. Contact Super Admin.";
    } else {
        // If status was NOT pending and User IS Super Admin => It's an override
        if ($current_status !== 'pending' && $is_super_admin) {
            $log_action = 'overridden';
            // Reason should reflect override + action
        }

        // SPLIT LOGIC: APPROVE (Update) vs REJECT (Delete)
        
        if ($action === 'approve') {
             // --- APPROVE LOGIC ---
             $new_status = 'verified';
             $stmt = $conn->prepare("UPDATE users SET verification_status=?, rejection_reason=NULL WHERE id=?");
             $stmt->bind_param("si", $new_status, $target_user_id);
             
             if ($stmt->execute()) {
                // Move Files
                $stmt_f = $conn->prepare("SELECT id_front_path, id_back_path FROM users WHERE id=?");
                $stmt_f->bind_param("i", $target_user_id);
                $stmt_f->execute();
                $res_f = $stmt_f->get_result()->fetch_assoc();
                $stmt_f->close();

                if ($res_f) {
                    $u_dir = "../assets/uploads/";
                    $v_dir = "../assets/image_users/";
                    if (!is_dir($v_dir)) mkdir($v_dir, 0777, true);
                    
                    $f_front = $res_f['id_front_path'];
                    $f_back = $res_f['id_back_path'];
                    
                    if ($f_front && file_exists($u_dir . $f_front)) rename($u_dir . $f_front, $v_dir . $f_front);
                    if ($f_back && file_exists($u_dir . $f_back)) rename($u_dir . $f_back, $v_dir . $f_back);
                }
                
                // Send Email
                require_once('../includes/email_helper.php');
                sendApprovalEmail($user_email, $user_name);
                
                $success_msg = "User Approved. Account activated.";
             } else {
                 $error_msg = "Database Error: " . $conn->error;
             }
             $stmt->close();

        } elseif ($action === 'reject') {
            // --- REJECT LOGIC (DELETE DATA) ---
            
            // 1. Send Email BEFORE deleting (so we have the email address)
            require_once('../includes/email_helper.php');
            sendRejectionEmail($user_email, $user_name, $reason);
            
            // 2. Delete Files
            $stmt_f = $conn->prepare("SELECT id_front_path, id_back_path FROM users WHERE id=?");
            $stmt_f->bind_param("i", $target_user_id);
            $stmt_f->execute();
            $res_f = $stmt_f->get_result()->fetch_assoc();
            $stmt_f->close();
            
            if ($res_f) {
                $u_dir = "../assets/uploads/";
                // Check and delete
                if (!empty($res_f['id_front_path']) && file_exists($u_dir . $res_f['id_front_path'])) {
                    unlink($u_dir . $res_f['id_front_path']);
                }
                if (!empty($res_f['id_back_path']) && file_exists($u_dir . $res_f['id_back_path'])) {
                    unlink($u_dir . $res_f['id_back_path']);
                }
            }

            // 3. Delete User Record
            // We need to delete from dependent tables if FKs exist without cascade?
            // Assuming simple schema: users table is main. 
            // Notifications/Loans might verify FK. But a "Pending" user has no loans/notifications usually.
            // OTP/Email logs might be issues if foreign keys exist.
            // Let's try deleting user.
            
            $stmt_del = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt_del->bind_param("i", $target_user_id);
            
            if ($stmt_del->execute()) {
                $success_msg = "User Rejected. Data has been permanently deleted.";
            } else {
                $error_msg = "Error deleting record: " . $conn->error;
            }
            $stmt_del->close();
        }
    }
}

// Fetch Records
// Both Super Admin and Regular Admin should primarily focus on PENDING requests to avoid clutter.
// Processed requests (Verified/Rejected) should strictly be removed from this view to prevent confusion.
$sql = "SELECT * FROM users WHERE verification_status = 'pending' ORDER BY created_at ASC";
$result = $conn->query($sql);

$page_title = "Verification Requests";
$admin_name = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="en">
<?php require('../includes/header_head.php'); ?>
<style>
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); }
    .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 800px; border-radius: 8px; position:relative; }
    .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
    .close:hover { color: black; }
    .img-fluid { max-width: 100%; height: auto; border-radius: 4px; border: 1px solid #ddd; }
</style>
<body class="user-page-body">
    <div class="dashboard-container">
        <?php include('../includes/sidebar_admin.php'); ?>
        <main class="main-content">
            <?php include('../includes/topbar_admin.php'); ?>
            
            <div class="flex-between">
                <h2>KYC Verification Requests</h2>
                <?php if ($is_super_admin): ?>
                    <span class="badge badge-primary">Super Admin Mode</span>
                <?php endif; ?>
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
                            <th style="padding: 16px;">User</th>
                            <th style="padding: 16px;">CNIC</th>
                            <th style="padding: 16px;">Documents</th>
                            <th style="padding: 16px;">Status</th>
                            <th style="padding: 16px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr style="border-bottom: 1px solid #edf2f7;">
                                <td style="padding: 16px;">
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                    <small style="color: #64748b;"><?php echo htmlspecialchars($row['email']); ?></small>
                                </td>
                                <td style="padding: 16px;"><?php echo htmlspecialchars($row['cnic']); ?></td>
                                <td style="padding: 16px;">
                                    <?php 
                                        $base_folder = ($row['verification_status'] === 'verified') ? '../assets/image_users/' : '../assets/uploads/';
                                    ?>
                                    <button class="btn btn-outline btn-small" onclick="openModal('<?php echo $base_folder . $row['id_front_path']; ?>', '<?php echo $base_folder . $row['id_back_path']; ?>')">
                                        <i class="fas fa-eye"></i> View IDs
                                    </button>
                                </td>
                                <td style="padding: 16px;">
                                    <?php 
                                        $s = $row['verification_status'];
                                        $color = ($s == 'verified') ? 'green' : (($s == 'rejected') ? 'red' : 'orange');
                                        echo "<span style='color:$color; font-weight:bold; text-transform:capitalize;'>$s</span>";
                                    ?>
                                </td>
                                <td style="padding: 16px;">
                                    <div style="display: flex; gap: 8px;">
                                        <?php if ($row['verification_status'] === 'pending' || $is_super_admin): ?>
                                            <form method="POST" onsubmit="return confirm('Approve this user?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="reason" value="Documents Verified">
                                                <button type="submit" class="btn btn-small" style="background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            
                                            <button onclick="rejectUser(<?php echo $row['id']; ?>)" class="btn btn-small" style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #cbd5e1;">Locked</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="padding: 30px; text-align: center; color: #64748b;">No verification requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Image Modal -->
    <div id="imgModal" class="modal">
        <div class="modal-content" style="text-align: center;">
            <span class="close" onclick="closeModal('imgModal')">&times;</span>
            <h3>Identity Documents</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <div>
                    <h4>Front Side</h4>
                    <img id="modalFront" src="" class="img-fluid" alt="Front ID">
                </div>
                <div>
                    <h4>Back Side</h4>
                    <img id="modalBack" src="" class="img-fluid" alt="Back ID">
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeModal('rejectModal')">&times;</span>
            <h3 style="color: #dc2626;">Reject Verification</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="user_id" id="rejectUserId">
                <input type="hidden" name="action" value="reject">
                
                <div class="form-group">
                    <label>Reason for Rejection</label>
                    <textarea name="reason" required class="form-control" rows="4" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;" placeholder="e.g., Image blurry, ID expired..."></textarea>
                </div>
                
                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" onclick="closeModal('rejectModal')" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: #dc2626; border-color: #dc2626;">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(front, back) {
            document.getElementById('modalFront').src = front;
            document.getElementById('modalBack').src = back;
            document.getElementById('imgModal').style.display = "block";
        }

        function rejectUser(id) {
            document.getElementById('rejectUserId').value = id;
            document.getElementById('rejectModal').style.display = "block";
        }

        function closeModal(id) {
            document.getElementById(id).style.display = "none";
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
            }
        }
    </script>
    <?php include('../includes/footer.php'); ?>
</body>
</html>
