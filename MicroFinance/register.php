<?php
require('includes/db_connect.php');
require('includes/auth_session.php');

$error_msg = "";
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed");
    }

    $fullname = sanitize($conn, $_POST['fullname']);
    $cnic = sanitize($conn, $_POST['cnic']);
    $phone = sanitize($conn, $_POST['phone']);
    $email = sanitize($conn, $_POST['email']);
    $country = sanitize($conn, $_POST['country']);
    $dob = sanitize($conn, $_POST['dob']);
    $address = sanitize($conn, $_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    $today = date("Y-m-d");
    
    if ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } elseif (!preg_match("/^[0-9-]+$/", $cnic)) {
        $error_msg = "CNIC should only contain numbers and hyphens.";
    } elseif (!preg_match("/^[0-9+ ]+$/", $phone)) {
        $error_msg = "Phone number should only contain numbers, spaces, or + sign.";
    } elseif (empty($country)) {
        $error_msg = "Please select your country.";
    } elseif (empty($dob) || $dob >= $today) {
        $error_msg = "Please enter a valid Date of Birth (must be in the past).";
    } elseif (empty($address)) {
        $error_msg = "Address is required.";
    } elseif (!preg_match("/^(?=.*[A-Za-z])(?=.*\d).{8,}$/", $password)) {
        $error_msg = "Password must be at least 8 characters long and include letters and numbers.";
    } else {
        // Check availability
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? OR cnic = ?");
        $stmt_check->bind_param("ss", $email, $cnic);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error_msg = "Email or CNIC already registered.";
            $stmt_check->close();
        } else {
            $stmt_check->close();

            // Check Pending Users (Cleanup or Error)
            $stmt_pending = $conn->prepare("SELECT id, email FROM pending_users WHERE email = ? OR cnic = ?");
            $stmt_pending->bind_param("ss", $email, $cnic);
            $stmt_pending->execute();
            $res_pending = $stmt_pending->get_result();
            if ($res_pending->num_rows > 0) {
                $p_row = $res_pending->fetch_assoc();
                if ($p_row['email'] == $email) {
                    // Same email - Cleanup previous attempt so we can insert new
                    $conn->query("DELETE FROM pending_users WHERE id = " . $p_row['id']);
                } else {
                    // Different email matched CNIC -> Conflict
                    $error_msg = "CNIC already exists in a pending registration.";
                    $stmt_pending->close();
                    // Don't close stmt_admin yet because we haven't opened it used it effectively here? 
                    // Actually existing structure check for admin is next.
                    // We need to flow correctly.
                }
            }
            $stmt_pending->close();

            if (empty($error_msg)) {
                // Check Admins Table for Email Uniqueness
                $stmt_admin = $conn->prepare("SELECT id FROM admins WHERE email = ?");
                $stmt_admin->bind_param("s", $email);
                $stmt_admin->execute();
                if ($stmt_admin->get_result()->num_rows > 0) {
                    $error_msg = "Email already registered as Administrator.";
                    $stmt_admin->close();
                } else {
                    $stmt_admin->close();
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $role = 'user'; 

                    // OTP Generation (5 Min Expiry)
                    $otp_code_plain = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                    $otp_code_hash = password_hash($otp_code_plain, PASSWORD_DEFAULT);
                    $otp_expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));
                    $is_email_verified = 0;

                    // Insert User (Store Hash)
                    $stmt_insert = $conn->prepare("INSERT INTO pending_users (name, email, password, phone, cnic, country, dob, address, role, otp_code, otp_expiry, is_email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_insert->bind_param("sssssssssssi", $fullname, $email, $hashed_password, $phone, $cnic, $country, $dob, $address, $role, $otp_code_hash, $otp_expiry, $is_email_verified);

                    if ($stmt_insert->execute()) {
                        // SET SESSION FOR OTP (Use pending_email, NOT user_id)
                        $_SESSION['pending_email'] = $email;
                        $_SESSION['email'] = $email; // For resend logic fallback
 
                
                // Send OTP Email (Send Plain)
                require_once('includes/email_helper.php');
                sendEmailOTP($email, $otp_code_plain);

                // Redirect to OTP Verification page
                header("Location: otp.php");
                exit();
            } else {
                $error_msg = "Error: " . $conn->error;
            }
                $stmt_insert->close();
            }
        } // Close if (empty($error_msg))
        
        $stmt_check->close(); 
    } // Close Availability Else
    } // Close Validation Else
} // Close POST
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Account - MicroFinance Bank</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<style>
        body {
            background-color: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .register-card {
            width: 100%;
            max-width: 650px; /* Slightly narrower for better vertical proportions */
            background: white;
            padding: 48px;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); /* Deep shadow */
        }

        .form-header-icon {
            width: 72px;
            height: 72px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: white;
            font-size: 1.75rem;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .form-grid-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .input-wrapper input, .input-wrapper select {
            background: #f8fafc;
            border-color: #e2e8f0;
            transition: all 0.3s ease;
        }

        .input-wrapper input:focus, .input-wrapper select:focus {
            background: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        @media (max-width: 768px) {
            .register-card {
                padding: 30px 20px;
            }
            .form-grid-row {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
    </style>
</head>
<body class="auth-body">

    <div class="register-card animate-fade">
        
        <!-- Header Section -->
        <div class="form-header" style="text-align: center; margin-bottom: 32px;">
            <div class="form-header-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2 style="font-size: 1.75rem; margin-bottom: 8px; color: var(--text-color); font-weight: 700;">Create New Account</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Create a new user account for a bank client.</p>
        
            <?php if ($error_msg): ?>
                <div style="background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 12px; margin-top: 20px; font-size: 0.9rem; border: 1px solid #fecaca; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>
        </div>

        <form action="register.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <!-- Full Name -->
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <div class="input-wrapper">
                    <input type="text" id="fullname" name="fullname" placeholder="Client Name" required>
                    <i class="fas fa-user" style="color: #94a3b8;"></i>
                </div>
            </div>

            <!-- CNIC & Phone -->
            <div class="form-grid-row">
                <div class="input-group">
                    <label for="cnic">CNIC Number</label>
                    <div class="input-wrapper">
                        <input type="text" id="cnic" name="cnic" placeholder="12345-1234567-1" pattern="[0-9-]+" title="Only numbers and hyphens allowed" oninput="this.value = this.value.replace(/[^0-9-]/g, '')" required>
                        <i class="fas fa-id-card" style="color: #94a3b8;"></i>
                    </div>
                </div>
                <div class="input-group">
                    <label for="phone">Phone Number</label>
                    <div class="input-wrapper">
                        <input type="text" id="phone" name="phone" placeholder="0300-1234567" pattern="[0-9+ ]+" title="Only numbers, spaces, and + allowed" oninput="this.value = this.value.replace(/[^0-9+ ]/g, '')" required>
                        <i class="fas fa-phone" style="color: #94a3b8;"></i>
                    </div>
                </div>
            </div>

            <!-- Email Address -->
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" placeholder="client@example.com" required>
                    <i class="fas fa-envelope" style="color: #94a3b8;"></i>
                </div>
            </div>

            <!-- Country & DOB -->
            <div class="form-grid-row">
                <div class="input-group">
                    <label for="country">Country</label>
                    <div class="input-wrapper">
                        <select id="country" name="country" required style="width: 100%; padding: 14px 16px; padding-left: 48px; border-radius: 12px; appearance: none; -webkit-appearance: none; cursor: pointer;">
                            <option value="" disabled selected>Select Country</option>
                            <option value="Pakistan">Pakistan</option>
                            <option value="USA">United States</option>
                            <option value="UK">United Kingdom</option>
                            <option value="Canada">Canada</option>
                            <option value="UAE">UAE</option>
                            <option value="Saudi Arabia">Saudi Arabia</option>
                            <option value="China">China</option>
                            <option value="Germany">Germany</option>
                            <option value="Australia">Australia</option>
                        </select>
                        <i class="fas fa-globe" style="color: #94a3b8;"></i>
                        <i class="fas fa-chevron-down" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #94a3b8;"></i>
                    </div>
                </div>
                <div class="input-group">
                    <label for="dob">Date of Birth</label>
                    <div class="input-wrapper">
                        <input type="date" id="dob" name="dob" required max="<?php echo date('Y-m-d'); ?>">
                        <i class="fas fa-calendar-alt" style="color: #94a3b8;"></i>
                    </div>
                </div>
            </div>

            <!-- Address (New Field) -->
            <div class="form-group">
                <label for="address">Residential Address</label>
                <div class="input-wrapper">
                    <input type="text" id="address" name="address" placeholder="House #, Street, City" required>
                    <i class="fas fa-map-marker-alt" style="color: #94a3b8;"></i>
                </div>
            </div>

            <!-- Passwords -->
            <div class="form-grid-row" style="margin-bottom: 8px;">
                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="Create password" required>
                        <i class="fas fa-lock" style="color: #94a3b8;"></i>
                        <i class="fas fa-eye" id="togglePassword" style="left: auto; right: 16px; cursor: pointer; z-index: 10; color: #94a3b8;"></i>
                    </div>
                </div>
                <div class="input-group">
                    <label for="confirm-password">Confirm Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm password" required>
                        <i class="fas fa-check-circle" style="color: #94a3b8;"></i>
                        <i class="fas fa-eye" id="toggleConfirmPassword" style="left: auto; right: 16px; cursor: pointer; z-index: 10; color: #94a3b8;"></i>
                    </div>
                </div>
            </div>
            
            <small class="password-hint" style="display: block; margin-bottom: 32px; font-size: 0.85rem; color: var(--text-muted); padding-left: 4px;">
                <i class="fas fa-info-circle"></i> Must be at least 8 characters with letters & numbers.
            </small>

            <button type="submit" class="btn btn-block" style="background: var(--primary-gradient); color: white; padding: 16px; font-size: 1rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); transition: transform 0.2s;">
                <i class="fas fa-plus-circle" style="margin-right: 8px;"></i> Create Client Account
            </button>

            <div style="text-align: center; margin-top: 28px;">
                <p style="color: var(--text-muted); font-size: 0.95rem;">Already have an account? <a href="login.php" style="font-weight: 600; color: var(--primary-color);">Sign in</a></p>
            </div>
        </form>
    </div>

    <!-- Additional Footer for Credibility -->
    <div style="position: fixed; bottom: 20px; text-align: center; width: 100%; color: rgba(255,255,255,0.4); font-size: 0.8rem; pointer-events: none;">
        &copy; 2026 MicroFinance Banking System. All rights reserved.
    </div>

    <script>
        // Password Toggle for Main Password
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        
        if (togglePassword) {
            togglePassword.addEventListener('click', function (e) {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
            });
        }

        // Password Toggle for Confirm Password
        const confirmInput = document.getElementById('confirm-password');
        const toggleConfirm = document.getElementById('toggleConfirmPassword');

        if (toggleConfirm) {
            toggleConfirm.addEventListener('click', function (e) {
                const type = confirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmInput.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
            });
        }

        // Real-time Validation
        const hint = document.querySelector('.password-hint');
        passwordInput.addEventListener('input', function() {
            const val = this.value;
            const hasLength = val.length >= 8;
            const hasLetter = /[A-Za-z]/.test(val);
            const hasNumber = /\d/.test(val);
            
            if (hasLength && hasLetter && hasNumber) {
                this.style.borderColor = 'var(--success)';
                hint.style.color = 'var(--success)';
                this.setCustomValidity('');
            } else {
                this.style.borderColor = 'var(--danger)'; // Will rely on focus style overlap or add specific error class
                hint.style.color = 'var(--text-muted)'; // Keep neutral until valid, or red if submitted? User asked for helper text.
                // Keeping it dynamic as requested before:
                hint.style.color = 'var(--danger)'; 
                if(val.length === 0) hint.style.color = 'var(--text-muted)'; // Reset if empty
                
                this.setCustomValidity('Password must be 8+ chars with letters and numbers');
            }
        });

        // Set Max Date for DOB to today
        const dobInput = document.getElementById('dob');
        const today = new Date().toISOString().split('T')[0];
        dobInput.setAttribute('max', today);

        // Check for Previous Rejection
        const emailInput = document.getElementById('email');
        const headerDiv = document.querySelector('.form-header'); // Place to insert alert

        emailInput.addEventListener('blur', function() {
            const email = this.value;
            if (email && email.includes('@')) {
                fetch(`check_rejection.php?email=${encodeURIComponent(email)}`)
                    .then(response => response.json())
                    .then(data => {
                        const existingAlert = document.getElementById('rejection-alert');
                        if (existingAlert) existingAlert.remove();

                        if (data.rejected) {
                            const alertDiv = document.createElement('div');
                            alertDiv.id = 'rejection-alert';
                            alertDiv.style.background = '#fff3cd';
                            alertDiv.style.color = '#856404';
                            alertDiv.style.padding = '15px';
                            alertDiv.style.borderRadius = '12px';
                            alertDiv.style.marginTop = '20px';
                            alertDiv.style.border = '1px solid #ffeeba';
                            alertDiv.style.fontSize = '0.9rem';
                            alertDiv.style.display = 'flex';
                            alertDiv.style.alignItems = 'start';
                            alertDiv.style.gap = '10px';
                            
                            alertDiv.innerHTML = `
                                <i class="fas fa-exclamation-triangle" style="margin-top: 3px;"></i>
                                <div>
                                    <strong>Notice:</strong> Your previous application was rejected on ${data.date}.
                                    <br>
                                    <span style="font-weight: 500;">Reason:</span> "${data.reason}"
                                    <br>
                                    <span style="font-size: 0.85rem; opacity: 0.9;">You can update your details and submit again.</span>
                                </div>
                            `;
                            
                            // Insert content after header title/p
                            headerDiv.appendChild(alertDiv);
                        }
                    })
                    .catch(err => console.error('Error checking rejection:', err));
            }
        });
    </script>
</body>
</html>
