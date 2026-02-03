<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireLogin();

$user_id = $_SESSION['user_id'];
// NOTE: Profile should arguably be editable even if pending to fix mistakes, 
// but current requirements say "Block access to all sensitive features".
// Let's block it for now as per "pending verification -> cannot access dashboard, loans, or payments".
// If profile is considered sensitive, block it. Or maybe allow readonly?
// Following strict instruction: "Users with status Pending Verification... Can only access: KYC upload page, Verification status page"
requireVerification($conn); 

$user_name = $_SESSION['user_name']; // Needed for topbar
$unread_count = getUnreadCount($conn, $user_id);
$success_msg = "";
$error_msg = "";

// Fetch Current User Data
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
$profile_pic = $user['profile_pic']; // Needed for topbar

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed");
    }

    $fullname = $_POST['fullname']; // Will bind in prepared statement
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    $password_update = false;
    $hashed_password = "";

    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", $_POST['new_password'])) {
                $error_msg = "Password must be at least 8 characters and include uppercase, lowercase letters, and numbers.";
            } else {
                $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $password_update = true;
            }
        } else {
            $error_msg = "New passwords do not match.";
        }
    }

    if (empty($error_msg)) {
        // Handle Profile Picture Upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            $file_tmp = $_FILES['profile_pic']['tmp_name'];
            $file_size = $_FILES['profile_pic']['size'];
            
            // Validate MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_types)) {
                $error_msg = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
            } elseif ($file_size > $max_size) {
                 $error_msg = "File size exceeds 2MB limit.";
            } else {
                $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                // Safe renaming
                $new_filename = "profile_" . $user_id . "_" . bin2hex(random_bytes(8)) . "." . $ext;
                $destination = "../assets/images/" . $new_filename;

                if (move_uploaded_file($file_tmp, $destination)) {
                    // Update profile pic in DB separately or prepare to update
                    $conn->query("UPDATE users SET profile_pic='$new_filename' WHERE id=$user_id");
                    $profile_pic = $new_filename; 
                } else {
                    $error_msg = "Failed to upload image.";
                }
            }
        }

         if (empty($error_msg)) {
            if ($password_update) {
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, address=?, password=? WHERE id=?");
                $stmt->bind_param("sssssi", $fullname, $email, $phone, $address, $hashed_password, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, address=? WHERE id=?");
                $stmt->bind_param("ssssi", $fullname, $email, $phone, $address, $user_id);
            }

            if ($stmt->execute()) {
                $success_msg = "Profile updated successfully.";
                // Refresh data
                $result = $conn->query($sql);
                $user = $result->fetch_assoc();
                $_SESSION['user_name'] = $fullname;
                $user_name = $fullname;
            } else {
                $error_msg = "Error updating profile. Please try again.";
                error_log("Profile Update Error: " . $conn->error);
            }
            $stmt->close();
        }
    }
}

$page_title = "My Profile";
?>
<!DOCTYPE html>
<html lang="en">

<?php require('../includes/header_head.php'); ?>

<body class="user-page-body">

    <div class="dashboard-container">
        <?php include('../includes/sidebar_user.php'); ?>

        <main class="main-content">
            <?php include('../includes/topbar.php'); ?>

            <?php if ($success_msg): ?>
                <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #059669; padding: 16px 24px; margin-bottom: 24px; border-radius: var(--radius-lg); display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-check-circle" style="font-size: 1.25rem;"></i>
                    <span><?php echo $success_msg; ?></span>
                </div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #dc2626; padding: 16px 24px; margin-bottom: 24px; border-radius: var(--radius-lg); display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-exclamation-circle" style="font-size: 1.25rem;"></i>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="profile-header-card">
                <div class="profile-avatar" onclick="document.getElementById('profile_upload').click()">
                    <?php if (!empty($user['profile_pic'])): ?>
                        <img id="profile_preview" src="../assets/images/<?php echo $user['profile_pic']; ?>" alt="Profile">
                    <?php else: ?>
                        <img id="profile_preview" src="https://via.placeholder.com/150" alt="Profile" style="display:none;">
                        <i id="default_icon" class="fas fa-user" style="font-size: 3rem; color: var(--primary-color);"></i>
                    <?php endif; ?>
                    
                    <div class="upload-overlay">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>

                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-id-badge"></i> CNIC: <?php echo htmlspecialchars($user['cnic']); ?>
                    </span>
                    <div style="margin-top: 16px; display: flex; gap: 12px;">
                        <span class="badge badge-success" style="padding: 8px 16px; font-size: 0.85rem; background: rgba(255,255,255,0.2); color: white;">
                            <i class="fas fa-check-circle" style="margin-right: 6px;"></i>Verified Account
                        </span>
                    </div>
                </div>
            </div>

            <div class="modern-form-card">
                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <h3 style="color: var(--primary-color); border-bottom: 2px solid var(--border-color); padding-bottom: 16px; margin-bottom: 28px; display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%); border-radius: var(--radius); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user-edit" style="color: var(--primary-color);"></i>
                        </div>
                        Personal Details
                    </h3>
                    
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Hidden File Input -->
                    <input type="file" id="profile_upload" name="profile_pic" accept="image/*" style="display: none;" onchange="previewImage(this)">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                        <div class="form-group">
                            <label>Full Name</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <div class="input-with-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <div class="input-with-icon">
                                <i class="fas fa-phone"></i>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <div class="input-with-icon">
                                <i class="fas fa-map-marker-alt"></i>
                                <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Enter your address">
                            </div>
                        </div>
                    </div>

                    <h3 style="margin-top: 48px; color: var(--primary-color); border-bottom: 2px solid var(--border-color); padding-bottom: 16px; margin-bottom: 28px; display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, rgba(17, 153, 142, 0.1) 0%, rgba(56, 239, 125, 0.1) 100%); border-radius: var(--radius); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-shield-alt" style="color: var(--secondary-color);"></i>
                        </div>
                        Security Settings
                    </h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                        <div class="form-group">
                            <label>New Password</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="new_password" placeholder="Leave blank to keep current">
                            </div>
                            <small class="password-hint" style="display: block; margin-top: 6px; font-size: 0.8rem; color: var(--text-muted);">
                                Password must be at least 8 characters and include uppercase, lowercase letters, and numbers.
                            </small>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="confirm_password" placeholder="Re-type new password">
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 40px; display: flex; justify-content: flex-end; gap: 12px;">
                        <a href="dashboard.php" class="btn btn-outline" style="border-radius: var(--radius-full);">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" style="border-radius: var(--radius-full);">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php 
    $page_has_sidebar = true;
    include('../includes/footer.php'); 
    ?>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var preview = document.getElementById('profile_preview');
                    var icon = document.getElementById('default_icon');
                    
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    if (icon) icon.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Password Validation
        const passwordInput = document.querySelector('input[name="new_password"]');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const val = this.value;
                if (val === "") {
                    this.style.borderColor = 'var(--border-color)';
                    this.setCustomValidity('');
                    return;
                }
                
                const hasLength = val.length >= 8;
                const hasUpper = /[A-Z]/.test(val);
                const hasLower = /[a-z]/.test(val);
                const hasNumber = /\d/.test(val);
                
                if (hasLength && hasUpper && hasLower && hasNumber) {
                    this.style.borderColor = 'var(--success)';
                    this.setCustomValidity('');
                    const hint = document.querySelector('.password-hint');
                    if(hint) hint.style.color = 'var(--success)';
                } else {
                    this.style.borderColor = 'var(--danger)';
                    this.setCustomValidity('Password must be at least 8 characters and include uppercase, lowercase letters, and numbers.');
                    const hint = document.querySelector('.password-hint');
                    if(hint) hint.style.color = 'var(--danger)';
                }
            });
        }
    </script>
</body>

</html>
