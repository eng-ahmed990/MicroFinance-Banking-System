<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireSuperAdmin();

$admin_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Fetch Admin
// Fetch Admin
$stmt = $conn->prepare("SELECT * FROM admins WHERE id=?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if (!$admin) {
    die("Admin not found.");
}

$msg = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed");
    }

    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $cnic = $_POST['cnic'];
    $email = $_POST['email'];
    
    $password_update = false;
    $hashed_password = "";

    if (!empty($_POST['password'])) {
        $pass_input = $_POST['password'];
        if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", $pass_input)) {
            $error = "Password must be at least 8 characters and include uppercase, lowercase letters, and numbers.";
        } else {
            $hashed_password = password_hash($pass_input, PASSWORD_DEFAULT);
            $password_update = true;
        }
    }

    if (empty($error)) {
        if ($password_update) {
            $stmt_update = $conn->prepare("UPDATE admins SET name=?, phone=?, cnic=?, email=?, password=? WHERE id=?");
            $stmt_update->bind_param("sssssi", $name, $phone, $cnic, $email, $hashed_password, $admin_id);
        } else {
            $stmt_update = $conn->prepare("UPDATE admins SET name=?, phone=?, cnic=?, email=? WHERE id=?");
            $stmt_update->bind_param("ssssi", $name, $phone, $cnic, $email, $admin_id);
        }

        if ($stmt_update->execute()) {
            $msg = "Admin details updated successfully.";
            // Refresh data
            $admin['name'] = $name;
            $admin['phone'] = $phone;
            $admin['cnic'] = $cnic;
            $admin['email'] = $email;
        } else {
            $error = "Error updating database: " . $conn->error;
        }
        $stmt_update->close();
    }
}

$page_title = "Edit Admin";
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
            
            <div class="container" style="max-width: 800px; margin: 0 auto;">
                <div class="card" style="padding: 40px;">
                    <div class="flex-between" style="border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 30px;">
                        <h2 style="margin: 0; color: var(--primary-color);">Edit Admin: <?php echo htmlspecialchars($admin['name']); ?></h2>
                        <a href="manage_admins.php" class="btn btn-outline btn-small"><i class="fas fa-arrow-left"></i> Back to List</a>
                    </div>

                    <?php if ($msg): ?>
                        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 5px;"><?php echo $msg; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 5px;"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                        </div>
                        <div class="form-group" style="margin-top: 15px;">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                             <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>" required class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                            </div>
                            <div class="form-group">
                                <label>CNIC</label>
                                <input type="text" name="cnic" value="<?php echo htmlspecialchars($admin['cnic']); ?>" required class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #eee;">
                            <label>Reset Password (Leave blank to keep current)</label>
                            <input type="password" name="password" id="admin-pass" placeholder="New Password" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                            <small class="password-hint" style="display: block; margin-top: 6px; font-size: 0.8rem; color: var(--text-muted);">
                                Password must be at least 8 characters and include uppercase, lowercase letters, and numbers.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 25px; padding: 12px; width: 100%;">Update Admin Profile</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <?php 
    $page_has_sidebar = true;
    include('../includes/footer.php'); 
    ?>
    <!-- Frontend Validation Script -->
    <script>
        const passwordInput = document.getElementById('admin-pass');
        const hint = document.querySelector('.password-hint');

        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const val = this.value;
                if (val.length === 0) {
                     this.style.borderColor = '#ddd';
                     hint.style.color = 'var(--text-muted)';
                     this.setCustomValidity('');
                     return;
                }

                const hasLength = val.length >= 8;
                const hasUpper = /[A-Z]/.test(val);
                const hasLower = /[a-z]/.test(val);
                const hasNumber = /\d/.test(val);
                
                if (hasLength && hasUpper && hasLower && hasNumber) {
                    this.style.borderColor = 'var(--success)';
                    hint.style.color = 'var(--success)';
                    this.setCustomValidity('');
                } else {
                    this.style.borderColor = 'var(--danger)';
                    hint.style.color = 'var(--danger)';
                    this.setCustomValidity('Password must be at least 8 characters and include uppercase, lowercase letters, and numbers.');
                }
            });
        }
    </script>
</body>
</html>
