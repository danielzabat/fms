<?php
session_start();
require_once 'includes/db.php'; // DB connection
require_once 'includes/mailer.php'; // Mailer functions

// Check if the user is logged in and OTP has been generated already
if (!isset($_SESSION['pending_user']) || !isset($_SESSION['otp'])) {
    // If not, redirect to login page
    header("Location: login.php");
    exit();
}

// Check if the user has exceeded the maximum OTP attempts
if (isset($_SESSION['otp_attempts']) && $_SESSION['otp_attempts'] >= 3) {
    $_SESSION['otp_error'] = "Too many incorrect attempts. Please login again.";
    session_destroy();
    header("Location: login.php");
    exit();
}

// Regenerate OTP
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT); // Generate a 6-digit OTP
$otp_expiration = time() + (5 * 60); // OTP valid for 5 minutes

// Save new OTP and expiration time in session
$_SESSION['otp'] = $otp;
$_SESSION['otp_expires'] = $otp_expiration;

// Send the new OTP to the user's email
$userEmail = $_SESSION['pending_user']['email'];
if (sendOTPEmail($userEmail, $otp)) {
    $_SESSION['otp_success'] = "A new OTP has been sent to your email.";
} else {
    $_SESSION['otp_error'] = "Failed to send OTP email. Try again.";
}

// Redirect back to the OTP verification page
header("Location: ../verify-otp.php");
exit();
