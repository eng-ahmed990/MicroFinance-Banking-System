<?php
require('includes/db_connect.php');
require('includes/auth_session.php');

$msg = "";
$msg_type = "";
$token = $_GET['token'] ?? "";

if (empty($token)) {
    die("Invalid request."); // Or redirect
}

// Check Token Validity
// Check Token Validity
$token_hash = hash('sha256', $token);

// Fetch token details regardless of expiry to debug/validate properly
$stmt = $conn->prepare("SELECT id, reset_token_expires_at FROM users WHERE reset_token_hash = ?");
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><link rel='stylesheet' href='assets/css/style.css'><title>Invalid Link</title></head><body class='auth-body'><div class='container' style='text-align:center; padding-top:100px;'><div class='glass-card' style='display:inline-block; padding:40px;'><h2 style='color:#dc2626'>Invalid Link</h2><p>This password reset link is invalid.</p><a href='forgot_password.php' class='btn btn-primary'>Request New Link</a></div></div></body></html>");
}

$user = $res->fetch_assoc();
$user_id = $user['id'];
$expiry = strtotime($user['reset_token_expires_at']);

// Check Expiry (PHP Time vs DB Time)
if (time() > $expiry) {
    die("<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><link rel='stylesheet' href='assets/css/style.css'><title>Expired Link</title></head><body class='auth-body'><div class='container' style='text-align:center; padding-top:100px;'><div class='glass-card' style='display:inline-block; padding:40px;'><h2 style='color:#dc2626'>Link Expired</h2><p>This password reset link has expired.</p><a href='forgot_password.php' class='btn btn-primary'>Request New Link</a></div></div></body></html>");
}

$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($pass !== $confirm_pass) {
        $msg = "Passwords do not match.";
        $msg_type = "error";
    } elseif (!preg_match("/^(?=.*[A-Za-z])(?=.*\d).{8,}$/", $pass)) {
        $msg = "Password must be at least 8 characters long and include letters and numbers.";
        $msg_type = "error";
    } else {
        // Update Password
        $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
        
        // Clear token
        $stmt_up = $conn->prepare("UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?");
        $stmt_up->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt_up->execute()) {
            $msg = "Password has been reset successfully!";
            $msg_type = "success";
            // Optional: Send confirmation email
        } else {
            $msg = "Error updating password.";
            $msg_type = "error";
        }
        $stmt_up->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - MicroFinance</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="auth-body">
    <div class="container animate-fade" style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
        <div class="glass-card" style="max-width: 450px; width: 100%; padding: 40px;">
            <div class="text-center mb-30">
                <h2 style="margin-bottom: 10px;">Set New Password</h2>
                <p style="color: var(--text-muted);">Please create a strong password for your account.</p>
            </div>

            <?php if ($msg): ?>
                <div class="alert" style="padding: 15px; border-radius: 8px; margin-bottom: 20px; <?php echo ($msg_type == 'success') ? 'background: #dcfce7; color: #166534;' : 'background: #fee2e2; color: #991b1b;'; ?>">
                    <?php echo $msg; ?>
                    <?php if ($msg_type == 'success'): ?>
                        <br><br>
                        <a href="http://localhost/MicroFinance/login.php" class="btn btn-primary btn-block" target="_self">Go to Login</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($msg_type !== 'success'): ?>
            <form action="" method="POST">
                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" required placeholder="New password (8+ chars, letters & numbers)">
                    </div>
                    <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 5px;">Must be at least 8 characters with letters & numbers.</small>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" required placeholder="Confirm new password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="padding: 14px;">
                    Reset Password
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
