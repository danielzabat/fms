<?php
require_once 'php/includes/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']); // safe output
$user_role = $_SESSION['user_role'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/fms-style.css">
    <link rel="shortcut icon" href="./assets/favicon/SIS.ico" type="image/x-icon">
    <title>Finance Management | User Setting</title>
</head>
<style>
</style>

<body>
    <header>
        <div class="header-content">
            <img src="./assets/img/SIS-logo.png" alt="Student Information System Logo">
            <div>
                <h1>Student Information System</h1>
                <h2>Finance</h2>

            </div>
        </div>
    </header>
    <main class="main">
        <aside class="sidebar">
            <nav aria-label="Main navigation">
                <ul>
                    <li><a href="student-fees.php"><span class="mdi mdi-account-school-outline"></span><span>Student Fees</span></a></li>
                    <li><a href="billing-invoicing.php"><span class="mdi mdi-invoice-list-outline"></span><span>Billing Invoicing</span></a></li>
                    <li><a href="refund-request.php"><span class="mdi mdi-cash-refund"></span><span>Refund Request</span></a></li>
                    <!-- Admin-only Modules -->
                    <?php if ($user_role === 'manager'): ?>
                        <li><a href="refund-approval.php"><span class="mdi mdi-cash-refund"></span><span>Refund Approval</span></a></li>
                        <li><a href="financial-report.php"><span class="mdi mdi-finance"></span><span>Financial Report</span></a></li>
                        <li><a href="audit-trail.php"><span class="mdi mdi-monitor-eye"></span><span>Audit Trail</span></a></li>
                    <?php endif; ?>
                    <li><a href="user-setting.php"><span class="mdi mdi-account-box"></span><span>User Setting</span></a></li>
                </ul>
            </nav>

            <nav aria-label="User options">
                <ul>
                    <li><a id="logoutTrigger"><span class="mdi mdi-logout"></span><span>Logout</span></a></li>
                </ul>
            </nav>
        </aside>
        <section class="content">
            <div class="content-header">
                <button class="js-sidenav-toggle" aria-label="Toggle navigation menu">
                    <span class="mdi mdi-menu"></span>
                </button>
                <h3>User Settings</h3>
            </div>
            <article class="module-content">
                <?php
                require_once 'php/includes/db.php';

                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();

                if (!$user) {
                    echo "<p class='error'>User not found.</p>";
                    exit;
                }
                ?>

                <div class="profile-section">
                    <h4>User Info</h4>
                    <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p><strong>Role:</strong> <?= htmlspecialchars($user['role']) ?></p>



                    <button id="changePwdBtn">Change Password</button>

                    <?php
                    if (!empty($_SESSION['pwd_success'])) {
                        echo '<p class="success message">' . htmlspecialchars($_SESSION['pwd_success']) . '</p>';
                        unset($_SESSION['pwd_success']);
                    }

                    if (!empty($_SESSION['pwd_error'])) {
                        echo '<p class="error message">' . htmlspecialchars($_SESSION['pwd_error']) . '</p>';
                        unset($_SESSION['pwd_error']);
                    }
                    ?>
                </div>

            </article>
        </section>
    </main>
    <!-- Password Change Modal -->
    <div id="changePwdModal" class="modal-overlay">
        <div class="modal-box">
            <h4>Change Password</h4>
            <form method="POST" action="php/change-password.php">
                <label for="current_password">Current Password:</label>
                <input type="password" name="current_password" required><br>

                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" required><br>

                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" name="confirm_password" required><br>

                <button type="submit">Update Password</button>
                <button type="button" class="close-btn" id="closeModalBtn">Cancel</button>
            </form>
        </div>
    </div>
    <div class="logout-modal-overlay" id="logoutModal">
        <div class="logout-modal-box">
            <h3>You're about to log out.</br> Do you want to continue?</h3>
            <div class="logout-modal-buttons">
                <button class="cancel-btn" id="cancelLogout">Cancel</button>
                <button class="logout-btn" id="confirmLogout">Log out</button>
            </div>
        </div>
    </div>
    <footer>
        <address>
            <p>For inquiries please contact 000-0000<br>
                Email: sisfinance3220@gmail.com</p>
        </address>
        <p>&copy; 2025 Student Information System<br>All Rights Reserved</p>
    </footer>
    <script src="./assets/js/fms-script.js"></script>
    <script>

    </script>
</body>

</html>