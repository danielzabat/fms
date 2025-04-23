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
    <title>Finance Management</title>
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
                    <li><a href="scholarship.php"><span class="mdi mdi-certificate-outline"></span><span>Scholarship</span></a></li>
                    <li><a href="refund.php"><span class="mdi mdi-cash-refund"></span><span>Refund</span></a></li>

                    <!-- Admin-only Modules -->
                    <?php if ($user_role === 'admin'): ?>
                        <li><a href="financial-report.php"><span class="mdi mdi-finance"></span><span>Financial Report</span></a></li>
                        <li><a href="audit-trail.php"><span class="mdi mdi-monitor-eye"></span><span>Audit Trail</span></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <nav aria-label="User options">
                <ul>
                    <li><a href="./php/logout.php"><span class="mdi mdi-logout"></span> <span>Logout</span></a></li>
                </ul>
            </nav>
        </aside>
        <section class="content">
            <div class="content-header">
                <button class="js-sidenav-toggle" aria-label="Toggle navigation menu">
                    <span class="mdi mdi-menu"></span>
                </button>
                <h3>Refund</h3>
            </div>
            <article class="module-content">
                <div class="refund">
                    <div class="filter-bar">
                        <label for="refundStatus">Filter by Status:</label>
                        <select id="refundStatus">
                            <option>All</option>
                            <option>Pending</option>
                            <option>Approved</option>
                            <option>Denied</option>
                        </select>
                    </div>

                    <table class="refund-table">
                        <thead>
                            <tr>
                                <th>Refund ID</th>
                                <th>Student Name</th>
                                <th>Amount Requested</th>
                                <th>Status</th>
                                <th>Date Requested</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>RF-001</td>
                                <td>James Patrick Intia</td>
                                <td>₱1,500.00</td>
                                <td>Pending</td>
                                <td>04/10/2024</td>
                                <td><button class="view-btn" onclick="openModal('RF-001')">View</button></td>
                            </tr>
                            <tr>
                                <td>RF-002</td>
                                <td>Juliun Kyle Berog</td>
                                <td>₱2,000.00</td>
                                <td>Approved</td>
                                <td>04/12/2024</td>
                                <td><button class="view-btn" onclick="openModal('RF-002')">View</button></td>
                            </tr>
                            <tr>
                                <td>RF-003</td>
                                <td>Heroes Torres</td>
                                <td>₱1,200.00</td>
                                <td>Denied</td>
                                <td>04/15/2024</td>
                                <td><button class="view-btn" onclick="openModal('RF-003')">View</button></td>
                            </tr>
                            <tr>
                                <td>RF-004</td>
                                <td>Ariel Mendoza</td>
                                <td>₱1,500.00</td>
                                <td>Pending</td>
                                <td>04/16/2024</td>
                                <td><button class="view-btn" onclick="openModal('RF-004')">View</button></td>
                            </tr>
                            <tr>
                                <td>RF-005</td>
                                <td>Daniel Zabat</td>
                                <td>₱2,000.00</td>
                                <td>Approved</td>
                                <td>04/17/2024</td>
                                <td><button class="view-btn" onclick="openModal('RF-005')">View</button></td>
                            </tr>
                        </tbody>
                    </table>

                    <div id="refundModal" class="modal-overlay">
                        <div class="modal-content"></div>
                    </div>
                </div>
            </article>
        </section>
    </main>
    <footer>
        <address>
            <p>For inquiries please contact 000-0000<br>
                Email: sisfinance3220@gmail.com</p>
        </address>
        <p>&copy; 2025 Student Information System<br>All Rights Reserved</p>
    </footer>
    <script src="./assets/js/fms-script.js"></script>
    <script>
        function getRefundData(refundId) {
            const row = Array.from(document.querySelectorAll("table.refund-table tbody tr"))
                .find(r => r.cells[0].textContent.trim() === refundId);

            if (!row) return null;

            return {
                id: row.cells[0].textContent.trim(),
                name: row.cells[1].textContent.trim(),
                amount: parseFloat(row.cells[2].textContent.replace(/[^\d.]/g, '')),
                status: row.cells[3].textContent.trim(),
                date: row.cells[4].textContent.trim(),
                reason: "Double payment for lab fee during enrollment."
            };
        }

        window.openModal = function(refundId) {
            const refund = getRefundData(refundId);
            if (!refund) return;

            const modal = document.getElementById('refundModal');
            const modalContent = modal.querySelector('.modal-content');
            modalContent.innerHTML = `
      <span class="close-button" onclick="closeModal()">&times;</span>
      <h2>Refund Details</h2>

      <div class="modal-section">
        <h4>Student Information</h4>
        <p><strong>Name:</strong> ${refund.name}</p>
        <p><strong>ID:</strong> 2023XXXXXX</p>
        <p><strong>Course:</strong> [Course Info]</p>
        <p><strong>Year Level:</strong> [Year Level]</p>
      </div>

      <div class="modal-section">
        <h4>Refund Metadata</h4>
        <p><strong>Refund ID:</strong> ${refund.id}</p>
        <p><strong>Status:</strong> ${refund.status}</p>
        <p><strong>Date Requested:</strong> ${refund.date}</p>
      </div>

      <div class="modal-section">
        <h4>Reason</h4>
        <p>${refund.reason}</p>
      </div>

      <div class="modal-section">
        <h4>Approval Panel</h4>
        <textarea placeholder="Admin notes or justification..." rows="3"></textarea>
        <div class="modal-actions">
          <button class="approve-btn" onclick="updateRefundStatus('Approved', '${refund.id}')">Approve</button>
          <button class="deny-btn" onclick="updateRefundStatus('Denied', '${refund.id}')">Deny</button>
          <button class="cancel-btn" onclick="closeModal()">Cancel</button>
        </div>
      </div>
    `;

            modal.style.display = 'flex';
        };

        window.closeModal = function() {
            document.getElementById('refundModal').style.display = 'none';
        };

        window.updateRefundStatus = function(newStatus, refundId) {
            const row = Array.from(document.querySelectorAll("table.refund-table tbody tr"))
                .find(r => r.cells[0].textContent.trim() === refundId);

            if (row) {
                row.cells[3].textContent = newStatus;
                alert(`Refund status updated to ${newStatus}`);
            }

            closeModal();
        };

        const statusFilter = document.getElementById('refundStatus');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => {
                const selected = statusFilter.value.toLowerCase();
                const rows = document.querySelectorAll('.refund-table tbody tr');

                rows.forEach(row => {
                    const status = row.cells[3].textContent.trim().toLowerCase();

                    if (selected === 'all' || status === selected) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    </script>

</body>

</html>