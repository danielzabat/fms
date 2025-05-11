<?php
session_start();
require_once 'includes/db.php';  // Ensure DB connection

// Declare $pdo as global to access it in this file
global $pdo;  // This makes the $pdo from db.php accessible here.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $refund_id = $_POST['refund_id'] ?? '';
    $status = $_POST['status'] ?? '';

    if (!in_array($status, ['Pending', 'Approved', 'Denied'])) {
        http_response_code(400);
        echo 'Invalid status.';
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE refund_requests SET status = :status WHERE refund_id = :refund_id");
        $stmt->execute([
            ':status' => $status,
            ':refund_id' => $refund_id
        ]);
        echo 'Success';
    } catch (PDOException $e) {
        error_log("Refund Update Error: " . $e->getMessage());
        http_response_code(500);
        echo 'Database error.';
    }
} else {
    http_response_code(405);
    echo 'Method Not Allowed';
}
