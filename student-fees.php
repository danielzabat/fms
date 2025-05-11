<?php
include 'php/includes/db.php';
require_once 'php/includes/auth.php';
require_once 'php/includes/add_audit.php';
require_once 'php/includes/crypto.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']); // safe output
$user_role = $_SESSION['user_role'];

// Fetch existing students for the dropdown
$students_stmt = $pdo->query("SELECT Student_ID, CONCAT(Student_ID, ' ', '|', ' ', First_Name, ' ', Last_Name) AS full_name FROM Student");
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission for creating a new fee
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_fee'])) {
    $student_id = $_POST['student_id'];
    $fee_type = $_POST['fee_type'];
    $amount = $_POST['amount'];
    $discount_amount = $_POST['discount_amount'];

    $enc_amount = encryptData($amount);
    $enc_discount = encryptData($discount_amount);

    $stmt = $pdo->prepare("INSERT INTO student_fees (Student_ID, fee_type, amount, discount_amount) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$student_id, $fee_type, $enc_amount, $enc_discount])) {

        $fee_id = $pdo->lastInsertId(); // Get the last inserted fee ID
        addAuditLog($pdo, 'INSERT', 'student_fees', $fee_id, "Added fee for student ID $student_id");
        // Update fee summary
        updateFeeSummary($student_id, $amount, $discount_amount);
        echo "<script>alert('New fee added successfully');</script>";
    } else {
        echo "<script>alert('Error: Could not add fee.');</script>";
    }
}

// Handle form submission for editing a fee
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_fee'])) {
    $fee_id = $_POST['fee_id'];
    $amount = $_POST['amount'];
    $discount_amount = $_POST['discount_amount'];

    try {
        $enc_amount = encryptData($amount);
        $enc_discount = encryptData($discount_amount);

        $stmt = $pdo->prepare("UPDATE student_fees SET amount = ?, discount_amount = ? WHERE fee_id = ?");
        if ($stmt->execute([$enc_amount, $enc_discount, $fee_id])) {
            addAuditLog($pdo, 'UPDATE', 'student_fees', $fee_id, "Updated fee ID $fee_id");
            echo "<script>alert('Fee updated successfully.');</script>";
        } else {
            throw new Exception("Update failed.");
        }
    } catch (Exception $e) {
        error_log("Update fee error: " . $e->getMessage());
        echo "<script>alert('Error: Could not update fee.');</script>";
    }
}

// Handle fee deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_fee'])) {
    $fee_id = $_POST['fee_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM student_fees WHERE fee_id = ?");
        if ($stmt->execute([$fee_id])) {
            addAuditLog($pdo, 'DELETE', 'student_fees', $fee_id, "Deleted fee record ID $fee_id");
            echo "<script>alert('Fee deleted successfully.');</script>";
        } else {
            throw new Exception("Delete failed.");
        }
    } catch (Exception $e) {
        error_log("Delete fee error: " . $e->getMessage());
        echo "<script>alert('Error: Could not delete fee.');</script>";
    }
}

// Fetch existing fees with optional filtering
$filter_student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

$query = "SELECT sf.fee_id, sf.Student_ID, sf.fee_type, sf.amount, sf.discount_amount, CONCAT(s.First_Name, ' ', s.Last_Name) AS student_name 
          FROM student_fees sf 
          JOIN student s ON sf.Student_ID = s.Student_ID 
          WHERE 1=1";
$params = [];

if ($filter_student_id) {
    $query .= " AND sf.Student_ID = ?";
    $params[] = $filter_student_id;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to update fee summary
function updateFeeSummary($student_id, $amount, $discount_amount)
{
    global $pdo;

    $total_fee = $amount - $discount_amount;

    $stmt = $pdo->prepare("SELECT * FROM fee_summaries WHERE Student_ID = ?");
    $stmt->execute([$student_id]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($summary) {
        $curr_total = decryptData($summary['total_computed_fee']);
        $curr_paid = decryptData($summary['amount_paid']);
        $new_total = $curr_total + $total_fee;
        $new_balance = $new_total - $curr_paid;

        $enc_total = encryptData($new_total);
        $enc_balance = encryptData($new_balance);

        $update_stmt = $pdo->prepare("UPDATE fee_summaries SET total_computed_fee = ?, outstanding_balance = ? WHERE Student_ID = ?");
        $update_stmt->execute([$enc_total, $enc_balance, $student_id]);
    } else {
        $enc_total = encryptData($total_fee);
        $enc_paid = encryptData(0.00);
        $enc_balance = encryptData($total_fee);

        $stmt = $pdo->prepare("INSERT INTO fee_summaries (Student_ID, total_computed_fee, amount_paid, outstanding_balance) VALUES (?, ?, ?, ?)");
        $stmt->execute([$student_id, $enc_total, $enc_paid, $enc_balance]);
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
    <title>Finance Management | Student Fees</title>
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
            <nav aria-label="User  options">
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
                <h3>Manage Student Fees</h3>
            </div>
            <article class="module-content">
                <div class="billing-container">
                    <h2>Add New Fee</h2>
                    <form method="POST" action="">
                        <label for="student_id">Student:</label>
                        <select id="student_id" name="student_id" required>
                            <option value="">Select a student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['Student_ID']; ?>"><?php echo $student['full_name']; ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label for="fee_type">Fee Type:</label>
                        <select id="fee_type" name="fee_type" required>
                            <option value="Tuition">Tuition</option>
                            <option value="Miscellaneous">Miscellaneous</option>
                            <option value="Laboratory">Laboratory</option>
                            <option value="Other">Other</option>
                        </select>

                        <label for="amount">Amount:</label>
                        <input type="number" id="amount" name="amount" required>

                        <label for="discount_amount">Discount Amount:</label>
                        <input type="number" id="discount_amount" name="discount_amount" value="0.00">

                        <button type="submit" name="add_fee">Add Fee</button>
                    </form>

                    <h3>Existing Fees</h3>
                    <form method="GET" action="">
                        <label for="filter_student_id">Filter by Student ID:</label>
                        <input type="text" id="filter_student_id" name="student_id" value="<?php echo htmlspecialchars($filter_student_id); ?>">
                        <button type="submit">Filter</button>
                    </form>

                    <table>
                        <tr>
                            <th>Fee ID</th>
                            <th>Student ID</th>
                            <th>Fee Type</th>
                            <th>Amount</th>
                            <th>Discount Amount</th>
                            <th>Actions</th>
                        </tr>
                        <?php foreach ($fees as $row): ?>
                            <tr>
                                <td><?php echo $row['fee_id']; ?></td>
                                <td><?php echo $row['Student_ID']; ?></td>
                                <td><?php echo $row['fee_type']; ?></td>
                                <td>₱ <?php echo number_format(decryptData($row['amount']), 2); ?></td>
                                <td>₱ <?php echo number_format(decryptData($row['discount_amount']), 2); ?></td>
                                <td>
                                    <!-- Edit Fee Form -->
                                    <form method="POST" action="" style="display:inline-block;">
                                        <input type="hidden" name="fee_id" value="<?php echo $row['fee_id']; ?>">
                                        <input type="number" name="amount" placeholder="New Amount" required>
                                        <input type="number" name="discount_amount" placeholder="New Discount" value="0.00">
                                        <button type="submit" name="edit_fee">Edit</button>
                                    </form>
                                    <!-- Delete Fee Form -->
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="fee_id" value="<?php echo $row['fee_id']; ?>">
                                        <button type="submit" name="delete_fee" onclick="return confirm('Are you sure you want to delete this fee?');">Delete</button>
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
</body>

</html>