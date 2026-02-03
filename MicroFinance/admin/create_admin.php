<?php
require('../includes/auth_session.php');
require('../includes/db_connect.php');

// Check if ANY admin already exists
$check_admin = $conn->query("SELECT id FROM users WHERE role='admin' LIMIT 1");
if ($check_admin && $check_admin->num_rows > 0) {
    die("<div style='font-family: sans-serif; text-align: center; padding: 50px;'>
            <h2 style='color: #dc2626;'>Setup Disabled</h2>
            <p>An administrator account already exists. For security reasons, this setup utility is now locked.</p>
            <a href='login.php' style='color: #2563eb; text-decoration: none;'>Go to Admin Login</a>
         </div>");
}

// SAFETY LOCK: Comment out the line below to run this script.
//die("Security Alert: This script is disabled for safety. Open the file and comment out line " . __LINE__ . " to use it.");

// Configuration
$admin_name = "System Admin";
$admin_email = "admin@microfinance.com";
$admin_password = "admin123"; // Default password
$admin_phone = "+92 300 0000000";
$admin_cnic = "00000-0000000-0";
$msg = "";
$status = "";

if (isset($_POST['create_admin'])) {
    // Hash the password
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

    // Check if admin exists in admins table
    $stmt_check = $conn->prepare("SELECT id FROM admins WHERE email=?");
    $stmt_check->bind_param("s", $admin_email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Update existing admin
        $stmt_check->close();
        $stmt_update = $conn->prepare("UPDATE admins SET password=? WHERE email=?");
        $stmt_update->bind_param("ss", $hashed_password, $admin_email);
        
        if ($stmt_update->execute()) {
            $msg = "Admin user '<strong>$admin_email</strong>' updated successfully.<br>Password reset to: <strong>$admin_password</strong>";
            $status = "success";
        } else {
            $msg = "Error updating record: " . $conn->error;
            $status = "error";
        }
        $stmt_update->close();
    } else {
        // Check conflict with Users table
        $stmt_user_check = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt_user_check->bind_param("s", $admin_email);
        $stmt_user_check->execute();
        if ($stmt_user_check->get_result()->num_rows > 0) {
            $msg = "Error: Email '<strong>$admin_email</strong>' is already used by a Customer account.";
            $status = "error";
            $stmt_user_check->close();
        } else {
            $stmt_user_check->close();
            // Create new admin
            $stmt_check->close(); // Ensure closed
            $stmt_insert = $conn->prepare("INSERT INTO admins (name, email, password, phone, cnic) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("sssss", $admin_name, $admin_email, $hashed_password, $admin_phone, $admin_cnic);
            
            if ($stmt_insert->execute()) {
                $msg = "New Admin user '<strong>$admin_email</strong>' created successfully.<br>Password: <strong>$admin_password</strong>";
                $status = "success";
            } else {
                $msg = "Error creating record: " . $conn->error;
                $status = "error";
            }
            $stmt_insert->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin - MicroFinance Bank</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/dist/css/all.min.css">
</head>
<body style="background: var(--bg-color); display: flex; align-items: center; justify-content: center; min-height: 100vh;">

    <div class="card" style="max-width: 500px; width: 100%; text-align: center;">
        <div style="width: 70px; height: 70px; background: var(--primary-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: white; font-size: 1.8rem;">
            <i class="fas fa-user-shield"></i>
        </div>
        <h2>Admin Setup Utility</h2>
        <p style="color: var(--text-muted); margin-bottom: 30px;">Initialize the default system administrator account.</p>

        <?php if ($msg): ?>
            <div style="background: <?php echo $status == 'success' ? 'var(--success-light)' : 'var(--danger-light)'; ?>; 
                        color: <?php echo $status == 'success' ? '#064e3b' : '#991b1b'; ?>; 
                        padding: 15px; border-radius: var(--radius); margin-bottom: 20px; text-align: left;">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div style="background: rgba(0,0,0,0.03); padding: 15px; border-radius: var(--radius); margin-bottom: 24px; text-align: left;">
            <p style="margin-bottom: 8px;"><strong>Default Config:</strong></p>
            <p style="margin-bottom: 4px; font-size: 0.9rem;">Email: <code style="background: #eee; padding: 2px 5px; border-radius: 4px;"><?php echo $admin_email; ?></code></p>
            <p style="margin: 0; font-size: 0.9rem;">Pass: <code style="background: #eee; padding: 2px 5px; border-radius: 4px;"><?php echo $admin_password; ?></code></p>
        </div>

        <form method="POST">
            <button type="submit" name="create_admin" class="btn btn-primary btn-block">
                <i class="fas fa-cogs"></i> Run Setup / Reset Admin
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--border-color);">
            <a href="login.php" class="btn btn-outline btn-block">Go to Admin Login</a>
        </div>
    </div>

</body>
</html>
