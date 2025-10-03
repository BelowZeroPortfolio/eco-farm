<?php
/**
 * Index Page for IoT Farm Monitoring System
 * 
 * Redirects users to appropriate page based on authentication status
 */

require_once 'config/database.php';

// Redirect based on authentication status
if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
?>