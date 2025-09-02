<?php
/**
 * Admin Logout
 */

define('BOGF_ACCESS', true);
require_once '../includes/config.php';

startSecureSession();

// Log logout activity if user is logged in
if (isAdminLoggedIn()) {
    $adminId = getCurrentAdminId();
    logAdminActivity($adminId, 'logout');
}

// Clear session data
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page with success message
header('Location: login.php?message=' . urlencode('You have been successfully logged out'));
exit;
?>