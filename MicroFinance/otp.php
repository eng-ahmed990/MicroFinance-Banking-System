<?php
require('includes/db_connect.php');
require('includes/auth_session.php');

    // Verify OTP Logic
    if (isset($_POST['otp'])) {
        $otp_input = trim($_POST['otp']);

        // CASE 1: New Registration (Pending User)
        if (isset($_SESSION['pending_email'])) {
            $email = $_SESSION['pending_email'];
            $stmt = $conn->prepare("SELECT otp_code, otp_expiry FROM pending_users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $stored_otp_hash = $row['otp_code'];
                $expiry = $row['otp_expiry'];

                if (password_verify($otp_input, $stored_otp_hash)) {
                    if (strtotime($expiry) > time()) {
                        // Valid OTP - Move to USERS table
                        
                        // Fetch all pending data
                        $stmt_full = $conn->prepare("SELECT * FROM pending_users WHERE email = ?");
                        $stmt_full->bind_param("s", $email);
                        $stmt_full->execute();
                        $user_data = $stmt_full->get_result()->fetch_assoc();
                        $stmt_full->close();
                        
                        if ($user_data) {
                            // Insert into USERS
                            $stmt_ins = $conn->prepare("INSERT INTO users (name, email, password, phone, cnic, country, dob, address, role, is_email_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
                            $stmt_ins->bind_param("sssssssss", $user_data['name'], $user_data['email'], $user_data['password'], $user_data['phone'], $user_data['cnic'], $user_data['country'], $user_data['dob'], $user_data['address'], $user_data['role']);
                            
                            if ($stmt_ins->execute()) {
                                $new_user_id = $stmt_ins->insert_id;
                                
                                // Delete from PENDING_USERS
                                $stmt_del = $conn->prepare("DELETE FROM pending_users WHERE email = ?");
                                $stmt_del->bind_param("s", $email);
                                $stmt_del->execute();
                                $stmt_del->close();
                                
                                // Login User
                                unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email']);
                                
                                $_SESSION['user_id'] = $new_user_id;
                                $_SESSION['user_name'] = $user_data['name'];
                                $_SESSION['role'] = $user_data['role'];
                                $_SESSION['user_email'] = $user_data['email'];
                                
                                unset($_SESSION['pending_email']);
                                
                                // Send Welcome Email
                                require_once('includes/email_helper.php');
                                sendWelcomeEmail($user_data['email'], $user_data['name']);

                                header("Location: user/verify.php?verified=true");
                                exit();
                            } else {
                                $error_msg = "Database Error: " . $conn->error;
                            }
                            $stmt_ins->close();
                        } else {
                            $error_msg = "Error: Pending user data not found.";
                        }
                    } else {
                        $error_msg = "OTP has expired. Please request a new one.";
                    }
                } else {
                    $error_msg = "Invalid OTP. Please try again.";
                }
            } else {
                $error_msg = "No pending registration found for this email.";
            }
            $stmt->close();

        // CASE 2: Existing User (Login/Reset/Change)
        } elseif (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT otp_code, otp_expiry FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $stored_otp_hash = $row['otp_code'];
                $expiry = $row['otp_expiry'];

                if (password_verify($otp_input, $stored_otp_hash)) {
                    if (strtotime($expiry) > time()) {
                        // Valid OTP - Mark Verified
                        $stmt_upd = $conn->prepare("UPDATE users SET is_email_verified = 1, otp_code = NULL, otp_expiry = NULL WHERE id = ?");
                        $stmt_upd->bind_param("i", $user_id);
                        $stmt_upd->execute();
                        $stmt_upd->close();

                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $error_msg = "OTP has expired.";
                    }
                } else {
                    $error_msg = "Invalid OTP.";
                }
            } else {
                $error_msg = "User not found.";
            }
            $stmt->close();
        } else {
            $error_msg = "Session expired. Please register or login again.";
        }
    }
    
    // Resend Logic
    if (isset($_POST['resend'])) {
        $table = "";
        $where_col = "";
        $identifier = "";

        if (isset($_SESSION['pending_email'])) {
            $table = "pending_users";
            $where_col = "email";
            $identifier = $_SESSION['pending_email'];
            $email_to = $identifier;
        } elseif (isset($_SESSION['user_id'])) {
            $table = "users";
            $where_col = "id";
            $identifier = $_SESSION['user_id'];
            $email_to = $_SESSION['email']; // Assuming check
        }

        if ($table) {
             // Rate Limiting
            $stmt_check = $conn->prepare("SELECT otp_expiry FROM $table WHERE $where_col = ?");
            if ($table == 'users') $stmt_check->bind_param("i", $identifier);
            else $stmt_check->bind_param("s", $identifier);
            
            $stmt_check->execute();
            $res_check = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();

            $can_resend = true;
            if ($res_check && $res_check['otp_expiry']) {
                $expiry_time = strtotime($res_check['otp_expiry']);
                // Expiry is +5 mins. If > time + 4 mins, created < 1 min ago.
                if ($expiry_time > (time() + 240)) {
                    $can_resend = false;
                    $error_msg = "Please wait 1 minute before requesting a new code.";
                }
            }

            if ($can_resend) {
                $otp_code_plain = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp_code_hash = password_hash($otp_code_plain, PASSWORD_DEFAULT);
                $otp_expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));
                
                $stmt_upd = $conn->prepare("UPDATE $table SET otp_code = ?, otp_expiry = ? WHERE $where_col = ?");
                $stmt_upd->bind_param("ss" . ($table=='users'?'i':'s'), $otp_code_hash, $otp_expiry, $identifier);
                if($stmt_upd->execute()) {
                    require_once('includes/email_helper.php');
                    sendEmailOTP($email_to, $otp_code_plain);
                    $success_msg = "New OTP sent.";
                }
                $stmt_upd->close();
            }
        } else {
            $error_msg = "Session expired.";
        }
    }

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - MicroFinance Bank</title>
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
                <a href="logout.php?redirect=index">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container animate-fade" style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 24px;">
        <div class="glass-card text-center" style="max-width: 450px; padding: 40px;">
            <div style="width: 80px; height: 80px; background: var(--secondary-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; box-shadow: 0 10px 30px rgba(17, 153, 142, 0.3);">
                <i class="fas fa-shield-alt" style="color: white; font-size: 2rem;"></i>
            </div>
            
            <h2 style="margin-bottom: 10px;">Verify Your Email</h2>
            <p style="color: var(--text-color); margin-bottom: 30px; opacity: 0.8;">We have sent a 6-digit code to your email address. Please enter it below to verify your account.</p>

            <?php if (isset($error_msg)) echo "<p style='color: red; background: #ffe6e6; padding: 10px; border-radius: 5px;'>$error_msg</p>"; ?>
            <?php if (isset($success_msg)) echo "<p style='color: green; background: #e6ffe6; padding: 10px; border-radius: 5px;'>$success_msg</p>"; ?>

            <form action="otp.php" method="POST">
                <div class="form-group">
                    <input type="text" name="otp" placeholder="000000"
                        style="text-align: center; font-size: 1.5rem; letter-spacing: 8px; font-weight: bold; background: rgba(255,255,255,0.9);"
                        maxlength="6" required pattern="[0-9]{6}" title="Please enter the 6-digit OTP">
                </div>

                <button type="submit" class="btn btn-secondary btn-block" style="font-weight: 600; margin-top: 24px; padding: 14px;">
                    Verify & Activate <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                </button>
            </form>

            <form action="otp.php" method="POST" style="margin-top: 24px;">
                 <input type="hidden" name="resend" value="1">
                 <button type="submit" style="background: none; border: none; font-size: 0.9rem; cursor: pointer; color: var(--primary-color); font-weight: 600; text-decoration: underline;">Resend Code</button>
            </form>
            <div style="margin-top: 15px;">
                <a href="local_inbox.php" target="_blank" style="font-size: 12px; color: #666; text-decoration: none;">[DEV: Open Local Inbox]</a>
            </div>
        </div>
    </div>

</body>

</html>
