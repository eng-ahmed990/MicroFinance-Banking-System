<?php
// Set secure session parameters
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']); // Logic for User
}

function isAdmin() {
    return isset($_SESSION['admin_id']); // Logic for Admin
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function requireVerification($conn) {
    if (!isset($_SESSION['user_id'])) return;
    
    // Check status if not in session or for security re-check
    $id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT verification_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $status = $res->fetch_assoc()['verification_status'];
    $stmt->close();
    
    $_SESSION['verification_status'] = $status; // Sync session

    if ($status !== 'verified') {
        // Redirect to verify page
        header("Location: ../user/verify.php");
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: ../admin/login.php");
        exit();
    }
}

function isSuperAdmin() {
    // Check if logged in as Admin AND email matches
    if (isAdmin() && isset($_SESSION['admin_email']) && $_SESSION['admin_email'] === 'admin@microfinance.com') {
        return true;
    }
    return false;
}

function requireSuperAdmin() {
    // Ensure the user is at least a logged-in admin first
    if (!isAdmin()) {
        header("Location: login.php");
        exit();
    }

    if (!isSuperAdmin()) {
        die("<div style='text-align:center; padding:50px; font-family:sans-serif;'>
                <h2 style='color:#dc2626'>Access Denied</h2>
                <p>Only the Super Admin (admin@microfinance.com) can perform this action.</p>
                <a href='dashboard.php'>Return to Dashboard</a>
             </div>");
    }
}

function getUnreadCount($conn, $user_id) {
    if (!$conn || !$user_id) return 0;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = ($result) ? $result->fetch_assoc()['unread'] : 0;
        $stmt->close();
        return $count;
    }
    return 0;
}

function getLoanSummary($conn, $loan_id) {
    if (!$conn || !$loan_id) return ['availed' => 0, 'paid' => 0, 'remaining' => 0];
    
    // Get Loan Availed Amount
    $stmt = $conn->prepare("SELECT amount FROM loans WHERE id = ?");
    $availed = 0;
    if ($stmt) {
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $res_loan = $stmt->get_result();
        $availed = ($res_loan && $res_loan->num_rows > 0) ? $res_loan->fetch_assoc()['amount'] : 0;
        $stmt->close();
    }
    
    // Get Total Verified Repayments
    $stmt = $conn->prepare("SELECT SUM(amount) as total_paid FROM repayments WHERE loan_id = ? AND status = 'verified'");
    $paid = 0;
    if ($stmt) {
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $res_paid = $stmt->get_result();
        $paid = ($res_paid) ? (float)$res_paid->fetch_assoc()['total_paid'] : 0;
        $stmt->close();
    }
    
    return [
        'availed' => $availed,
        'paid' => $paid,
        'remaining' => $availed - $paid
    ];
}

// CSRF Protection
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        error_log("CSRF Failure: Session token missing. ID: " . session_id());
        return false;
    }
    
    if (!isset($token)) {
         error_log("CSRF Failure: POST token missing.");
         return false;
    }

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        error_log("CSRF Failure: Token mismatch. Session: " . substr($_SESSION['csrf_token'], 0, 5) . "... POST: " . substr($token, 0, 5) . "...");
        return false;
    }
    
    return true;
}

// Function to sanitize inputs
function sanitize($conn, $input) {
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($input))));
}
?>
