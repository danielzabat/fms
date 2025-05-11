<?php
session_start();
require_once 'includes/db.php'; // DB connection
require_once 'includes/mailer.php'; // Mailer functions

// Helper: sanitize input
function sanitize($data)
{
    return htmlspecialchars(trim($data));
}

// Input validation
$username = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = "Both fields are required.";
    $_SESSION['username'] = $username;
    header("Location: ../login.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, username, password_hash, role, email FROM users WHERE username = :username LIMIT 1");
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($password, $user['password_hash'])) {
            // Credentials are valid, now generate and send OTP

            // Generate 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_expiration = time() + (5 * 60); // OTP valid for 5 minutes

            // Save OTP and expiration in session
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_expires'] = $otp_expiration;

            // Also save user temporarily
            $_SESSION['pending_user'] = [
                'id' => $user['id'],
                'role' => $user['role'],
                'username' => $user['username'],
                'email' => $user['email']
            ];

            // Send OTP to user's Gmail
            if (sendOTPEmail($user['email'], $otp)) {
                // Redirect to OTP verification page
                header("Location: ../verify-otp.php");
                exit();
            } else {
                $_SESSION['login_error'] = "Failed to send OTP email. Try again.";
                header("Location: ../login.php");
                exit();
            }
        } else {
            $_SESSION['login_error'] = "Invalid username or password.";
            $_SESSION['username'] = $username;
            header("Location: ../login.php");
            exit();
        }
    } else {
        $_SESSION['login_error'] = "Invalid username or password.";
        $_SESSION['username'] = $username;
        header("Location: ../login.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Login DB error: " . $e->getMessage());
    $_SESSION['login_error'] = "An unexpected error occurred. Please try again later.";
    header("Location: ../login.php");
    exit();
}
