<?php
require_once 'php/includes/auth.php';
require_once 'php/includes/db.php'; // Make sure this file sets up $pdo safely

requireLogin();
requireRole('manager');

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);
$user_role = $_SESSION['user_role'];

// Set sort parameters with defaults
$allowedColumns = ['audit_id', 'user_id', 'action', 'entity', 'entity_id', 'description', 'timestamp'];
$sortColumn = in_array($_GET['sort'] ?? '', $allowedColumns) ? $_GET['sort'] : 'timestamp';
$sortOrder = ($_GET['order'] ?? '') === 'asc' ? 'ASC' : 'DESC';

try {
    $stmt = $pdo->prepare("SELECT * FROM finance_audit_trail ORDER BY $sortColumn $sortOrder");
    $stmt->execute();
    $auditRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to fetch audit trail: " . htmlspecialchars($e->getMessage());
    $auditRows = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Management | Audit Trail</title>
    <link rel="stylesheet" href="./assets/css/fms-style.css">
    <link rel="shortcut icon" href="./assets/favicon/SIS.ico" type="image/x-icon">
</head>

<body>
    <div class="logout-modal-overlay" id="logoutModal">
        <div class="logout-modal-box">
            <h3>You're about to log out.</br> Do you want to continue?</h3>
            <div class="logout-modal-buttons">
                <button class="cancel-btn" id="cancelLogout">Cancel</button>
                <button class="logout-btn" id="confirmLogout">Log out</button>
            </div>
        </div>
    </div>
    <header>
        <div class="header-content">
            <img src="./assets/img/SIS-logo.png" alt="Student Information System Logo">
            <div>
                <h1>Student Information System</h1>
                <h2>Finance Management</h2>
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
                <h3>Audit Trail</h3>
            </div>
            <article class="module-content">
                <div class="audit-trail">
                    <div class="search-container">
                        <input type="text" id="searchInput" placeholder="Search audit trail..." />
                    </div>

                    <?php if (!empty($error)): ?>
                        <p class="error"><?= $error ?></p>
                    <?php endif; ?>

                    <table id="auditTable">
                        <thead>
                            <tr>
                                <?php
                                foreach (['audit_id', 'user_id', 'action', 'entity', 'entity_id', 'description', 'timestamp'] as $col) {
                                    $label = ucwords(str_replace('_', ' ', $col));
                                    $order = ($sortColumn === $col && $sortOrder === 'ASC') ? 'desc' : 'asc';
                                    echo "<th><a href=\"?sort=$col&order=$order\">$label</a></th>";
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['audit_id']) ?></td>
                                    <td><?= htmlspecialchars($row['user_id']) ?></td>
                                    <td><?= htmlspecialchars($row['action']) ?></td>
                                    <td><?= htmlspecialchars($row['entity']) ?></td>
                                    <td><?= htmlspecialchars($row['entity_id']) ?></td>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td><?= htmlspecialchars($row['timestamp']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
        const searchInput = document.getElementById("searchInput");
        const table = document.getElementById("auditTable").getElementsByTagName("tbody")[0];

        searchInput.addEventListener("input", () => {
            const filter = searchInput.value.toLowerCase();
            const rows = table.getElementsByTagName("tr");

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
        });
    </script>
</body>

</html>