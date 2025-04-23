<?php
session_start();

// Redirect to home if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: student-fees.php");
    exit();
}

// Retain input and show error message
$username = $_SESSION['username'] ?? ''; // Retain username from session if set
$error = $_SESSION['login_error'] ?? ''; // Show any login errors
unset($_SESSION['login_error']); // Clear error after display
unset($_SESSION['username']); // Clear username after using it

// $hashed_password = password_hash('password123', PASSWORD_BCRYPT);
// echo "Hashed password for 'password123': $hashed_password\n"; // For demonstration purposes only
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="./assets/css/login.css">
    <link rel="shortcut icon" href="./assets/favicon/SIS.ico" type="image/x-icon">
    <title>Finance Management</title>
</head>

<body>
    <main class="login-wrapper">
        <div class="login-card">
            <img src="./assets/img/SIS-logo.png" alt="SIS Icon" />
            <?php if (!empty($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form method="POST" action="php/login_handler.php">
                <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username); ?>" required />
                <input type="password" name="password" placeholder="Password" required />
                <button type="submit">Login</button>
            </form>
        </div>
    </main>
</body>

</html>