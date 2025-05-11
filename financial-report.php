<?php
// Include necessary files
require_once 'php/includes/auth.php'; // Authentication and authorization check
require_once 'php/includes/crypto.php'; // For encryption/decryption
require_once 'php/includes/add_audit.php'; // For logging actions to the audit trail
require_once 'php/includes/db.php'; // Database connection

// Include TCPDF library for PDF generation
require_once 'php/vendor/tcpdf/tcpdf.php';

// Ensure the user is logged in and is an manager
$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']); // For safe output
$user_role = $_SESSION['user_role'];
requireLogin(); // Checks if user is logged in
requireRole('manager'); // Ensures the user has 'manager' role

$reportBreakdown = [];
$totalCollected = $totalDue = $totalRefunds = 0.00;

$report_type = $_GET['report_type'] ?? 'Monthly';
$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

// Flag to indicate if report was generated
$reportGenerated = false;

// Only process if the user submitted the form
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['from']) && isset($_GET['to'])) {
    $reportGenerated = true; // Set flag to true when report is generated

    try {
        // Prepare SQL queries based on the report type (monthly or yearly)
        if ($report_type == 'Monthly') {
            $date_condition = "AND DATE_FORMAT(date_created, '%Y-%m') = DATE_FORMAT(:from, '%Y-%m')";
        } else {
            $date_condition = "AND YEAR(date_created) = YEAR(:from)";
        }

        // Retrieve total collected amount (decrypting data)
        $stmt1 = $pdo->prepare("SELECT total_amount
                            FROM invoices
                            WHERE status = 'Paid' AND date_created BETWEEN :from AND :to");
        $stmt1->execute(['from' => $from, 'to' => $to]);

        $totalCollected = 0.00;
        // Loop through the fetched data and decrypt the total_amount
        while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
            $decryptedAmount = decryptData($row['total_amount']);
            if ($decryptedAmount !== false) {
                $totalCollected += floatval($decryptedAmount); // Add decrypted value to total
            }
        }

        // Retrieve total due amount (decrypted data from fee_summaries)
        $stmt2 = $pdo->prepare("SELECT outstanding_balance
                            FROM fee_summaries");
        $stmt2->execute();
        $totalDue = 0.00;

        // Loop through and decrypt outstanding_balance for fee summaries
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $decryptedBalance = decryptData($row['outstanding_balance']);
            if ($decryptedBalance !== false) {
                $totalDue += floatval($decryptedBalance); // Add decrypted balance to total due
            }
        }

        // Retrieve total refunds (decrypted data from refund_requests)
        $stmt3 = $pdo->prepare("SELECT amount_requested
                            FROM refund_requests
                            WHERE status = 'Approved' AND date_requested BETWEEN :from AND :to");
        $stmt3->execute(['from' => $from, 'to' => $to]);
        $totalRefunds = 0.00;

        // Loop through and decrypt amount_requested for refunds
        while ($row = $stmt3->fetch(PDO::FETCH_ASSOC)) {
            $decryptedRefund = decryptData($row['amount_requested']);
            if ($decryptedRefund !== false) {
                $totalRefunds += floatval($decryptedRefund); // Add decrypted refund to total refunds
            }
        }

        // Encrypt the data before storing it in the financial_reports table
        $encryptedCollected = encryptData($totalCollected);
        $encryptedDue = encryptData($totalDue);
        $encryptedRefunds = encryptData($totalRefunds);

        // Insert into financial_reports table with encrypted data
        $stmtInsert = $pdo->prepare("INSERT INTO financial_reports 
                                (report_type, period_from, period_to, total_collected, total_due, total_refunds, generated_by) 
                                VALUES (:type, :from, :to, :collected, :due, :refunds, :generated)");
        $stmtInsert->execute([
            'type' => $report_type,
            'from' => $from,
            'to' => $to,
            'collected' => $encryptedCollected,
            'due' => $encryptedDue,
            'refunds' => $encryptedRefunds,
            'generated' => $username
        ]);

        // Get the last inserted financial report ID
        $reportId = $pdo->lastInsertId();

        // Add an audit trail log with the generated report ID as the entity ID
        addAuditLog($pdo, 'GENERATE', 'financial_reports', $reportId, 'Generated financial report from ' . $from . ' to ' . $to);

        // Prepare grouped breakdown for display
        $reportBreakdown = [];

        if ($report_type === 'Monthly') {
            // Group by month
            $stmtGrouped = $pdo->prepare("
        SELECT DATE_FORMAT(date_created, '%Y-%m') AS period, total_amount 
        FROM invoices 
        WHERE status = 'Paid' AND date_created BETWEEN :from AND :to
    ");
        } else {
            // Group by year
            $stmtGrouped = $pdo->prepare("
        SELECT DATE_FORMAT(date_created, '%Y') AS period, total_amount 
        FROM invoices 
        WHERE status = 'Paid' AND date_created BETWEEN :from AND :to
    ");
        }
        $stmtGrouped->execute(['from' => $from, 'to' => $to]);

        while ($row = $stmtGrouped->fetch(PDO::FETCH_ASSOC)) {
            $period = $row['period'];
            $decrypted = decryptData($row['total_amount']);
            if ($decrypted !== false) {
                if (!isset($reportBreakdown[$period])) {
                    $reportBreakdown[$period] = ['collected' => 0, 'due' => 0, 'refund' => 0];
                }
                $reportBreakdown[$period]['collected'] += floatval($decrypted);
            }
        }

        // Add outstanding balances from fee_summaries (static due)
        $stmtDue = $pdo->prepare("
    SELECT Student_ID, outstanding_balance 
    FROM fee_summaries
");
        $stmtDue->execute();

        foreach ($reportBreakdown as $period => &$data) {
            $data['due'] = 0;
        }
        while ($row = $stmtDue->fetch(PDO::FETCH_ASSOC)) {
            $decrypted = decryptData($row['outstanding_balance']);
            if ($decrypted !== false) {
                foreach ($reportBreakdown as &$entry) {
                    $entry['due'] += floatval($decrypted); // Apply same due to each period
                }
            }
        }

        // Add refund breakdown
        if ($report_type === 'Monthly') {
            $stmtRefund = $pdo->prepare("
        SELECT DATE_FORMAT(date_requested, '%Y-%m') AS period, amount_requested 
        FROM refund_requests 
        WHERE status = 'Approved' AND date_requested BETWEEN :from AND :to
    ");
        } else {
            $stmtRefund = $pdo->prepare("
        SELECT DATE_FORMAT(date_requested, '%Y') AS period, amount_requested 
        FROM refund_requests 
        WHERE status = 'Approved' AND date_requested BETWEEN :from AND :to
    ");
        }
        $stmtRefund->execute(['from' => $from, 'to' => $to]);

        while ($row = $stmtRefund->fetch(PDO::FETCH_ASSOC)) {
            $period = $row['period'];
            $decrypted = decryptData($row['amount_requested']);
            if ($decrypted !== false) {
                if (!isset($reportBreakdown[$period])) {
                    $reportBreakdown[$period] = ['collected' => 0, 'due' => 0, 'refund' => 0];
                }
                $reportBreakdown[$period]['refund'] += floatval($decrypted);
            }
        }
    } catch (PDOException $e) {
        // Handle any database errors
        $errorMessage = 'Error fetching financial report data: ' . $e->getMessage();
        // Log the error to an error log
        error_log($errorMessage);
        $totalCollected = $totalDue = $totalRefunds = 0.00; // Default to zero in case of an error
    }
}


// Handle PDF generation with password if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_pdf'])) {
    // Validate password
    $pdfPassword = $_POST['pdf_password'] ?? '';

    if (empty($pdfPassword)) {
        $pdfError = "Password is required for PDF generation";
    } else {
        try {
            // Get the most recent report from the financial_reports table
            $stmt = $pdo->prepare("
                SELECT report_type, period_from, period_to, 
                       total_collected, total_due, total_refunds, generated_by
                FROM financial_reports
                WHERE generated_by = :username
                ORDER BY date_generated DESC
                LIMIT 1
            ");
            $stmt->execute(['username' => $username]);
            $reportData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reportData) {
                throw new Exception("No financial report found for this user");
            }

            // Decrypt the financial data
            $totalCollected = decryptData($reportData['total_collected']);
            $totalDue = decryptData($reportData['total_due']);
            $totalRefunds = decryptData($reportData['total_refunds']);

            // Validate decrypted data
            if ($totalCollected === false || $totalDue === false || $totalRefunds === false) {
                throw new Exception("Failed to decrypt financial data");
            }

            // Create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Student Information System');
            $pdf->SetTitle('Financial Report');
            $pdf->SetSubject('Financial Report');
            $pdf->SetKeywords('Finance, Report, SIS');

            // Set password protection before adding content
            $pdf->SetProtection(
                array('print', 'copy'),
                $pdfPassword,           // User password
                null,                   // Owner password (same as user if null)
                2                       // Use AES-128 encryption
            );

            $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Student Information System 3220 - Finance', 'Generated on: ' . date('Y-m-d H:i:s'));

            // Set header and footer fonts
            $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            // Set margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            // Set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            // Set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            // Add a page
            $pdf->AddPage();

            // Set font
            $pdf->SetFont('dejavusans', '', 14);
            $pdf->Cell(0, 10, 'Financial Report', 0, 1, 'C');


            // Report details from database
            $pdf->Cell(0, 10, 'Report Period: ' . date('F j, Y', strtotime($reportData['period_from'])) .
                ' - ' . date('F j, Y', strtotime($reportData['period_to'])), 0, 1);
            $pdf->Cell(0, 10, 'Report Type: ' . htmlspecialchars($reportData['report_type']), 0, 1);
            $pdf->Cell(0, 10, 'Generated By: ' . htmlspecialchars($reportData['generated_by']), 0, 1);

            // Summary figures from decrypted data
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 10, 'Summary Figures', 0, 1);
            $pdf->SetFont('dejavusans', '', 12);


            $pdf->Cell(0, 10, 'Total Collected: ₱' . number_format($totalCollected, 2), 0, 1);
            $pdf->Cell(0, 10, 'Total Due: ₱' . number_format($totalDue, 2), 0, 1);
            $pdf->Cell(0, 10, 'Total Refunds: ₱' . number_format($totalRefunds, 2), 0, 1);

            // Additional notes
            $pdf->SetFont('dejavusans', 'I', 10);
            $pdf->Ln(10);
            $pdf->MultiCell(0, 10, 'Note: This report contains sensitive financial information. Please ensure the file is stored securely and the password is not shared.', 0, 'L');

            // Generate a unique filename
            $filename = 'financial_report_' . date('Ymd_His') . '.pdf';

            // Output PDF to browser for download
            $pdf->Output($filename, 'D');
            exit;
        } catch (Exception $e) {
            $pdfError = "Error generating PDF: " . $e->getMessage();
            error_log($pdfError);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/fms-style.css">
    <link rel="shortcut icon" href="./assets/favicon/SIS.ico" type="image/x-icon">
    <title>Finance Management | Report</title>
</head>

<body>
    <!-- Header Section -->
    <header>
        <div class="header-content">
            <img src="./assets/img/SIS-logo.png" alt="Student Information System Logo">
            <div>
                <h1>Student Information System</h1>
                <h2>Finance</h2>
            </div>
        </div>
    </header>

    <!-- Main Content -->
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

        <!-- Financial Report Section -->
        <section class="content">
            <div class="content-header">
                <button class="js-sidenav-toggle" aria-label="Toggle navigation menu">
                    <span class="mdi mdi-menu"></span>
                </button>
                <h3>Financial Report</h3>
            </div>
            <article class="module-content">
                <div class="finance">
                    <form method="GET" class="report-controls">
                        <div class="tabs">
                            <label class="tab">
                                <input type="radio" id="monthly" name="report_type" value="Monthly" <?= $report_type === 'Monthly' ? 'checked' : '' ?>>
                                <span>Monthly</span>
                            </label>
                            <label class="tab">
                                <input type="radio" id="annual" name="report_type" value="Annual" <?= $report_type === 'Annual' ? 'checked' : '' ?>>
                                <span>Annual</span>
                            </label>
                        </div>

                        <div class="date-picker">
                            <label for="from">From:</label>
                            <input type="date" id="from" name="from" required value="<?= htmlspecialchars($from ?? '') ?>">

                            <label for="to">To:</label>
                            <input type="date" id="to" name="to" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= htmlspecialchars($to ?? '') ?>" required>
                            <button type="submit">Generate Report</button>
                        </div>
                    </form>
                    <?php if (isset($reportBreakdownMessage)): ?>
                        <div class="no-data-message">
                            <p><?= htmlspecialchars($reportBreakdownMessage) ?></p>
                        </div>
                    <?php elseif (!empty($reportBreakdown)): ?>
                        <div class="kpi-cards">
                            <div class="card"><strong>Total Collected:</strong> ₱<?= number_format($totalCollected, 2) ?></div>
                            <div class="card"><strong>Total Due:</strong> ₱<?= number_format($totalDue, 2) ?></div>
                            <div class="card"><strong>Total Refunds:</strong> ₱<?= number_format($totalRefunds, 2) ?></div>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <th> <?php echo $report_type === 'Monthly' ? 'Month' : 'Year'; ?></th>
                                    <th>Collected</th>
                                    <th>Due</th>
                                    <th>Refunds</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportBreakdown as $period => $data): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($period) ?></td>
                                        <td>₱<?= number_format($data['collected'], 2) ?></td>
                                        <td>₱<?= number_format($data['due'], 2) ?></td>
                                        <td>₱<?= number_format($data['refund'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="detail-view">
                            <h3>Report Details</h3>
                            <p><strong>Report Period:</strong> <?= date('F j, Y', strtotime($from)) ?> - <?= date('F j, Y', strtotime($to)) ?></p>
                            <p><strong>Report Type:</strong> <?= htmlspecialchars($report_type) ?></p>
                            <p><strong>Summary:</strong> Total transactions reviewed include collections, dues, and refunds for the selected period.</p>
                            <p><strong>Generated By:</strong> <?= htmlspecialchars($username) ?></p>
                        </div>

                        <button id="openPdfModal">Download PDF Report</button>
                        <!-- PDF Download Form - Only shown when report is generated -->
                        <div id="pdfModal" class="modal">
                            <div class="modal-content" id="pdfModalContent">
                                <span class="close-btn" id="closePdfModal">&times;</span>
                                <h3>Download PDF Report</h3>
                                <form method="POST" id="pdfForm">
                                    <input type="hidden" name="generate_pdf" value="1">
                                    <label for="pdf_password">Enter PDF Password:</label>
                                    <input type="password" id="pdf_password" name="pdf_password" required>
                                    <button type="submit">Download PDF</button>
                                    <?php if (isset($pdfError)): ?>
                                        <div class="error-message"><?= htmlspecialchars($pdfError) ?></div>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

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

    <!-- Scripts -->
    <script src="./assets/js/fms-script.js"></script>
    <script src="./assets/js/financial-report.js"></script>


</body>

</html>