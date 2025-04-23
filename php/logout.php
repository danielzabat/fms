<?php

/**
 * logout.php
 * 
 * Securely logs out the user by:
 * 1. Starting the session (if not already started)
 * 2. Clearing all session variables
 * 3. Unsetting the session cookie
 * 4. Destroying the session
 * 5. Redirecting to login page with a logout indicator
 */

// Start the session
session_start();

// Clear all session variables in this script's memory
$_SESSION = [];

// If session uses cookies, clear the session cookie to prevent reuse
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params(); // Get cookie settings
    setcookie(
        session_name(),
        '',
        time() - 42000, // Expire the cookie
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session data on the server
session_destroy();

// Redirect user to login page with a logout success message

if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    header("Location: ../login.php?timeout=1");
} else {
    header("Location: ../login.php?logout=1");
}

exit();
