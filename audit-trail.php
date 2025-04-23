<?php
require_once 'php/includes/auth.php';

requireLogin();
requireRole('admin');

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
                <h3>Audit Trail</h3>
            </div>
            <article class="module-content">
                <div class="audit-trail">


                    <div class="search-container">
                        <input type="text" id="searchInput" placeholder="Search by action, entity, or user ID..."
                            aria-label="Search Audit Trail" />
                    </div>
                    <table aria-label="Audit Trail Table">
                        <thead>
                            <tr>
                                <th>Audit ID</th>
                                <th>User ID</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Entity ID</th>
                                <th>Description</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody id="auditTableBody">
                            <tr>
                                <td>1</td>
                                <td>101</td>
                                <td>UPDATE</td>
                                <td>invoices</td>
                                <td>56</td>
                                <td>Updated invoice status to paid</td>
                                <td>2025-04-12 14:35:00</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>102</td>
                                <td>CREATE</td>
                                <td>payments</td>
                                <td>78</td>
                                <td>Created new payment record</td>
                                <td>2025-04-12 15:20:11</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>103</td>
                                <td>DELETE</td>
                                <td>refund_requests</td>
                                <td>34</td>
                                <td>Deleted refund request entry</td>
                                <td>2025-04-13 09:12:45</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>104</td>
                                <td>CREATE</td>
                                <td>scholarships</td>
                                <td>90</td>
                                <td>Added new scholarship grant</td>
                                <td>2025-04-13 10:45:23</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>105</td>
                                <td>UPDATE</td>
                                <td>student_fees</td>
                                <td>67</td>
                                <td>Adjusted student fee amount</td>
                                <td>2025-04-13 11:07:10</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>101</td>
                                <td>VIEW</td>
                                <td>financial_reports</td>
                                <td>12</td>
                                <td>Viewed monthly financial report</td>
                                <td>2025-04-13 11:35:42</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>106</td>
                                <td>CREATE</td>
                                <td>invoices</td>
                                <td>88</td>
                                <td>Generated new invoice for student</td>
                                <td>2025-04-13 12:10:18</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>102</td>
                                <td>UPDATE</td>
                                <td>users</td>
                                <td>105</td>
                                <td>Changed user password</td>
                                <td>2025-04-13 12:30:05</td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>107</td>
                                <td>DELETE</td>
                                <td>student_fees</td>
                                <td>69</td>
                                <td>Removed incorrect fee record</td>
                                <td>2025-04-13 13:00:33</td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>103</td>
                                <td>VIEW</td>
                                <td>scholarship_applications</td>
                                <td>45</td>
                                <td>Reviewed scholarship application status</td>
                                <td>2025-04-13 13:22:17</td>
                            </tr>
                        </tbody>
                    </table>
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
        const searchInput = document.getElementById("searchInput");
        const tableBody = document.getElementById("auditTableBody");

        const handleSearchInput = () => {
            const filter = searchInput.value.toLowerCase();
            const rows = tableBody.getElementsByTagName("tr");

            for (const row of rows) {
                const cells = row.getElementsByTagName("td");
                let match = false;

                for (const cell of cells) {
                    if (cell.textContent.toLowerCase().includes(filter)) {
                        match = true;
                        break;
                    }
                }

                row.style.display = match ? "" : "none";
            }
        };

        searchInput.addEventListener("input", handleSearchInput);
    </script>
</body>

</html>