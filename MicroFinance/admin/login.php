<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');

$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email']; // bind_param handles sanitization
    $password = $_POST['password'];

    // Use Prepared Statement, Query 'admins' table
    $stmt = $conn->prepare("SELECT id, name, email, password FROM admins WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Prevent Session Fixation
            session_regenerate_id(true);
            
            // Clear any User Session Data
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['email'], $_SESSION['role']);

            $_SESSION['admin_id'] = $row['id']; 
            $_SESSION['admin_name'] = $row['name'];
            $_SESSION['admin_email'] = $row['email'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error_msg = "Invalid password.";
        }
    } else {
        $error_msg = "Admin account not found or access denied.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MicroFinance Bank</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body class="auth-body" style="justify-content: center; align-items: center;">

    <div class="container animate-fade" style="max-width: 480px; margin: auto; padding: 24px;">
        <div class="glass-card" style="padding: 48px 40px; border-radius: var(--radius-xl);">
            <div class="text-center" style="margin-bottom: 32px;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);">
                    <i class="fas fa-user-shield" style="color: white; font-size: 2rem;"></i>
                </div>
                <h2 style="color: var(--text-color); margin-bottom: 8px;">Admin Portal</h2>
                <p style="color: var(--text-muted);">Secure access for administrators</p>
                
                <?php if ($error_msg): ?>
                    <div style="background: var(--danger-light); color: #dc2626; padding: 14px 20px; border-radius: var(--radius); margin-top: 20px; display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>
            </div>

            <form action="login.php" method="POST">
                <div class="input-group">
                    <label for="admin-user">Admin Email</label>
                    <div class="input-wrapper">
                        <input type="email" id="admin-user" name="email" placeholder="Enter your email" required>
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label for="admin-pass">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="admin-pass" name="password" placeholder="Enter your password" required>
                        <i class="fas fa-lock"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-block" style="background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); color: white; padding: 16px; margin-top: 24px; font-size: 1rem; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);">
                    <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i> Access Admin Panel
                </button>
            </form>
            
            <div style="margin-top: 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; padding-top: 24px; border-top: 1px solid var(--border-color);">

            </div>
        </div>
        
        <!-- Footer Info -->
        <div style="text-align: center; margin-top: 40px; color: rgba(255,255,255,0.6); font-size: 0.85rem;">
            <p style="margin-bottom: 8px;"><strong style="color: white;">MicroFinance Bank PLC</strong></p>
            <p>123 Financial District, Banking Plaza</p>
            <p style="margin-top: 16px; font-size: 0.8rem; color: rgba(255,255,255,0.4);">Licensed by the Central Regulatory Authority</p>
        </div>
    </div>

</body>

</html>
