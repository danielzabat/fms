<?php
session_start();
require_once './db.php'; // DB connection: use PDO with prepared statements

// Helper: sanitize input
function sanitize($data)
{
    return htmlspecialchars(trim($data));
}

// Basic input validation
$username = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? ''; // Raw password, do not sanitize yet

if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = "Both fields are required.";
    $_SESSION['username'] = $username; // Preserve username
    header("Location: ../login.php");
    exit();
}

try {
    // Prepare SQL to prevent SQL injection
    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = :username LIMIT 1");
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debugging: check if the user was found
    if ($user) {
        // Check if password matches
        if (password_verify($password, $user['password_hash'])) {
            // Valid credentials - create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username'] = $user['username'];

            // Redirect to homepage
            header("Location: ../student-fees.php");
            exit();
        } else {
            // Invalid password
            $_SESSION['login_error'] = "Invalid username or password.";
            $_SESSION['username'] = $username; // Retain username input
            header("Location: ../login.php");
            exit();
        }
    } else {
        // No user found with the provided username
        $_SESSION['login_error'] = "Invalid username or password.";
        $_SESSION['username'] = $username; // Retain username input
        header("Location: ../login.php");
        exit();
    }
} catch (PDOException $e) {
    // Log the actual error in a log file or monitoring system
    error_log("Login DB error: " . $e->getMessage());
    $_SESSION['login_error'] = "An unexpected error occurred. Please try again later.";
    header("Location: ../login.php");
    exit();
}
