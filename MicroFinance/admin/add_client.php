<?php
require('../includes/db_connect.php');
require('../includes/auth_session.php');
requireAdmin();

// Redirect to dashboard or users page as this feature is deprecated
header("Location: dashboard.php");
exit();
?>
