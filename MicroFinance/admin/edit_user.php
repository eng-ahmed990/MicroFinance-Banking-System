<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireAdmin();

$user_id = isset($_GET['id']) ? sanitize($conn, $_GET['id']) : null;
$msg = "";
$error = "";

if (!$user_id) {
    header("Location: users.php");
    exit();
}

// Fetch User
$sql = "SELECT * FROM users WHERE id=$user_id";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    echo "User not found.";
    exit();
}
$user = $result->fetch_assoc();

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed");
    }

    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $cnic = $_POST['cnic'];
    
    // Use Prepared Statement
    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, cnic=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $email, $phone, $cnic, $user_id);
    
    if ($stmt->execute()) {
        $msg = "User details updated successfully.";
        // Refresh
        $user['name'] = $name;
        $user['email'] = $email;
        $user['phone'] = $phone;
        $user['cnic'] = $cnic;
    } else {
        $error = "Error updating user: " . $conn->error;
    }
    $stmt->close();
}

$page_title = "Edit User";
// Dummy admin name for this page since we don't include topbar, or we should?
// The design in original was centered card. I'll transform it to dashboard layout for consistency.
$admin_name = $_SESSION['name'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">

<?php require('../includes/header_head.php'); ?>

<body class="user-page-body">

    <div class="dashboard-container">
        <?php include('../includes/sidebar_admin.php'); ?>

        <main class="main-content">
            <?php include('../includes/topbar_admin.php'); ?>

            <div class="container" style="max-width: 800px; margin: 0 auto;">
                <div class="card" style="padding: 40px 60px; text-align: left;">
                    <div class="flex-between" style="border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 30px; gap: 20px; width: 100%;">
                        <h2 style="margin: 0; font-size: 1.8rem; color: var(--primary-color);">Edit User: <?php echo htmlspecialchars($user['name']); ?></h2>
                        <a href="users.php" class="btn btn-outline btn-small" style="white-space: nowrap; border-radius: 20px;"><i class="fa-solid fa-arrow-left"></i> Back</a>
                    </div>

                    <?php if ($msg): ?>
                        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 5px;"><?php echo $msg; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 5px;"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="edit_user.php?id=<?php echo $user_id; ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>CNIC</label>
                            <input type="text" name="cnic" value="<?php echo htmlspecialchars($user['cnic']); ?>" required>
                        </div>
                        
                        <div style="margin-top: 30px;">
                            <button class="btn btn-primary btn-block" style="padding: 15px; font-weight: 600; border-radius: 12px;">Update User Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <?php 
    $page_has_sidebar = true;
    include('../includes/footer.php'); 
    ?>
