<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/crypto.php';
require_once 'includes/add_audit.php';


requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize and assign POST inputs
        $refund_id = $_POST['refund_id'] ?? '';
        $amount_requested = $_POST['amount_requested'] ?? '';
        $date_requested = $_POST['date_requested'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $Student_ID = $_POST['student_id'] ?? '';

        // Input validation: Ensure no empty fields
        if (empty($Student_ID) || empty($amount_requested) || empty($date_requested) || empty($reason)) {
            $_SESSION['refund_error'] = 'All fields are required.';
            $_SESSION['refund_form_data'] = $_POST;
            header('Location: ../refund-request.php');
            exit;
        }

        // Validate that the student exists
        $check = $pdo->prepare("SELECT COUNT(*) FROM student WHERE Student_ID = :Student_ID");
        $check->bindParam(':Student_ID', $Student_ID);
        $check->execute();

        if ($check->fetchColumn() == 0) {
            $_SESSION['refund_error'] = 'Student ID not found. Please check and try again.';
            $_SESSION['refund_form_data'] = $_POST;
            header('Location: ../refund-request.php');
            exit;
        }

        // Encrypt sensitive fields (but not Student_ID)
        $enc_amount_requested = encryptData($amount_requested);
        $enc_reason = encryptData($reason);

        // Default status
        $status = 'pending';

        // Get the next refund number
        $stmt = $pdo->prepare("SELECT MAX(refund_id) AS max_refund_id FROM refund_requests");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $next_refund_id = $result['max_refund_id'] + 1;

        // Insert into refund_requests
        $query = "INSERT INTO refund_requests (refund_id, Student_ID, amount_requested, reason, status, date_requested)
                  VALUES (:refund_id, :Student_ID, :amount_requested, :reason, :status, :date_requested)";
        $stmt = $pdo->prepare($query);

        $stmt->bindParam(':refund_id', $next_refund_id);
        $stmt->bindParam(':Student_ID', $Student_ID); // Student ID is not encrypted
        $stmt->bindParam(':amount_requested', $enc_amount_requested);
        $stmt->bindParam(':reason', $enc_reason);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':date_requested', $date_requested);

        if ($stmt->execute()) {
            // Add to audit trail
            $description = "Refund request created for student ID {$Student_ID}";
            addAuditLog($pdo, 'CREATE', 'refund_requests', $next_refund_id, $description);

            unset($_SESSION['refund_form_data']);
            unset($_SESSION['refund_error']);
            header('Location: ../refund-request.php?submitted=1');
            exit;
        } else {
            error_log("Refund insert error: " . implode(', ', $stmt->errorInfo()));
            $_SESSION['refund_error'] = 'There was an error processing your request. Please try again.';
            $_SESSION['refund_form_data'] = $_POST;
            header('Location: ../refund-request.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Refund processing exception: " . $e->getMessage());
        $_SESSION['refund_error'] = 'Database error occurred. Please contact the administrator.';
        $_SESSION['refund_form_data'] = $_POST;
        header('Location: ../refund-request.php');
        exit;
    }
}
