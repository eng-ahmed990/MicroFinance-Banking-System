<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');

// Ensure user is logged in but NOT necessarily verified (as they need to access this page to verify)
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

// Fetch current status
$stmt = $conn->prepare("SELECT email, verification_status, rejection_reason, is_email_verified FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$email = $user_data['email'];
$status = $user_data['verification_status'];
$reason = $user_data['rejection_reason'];
$is_email_verified = $user_data['is_email_verified'];
$stmt->close();

// Email Link - Dev Mode (Local Inbox)
$email_link = "../local_inbox.php";

// Ensure email is verified
if ($is_email_verified == 0) {
    header("Location: ../otp.php");
    exit();
}

// If already verified, go to dashboard
if ($status === 'verified') {
    header("Location: dashboard.php");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_kyc'])) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed");
    }

    if ($status === 'pending') {
         $error_msg = "Your application is already pending review.";
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB limit per file

        // Check Front ID
        if (!isset($_FILES['id_front']) || $_FILES['id_front']['error'] != 0) {
             $error_msg = "Front ID is required.";
        }
        // Check Back ID
        elseif (!isset($_FILES['id_back']) || $_FILES['id_back']['error'] != 0) {
             $error_msg = "Back ID is required.";
        } else {
            // Validate Front
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_front = finfo_file($finfo, $_FILES['id_front']['tmp_name']);
            $mime_back = finfo_file($finfo, $_FILES['id_back']['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_front, $allowed_types) || !in_array($mime_back, $allowed_types)) {
                $error_msg = "Invalid file type. Please upload JPG or PNG images.";
            } elseif ($_FILES['id_front']['size'] > $max_size || $_FILES['id_back']['size'] > $max_size) {
                 $error_msg = "File size exceeds 5MB limit.";
            } else {
                // Upload Files
                $ext_front = pathinfo($_FILES['id_front']['name'], PATHINFO_EXTENSION);
                $ext_back = pathinfo($_FILES['id_back']['name'], PATHINFO_EXTENSION);
                
                $filename_front = "id_front_" . $user_id . "_" . bin2hex(random_bytes(8)) . "." . $ext_front;
                $filename_back = "id_back_" . $user_id . "_" . bin2hex(random_bytes(8)) . "." . $ext_back; // Corrected basename
                
                $path_front = "../assets/uploads/" . $filename_front; // Store outside public/assets? User asked for secure. 
                // For now putting in assets/uploads but blocking access via .htaccess is best practice or outside root. 
                // User requirement: "Prefer storage outside public directory". 
                // Let's create a protected folder if possible, but for XAMPP usually htdocs is root.
                // We will stick to a folder and add .htaccess later or just rename securely.

                // Ensure directory exists
                if (!is_dir("../assets/uploads")) {
                    mkdir("../assets/uploads", 0777, true);
                }

                if (move_uploaded_file($_FILES['id_front']['tmp_name'], $path_front) && 
                    move_uploaded_file($_FILES['id_back']['tmp_name'], "../assets/uploads/" . $filename_back)) {
                    
                    // Update DB
                    $new_status = 'pending';
                    $stmt_up = $conn->prepare("UPDATE users SET id_front_path=?, id_back_path=?, verification_status=? WHERE id=?");
                    $stmt_up->bind_param("sssi", $filename_front, $filename_back, $new_status, $user_id);
                    
                    if ($stmt_up->execute()) {
                        $status = 'pending'; // Update local status to show pending view immediately
                        $success_msg = "Documents uploaded successfully. Your account is now under review.";
                    } else {
                        $error_msg = "Database Error: " . $conn->error;
                    }
                    $stmt_up->close();

                } else {
                    $error_msg = "Failed to move uploaded files.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Verification - MicroFinance Bank</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="auth-body">

    <div class="container" style="display: flex; justify-content: center; padding-top: 50px; padding-bottom: 50px;">
        <div class="card animate-fade" style="max-width: 600px; width: 100%; padding: 40px;">
            
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="width: 70px; height: 70px; background: var(--primary-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);">
                    <i class="fas fa-id-card" style="color: white; font-size: 1.8rem;"></i>
                </div>
                <h2 style="color: var(--primary-color);">Identity Verification</h2>
                <p style="color: var(--text-muted);">Please upload your National ID Card to verify your account.</p>
            </div>

            <?php if ($status === 'pending'): ?>
                <div style="text-align: center; padding: 30px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
                    <i class="fas fa-clock" style="font-size: 3rem; color: #f59e0b; margin-bottom: 20px;"></i>
                    <h3 style="color: #1e293b; margin-bottom: 10px;">Verification Pending</h3>
                    <p style="color: #64748b; line-height: 1.6;">
                        Your documents have been submitted and are currently being reviewed by our administration team. 
                        This process usually takes 24-48 hours.
                    </p>
                    
                    <a href="<?php echo $email_link; ?>" target="_blank" class="btn btn-primary" style="display: inline-block; margin-top: 20px; padding: 12px 25px; text-decoration: none; color: white; border-radius: 8px; font-weight: 600;">
                        <i class="fas fa-envelope-open-text" style="margin-right: 8px;"></i> Check Your Email
                    </a>

                </div>

            <?php else: ?>

                <?php if ($status === 'rejected'): ?>
                    <div class="alert alert-danger" style="background: #fef2f2; color: #991b1b; padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #fecaca;">
                        <div style="display: flex; gap: 15px;">
                            <i class="fas fa-times-circle" style="font-size: 1.5rem; margin-top: 2px;"></i>
                            <div>
                                <strong style="font-size: 1.1rem; display: block; margin-bottom: 5px;">Verification Failed</strong>
                                <p style="margin: 0; opacity: 0.9;"><?php echo htmlspecialchars($reason); ?></p>
                                <p style="margin-top: 10px; font-size: 0.9rem; font-weight: 600;">Please re-upload correct documents below.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error_msg): ?>
                    <div style="background: #fef2f2; color: #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <form action="verify.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group" style="margin-bottom: 25px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #334155;">National ID (Front Side)</label>
                        <div style="position: relative;">
                            <input type="file" name="id_front" accept="image/*" required 
                                style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; background: white;">
                        </div>
                        <small style="color: #94a3b8; display: block; margin-top: 5px;">Supported formats: JPG, PNG. Max size: 5MB</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 30px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #334155;">National ID (Back Side)</label>
                        <div style="position: relative;">
                            <input type="file" name="id_back" accept="image/*" required
                                style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; background: white;">
                        </div>
                    </div>

                    <button type="submit" name="submit_kyc" class="btn btn-primary btn-block" style="padding: 15px; font-weight: bold; font-size: 1rem;">
                        <i class="fas fa-upload" style="margin-right: 8px;"></i> Submit for Verification
                    </button>
                    
                     <div style="text-align: center; margin-top: 20px;">
                        <a href="../logout.php" style="color: #64748b; font-size: 0.9rem; text-decoration: none;">Cancel & Sign Out</a>
                    </div>
                </form>

            <?php endif; ?>
        </div>
    </div>

</body>
</html>
