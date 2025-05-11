<?php
require_once 'php/includes/auth.php';
require_once 'php/includes/db.php';
require_once 'php/includes/crypto.php'; // for decryptData()
require_once 'php/includes/add_audit.php'; // for addAuditLog()

requireLogin();
requireRole('manager');

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);
$user_role = $_SESSION['user_role'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/fms-style.css">
    <link rel="stylesheet" href="./assets/css/refund.css">
    <link rel="shortcut icon" href="./assets/favicon/SIS.ico" type="image/x-icon">
    <title>Finance Management | Refund Approval</title>
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
        <section class="content">
            <div class="content-header">
                <button class="js-sidenav-toggle" aria-label="Toggle navigation menu">
                    <span class="mdi mdi-menu"></span>
                </button>
                <h3>Refund Approval</h3>
            </div>
            <article class="module-content">
                <div class="refund">
                    <div class="filter-bar">
                        <label for="refundStatus">Filter by Status:</label>
                        <select id="refundStatus">
                            <option value="All">All</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Denied">Denied</option>
                        </select>
                    </div>

                    <table class="refund-table">
                        <thead>
                            <tr>
                                <th>Refund ID</th>
                                <th>Student ID</th>
                                <th>Amount Requested</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Date Requested</th>
                                <?php if ($user_role === 'manager'): ?>
                                    <th>Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="refundTableBody">
                            <?php
                            try {
                                $stmt = $pdo->query("
        SELECT rr.refund_id, rr.Student_ID,
               rr.amount_requested, rr.reason, rr.status, rr.date_requested
        FROM refund_requests rr
        ORDER BY rr.date_requested DESC
    ");

                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $refund_id = htmlspecialchars($row['refund_id']);
                                    $student_id = htmlspecialchars($row['Student_ID']);
                                    $amount_requested = htmlspecialchars(decryptData($row['amount_requested']));
                                    $reason = htmlspecialchars(decryptData($row['reason']));
                                    $status = htmlspecialchars($row['status']);
                                    $date_requested = date('m/d/Y', strtotime($row['date_requested']));

                                    echo "<tr>";
                                    echo "<td>{$refund_id}</td>";
                                    echo "<td>{$student_id}</td>";
                                    echo "<td>₱" . number_format((float)$amount_requested, 2) . "</td>";
                                    echo "<td>{$reason}</td>";
                                    echo "<td>{$status}</td>";
                                    echo "<td>{$date_requested}</td>";

                                    if ($user_role === 'manager') {
                                        echo "<td class='action-buttons'>
                    <div class='button-container'>
                        <button class='approve-btn' onclick=\"updateRefundStatus('Approved', {$refund_id})\">Approve</button>
                        <button class='deny-btn' onclick=\"updateRefundStatus('Denied', {$refund_id})\">Deny</button>
                    </div>
                  </td>";
                                    }

                                    echo "</tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='7'>Error loading data: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>

                    <div id="refundModal" class="modal-overlay">
                        <div class="modal-content"></div>
                    </div>
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

    <script src="./assets/js/fms-script.js"></script>

    <script>
        function applyRefundStatusFilter() {
            const selectedStatus = document.getElementById("refundStatus").value;
            const rows = document.querySelectorAll("#refundTableBody tr");

            rows.forEach(row => {
                const rowStatus = row.cells[4].textContent.trim();
                row.style.display = (selectedStatus === "All" || rowStatus === selectedStatus) ? "" : "none";
            });
        }

        function updateRefundStatus(newStatus, refundId) {
            fetch('php/update_refund_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `refund_id=${encodeURIComponent(refundId)}&status=${encodeURIComponent(newStatus)}`
                })
                .then(response => response.text())
                .then(data => {
                    if (data === 'Success') {
                        const row = Array.from(document.querySelectorAll("#refundTableBody tr"))
                            .find(r => r.cells[0].textContent.trim() === String(refundId));

                        if (row) {
                            row.cells[4].textContent = newStatus;

                            const approveBtn = row.querySelector(".approve-btn");
                            const denyBtn = row.querySelector(".deny-btn");
                            if (approveBtn) approveBtn.disabled = true;
                            if (denyBtn) denyBtn.disabled = true;

                            applyRefundStatusFilter();
                        }

                        alert(`Refund status updated to ${newStatus}`);
                    } else {
                        alert("Error: " + data);
                    }
                })
                .catch(error => {
                    console.error('Error updating status:', error);
                    alert("Failed to update refund status.");
                });
        }

        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("refundStatus").addEventListener("change", applyRefundStatusFilter);
        });
    </script>

</body>

</html>