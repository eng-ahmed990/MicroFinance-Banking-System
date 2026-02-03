<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireAdmin();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['loan_id']) && isset($_POST['status'])) {
    $loan_id = (int)$_POST['loan_id'];
    $status = sanitize($conn, $_POST['status']);
    
    // Handle Delete Action
    if ($status == 'delete') {
        $stmt_del = $conn->prepare("DELETE FROM loans WHERE id=?");
        $stmt_del->bind_param("i", $loan_id);
        if ($stmt_del->execute()) {
            $msg = "Loan application #LN-$loan_id deleted successfully.";
        }
        $stmt_del->close();
    } else {
        // Update loan status
        $stmt_update = $conn->prepare("UPDATE loans SET status=? WHERE id=?");
        $stmt_update->bind_param("si", $status, $loan_id);
        
        if ($stmt_update->execute()) {
            $msg = "Loan status updated to $status.";
            
            // Fetch user_id for notification
            $stmt_uid = $conn->prepare("SELECT user_id FROM loans WHERE id=?");
            $stmt_uid->bind_param("i", $loan_id);
            $stmt_uid->execute();
            $res_uid = $stmt_uid->get_result();
            
            if ($res_uid->num_rows > 0) {
                $uid = $res_uid->fetch_assoc()['user_id'];
                $notif_msg = "Your loan application #LN-$loan_id has been $status.";
                
                $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $stmt_notif->bind_param("is", $uid, $notif_msg);
                $stmt_notif->execute();
                $stmt_notif->close();
            }
            $stmt_uid->close();
        }
        $stmt_update->close();
    }
}

// Fetch all loans with user details
$sql = "SELECT loans.*, users.name, users.phone, users.email, users.profile_pic FROM loans JOIN users ON loans.user_id = users.id ORDER BY field(loans.status, 'pending') DESC, loans.applied_at DESC";
$result = $conn->query($sql);

$page_title = "Manage Loans";
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

            <?php if (isset($msg) && $msg): ?>
                <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-top: 20px;">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="modern-form-card" style="border-top: 5px solid <?php echo ($row['status'] == 'pending') ? 'var(--warning)' : (($row['status'] == 'approved' || $row['status'] == 'paid') ? 'var(--success)' : 'var(--danger)'); ?>; background: var(--white); padding: 0; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; display: flex; flex-direction: column;">
                        
                        <!-- Header: User & Status -->
                        <div style="padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 42px; height: 42px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #e2e8f0;">
                                    <?php if (!empty($row['profile_pic'])): ?>
                                        <img src="../assets/images/<?php echo $row['profile_pic']; ?>" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="fas fa-user" style="color: #cbd5e0; font-size: 1.2rem;"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 1rem; color: var(--text-color); font-weight: 700;"><?php echo htmlspecialchars($row['name']); ?></h4>
                                    <span style="font-size: 0.8rem; color: #64748b;">#LN-<?php echo $row['id']; ?></span>
                                </div>
                            </div>
                            <span class="status-pill status-<?php echo strtolower($row['status']); ?>" style="padding: 6px 12px; font-size: 0.75rem; border-radius: 20px; letter-spacing: 0.5px;"><?php echo ucfirst($row['status']); ?></span>
                        </div>

                        <!-- Body: Key Metrics -->
                        <div style="padding: 20px; flex: 1;">
                            <?php $summary = getLoanSummary($conn, $row['id']); ?>
                            
                            <!-- Hero Amount -->
                            <div style="text-align: center; margin-bottom: 20px; padding: 15px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 8px;">
                                <span style="font-size: 0.85rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 4px;">Loan Amount</span>
                                <span style="font-size: 1.5rem; font-weight: 800; color: var(--primary-color);">PKR <?php echo number_format($summary['availed'], 2); ?></span>
                            </div>

                            <!-- Details Grid -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; font-size: 0.9rem;">
                                <div>
                                    <span style="display: block; color: #64748b; font-size: 0.75rem; margin-bottom: 2px;">Monthly Income</span>
                                    <strong style="color: #334155;">PKR <?php echo number_format($row['monthly_income']); ?></strong>
                                </div>
                                <div style="text-align: right;">
                                    <span style="display: block; color: #64748b; font-size: 0.75rem; margin-bottom: 2px;">Employment</span>
                                    <strong style="color: #334155;"><?php echo htmlspecialchars($row['employment_status']); ?></strong>
                                </div>
                                <div>
                                    <span style="display: block; color: #64748b; font-size: 0.75rem; margin-bottom: 2px;">Duration</span>
                                    <strong style="color: #334155;"><?php echo $row['duration']; ?> Months</strong>
                                </div>
                                <div style="text-align: right;">
                                    <span style="display: block; color: #64748b; font-size: 0.75rem; margin-bottom: 2px;">Guarantor</span>
                                    <strong style="color: #334155;"><?php echo htmlspecialchars($row['guarantor'] ?: 'N/A'); ?></strong>
                                </div>
                            </div>
                            
                            <div style="margin-top: 20px; border-top: 1px dashed #e2e8f0; padding-top: 15px;">
                                <span style="display: block; color: #64748b; font-size: 0.75rem; margin-bottom: 6px;">Purpose</span>
                                <p style="margin: 0; color: #475569; font-style: italic; font-size: 0.9rem; line-height: 1.4; background: #fafafa; padding: 10px; border-radius: 6px; border: 1px solid #eee;">
                                    "<?php echo htmlspecialchars($row['purpose']); ?>"
                                </p>
                            </div>
                        </div>

                        <!-- Footer: Actions -->
                         <?php if ($row['status'] == 'pending'): ?>
                        <div style="padding: 15px 20px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                            <form action="loans.php" method="POST">
                                <input type="hidden" name="loan_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="status" value="approved">
                                <button title="Approve" class="btn btn-sm" style="width: 100%; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; border-radius: 6px; padding: 10px; font-weight: 600;">
                                    <i class="fas fa-check"></i> <span style="display: none; display: md-inline;">Approve</span>
                                </button>
                            </form>
                            <form action="loans.php" method="POST">
                                <input type="hidden" name="loan_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="status" value="rejected">
                                <button title="Reject" class="btn btn-sm" style="width: 100%; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: 6px; padding: 10px; font-weight: 600;">
                                    <i class="fas fa-times"></i> <span style="display: none; display: md-inline;">Reject</span>
                                </button>
                            </form>
                            <form action="loans.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this application?');">
                                <input type="hidden" name="loan_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="status" value="delete">
                                <button title="Delete" class="btn btn-sm" style="width: 100%; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e0; border-radius: 6px; padding: 10px; font-weight: 600;">
                                    <i class="fas fa-trash-alt"></i> <span style="display: none; display: md-inline;">Delete</span>
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <!-- If not pending, show simplified status footer -->
                        <div style="padding: 15px 20px; background: #f8fafc; border-top: 1px solid #f1f5f9; text-align: center;">
                            <span style="font-size: 0.85rem; color: #64748b;">Action completed on <?php echo date("M d, Y", strtotime($row['applied_at'])); // Ideally updated_at if available ?></span>
                        </div>
                        <?php endif; ?>

                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; grid-column: 1/-1;">No loan applications found.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php 
    $page_has_sidebar = true;
    include('../includes/footer.php'); 
    ?>
