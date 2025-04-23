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
                <h3>Billing Invoice</h3>
            </div>
            <article class="module-content">
                <div class="billing-container">
                    <div class="search-section">
                        <input type="text" id="searchInput"
                            placeholder="Search by Student Name or ID or Invoice Reference" oninput="filterData()" />
                    </div>

                    <div id="billingData"></div>
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
        const students = [{
                id: "0123456789",
                name: "Berog, Juliun Kyle",
                course: "Bachelor of Science in Information Technology",
                year: "3rd Year",
                previousBalance: 0,
                currentBalance: 5325,
                scholarship: 0,
                invoices: [{
                        no: 1,
                        particular: "MISCELLANEOUS FEE",
                        method: "",
                        reference: "BILLS-0000001 | 2nd Sem 2024-2025 | Jan 3, 2025, 2:16:48 PM | System Generated",
                        amount: 4975,
                        status: "NOT PAID"
                    },
                    {
                        no: 2,
                        particular: "FOUNDATION FEE",
                        method: "",
                        reference: "BILLS-0000002 | 2nd Sem 2024-2025 | Jan 6, 2025, 11:51:57 AM | System Generated",
                        amount: 350,
                        status: "NOT PAID"
                    }
                ]
            },
            {
                id: "22010001",
                name: "Dela Cruz, Maria Angelica",
                course: "Bachelor of Science in Business Administration",
                year: "2nd Year",
                previousBalance: 500,
                currentBalance: 2750,
                scholarship: 100,
                invoices: [{
                        no: 1,
                        particular: "TUITION FEE",
                        method: "",
                        reference: "BILLS-190004000111 | 1st Sem 2024-2025 | Jan 4, 2025, 10:00:00 AM | Registrar",
                        amount: 2000,
                        status: "PARTIAL"
                    },
                    {
                        no: 2,
                        particular: "LIBRARY FEE",
                        method: "",
                        reference: "BILLS-190004000112 | 1st Sem 2024-2025 | Jan 5, 2025, 9:30:00 AM | Registrar",
                        amount: 750,
                        status: "NOT PAID"
                    }
                ]
            }
        ];

        function formatCurrency(value) {
            return `₱${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        function filterData() {
            const searchValue = document.getElementById("searchInput").value.trim().toLowerCase();
            const container = document.getElementById("billingData");
            container.innerHTML = "";

            const matchedStudent = students.find(student =>
                student.id.toLowerCase() === searchValue ||
                student.name.toLowerCase() === searchValue ||
                student.invoices.some(inv => inv.reference.toLowerCase().includes(searchValue))
            );

            if (matchedStudent && searchValue !== "") {
                let html = `
              <div class="student-info">
                <h2>${matchedStudent.id}</h2>
                <h2>${matchedStudent.name}</h2>
                <p>${matchedStudent.course}</p>
                <p>${matchedStudent.year}</p>
              </div>
              <div class="balance-summary">
                <div><strong>Previous Balance</strong><br>${formatCurrency(matchedStudent.previousBalance)}</div>
                <div><strong>Current Balance</strong><br>${formatCurrency(matchedStudent.currentBalance)}</div>
                <div><strong>Scholarship</strong><br>${formatCurrency(matchedStudent.scholarship)}</div>
              </div>
              <table>
                <thead>
                  <tr>
                    <th>No.</th>
                    <th>Particular</th>
                    <th>Reference</th>
                    <th>Amount</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>`;

                matchedStudent.invoices.forEach(inv => {
                    html += `
                <tr>
                  <td>${inv.no}</td>
                  <td>${inv.particular}</td>
                  <td>${inv.reference}</td>
                  <td>${formatCurrency(inv.amount)}</td>
                  <td><span class="status ${inv.status.replace(/ /g, '-').toLowerCase()}">${inv.status}</span></td>
                </tr>`;
                });

                html += `</tbody></table><br>`;
                container.innerHTML = html;
            }
        }
    </script>
</body>

</html>