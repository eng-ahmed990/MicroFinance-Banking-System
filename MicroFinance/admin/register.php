<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireSuperAdmin(); // Only Super Admin can create new admins

$error_msg = "";
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed. Please refresh the page.");
    }

    $fullname = sanitize($conn, $_POST['fullname']);
    $cnic = sanitize($conn, $_POST['cnic']);
    $phone = sanitize($conn, $_POST['phone']);
    $email = sanitize($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Backend Validation
    if ($confirm_password !== $password) {
        $error_msg = "Passwords do not match.";
    } elseif (!preg_match("/^[0-9-]+$/", $cnic)) {
        $error_msg = "CNIC should only contain numbers and hyphens.";
    } elseif (!preg_match("/^[0-9+ ]+$/", $phone)) {
        $error_msg = "Phone number should only contain numbers, spaces, or + sign.";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", $password)) {
        $error_msg = "Password must be at least 8 characters and include uppercase, lowercase letters, and numbers.";
    } else {
        // Check if email or CNIC already exists in ADMINS table
        $stmt_check = $conn->prepare("SELECT id FROM admins WHERE email = ? OR cnic = ?");
        $stmt_check->bind_param("ss", $email, $cnic);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error_msg = "Email or CNIC already registered as Admin.";
        } else {
            // Check cross-role conflict in USERS table
            $stmt_user = $conn->prepare("SELECT id FROM users WHERE email = ? OR cnic = ?");
            $stmt_user->bind_param("ss", $email, $cnic);
            $stmt_user->execute();
            
            if ($stmt_user->get_result()->num_rows > 0) {
                 $error_msg = "Email or CNIC is already in use by a Customer account. Separation rules prevent using the same credentials.";
                 $stmt_user->close();
            } else {
                $stmt_user->close();
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new Admin into ADMINS table
                $stmt_insert = $conn->prepare("INSERT INTO admins (name, email, password, phone, cnic) VALUES (?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("sssss", $fullname, $email, $hashed_password, $phone, $cnic);

                if ($stmt_insert->execute()) {
                    $success_msg = "New Admin account created successfully!";
                } else {
                    $error_msg = "Error creating admin: " . $conn->error;
                }
                $stmt_insert->close();
            }
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Admin - MicroFinance Bank</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body class="user-page-body">

    <div class="dashboard-container">
        <?php include('../includes/sidebar_admin.php'); ?>

        <main class="main-content">
            <?php include('../includes/topbar_admin.php'); ?>
            
            <div class="flex-between">
                <h2>Create Admin Account</h2>
                <a href="manage_admins.php" class="btn btn-outline btn-small"><i class="fas fa-arrow-left"></i> Back to List</a>
            </div>

            <div class="card" style="max-width: 800px; margin: 20px auto;">
                <div class="form-header" style="text-align: center; margin-bottom: 30px;">
                    <div style="width: 70px; height: 70px; background: var(--secondary-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: white; font-size: 1.8rem;">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3>New Administrator Details</h3>
                    <p style="color: var(--text-muted);">This user will have full access to the system.</p>
                </div>

                <?php if ($error_msg): ?>
                    <div style="background: var(--danger-light); color: #dc2626; padding: 15px; border-radius: var(--radius); margin-bottom: 20px; text-align: center;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success_msg): ?>
                    <div style="background: var(--success-light); color: #059669; padding: 15px; border-radius: var(--radius); margin-bottom: 20px; text-align: center;">
                        <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="input-group">
                        <label for="fullname">Full Name</label>
                        <div class="input-wrapper">
                            <input type="text" id="fullname" name="fullname" placeholder="Admin Name" required>
                            <i class="fas fa-user"></i>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <label for="cnic">CNIC Number</label>
                            <div class="input-wrapper">
                                <input type="text" id="cnic" name="cnic" placeholder="12345-1234567-1" pattern="[0-9-]+" title="Only numbers and hyphens allowed" oninput="this.value = this.value.replace(/[^0-9-]/g, '')" required>
                                <i class="fas fa-id-card"></i>
                            </div>
                        </div>
                        <div class="input-group">
                            <label for="phone">Phone Number</label>
                            <div class="input-wrapper">
                                <input type="text" id="phone" name="phone" placeholder="0300-1234567" pattern="[0-9+ ]+" title="Only numbers, spaces, and + allowed" oninput="this.value = this.value.replace(/[^0-9+ ]/g, '')" required>
                                <i class="fas fa-phone"></i>
                            </div>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="email">Admin Email</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" placeholder="admin@microfinance.com" required>
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <label for="password">Password</label>
                            <div class="input-wrapper">
                                <input type="password" id="password" name="password" placeholder="Create password" required>
                                <i class="fas fa-lock"></i>
                                <i class="fas fa-eye" id="togglePassword" style="left: auto; right: 16px; cursor: pointer; z-index: 10;"></i>
                            </div>
                            <small class="password-hint" style="display: block; margin-top: 6px; font-size: 0.8rem; color: var(--text-muted);">
                                Password must be at least 8 characters and include uppercase, lowercase letters, and numbers.
                            </small>
                        </div>
                        <div class="input-group">
                            <label for="confirm-password">Confirm Password</label>
                            <div class="input-wrapper">
                                <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm password" required>
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-block" style="background: var(--secondary-gradient); color: white; margin-top: 20px; padding: 14px;">
                        <i class="fas fa-user-plus" style="margin-right: 8px;"></i> Create Administrator
                    </button>
                </form>
            </div>
        </main>
    </div>

    <!-- Frontend Validation & Toggle Script -->
    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        const hint = document.querySelector('.password-hint');

        // Toggle Password
        if (togglePassword) {
            togglePassword.addEventListener('click', function (e) {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
            });
        }

        // Real-time Validation
        passwordInput.addEventListener('input', function() {
            const val = this.value;
            const hasLength = val.length >= 8;
            const hasUpper = /[A-Z]/.test(val);
            const hasLower = /[a-z]/.test(val);
            const hasNumber = /\d/.test(val);
            
            if (hasLength && hasUpper && hasLower && hasNumber) {
                this.style.borderColor = 'var(--success)';
                hint.style.color = 'var(--success)';
                this.setCustomValidity('');
            } else {
                this.style.borderColor = 'var(--danger)';
                hint.style.color = 'var(--danger)';
                this.setCustomValidity('Password must be at least 8 characters and include uppercase, lowercase letters, and numbers.');
            }
        });
    </script>
</body>
</html>
