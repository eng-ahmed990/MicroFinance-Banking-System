<?php
require('includes/db_connect.php');
require('includes/auth_session.php');

$msg = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            $user_id = $user['id'];
            
            // Generate Token
            $token = bin2hex(random_bytes(32)); // 64 chars
            $token_hash = hash('sha256', $token);
            $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));
            
            // Store Hash in DB
            $stmt_up = $conn->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?");
            $stmt_up->bind_param("ssi", $token_hash, $expiry, $user_id);
            if ($stmt_up->execute()) {
                // Send Email with original token
                require_once('includes/email_helper.php');
                if (sendPasswordResetEmail($email, $token)) {
                    $msg = "A password reset link has been sent to <b>$email</b>. Please check your inbox (Local Inbox).";
                    $msg_type = "success";
                } else {
                    $msg = "Failed to send email. Please try again.";
                    $msg_type = "error";
                }
            } else {
                $msg = "Database error. Please try again.";
                $msg_type = "error";
            }
        } else {
            // Security: Generic message
            $msg = "If an account exists with this email, a reset link has been sent.";
            $msg_type = "success";
        }
        $stmt->close();
    } else {
        $msg = "Invalid email format.";
        $msg_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - MicroFinance</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="auth-body">
    <div class="container animate-fade" style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
        <div class="glass-card" style="max-width: 450px; width: 100%; padding: 40px;">
            <div class="text-center mb-30">
                <div style="width: 70px; height: 70px; background: rgba(239, 68, 68, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i class="fas fa-lock-open" style="color: #ef4444; font-size: 1.8rem;"></i>
                </div>
                <h2>Forgot Password?</h2>
                <p style="color: var(--text-muted);">Enter your email address and we'll send you a link to reset your password.</p>
            </div>

            <?php if ($msg): ?>
                <div class="alert" style="padding: 15px; border-radius: 8px; margin-bottom: 20px; <?php echo ($msg_type == 'success') ? 'background: #dcfce7; color: #166534;' : 'background: #fee2e2; color: #991b1b;'; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" required placeholder="Enter your registered email">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="padding: 14px;">
                    Send Reset Link
                </button>
            </form>
            
            <div class="text-center mt-30">
                <a href="login.php" style="font-weight: 600; color: var(--text-muted);"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>
            
             <div class="text-center mt-20">
                <a href="local_inbox.php" target="_blank" style="font-size: 12px; color: #999;">[DEV: Open Local Inbox]</a>
            </div>
        </div>
    </div>
</body>
</html>
