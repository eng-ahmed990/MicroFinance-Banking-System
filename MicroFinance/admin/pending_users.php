<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireAdmin();

$search = isset($_GET['search']) ? sanitize($conn, $_GET['search']) : "";

// Handle Actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['user_id'])) {
    $action = $_POST['action'];
    $uid = (int)$_POST['user_id'];
    
    if ($action == 'approve') {
        // Fetch Pending User Data
        $stmt_fetch = $conn->prepare("SELECT * FROM pending_users WHERE id = ?");
        $stmt_fetch->bind_param("i", $uid);
        $stmt_fetch->execute();
        $res_fetch = $stmt_fetch->get_result();
        
        if ($res_fetch->num_rows == 1) {
            $data = $res_fetch->fetch_assoc();
            
            // Insert into Users
            $stmt_ins = $conn->prepare("INSERT INTO users (name, email, password, phone, cnic, country, dob, address, role, is_email_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_ins->bind_param("sssssssssis", $data['name'], $data['email'], $data['password'], $data['phone'], $data['cnic'], $data['country'], $data['dob'], $data['address'], $data['role'], $data['is_email_verified'], $data['created_at']);
            
            if ($stmt_ins->execute()) {
                // Delete from Pending
                $conn->query("DELETE FROM pending_users WHERE id = $uid");
                
                // Send Approval Email
                $to = $data['email'];
                $subject = "Account Approved - MicroFinance Bank";
                $message = "Dear " . $data['name'] . ",\n\nYour account has been approved by the administration. You can now login to your account.\n\nLogin here: http://localhost/MicroFinance/login.php\n\nRegards,\nMicroFinance Team";
                $headers = "From: no-reply@microfinance.com";
                mail($to, $subject, $message, $headers); 

                $msg = "User approved and moved to main users list.";
            } else {
                $msg = "Error moving user: " . $conn->error;
            }
            $stmt_ins->close();
        } else {
            $msg = "User not found.";
        }
        $stmt_fetch->close();
        
    } elseif ($action == 'reject') {
        $reason = isset($_POST['reason']) ? trim($_POST['reason']) : "Does not meet requirements.";
        
        // Fetch data for log and email
        $stmt_fetch = $conn->prepare("SELECT email, name, cnic FROM pending_users WHERE id = ?");
        $stmt_fetch->bind_param("i", $uid);
        $stmt_fetch->execute();
        $res = $stmt_fetch->get_result();
        
        if ($row = $res->fetch_assoc()) {
             // 1. Send Rejection Email
             $to = $row['email'];
             $subject = "Account Application Rejected - MicroFinance Bank";
             $message = "Dear " . $row['name'] . ",\n\nYour account application has been rejected.\n\nReason: " . $reason . "\n\nYou can re-apply by correcting the information.\n\nRegards,\nMicroFinance Team";
             $headers = "From: no-reply@microfinance.com";
             mail($to, $subject, $message, $headers);
             
             // 2. Log Rejection for Feedback Loop
             $stmt_log = $conn->prepare("INSERT INTO rejected_registrations (email, cnic, name, reason) VALUES (?, ?, ?, ?)");
             $stmt_log->bind_param("ssss", $row['email'], $row['cnic'], $row['name'], $reason);
             $stmt_log->execute();
             $stmt_log->close();
        }
        $stmt_fetch->close();

        // 3. Delete from Pending
        $stmt = $conn->prepare("DELETE FROM pending_users WHERE id=?");
        $stmt->bind_param("i", $uid);
        if ($stmt->execute()) {
            $msg = "User application rejected and deleted.";
        }
        $stmt->close();
    }
}

$sql = "SELECT * FROM pending_users ORDER BY created_at DESC";
if ($search) {
    $sql = "SELECT * FROM pending_users WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $search_param = "%$search%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$page_title = "Pending Approvals";
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
                <h2>Pending Registrations</h2>
                <form action="pending_users.php" method="GET">
                    <div class="input-with-icon">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>"
                            style="padding: 10px 10px 10px 40px; width: 300px; border-radius: 20px; border: 1px solid #ddd;">
                    </div>
                </form>
            </div>

            <div class="user-grid">
                <?php if (isset($msg) && $msg): ?>
                     <div class="alert alert-success" style="grid-column: 1/-1; background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; text-align: center;">
                        <?php echo $msg; ?>
                     </div>
                <?php endif; ?>

                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="stat-card-modern" style="border-left: 5px solid <?php echo ($row['is_email_verified']) ? 'var(--info)' : 'var(--warning)'; ?>; display: block;">
                        <div class="user-card-header"></div>
                        <div class="flex-center" style="margin-bottom: 15px;">
                             <div style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden; border: 3px solid var(--border-color);">
                                <?php if (!empty($row['profile_pic'])): ?>
                                    <img src="../assets/images/<?php echo $row['profile_pic']; ?>" style="width:100%; height:100px; object-fit:cover;">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: #eee; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-user-clock" style="font-size: 2rem; color: #aaa;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="user-card-body" style="text-align: center;">
                            <h4><?php echo htmlspecialchars($row['name']); ?></h4>
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 8px;"><?php echo htmlspecialchars($row['email']); ?></p>
                            
                            <?php if ($row['is_email_verified']): ?>
                                <span style="background: #e0f2f1; color: #00695c; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">Email Verified</span>
                            <?php else: ?>
                                <span style="background: #fff3e0; color: #ef6c00; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">Email Pending</span>
                            <?php endif; ?>

                            <div style="margin: 15px 0; font-size: 0.85rem; color: #666; text-align: left; background: #f8fafc; padding: 10px; border-radius: 8px;">
                                <div style="margin-bottom: 5px;"><i class="fas fa-phone-alt" style="width: 20px; text-align: center; margin-right: 5px;"></i> <?php echo htmlspecialchars($row['phone']); ?></div>
                                <div style="margin-bottom: 5px;"><i class="fas fa-id-card" style="width: 20px; text-align: center; margin-right: 5px;"></i> <?php echo htmlspecialchars($row['cnic']); ?></div>
                                <div style="margin-bottom: 5px;"><i class="fas fa-globe" style="width: 20px; text-align: center; margin-right: 5px;"></i> <?php echo htmlspecialchars($row['country']); ?></div>
                            </div>
                            
                            <div class="user-card-actions" style="display: flex; gap: 8px; justify-content: space-between; flex-wrap: wrap;">
                                
                                <form action="pending_users.php" method="POST" style="flex: 1; display: flex;">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button class="btn btn-small btn-primary" style="width: 100%; padding: 8px;"><i class="fas fa-check-circle"></i> Approve</button>
                                </form>

                                <button onclick="openRejectModal(<?php echo $row['id']; ?>)" class="btn btn-small btn-secondary" style="flex: 1; background: #fff2f2; color: var(--danger); border: 1px solid var(--danger); padding: 8px;">
                                    <i class="fas fa-times-circle"></i> Reject
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">
                        <i class="fas fa-clipboard-check" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.5;"></i>
                        <p>No pending registration requests.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 400px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; color: var(--danger);">Reject Application</h3>
                <span onclick="closeRejectModal()" style="color: #aaa; font-size: 24px; font-weight: bold; cursor: pointer;">&times;</span>
            </div>
            
            <form action="pending_users.php" method="POST">
                <input type="hidden" name="user_id" id="rejectUserId">
                <input type="hidden" name="action" value="reject">
                
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Reason for Rejection:</label>
                    <textarea name="reason" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; resize: vertical;" placeholder="e.g. ID photo not clear, fake details..." required></textarea>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeRejectModal()" class="btn btn-outline" style="padding: 8px 16px;">Cancel</button>
                    <button type="submit" class="btn btn-secondary" style="background: var(--danger); color: white; border: none; padding: 8px 16px;">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(id) {
            document.getElementById('rejectUserId').value = id;
            document.getElementById('rejectModal').style.display = "block";
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = "none";
        }
        
        window.onclick = function(event) {
            if (event.target.id == 'rejectModal') {
                closeRejectModal();
            }
        }
    </script>

    <?php 
    $page_has_sidebar = true;
    include('../includes/footer.php'); 
    ?>
</body>
</html>
