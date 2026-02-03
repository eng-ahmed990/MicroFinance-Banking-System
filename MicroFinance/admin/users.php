<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireAdmin();

$search = isset($_GET['search']) ? sanitize($conn, $_GET['search']) : "";

// Handle Actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['user_id'])) {
    $action = $_POST['action'];
    $uid = (int)$_POST['user_id'];
    $stmt = null;

    // Strict Super Admin Check for Actions
    if (!isSuperAdmin()) {
        $msg = "Error: Only Super Admin can manage accounts.";
    } else {
        if ($action == 'delete') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $uid);
        if ($stmt->execute()) {
            $msg = "User deleted successfully.";
        }
    } elseif ($action == 'ban') {
        $stmt = $conn->prepare("UPDATE users SET role='banned' WHERE id=?");
        $stmt->bind_param("i", $uid);
        if ($stmt->execute()) {
            $msg = "User has been banned.";
        }
        } elseif ($action == 'unban') {
            $stmt = $conn->prepare("UPDATE users SET role='user' WHERE id=?");
            $stmt->bind_param("i", $uid);
            if ($stmt->execute()) {
                $msg = "User has been unbanned.";
            }
        }
    }
    
    if ($stmt) $stmt->close();
}

$sql = "SELECT * FROM users WHERE role != 'admin'"; // Exclude admins
if ($search) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $stmt = $conn->prepare($sql);
    $search_param = "%$search%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    // $stmt->close(); // Keep open or copy result? result object is independent.
} else {
    $result = $conn->query($sql);
}

$page_title = "Manage Users";
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
                <h2>User Management</h2>
                <form action="users.php" method="GET">
                    <div class="input-with-icon">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search user..." value="<?php echo htmlspecialchars($search); ?>"
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
                    <div class="stat-card-modern" style="border-left: 5px solid <?php echo ($row['role'] == 'banned') ? 'var(--danger)' : 'var(--success)'; ?>; display: block;">
                        <div class="user-card-header"></div>
                        <div class="flex-center" style="margin-bottom: 15px;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden; border: 3px solid var(--border-color);">
                                <?php if (!empty($row['profile_pic'])): ?>
                                    <img src="../assets/images/<?php echo $row['profile_pic']; ?>" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: #eee; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-user-tie" style="font-size: 2rem; color: #ccc;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="user-card-body" style="text-align: center;">
                            <h4><?php echo htmlspecialchars($row['name']); ?></h4>
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 8px;"><?php echo htmlspecialchars($row['email']); ?></p>
                            
                            <?php if ($row['role'] == 'banned'): ?>
                                <span style="background: #ffebee; color: #c62828; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">Banned</span>
                            <?php else: ?>
                                <span style="background: var(--success-light); color: var(--success); padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">Active Client</span>
                            <?php endif; ?>

                            <div style="margin: 15px 0; font-size: 0.85rem; color: #666; text-align: left; background: #f8fafc; padding: 10px; border-radius: 8px;">
                                <div style="margin-bottom: 5px;"><i class="fas fa-phone-alt" style="width: 20px; text-align: center; margin-right: 5px;"></i> <?php echo htmlspecialchars($row['phone']); ?></div>
                                <?php 
                                // Simple summary of all user loans
                                $uid = $row['id'];
                                $sum_sql = "SELECT SUM(amount) as total_availed FROM loans WHERE user_id = $uid AND status = 'approved'";
                                $sum_res = $conn->query($sum_sql);
                                $total_availed = ($sum_res) ? (float)$sum_res->fetch_assoc()['total_availed'] : 0;
                                
                                $paid_sql = "SELECT SUM(amount) as total_paid FROM repayments WHERE user_id = $uid AND status = 'verified'";
                                $paid_res = $conn->query($paid_sql);
                                $total_paid = ($paid_res) ? (float)$paid_res->fetch_assoc()['total_paid'] : 0;
                                
                                $remaining = $total_availed - $total_paid;
                                ?>
                                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #eee; color: var(--primary-color);">
                                    <strong>Total Remaining:</strong> PKR <?php echo number_format($remaining, 2); ?>
                                </div>
                            </div>
                            
                            <div class="user-card-actions" style="display: flex; gap: 8px; justify-content: space-between; flex-wrap: wrap;">
                                <!-- View IDs Button (Visible to all admins if docs exist) -->
                                <?php if (!empty($row['id_front_path'])): ?>
                                    <?php 
                                        $base_folder = ($row['verification_status'] === 'verified') ? '../assets/image_users/' : '../assets/uploads/';
                                        $f_path = $base_folder . $row['id_front_path'];
                                        $b_path = $base_folder . $row['id_back_path'];
                                    ?>
                                    <button onclick="openModal('<?php echo $f_path; ?>', '<?php echo $b_path; ?>')" class="btn btn-small btn-outline" style="width: 100%; border-color: var(--primary-color); color: var(--primary-color);">
                                        <i class="fas fa-id-card"></i> View IDs
                                    </button>
                                <?php else: ?>
                                    <button disabled class="btn btn-small btn-outline" style="width: 100%; border-color: #eee; color: #ccc; cursor: not-allowed;">
                                        <i class="fas fa-eye-slash"></i> No IDs
                                    </button>
                                <?php endif; ?>

                                <?php if (isSuperAdmin()): ?>
                                    <!-- Edit Button -->
                                    <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="btn btn-small btn-outline" style="text-decoration: none; flex: 1; text-align: center; padding: 6px;"><i class="fas fa-edit"></i> Edit</a>
                                    
                                    <!-- Ban/Unban Button -->
                                    <form action="users.php" method="POST" style="flex: 1; display: flex;">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <?php if ($row['role'] == 'banned'): ?>
                                            <input type="hidden" name="action" value="unban">
                                            <button class="btn btn-small btn-primary" style="background: #e8f5e9; color: #2e7d32; border: 1px solid #2e7d32; width: 100%; padding: 6px;"><i class="fas fa-check"></i> Unban</button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="ban">
                                            <button class="btn btn-small btn-secondary" style="background: #fff2f2; color: var(--danger); border: 1px solid var(--danger); width: 100%; padding: 6px;" onclick="return confirm('Are you sure you want to ban this user?');"><i class="fas fa-ban"></i> Ban</button>
                                        <?php endif; ?>
                                    </form>

                                    <!-- Delete Button -->
                                    <form action="users.php" method="POST" style="flex: 1; display: flex;">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button class="btn btn-small btn-primary" style="background: #6c757d; border: none; width: 100%; padding: 6px;" onclick="return confirm('Are you sure you want to delete this user? This action is irreversible.');"><i class="fas fa-trash-alt"></i> Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="grid-column: 1/-1; text-align: center;">No users found.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Image Modal -->
    <div id="imgModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8);">
        <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 800px; border-radius: 8px; position:relative; text-align: center;">
            <span class="close" onclick="closeModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <h3 style="margin-top: 0;">Identity Documents</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <div>
                    <h4>Front Side</h4>
                    <img id="modalFront" src="" style="max-width: 100%; height: auto; border-radius: 4px; border: 1px solid #ddd;" alt="Front ID">
                </div>
                <div>
                    <h4>Back Side</h4>
                    <img id="modalBack" src="" style="max-width: 100%; height: auto; border-radius: 4px; border: 1px solid #ddd;" alt="Back ID">
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal(front, back) {
            document.getElementById('modalFront').src = front;
            document.getElementById('modalBack').src = back;
            document.getElementById('imgModal').style.display = "block";
        }

        function closeModal() {
            document.getElementById('imgModal').style.display = "none";
        }
        
        window.onclick = function(event) {
            if (event.target.id == 'imgModal') {
                event.target.style.display = "none";
            }
        }
    </script>

    <?php 
    $page_has_sidebar = true;
    include('../includes/footer.php'); 
    ?>
