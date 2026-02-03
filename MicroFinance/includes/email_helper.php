<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/PHPMailer.php';
require __DIR__ . '/SMTP.php';
require __DIR__ . '/Exception.php';
require __DIR__ . '/config_mail.php';

// Private helper for Banking HTML Template
function getBankingTemplate($subject, $content) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 40px; border-radius: 8px; border: 1px solid #e1e4e8; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2c3e50; padding-bottom: 20px; }
            .header h1 { color: #2c3e50; margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; }
            .content { color: #333333; line-height: 1.6; font-size: 16px; }
            .otp-box { background: #f0f4f8; padding: 15px; text-align: center; font-size: 28px; font-weight: bold; margin: 25px 0; letter-spacing: 5px; color: #2c3e50; border-radius: 4px; border: 1px dashed #2c3e50; }
            .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eeeeee; font-size: 12px; color: #7f8c8d; text-align: center; }
            .btn { display: inline-block; background: #2c3e50; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin-top: 20px; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>MicroFinance Bank</h1>
            </div>
            <div class='content'>
                $content
            </div>
            <div class='footer'>
                <p>MicroFinance Bank &bull; Secure Banking System</p>
                <p>This is an automated message. Please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function sendMail($to_email, $subject, $body) {
    // DATABASE SIMULATION MODE (Developer Tool)
    // Emails are logged to 'email_outbox' table and can be viewed at 'local_inbox.php'
    
    global $conn;
    if (!isset($conn)) {
        require(__DIR__ . '/db_connect.php');
    }

    $status = 'simulated';
    $stmt = $conn->prepare("INSERT INTO email_outbox (to_email, subject, body, status) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssss", $to_email, $subject, $body, $status);
        $stmt->execute();
        $stmt->close();
        return true; 
    } else {
        error_log("Failed to log email to DB: " . $conn->error);
        return false;
    }
}

function sendEmailOTP($to_email, $otp_code) {
    $subject = "Account Verification – One-Time Password (OTP)";
    $body = "
    <p>Dear Customer,</p>
    <p>Thank you for registering with MicroFinance Bank.</p>
    <p>To verify your email address and continue the registration process, please use the following One-Time Password (OTP):</p>
    
    <div class='otp-box'>$otp_code</div>
    
    <p>This code is valid for 10 minutes and can be used only once.</p>
    <p>If you did not request this registration, please ignore this email.<br>
    For your security, do not share this code with anyone.</p>
    
    <p>Kind regards,<br>MicroFinance Bank</p>
    ";
    return sendMail($to_email, $subject, $body);
}

function sendWelcomeEmail($to_email, $name) {
    $subject = "Welcome to MicroFinance Bank";
    $body = "
    <p>Dear Customer,</p>
    <p>Welcome to MicroFinance Bank.</p>
    <p>Your email address has been successfully verified, and your account registration is complete.</p>
    <p>Please proceed to upload your identity documents (KYC) to complete the verification process.<br>
    Once submitted, your documents will be reviewed by our administration team.</p>
    <p>Thank you for choosing MicroFinance Bank.</p>
    
    <p>Kind regards,<br>MicroFinance Bank</p>
    ";
    sendMail($to_email, $subject, $body);
}

function sendApprovalEmail($to_email, $name) {
    $subject = "Account Approved – Registration Completed";
    $body = "
    <p>Dear Customer,</p>
    <p>We are pleased to inform you that your account with MicroFinance Bank has been successfully verified and approved.</p>
    <p>You can now log in securely and access all available services.</p>
    <p>If you need any assistance, please contact our support team.</p>
    
    <center><a href='http://localhost/MicroFinance/login.php' target='_blank' class='btn' style='color: white;'>Login Now</a></center>
    
    <p>Thank you for trusting MicroFinance Bank.</p>
    
    <p>Kind regards,<br>MicroFinance Bank</p>
    ";
    $content = getBankingTemplate($subject, $body);
    sendMail($to_email, $subject, $content);
}

function sendKYCPendingEmail($to_email, $name) {
    $subject = "KYC Documents Received – Under Review";
    $body = "
    <p>Dear Customer,</p>
    <p>We have successfully received your identity documents.</p>
    <p>Your account is now under verification review by our administration team.<br>
    You will be notified once the review process is completed.</p>
    <p>Thank you for your patience.</p>
    
    <p>Kind regards,<br>MicroFinance Bank</p>
    ";
    sendMail($to_email, $subject, $body);
}
function sendPasswordResetEmail($to_email, $token) {
    $reset_link = "http://localhost/MicroFinance/reset_password.php?token=" . $token;
    
    $subject = "Password Reset Request";
    $body = "
    <p>Dear Customer,</p>
    <p>We received a request to reset the password for your MicroFinance Bank account.</p>
    <p>Please click the button below to set a new password. This link is valid for 15 minutes.</p>
    
    <center>
        <a href='$reset_link' class='btn' style='color: #ffffff; background-color: #d9534f;'>Reset Verified Password</a>
    </center>
    
    <p style='margin-top: 20px;'>If you did not request this change, please ignore this email. Your account remains secure.</p>
    
    <p>Kind regards,<br>MicroFinance Bank</p>
    ";
    return sendMail($to_email, $subject, $body);
}
function sendRejectionEmail($to_email, $name, $reason) {
    $subject = "Account Application Status";
    $body = "
    <p>Dear $name,</p>
    <p>Thank you for your interest in MicroFinance Bank.</p>
    <p>After reviewing your application, we regret to inform you that we cannot approve your account at this time.</p>
    
    <div style='background: #fff5f5; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0; color: #7f1d1d;'>
        <strong>Reason:</strong><br>
        " . htmlspecialchars($reason) . "
    </div>
    
    <p>Your application data has been removed from our system in accordance with our data privacy policy.</p>
    <p>You are welcome to apply again with correct details/documents.</p>
    
    <p>Regards,<br>MicroFinance Bank Team</p>
    ";
    return sendMail($to_email, $subject, $body);
}
?>
