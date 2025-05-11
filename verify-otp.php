<?php
session_start();

if (
    !isset($_SESSION['otp']) ||
    !isset($_SESSION['pending_user']) ||
    (isset($_SESSION['otp_attempts']) && $_SESSION['otp_attempts'] >= 3)
) {
    session_destroy();
    header("Location: login.php?failed-attempt=1");
    exit();
}

// Check if OTP has expired
if (time() > $_SESSION['otp_expires']) {
    session_destroy();
    header("Location: login.php?failed-attempt=1");
    exit();
}

$success = $_SESSION['otp_success'] ?? '';
unset($_SESSION['otp_success']);

$error = $_SESSION['otp_error'] ?? '';
unset($_SESSION['otp_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = trim($_POST['otp']);

    if (empty($enteredOtp)) {
        $_SESSION['otp_error'] = "OTP is required.";
        header("Location: verify-otp.php");
        exit();
    }

    // Check for OTP expiration
    if (time() > $_SESSION['otp_expires']) {
        $_SESSION['otp_error'] = "OTP expired. Please log in again.";
        session_destroy();
        header("Location: login.php?failed-attempt=1");
        exit();
    }

    // Verify OTP
    if ($enteredOtp === $_SESSION['otp']) {
        // OTP is correct → complete login
        $_SESSION['user_id'] = $_SESSION['pending_user']['id'];
        $_SESSION['user_role'] = $_SESSION['pending_user']['role'];
        $_SESSION['username'] = $_SESSION['pending_user']['username'];



        // Cleanup
        unset($_SESSION['otp']);
        unset($_SESSION['otp_expires']);
        unset($_SESSION['pending_user']);

        header("Location: student-fees.php");
        exit();
    } else {
        $_SESSION['otp_error'] = "Invalid OTP. Please try again.";
        header("Location: verify-otp.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <title>Finance Management</title>
    <link rel="stylesheet" href="./assets/css/login.css">
    <link rel="shortcut icon" href="./assets/favicon/SIS.ico" type="image/x-icon">
</head>

<body>
    <main class="login-wrapper">
        <div class="login-card">
            <h2>Verify OTP</h2>

            <?php if (empty($success) && empty($error)): ?>
                <p class="success"><?php echo 'Please enter the 6-digit code sent to your email.'; ?></p>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <p class="success"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <form method="POST" action="php/verify-otp-handler.php">
                <input type="text" name="otp" placeholder="Enter here" class="otp-input" required />
                <button type="submit">Verify</button>
            </form>

            <form method="POST" action="php/resend-otp.php" style="margin-top: 10px;">
                <button type="submit" class="resend-otp">Click here to resend</button>
            </form>
        </div>
    </main>
</body>

</html>