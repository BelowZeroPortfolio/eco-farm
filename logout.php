<?php
/**
 * Simple Logout Script
 */

// Start session
session_start();

// Log the logout event
if (isset($_SESSION['username'])) {
    error_log("User logged out: " . $_SESSION['username']);
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page with message
header('Location: login.php?message=' . urlencode('You have been logged out successfully.'));
exit();
?>