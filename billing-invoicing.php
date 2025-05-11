<?php


include 'php/includes/db.php';
require_once 'php/includes/auth.php';
require_once 'php/includes/add_audit.php';
require_once 'php/includes/crypto.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);
$user_role = $_SESSION['user_role'];

$students_stmt = $pdo->query("SELECT Student_ID FROM Student");
$students = $students_stmt->fetchAll();


function generateInvoiceNumber($pdo)
{
    $datePart = date('Y-md');
    $prefix = "INV-$datePart-";

    // Fetch the last invoice number for the current date
    $stmt = $pdo->prepare("SELECT invoice_number FROM invoices ORDER BY invoice_id DESC LIMIT 1");
    $stmt->execute();
    $lastInvoiceEncrypted = $stmt->fetchColumn();

    $newNumber = '0001';

    if ($lastInvoiceEncrypted) {
        $lastInvoice = decryptData($lastInvoiceEncrypted);

        // Match the latest invoice with the same date format
        if (preg_match("/^INV-$datePart-(\d{4})$/", $lastInvoice, $matches)) {
            $lastNumber = (int)$matches[1];
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }
    }

    return $prefix . $newNumber;
}


// Handle form submission for creating a new invoice
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_invoice'])) {
    $student_id = $_POST['student_id'];
    $invoice_number = encryptData(generateInvoiceNumber($pdo));
    $total_amount = encryptData($_POST['total_amount']);
    $status = $_POST['status'];
    $date_created = date('Y-m-d H:i:s');

    // Validate that total amount is not negative
    $decrypted_amount = decryptData($total_amount);
    if ($decrypted_amount < 0) {
        echo "<script>alert('Error: Total amount cannot be negative.');</script>";
    } else {
        // Prepare and execute the statement
        $stmt = $pdo->prepare("INSERT INTO invoices (Student_ID, invoice_number, date_created, status, total_amount) VALUES (?, ?, ?, ?, ?)");

        // Execute the statement
        if ($stmt->execute([$student_id, $invoice_number, $date_created, $status, $total_amount])) {
            $invoice_id = $pdo->lastInsertId();
            addAuditLog($pdo, 'CREATE', 'invoices', $invoice_id, "Created new invoice for student ID $student_id.");
            echo "<script>alert('New invoice created successfully');</script>";
        } else {
            echo "<script>alert('Error: Could not create invoice.');</script>";
        }
    }
    header("Location: billing-invoicing.php");
    exit();
}

// Handle form submission for processing payments
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_payment'])) {
    $invoice_id = $_POST['invoice_id'];
    $payment_amount = encryptData($_POST['payment_amount']);

    // First, get the current total amount
    $stmt = $pdo->prepare("SELECT total_amount FROM invoices WHERE invoice_id = ?");
    $stmt->execute([$invoice_id]);
    $encrypted_current_amount = $stmt->fetchColumn();
    $current_amount = decryptData($encrypted_current_amount);

    // Validate that payment amount is positive and doesn't exceed current amount
    $decrypted_payment = decryptData($payment_amount);
    if ($decrypted_payment <= 0) {
        echo "<script>alert('Error: Payment amount must be greater than zero.');</script>";
    } elseif ($decrypted_payment > $current_amount) {
        echo "<script>alert('Error: Payment amount cannot exceed the remaining balance of " . number_format($current_amount, 2) . "');</script>";
    } else {
        // Update the invoice status and total amount
        $stmt = $pdo->prepare("UPDATE invoices SET total_amount = ?, status = CASE WHEN ? <= 0 THEN 'paid' ELSE 'partial' END WHERE invoice_id = ?");

        // Calculate new encrypted amount
        $new_amount = encryptData($current_amount - $decrypted_payment);

        if ($stmt->execute([$new_amount, $current_amount - $decrypted_payment, $invoice_id])) {
            addAuditLog($pdo, 'UPDATE', 'invoices', $invoice_id, "Processed payment for invoice ID $invoice_id.");
            echo "<script>alert('Payment processed successfully.');</script>";
        } else {
            echo "<script>alert('Error: Could not process payment.');</script>";
        }
    }
    header("Location: billing-invoicing.php");
    exit();
}

// Handle form submission for editing an invoice
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_invoice'])) {
    $invoice_id = $_POST['invoice_id'];
    $total_amount = encryptData($_POST['total_amount']);
    $status = $_POST['status'];

    // Validate that total amount is not negative
    $decrypted_amount = decryptData($total_amount);
    if ($decrypted_amount < 0) {
        echo "<script>alert('Error: Total amount cannot be negative.');</script>";
    } else {
        // Update the invoice details
        $stmt = $pdo->prepare("UPDATE invoices SET total_amount = ?, status = ? WHERE invoice_id = ?");
        if ($stmt->execute([$total_amount, $status, $invoice_id])) {
            addAuditLog($pdo, 'UPDATE', 'invoices', $invoice_id, "Edited invoice ID $invoice_id.");
            echo "<script>alert('Invoice updated successfully.');</script>";
        } else {
            echo "<script>alert('Error: Could not update invoice.');</script>";
        }
    }
    header("Location: billing-invoicing.php");
    exit();
}

// Handle invoice deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_invoice'])) {
    $invoice_id = $_POST['invoice_id'];

    // Delete the invoice
    $stmt = $pdo->prepare("DELETE FROM invoices WHERE invoice_id = ?");
    if ($stmt->execute([$invoice_id])) {
        addAuditLog($pdo, 'DELETE', 'invoices', $invoice_id, "Deleted invoice ID $invoice_id.");
        echo "<script>alert('Invoice deleted successfully.');</script>";
    } else {
        echo "<script>alert('Error: Could not delete invoice.');</script>";
    }
    header("Location: billing-invoicing.php");
    exit();
}

// Fetch existing invoices with optional filtering
$filter_student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

$query = "SELECT * FROM invoices WHERE 1=1";
$params = [];

if ($filter_student_id) {
    $query .= " AND student_id = ?";
    $params[] = $filter_student_id;
}

if ($filter_status) {
    $query .= " AND status = ?";
    $params[] = $filter_status;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Decrypt sensitive data for display
foreach ($invoices as &$row) {
    $row['invoice_number'] = decryptData($row['invoice_number']);
    $row['total_amount'] = decryptData($row['total_amount']);
}
unset($row);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/fms-style.css">
    <link rel="shortcut icon" href="./assets/favicon/SIS.ico" type="image/x-icon">
    <title>Finance Management | Billing Invoicing</title>
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
                <h3>Billing Invoice</h3>
            </div>
            <article class="module-content">
                <div class="billing-container">
                    <h2>Billing Invoicing</h2>

                    <!-- Create Invoice Form -->
                    <form method="POST" action="">
                        <label for="student_id">Student ID:</label>
                        <select id="student_id" name="student_id" required>
                            <option value="">Select a student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo htmlspecialchars($student['Student_ID']); ?>"><?php echo htmlspecialchars($student['Student_ID']); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label for="invoice_number_display">Invoice Number:</label>
                        <input type="text" id="invoice_number_display" value="Auto-generated" disabled>

                        <label for="total_amount">Total Amount:</label>
                        <input type="number" id="total_amount" name="total_amount" min="0" step="0.01" required>

                        <label for="status">Status:</label>
                        <select id="status" name="status">
                            <option value="paid">Paid</option>
                            <option value="partial">Partial</option>
                            <option value="not_paid">Not Paid</option>
                        </select>

                        <button type="submit" name="create_invoice">Create Invoice</button>
                    </form>

                    <!-- Filter Invoices -->
                    <h3>Filter Invoices</h3>
                    <form method="GET" action="">
                        <label for="filter_student_id">Student ID:</label>
                        <input type="text" id="filter_student_id" name="student_id" value="<?php echo htmlspecialchars($filter_student_id); ?>">

                        <label for="filter_status">Status:</label>
                        <select id="filter_status" name="status">
                            <option value="">All</option>
                            <option value="paid" <?php if ($filter_status == 'paid') echo 'selected'; ?>>Paid</option>
                            <option value="partial" <?php if ($filter_status == 'partial') echo 'selected'; ?>>Partial</option>
                            <option value="not_paid" <?php if ($filter_status == 'not_paid') echo 'selected'; ?>>Not Paid</option>
                        </select>

                        <button type="submit">Filter</button>
                    </form>

                    <h3>Existing Invoices</h3>
                    <table>
                        <tr>
                            <th>Invoice ID</th>
                            <th>Student ID</th>
                            <th>Invoice Number</th>
                            <th>Date Created</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                            <th>Actions</th>
                        </tr>
                        <?php foreach ($invoices as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['invoice_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['Student_ID']); ?></td>
                                <td><?php echo htmlspecialchars($row['invoice_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['date_created']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td><?php echo number_format($row['total_amount'], 2); ?></td>
                                <td>
                                    <!-- Payment Processing Form -->
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="invoice_id" value="<?php echo htmlspecialchars($row['invoice_id']); ?>">
                                        <input type="number" name="payment_amount" placeholder="Payment Amount" min="0.01" max="<?php echo $row['total_amount']; ?>" step="0.01" required>
                                        <button type="submit" name="process_payment">Pay</button>
                                    </form>
                                    <!-- Edit Invoice Form -->
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="invoice_id" value="<?php echo htmlspecialchars($row['invoice_id']); ?>">
                                        <input type="number" name="total_amount" placeholder="New Total Amount" min="0" step="0.01" value="<?php echo $row['total_amount']; ?>" required>
                                        <select name="status" required>
                                            <option value="paid" <?php if ($row['status'] == 'paid') echo 'selected'; ?>>Paid</option>
                                            <option value="partial" <?php if ($row['status'] == 'partial') echo 'selected'; ?>>Partial</option>
                                            <option value="not_paid" <?php if ($row['status'] == 'not_paid') echo 'selected'; ?>>Not Paid</option>
                                        </select>
                                        <button type="submit" name="edit_invoice">Edit</button>
                                    </form>
                                    <!-- Delete Invoice Form -->
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="invoice_id" value="<?php echo htmlspecialchars($row['invoice_id']); ?>">
                                        <button type="submit" name="delete_invoice" onclick="return confirm('Are you sure you want to delete this invoice?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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

    </script>
</body>

</html>