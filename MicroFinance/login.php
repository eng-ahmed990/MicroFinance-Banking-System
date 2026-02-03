<?php
require('includes/db_connect.php');
require('includes/auth_session.php');

$error_msg = "";

if (isset($_GET['registered'])) {
    $success_msg = "Registration successful! Please login.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed");
    }

    $email = $_POST['email']; // bind_param handles sanitization
    $password = $_POST['password'];

    // Use Prepared Statement
    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            if ($row['role'] == 'banned') {
                $error_msg = "Your account is blocked please visit your nearest branch";
            } else {
                // Prevent Session Fixation
                session_regenerate_id(true);
                
                // Clear any Admin Session Data
                unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email']);

                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['user_email'] = $email;
                $_SESSION['role'] = 'user'; 

                // Check Verification Status
                $stmt_status = $conn->prepare("SELECT verification_status, is_email_verified FROM users WHERE id = ?");
                $stmt_status->bind_param("i", $row['id']);
                $stmt_status->execute();
                $res_status = $stmt_status->get_result();
                $user_data = $res_status->fetch_assoc();
                $v_status = $user_data['verification_status'];
                $is_email_verified = $user_data['is_email_verified'];
                $stmt_status->close();
                
                $_SESSION['verification_status'] = $v_status;

                if ($is_email_verified == 0) {
                    header("Location: otp.php");
                } elseif ($v_status === 'verified') {
                    header("Location: user/dashboard.php");
                } else {
                    // Redirect to KYC page for all other statuses
                    header("Location: user/verify.php");
                }
                exit();
            }
        } else {
            $error_msg = "Invalid email or password."; 
        }
    } else {
        $error_msg = "Invalid email or password."; // Generic error for security
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MicroFinance Bank</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body class="auth-body">
    <header>
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; max-width: 1400px;">
            <div class="logo">
                MicroFinance <span>Banking</span>
            </div>
            <nav>
                <a href="index.php">Home</a>
                <a href="about.php">About</a>
                <a href="register.php">Register</a>
            </nav>
        </div>
    </header>

    <div class="container" style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 24px;">
        <div class="split-card animate-fade" style="max-width: 900px; min-height: auto;">
            <!-- Left Side: Branding -->
            <div class="brand-side" style="padding: 48px 40px;">
                <div class="brand-content">
                    <div class="brand-logo">
                        MicroFinance <span>Banking</span>
                    </div>
                    <p class="brand-text">
                        Welcome back! Access your dashboard to manage loans, track repayments, and stay updated with your financial journey.
                    </p>
                    <ul class="brand-features">
                        <li><i class="fas fa-chart-line"></i> Real-time loan tracking</li>
                        <li><i class="fas fa-bell"></i> Instant notifications</li>
                        <li><i class="fas fa-shield-alt"></i> Secure transactions</li>
                        <li><i class="fas fa-clock"></i> 24/7 account access</li>
                    </ul>
                </div>
            </div>

            <!-- Right Side: Login Form -->
            <div class="form-side" style="padding: 48px 40px;">
                <div class="form-header">
                    <div style="width: 70px; height: 70px; background: var(--primary-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);">
                        <i class="fas fa-user" style="color: white; font-size: 1.75rem;"></i>
                    </div>
                    <h2>Welcome Back</h2>
                    <p>Login to manage your loans and repayments</p>
                </div>
                
                <?php if (isset($success_msg) && $success_msg): ?>
                    <div style="background: var(--success-light); color: #059669; padding: 14px 20px; border-radius: var(--radius); margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_msg): ?>
                    <div style="background: var(--danger-light); color: #dc2626; padding: 14px 20px; border-radius: var(--radius); margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="input-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 400; font-size: 0.9rem; color: var(--text-muted);">
                            <input type="checkbox" style="width: auto; accent-color: var(--primary-color);">
                            Remember me
                        </label>
                        <a href="forgot_password.php" style="font-size: 0.9rem; color: var(--primary-color);">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="padding: 16px; font-size: 1rem;">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>

                    <div class="login-link" style="margin-top: 32px;">
                        <p>New here? <a href="register.php">Create an Account</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div style="position: fixed; bottom: 20px; width: 100%; text-align: center; color: rgba(255,255,255,0.6); font-size: 0.85rem; pointer-events: none;">
        &copy; 2026 MicroFinance Bank. All rights reserved.
    </div>
</body>

</html>
