<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all admin session data
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);

// Destroy session entirely if no other sessions are needed
session_destroy();

// Redirect back to Admin Login page
header("Location: index.php");
exit();
?>