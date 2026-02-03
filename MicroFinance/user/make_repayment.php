<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$unread_count = getUnreadCount($conn, $user_id);

// Fetch User Data for Profile Pic
$sql_user = "SELECT profile_pic FROM users WHERE id = $user_id";
$res_user = $conn->query($sql_user);
$u_data = $res_user->fetch_assoc();
$profile_pic = $u_data['profile_pic'] ?? null;

$success_msg = "";
$error_msg = "";

// Get active loans (Use Prepared Statement for consistency)
$stmt = $conn->prepare("SELECT id, amount, status FROM loans WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_loans = $stmt->get_result();
$stmt->close(); // Close immediately to free resources

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $loan_id = (int)$_POST['loan_id']; // Cast to int for safety
    $amount = (float)$_POST['amount'];
    $method = sanitize($conn, $_POST['method']);
    
    // Verify Loan Ownership (IDOR Prevention)
    $stmt_verify = $conn->prepare("SELECT id FROM loans WHERE id = ? AND user_id = ?");
    $stmt_verify->bind_param("ii", $loan_id, $user_id);
    $stmt_verify->execute();
    $stmt_verify->store_result();
    
    if ($stmt_verify->num_rows > 0) {
        $stmt_verify->close();
        
        // Insert Repayment Using Prepared Statement
        $stmt_insert = $conn->prepare("INSERT INTO repayments (loan_id, user_id, amount, payment_date, method, status) VALUES (?, ?, ?, NOW(), ?, 'pending')");
        $stmt_insert->bind_param("iids", $loan_id, $user_id, $amount, $method);
        
        if ($stmt_insert->execute()) {
            $success_msg = "Payment submitted for verification.";
        } else {
            $error_msg = "Error submitting payment: " . $conn->error;
        }
        $stmt_insert->close();
    } else {
        $stmt_verify->close();
        $error_msg = "Invalid Loan ID selected.";
    }
}

$page_title = "Make Repayment";
?>
<!DOCTYPE html>
<html lang="en">

<?php require('../includes/header_head.php'); ?>

<body class="user-page-body">

    <div class="dashboard-container">
        <?php include('../includes/sidebar_user.php'); ?>

        <main class="main-content">
            <?php include('../includes/topbar.php'); ?>

            <div class="flex-between" style="margin-bottom: 24px;">
                <h2 style="margin: 0;">Submit Repayment</h2>
                <a href="repayments.php" class="btn btn-outline btn-small"><i class="fas fa-arrow-left"></i> Back to Schedule</a>
            </div>
            
            <?php if ($success_msg): ?>
                <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
             <?php if ($error_msg): ?>
                <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="modern-form-card" style="max-width: 600px; padding: 30px;">
                <form action="make_repayment.php" method="POST">
                    <div class="form-group">
                        <label>Select Loan ID</label>
                        <select name="loan_id" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ddd;">
                            <?php 
                            if ($result_loans->num_rows > 0) {
                                while($row = $result_loans->fetch_assoc()) {
                                    echo "<option value='".$row['id']."'>#LN-".$row['id']." (PKR ".number_format($row['amount'],2).")</option>";
                                } 
                            } else {
                                echo "<option value=''>No Active Loans</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Amount to Pay (PKR)</label>
                        <div class="input-with-icon">
                            <i class="fas fa-money-bill-wave"></i>
                            <input type="number" name="amount" value="150" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="method" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ddd;">
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="EasyPaisa / JazzCash">EasyPaisa / JazzCash</option>
                            <option value="Credit/Debit Card">Credit/Debit Card</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Transaction ID / Reference No.</label>
                        <div class="input-with-icon">
                            <i class="fas fa-fingerprint"></i>
                            <input type="text" name="transaction_id" placeholder="e.g. TXN-12345678">
                        </div>
                    </div>

                    <button class="btn btn-primary btn-block" style="padding: 12px; font-weight: bold; margin-top: 20px;">
                        Submit Payment <i class="fas fa-paper-plane" style="margin-left: 8px;"></i>
                    </button>
                </form>
            </div>
        </main>
    </div>

    <?php 
    $page_has_sidebar = true;
    include('../includes/footer.php'); 
    ?>
</body>
</html>
