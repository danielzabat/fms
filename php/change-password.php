<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/add_audit.php';
require_once 'includes/auth.php';

requireLogin();

$userId = $_SESSION['user_id'];
$currentPwd = $_POST['current_password'] ?? '';
$newPwd = $_POST['new_password'] ?? '';
$confirmPwd = $_POST['confirm_password'] ?? '';

// Basic validations
if (empty($currentPwd) || empty($newPwd) || empty($confirmPwd)) {
    $_SESSION['pwd_error'] = "All fields are required.";
    header('Location: ../user-setting.php');
    exit();
}

if ($newPwd !== $confirmPwd) {
    $_SESSION['pwd_error'] = "New password and confirmation do not match.";
    header('Location: ../user-setting.php');
    exit();
}

if (strlen($newPwd) < 6) {
    $_SESSION['pwd_error'] = "New password must be at least 6 characters long.";
    header('Location: ../user-setting.php');
    exit();
}

// Get current hashed password from DB
$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user || !password_verify($currentPwd, $user['password_hash'])) {
    $_SESSION['pwd_error'] = "Current password is incorrect.";
    header('Location: ../user-setting.php');
    exit();
}

// Hash and update the new password
$newHashed = password_hash($newPwd, PASSWORD_DEFAULT);

$update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
if ($update->execute([$newHashed, $userId])) {
    $_SESSION['pwd_success'] = "Password updated successfully.";
    addAuditLog($pdo, 'PASSWORD_CHANGE', 'users', $userId, 'User updated their password.');
} else {
    $_SESSION['pwd_error'] = "Failed to update password. Try again.";
}

header('Location: ../user-setting.php');
exit();
