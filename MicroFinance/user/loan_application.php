<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireLogin();

$user_id = $_SESSION['user_id'];
requireVerification($conn); // Enforce KYC
$user_name = $_SESSION['user_name'];
$unread_count = getUnreadCount($conn, $user_id);

// Fetch User Data for Profile Pic
$sql_user = "SELECT profile_pic FROM users WHERE id = $user_id";
$res_user = $conn->query($sql_user);
$u_data = $res_user->fetch_assoc();
$profile_pic = $u_data['profile_pic'] ?? null;

$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed");
    }

    $amount = (float)sanitize($conn, $_POST['amount']);
    $purpose_desc = sanitize($conn, $_POST['purpose']);
    $duration = sanitize($conn, $_POST['duration']);
    
    // Additional fields (concatenated to purpose for now)
    $income = (float)sanitize($conn, $_POST['income']);
    $employment = sanitize($conn, $_POST['employment']);
    $guarantor = sanitize($conn, $_POST['guarantor']);

    // Check Active Loans (Pending/Approved)
    $stmt_active = $conn->prepare("SELECT id FROM loans WHERE user_id = ? AND status IN ('pending', 'approved')");
    $stmt_active->bind_param("i", $user_id);
    $stmt_active->execute();
    $stmt_active->store_result();
    $active_loans_count = $stmt_active->num_rows;
    $stmt_active->close();

    // Check Daily Limit (Max 2)
    $stmt_daily = $conn->prepare("SELECT COUNT(*) as count FROM loans WHERE user_id = ? AND DATE(applied_at) = CURDATE()");
    $stmt_daily->bind_param("i", $user_id);
    $stmt_daily->execute();
    $daily_res = $stmt_daily->get_result()->fetch_assoc();
    $daily_limit_count = $daily_res['count'];
    $stmt_daily->close();

    // Validation
    if ($active_loans_count > 0) {
        $error_msg = "You already have an active loan (Pending or Approved). You must clear it before applying again.";
    } elseif ($daily_limit_count >= 2) {
        $error_msg = "Daily application limit reached. You can only submit 2 applications per day.";
    } elseif ($amount <= 0) {
        $error_msg = "Loan amount must be greater than zero.";
    } elseif ($income <= 0) {
        $error_msg = "Please enter a valid monthly income.";
    } elseif ($amount > ($income * 5)) {
        $max_allowed = $income * 5;
        $error_msg = "Loan amount exceeds the maximum limit (5x your monthly income). Your current limit is PKR " . number_format($max_allowed, 2);
    } else {
        // Use Prepared Statement for Insertion
        $stmt = $conn->prepare("INSERT INTO loans (user_id, amount, purpose, duration, monthly_income, employment_status, guarantor, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("idssdss", $user_id, $amount, $purpose_desc, $duration, $income, $employment, $guarantor);

        if ($stmt->execute()) {
            $success_msg = "Loan application submitted successfully!";
        } else {
            $error_msg = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

$page_title = "Apply Loan";
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
                <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="modern-form-card">
                <form action="loan_application.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div style="display: grid; grid-template-columns: 1fr 350px; gap: 40px;">
                        <!-- Left Column: Form Fields -->
                        <div>
                            <div class="alert alert-info" style="margin-bottom: 25px;">
                                <i class="fas fa-info-circle"></i> <strong>Note:</strong> Ensure your profile is up to
                                date before applying.
                            </div>

                            <h3 style="color: var(--primary-color);">Loan Details</h3>
                            <div class="form-group">
                                <label>Loan Amount Required (PKR)</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <input type="number" name="amount" id="loan_amount" placeholder="Min: 500" min="500" required>
                                </div>
                                <small id="limit_text" style="color: #666; display: block; margin-top: 5px;"></small>
                            </div>

                            <div class="form-group">
                                <label>Purpose of Loan</label>
                                <textarea name="purpose" rows="3"
                                    style="width: 100%; border-radius: 8px; padding: 10px; border: 1px solid #ddd;"
                                    placeholder="Describe usage (e.g., Business expansion)" required></textarea>
                            </div>

                            <h3 style="color: var(--primary-color); margin-top: 30px;">Financial Info</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label>Monthly Income (PKR)</label>
                                    <input type="number" name="income" id="monthly_income" placeholder="Enter your income" min="1" required>
                                </div>
                                <div class="form-group">
                                    <label>Employment Status</label>
                                    <select name="employment">
                                        <option value="Salaried">Salaried</option>
                                        <option value="Self-Employed">Self-Employed</option>
                                        <option value="Business Owner">Business Owner</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Summary/Extra -->
                        <div style="background: #f8fafc; padding: 25px; border-radius: 12px; height: fit-content;">
                            <h4 style="color: var(--primary-color);">Loan Configuration</h4>

                            <div class="form-group">
                                <label>Duration</label>
                                <select name="duration" style="background: white;">
                                    <option value="6">6 Months</option>
                                    <option value="12">12 Months</option>
                                    <option value="24">24 Months</option>
                                    <option value="36">36 Months</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Guarantor (Optional)</label>
                                <input type="text" name="guarantor" placeholder="Name">
                            </div>

                            <div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
                                <button class="btn btn-primary btn-block" style="padding: 12px; font-weight: bold;">
                                    Submit Application <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
                                </button>
                            </div>
                        </div>
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
        const incomeInput = document.getElementById('monthly_income');
        const amountInput = document.getElementById('loan_amount');
        const limitText = document.getElementById('limit_text');

        function updateLimit() {
            const income = parseFloat(incomeInput.value) || 0;
            const maxLoan = income * 5;
            
            if (income > 0) {
                limitText.innerHTML = `Your maximum loan limit is <strong>PKR ${maxLoan.toLocaleString()}</strong> (5x income)`;
                amountInput.max = maxLoan;
            } else {
                limitText.textContent = 'Enter monthly income to see your limit';
                amountInput.removeAttribute('max');
            }
        }

        incomeInput.addEventListener('input', updateLimit);
        
        amountInput.addEventListener('input', function() {
            const income = parseFloat(incomeInput.value) || 0;
            const amount = parseFloat(amountInput.value) || 0;
            const maxLoan = income * 5;

            if (income > 0 && amount > maxLoan) {
                this.style.borderColor = 'red';
                limitText.style.color = 'red';
            } else {
                this.style.borderColor = '';
                limitText.style.color = '#666';
            }
        });
    </script>
</body>

</html>
