<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireAdmin();

$page_title = "Admin Profile";
$admin_id = $_SESSION['admin_id'];
$msg = "";
$status = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize($conn, $_POST['name']);
    $phone = sanitize($conn, $_POST['phone']);
    $password = $_POST['password'];

    if (!empty($password)) {
        if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", $password)) {
            $msg = "Password must be at least 8 characters and include uppercase, lowercase letters, and numbers.";
            $status = "error";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET name='$name', phone='$phone', password='$hashed_password' WHERE id=$admin_id";
        }
    } else {
        $sql = "UPDATE users SET name='$name', phone='$phone' WHERE id=$admin_id";
    }

    if ($conn->query($sql) === TRUE) {
        $_SESSION['name'] = $name; // Update session name
        $msg = "Profile updated successfully!";
        $status = "success";
    } else {
        $msg = "Error updating profile: " . $conn->error;
        $status = "error";
    }
}

// Fetch admin details
$sql = "SELECT * FROM users WHERE id=$admin_id";
$result = $conn->query($sql);
$admin = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">

<?php require('../includes/header_head.php'); ?>

<body class="admin-page-body">

    <div class="dashboard-container">
        <?php include('../includes/sidebar_admin.php'); ?>

        <main class="main-content">
            <?php include('../includes/topbar_admin.php'); ?>

            <div class="flex-between mb-30">
                <h2>My Profile</h2>
            </div>
            
            <?php if ($msg): ?>
                <div class="alert alert-<?php echo $status; ?>" style="background: <?php echo $status == 'success' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $status == 'success' ? '#065f46' : '#991b1b'; ?>; padding: 15px; border-radius: var(--radius); margin-bottom: 24px;">
                    <i class="fas <?php echo $status == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div class="card" style="max-width: 800px;">
                <div style="display: flex; gap: 30px; align-items: flex-start;">
                    <div style="width: 120px; height: 120px; background: var(--primary-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-lg);">
                        <i class="fas fa-user-shield" style="font-size: 3rem; color: white;"></i>
                    </div>
                    
                    <div style="flex: 1;">
                        <form action="profile.php" method="POST">
                            <div class="form-group">
                                <label for="email">Email (Cannot be changed)</label>
                                <div class="input-wrapper">
                                    <input type="email" value="<?php echo htmlspecialchars($admin['email']); ?>" disabled style="background: #f1f5f9; cursor: not-allowed;">
                                    <i class="fas fa-envelope"></i>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <div class="input-wrapper">
                                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <div class="input-wrapper">
                                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>" required>
                                    <i class="fas fa-phone"></i>
                                </div>
                            </div>

                            <div style="margin: 30px 0; border-top: 1px solid var(--border-color); padding-top: 20px;">
                                <h4 style="margin-bottom: 20px; color: var(--primary-color);">Security Settings</h4>
                                <div class="form-group">
                                    <label for="password">New Password (Leave blank to keep current)</label>
                                    <div class="input-wrapper">
                                        <input type="password" id="password" name="password" placeholder="Enter new password">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 5px;">Password must be at least 8 characters and include uppercase, lowercase letters, and numbers.</p>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const val = this.value;
                if (val === "") {
                    this.style.borderColor = 'var(--border-color)';
                    this.parentElement.querySelector('.input-error-msg')?.remove();
                    return;
                }
                
                const hasLength = val.length >= 8;
                const hasUpper = /[A-Z]/.test(val);
                const hasLower = /[a-z]/.test(val);
                const hasNumber = /\d/.test(val);
                
                if (hasLength && hasUpper && hasLower && hasNumber) {
                    this.style.borderColor = 'var(--success)';
                } else {
                    this.style.borderColor = 'var(--danger)';
                    this.setCustomValidity('Password must be at least 8 characters and include uppercase, lowercase letters, and numbers.');
                }
            });
        }
    </script>
</body>
</html>
