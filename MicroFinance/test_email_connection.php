<?php
// Simple script to test SMTP Connection
require __DIR__ . '/includes/PHPMailer.php';
require __DIR__ . '/includes/SMTP.php';
require __DIR__ . '/includes/Exception.php';
require __DIR__ . '/includes/config_mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "<h2>SMTP Connection Test</h2>";

if (SMTP_USER === 'your_email@gmail.com') {
    die("<p style='color:red'>Error: You have not configured your email in <b>includes/config_mail.php</b>. Please open that file and set your Gmail and App Password.</p>");
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
    $mail->addAddress(SMTP_USER); // Send to self

    $mail->isHTML(true);
    $mail->Subject = 'MicroFinance SMTP Test';
    $mail->Body    = 'If you are reading this, your email configuration is correct!';

    $mail->send();
    echo "<p style='color:green; font-weight:bold;'>SUCCESS: Email sent successfully! Your configuration is working.</p>";
} catch (Exception $e) {
    echo "<p style='color:red; font-weight:bold;'>FAILED: " . $mail->ErrorInfo . "</p>";
    echo "<p>Common fixes:</p><ul>";
    echo "<li>Check if 'Less Secure Apps' is off (good) and you are using an <b>App Password</b> (required).</li>";
    echo "<li>Verify internet connection.</li>";
    echo "<li>Check if firewall blocks port 587.</li>";
    echo "</ul>";
}
?>

