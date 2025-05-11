<?php
// -------------------------
// auth.php
// Reusable authentication and RBAC helper
// -------------------------

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------
// Session timeout settings
// -------------------------

// Define session lifetime in seconds (e.g., 15 minutes = 900 seconds)
define('SESSION_TIMEOUT', 9999999); // 180 seconds (3 minutes)


// -------------------------
// Error Handling and Logging
// -------------------------

// Log a message if the user has been logged out due to inactivity
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    // Log user timeout (optional)
    error_log("User session timed out due to inactivity.");
}

// -------------------------
// Check for inactivity
// -------------------------

if (isset($_SESSION['LAST_ACTIVITY'])) {
    // If the session has been inactive longer than the timeout
    if (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT) {
        // Log out the user
        session_unset();      // Clear session data
        session_destroy();    // Destroy session
        // Redirect to login with timeout message
        header("Location: login.php?timeout=1");
        exit(); // Always exit after redirect to prevent further script execution
    }
}

// Update the last activity timestamp for the session
$_SESSION['LAST_ACTIVITY'] = time();


// -------------------------
// Login Check Function
// -------------------------

/**
 * Require user to be logged in.
 * If not logged in, redirect to login page.
 */
function requireLogin(): void
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !isset($_SESSION['username'])) {
        // Log unauthorized access attempt
        error_log("Unauthorized access attempt detected.");

        // Redirect to login with unauthorized error message
        header("Location: login.php?error=unauthorized");
        exit(); // Always exit after redirect
    }
}


// -------------------------
// Role-Based Access Control (RBAC)
// -------------------------

/**
 * Require the logged-in user to have a specific role.
 * 
 * @param string $role Role required to access the page (e.g., 'admin')
 */
function requireRole(string $role): void
{
    requireLogin(); // Ensure the user is logged in

    if ($_SESSION['user_role'] !== $role) {
        // Log unauthorized access attempt
        error_log("Access denied for user ID {$_SESSION['user_id']} with role {$_SESSION['user_role']}");

        // Return a 403 Forbidden response and exit
        http_response_code(403);
        exit("Access denied: You do not have permission to view this page.");
    }
}
