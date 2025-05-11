<?php
session_start();
require_once 'php/includes/auth.php'; // Ensure auth file is included for session checking
require_once 'php/includes/db.php'; // Ensure db.php is included for database connection

requireLogin(); // Ensure the user is logged in

// Get user session data
$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);
$user_role = $_SESSION['user_role'];

// Fetch the next refund ID from the database
$stmt = $pdo->prepare("SELECT MAX(refund_id) AS max_refund_id FROM refund_requests");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$next_refund_id = ($result['max_refund_id'] ?? 0) + 1; // Handle case when table is empty
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/fms-style.css">
    <link rel="stylesheet" href="./assets/css/refund.css">
    <link rel="shortcut icon" href="./assets/favicon/SIS.ico" type="image/x-icon">
    <title>Finance Management | Refund Request</title>
</head>

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
        <!-- Sidebar Section -->
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
                    <li><a id="logoutTrigger"><span class="mdi mdi-logout"></span> <span>Logout</span></a></li>
                </ul>
            </nav>
        </aside>

        <!-- Content Section -->
        <section class="content">
            <div class="content-header">
                <button class="js-sidenav-toggle" aria-label="Toggle navigation menu">
                    <span class="mdi mdi-menu"></span>
                </button>
                <h3>Refund Request</h3>
            </div>

            <article class="module-content">
                <div class="refund-request">
                    <form action="php/process_refund_request.php" method="POST">
                        <table class="refund-form-table">
                            <?php
                            // Display any error messages
                            if (!empty($_SESSION['refund_error'])) {
                                echo '<div class="error">' . htmlspecialchars($_SESSION['refund_error']) . '</div>';
                                unset($_SESSION['refund_error']);
                            }

                            // Restore previously submitted data
                            $form_data = $_SESSION['refund_form_data'] ?? [];
                            function old($key)
                            {
                                global $form_data;
                                return htmlspecialchars($form_data[$key] ?? '');
                            }
                            ?>
                            <tr>
                                <td><label for="refund_id">Refund ID:</label></td>
                                <td><input type="text" name="refund_id" id="refund_id" value="<?php echo $next_refund_id; ?>" readonly disabled></td>
                            </tr>

                            <tr>
                                <td><label for="student_id">Student ID:</label></td>
                                <td><input type="text" name="student_id" id="student_id" value="<?= old('student_id') ?>" placeholder=" Enter Student ID" required></td>
                            </tr>


                            <tr>
                                <td><label for="amount_requested">Amount Requested:</label></td>
                                <td><input type="number" name="amount_requested" id="amount_requested" value="<?= old('amount_requested') ?>" step="0.01" min="0" required></td>
                            </tr>

                            <tr>
                                <td><label for="date_requested">Date Requested:</label></td>
                                <td><input type="text" name="date_requested" id="date_requested" value="<?php echo date('Y-m-d'); ?>" readonly></td>
                            </tr>

                            <tr>
                                <td><label for="reason">Reason for Refund:</label></td>
                                <td><textarea name="reason" id="reason" rows="4" required><?= old('reason') ?></textarea></td>
                            </tr>

                            <tr>
                                <td colspan="2" class="submit-btn-cell">
                                    <button type="submit">Submit Refund Request</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
            </article>
        </section>
    </main>
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

    <!-- Modal Popup HTML -->
    <div id="popupModal" class="modal-overlay">
        <div class="modal-content">
            <span id="closePopupBtn" class="close-button">&times;</span>
            <h2>Refund Request Submitted</h2>
            <p>Your refund request has been successfully submitted and is now pending review.</p>
        </div>
    </div>

    <script src="./assets/js/fms-script.js"></script>

</body>

</html>