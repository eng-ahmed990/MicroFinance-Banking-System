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

$page_title = "Loan Calculator";
?>
<!DOCTYPE html>
<html lang="en">

<?php require('../includes/header_head.php'); ?>

<body class="user-page-body">

    <div class="dashboard-container">
        <?php include('../includes/sidebar_user.php'); ?>

        <main class="main-content">
            <?php include('../includes/topbar.php'); ?>

            <div class="calculator-grid">
                <!-- Inputs -->
                <div class="calc-input-section">
                    <h3 style="margin-bottom: 28px; color: var(--text-color); display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-sliders-h" style="color: var(--primary-color);"></i>
                        Loan Details
                    </h3>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-money-bill-wave" style="color: var(--secondary-color);"></i>
                            Loan Amount (PKR)
                        </label>
                        <input type="number" id="calc-amount" value="50000" style="font-size: 1.1rem; padding: 16px;">
                        <div style="display: flex; gap: 8px; margin-top: 12px;">
                            <button type="button" onclick="setAmount(25000)" class="btn btn-outline btn-small" style="flex: 1; border-radius: var(--radius-full);">25K</button>
                            <button type="button" onclick="setAmount(50000)" class="btn btn-outline btn-small" style="flex: 1; border-radius: var(--radius-full);">50K</button>
                            <button type="button" onclick="setAmount(100000)" class="btn btn-outline btn-small" style="flex: 1; border-radius: var(--radius-full);">100K</button>
                            <button type="button" onclick="setAmount(200000)" class="btn btn-outline btn-small" style="flex: 1; border-radius: var(--radius-full);">200K</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-percent" style="color: var(--secondary-color);"></i>
                            Interest Rate (% per year)
                        </label>
                        <input type="number" id="calc-rate" value="10" step="0.5" style="font-size: 1.1rem; padding: 16px;">
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-calendar-alt" style="color: var(--secondary-color);"></i>
                            Duration (Months)
                        </label>
                        <input type="number" id="calc-months" value="12" style="font-size: 1.1rem; padding: 16px;">
                        <div style="display: flex; gap: 8px; margin-top: 12px;">
                            <button type="button" onclick="setMonths(6)" class="btn btn-outline btn-small" style="flex: 1; border-radius: var(--radius-full);">6M</button>
                            <button type="button" onclick="setMonths(12)" class="btn btn-outline btn-small" style="flex: 1; border-radius: var(--radius-full);">12M</button>
                            <button type="button" onclick="setMonths(24)" class="btn btn-outline btn-small" style="flex: 1; border-radius: var(--radius-full);">24M</button>
                            <button type="button" onclick="setMonths(36)" class="btn btn-outline btn-small" style="flex: 1; border-radius: var(--radius-full);">36M</button>
                        </div>
                    </div>
                    
                    <button class="btn btn-primary btn-block" onclick="calculateEMI()" style="margin-top: 28px; padding: 16px; font-size: 1rem; border-radius: var(--radius);">
                        <i class="fas fa-calculator"></i> Calculate Payment
                    </button>
                    
                    <div style="margin-top: 24px; padding: 16px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%); border-radius: var(--radius); border: 1px dashed var(--border-color);">
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-info-circle" style="color: var(--primary-color);"></i>
                            Interest rates are indicative and subject to change based on your credit profile.
                        </p>
                    </div>
                </div>

                <!-- Results -->
                <div class="result-card">
                    <div style="position: relative; z-index: 1;">
                        <h3 style="color: white; margin-bottom: 24px; font-size: 1.1rem; opacity: 0.9;">
                            <i class="fas fa-chart-pie" style="margin-right: 10px;"></i>Estimated Repayment
                        </h3>
                        
                        <div style="margin-bottom: 8px; font-size: 0.9rem; opacity: 0.7;">Monthly EMI</div>
                        <div id="res-emi" class="emi-amount" style="font-size: 2.75rem;">PKR 0.00</div>

                        <div style="margin-top: 40px;">
                            <div class="result-row">
                                <span class="result-label"><i class="fas fa-percentage" style="margin-right: 8px; opacity: 0.7;"></i>Total Interest</span>
                                <span class="result-value" id="res-interest">PKR 0.00</span>
                            </div>
                            <div class="result-row">
                                <span class="result-label"><i class="fas fa-wallet" style="margin-right: 8px; opacity: 0.7;"></i>Principal Amount</span>
                                <span class="result-value" id="res-principal">PKR 0.00</span>
                            </div>
                            <div class="result-row" style="border-top: 2px solid rgba(255,255,255,0.2); margin-top: 16px; padding-top: 20px; border-bottom: none;">
                                <span class="result-label" style="font-size: 1.05rem; font-weight: 600;"><i class="fas fa-coins" style="margin-right: 8px;"></i>Total Payable</span>
                                <span class="result-value" id="res-total" style="font-size: 1.25rem; color: var(--accent-gold);">PKR 0.00</span>
                            </div>
                        </div>
                        
                        <a href="loan_application.php" class="btn btn-white btn-block" style="margin-top: 32px; border-radius: var(--radius);">
                            <i class="fas fa-arrow-right"></i> Apply Now
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php 
    $page_has_sidebar = true;
    include('../includes/footer.php'); 
    ?>

    <script>
        function setAmount(val) {
            document.getElementById('calc-amount').value = val;
            calculateEMI();
        }
        
        function setMonths(val) {
            document.getElementById('calc-months').value = val;
            calculateEMI();
        }
        
        function calculateEMI() {
            const amount = parseFloat(document.getElementById('calc-amount').value);
            const rate = parseFloat(document.getElementById('calc-rate').value) / 100 / 12;
            const months = parseFloat(document.getElementById('calc-months').value);

            if (amount && rate && months) {
                const x = Math.pow(1 + rate, months);
                const emi = (amount * x * rate) / (x - 1);
                const total = emi * months;
                const interest = total - amount;

                document.getElementById('res-emi').innerText = 'PKR ' + emi.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('res-total').innerText = 'PKR ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('res-interest').innerText = 'PKR ' + interest.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('res-principal').innerText = 'PKR ' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
        }
        
        // Add event listeners for real-time calculation
        document.getElementById('calc-amount').addEventListener('input', calculateEMI);
        document.getElementById('calc-rate').addEventListener('input', calculateEMI);
        document.getElementById('calc-months').addEventListener('input', calculateEMI);
        
        // Run on load
        calculateEMI();
    </script>
</body>

</html>
