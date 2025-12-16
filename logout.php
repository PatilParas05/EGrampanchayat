<?php
/**
 * Logout Handler
 * Terminates the user session and redirects to the home page.
 */

// Start the session (required to access session data)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the public home page
header('Location: index.php?message=Logged out successfully.');
exit;
?>