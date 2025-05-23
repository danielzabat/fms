<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/crypto.php';
require_once 'vendor/tcpdf/tcpdf.php';

// Allow only logged-in admins
requireLogin();
requireRole('admin');

// Input: report ID and password prompt (optional)
$reportId = $_GET['id'] ?? null;
$passwordInput = $_POST['password'] ?? null;

header('Content-Type: application/json');

// Handle missing or invalid parameters
if (!$reportId || !is_numeric($reportId)) {
    http_response_code(400); // Bad request
    echo json_encode(['error' => 'Invalid or missing report ID']);
    exit;
}

// Fetch report and verify ownership
try {
    $stmt = $pdo->prepare("SELECT * FROM financial_reports WHERE id = :id");
    $stmt->execute(['id' => $reportId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        http_response_code(404); // Not found
        echo json_encode(['error' => 'Report not found']);
        exit;
    }

    // Ensure only the generating admin can download
    if ($report['generated_by'] !== $_SESSION['username']) {
        http_response_code(403); // Forbidden
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Check password (prompt user for re-authentication)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Fetch hashed password from DB
        $userStmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
        $userStmt->execute(['id' => $_SESSION['user_id']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($passwordInput, $user['password'])) {
            http_response_code(401); // Unauthorized
            echo json_encode(['error' => 'Invalid password']);
            exit;
        }

        // Decrypt values
        $collected = decryptData($report['total_collected']);
        $due = decryptData($report['total_due']);
        $refunds = decryptData($report['total_refunds']);

        // Create PDF
        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Student Information System');
        $pdf->SetTitle('Financial Report');
        $pdf->SetMargins(20, 20, 20);
        $pdf->AddPage();

        $html = "<h1>Financial Report</h1>
                 <p><strong>Period:</strong> {$report['period_from']} to {$report['period_to']}</p>
                 <p><strong>Type:</strong> {$report['report_type']}</p>
                 <p><strong>Generated By:</strong> {$report['generated_by']}</p>
                 <hr>
                 <p><strong>Total Collected:</strong> ₱" . number_format($collected, 2) . "</p>
                 <p><strong>Total Due:</strong> ₱" . number_format($due, 2) . "</p>
                 <p><strong>Total Refunds:</strong> ₱" . number_format($refunds, 2) . "</p>";

        $pdf->writeHTML($html, true, false, true, false, '');

        // Password-protect PDF using the user's input
        $pdf->SetProtection(['print'], $passwordInput, null, 0, null);

        $filename = 'Financial_Report_' . $reportId . '.pdf';

        // Send as downloadable file
        $pdf->Output($filename, 'D'); // D = Download
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500); // Server error
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
}
