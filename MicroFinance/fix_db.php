<?php
require('includes/db_connect.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Fix - MicroFinance Bank</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/dist/css/all.min.css">
</head>
<body style="background: var(--bg-color); padding: 40px;">

    <div class="container" style="max-width: 800px;">
        <div class="card">
            <h2 style="margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <i class="fas fa-database" style="color: var(--primary-color); margin-right: 10px;"></i> 
                Database Schema Updater
            </h2>

            <div style="background: #1e293b; color: #a5b4fc; padding: 20px; border-radius: var(--radius); font-family: monospace; font-size: 0.9rem; line-height: 1.8; margin-bottom: 24px;">
            <?php
            // 1. Update 'users' table 'role' column
            echo "> Checking 'role' column in 'users' table...<br>";
            if ($conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'banned') DEFAULT 'user'")) {
                echo "<span style='color: #4ade80;'>[OK]</span> Role column updated.<br>";
            } else {
                echo "<span style='color: #f87171;'>[ERROR]</span> " . $conn->error . "<br>";
            }

            // 2. Add 'profile_pic' to 'users' if not exists
            echo "> Checking 'profile_pic' column...<br>";
            $check_pic = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
            if ($check_pic->num_rows == 0) {
                if ($conn->query("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL AFTER address")) {
                    echo "<span style='color: #4ade80;'>[SUCCESS]</span> Added 'profile_pic' column.<br>";
                }
            } else {
                echo "<span style='color: #94a3b8;'>[INFO]</span> 'profile_pic' column already exists.<br>";
            }

            // 3. Add Loan details to 'loans' table if not exists
            $columns_to_add = [
                'monthly_income' => "DECIMAL(10, 2) DEFAULT 0.00 AFTER duration",
                'employment_status' => "VARCHAR(50) DEFAULT NULL AFTER monthly_income",
                'guarantor' => "VARCHAR(100) DEFAULT NULL AFTER employment_status"
            ];

            foreach ($columns_to_add as $col => $def) {
                echo "> Checking '$col' column for loans...<br>";
                $check = $conn->query("SHOW COLUMNS FROM loans LIKE '$col'");
                if ($check->num_rows == 0) {
                    if ($conn->query("ALTER TABLE loans ADD COLUMN $col $def")) {
                        echo "<span style='color: #4ade80;'>[SUCCESS]</span> Added '$col' column.<br>";
                    } else {
                        echo "<span style='color: #f87171;'>[ERROR]</span> Error adding '$col': " . $conn->error . "<br>";
                    }
                } else {
                    echo "<span style='color: #94a3b8;'>[INFO]</span> '$col' column already exists.<br>";
                }
            }
            
            echo "<br><span style='color: white; font-weight: bold;'>Status: Database check completed.</span>";
            ?>
            </div>

            <div style="display: flex; gap: 16px;">
                <a href="admin/dashboard.php" class="btn btn-primary"><i class="fas fa-th-large"></i> Go to Admin Dashboard</a>
                <a href="index.php" class="btn btn-outline"><i class="fas fa-home"></i> Go Home</a>
            </div>
        </div>
    </div>

</body>
</html>
