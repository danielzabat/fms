<?php

/**
 * Adds an entry to the audit_trail table.
 *
 * Usage:
 *    require_once 'audit_log.php';
 *    addAuditLog($pdo, 'CREATE', 'students', 101, 'Created new student record.');
 *
 * @param PDO $pdo PDO database connection object.
 * @param string $action The type of action performed (e.g., CREATE, UPDATE, DELETE).
 * @param string $entity The table or module affected.
 * @param int $entity_id The ID of the affected record.
 * @param string $description Additional details about the action.
 * @return bool True if audit log is successfully added, false otherwise.
 */
function addAuditLog(PDO $pdo, string $action, string $entity, int $entity_id, string $description): bool
{
    // Ensure session is started before accessing session variables
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Check if user session variables are set
    if (!isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['user_role'])) {
        return false; // Cannot proceed without valid session
    }

    // Sanitize session data (defensive programming)
    $user_id = intval($_SESSION['user_id']);

    try {
        // Prepare the SQL statement using placeholders for safety
        $stmt = $pdo->prepare("
            INSERT INTO finance_audit_trail (user_id, action, entity, entity_id, description)
            VALUES (:user_id, :action, :entity, :entity_id, :description)
        ");

        // Bind parameters securely
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':action', $action, PDO::PARAM_STR);
        $stmt->bindParam(':entity', $entity, PDO::PARAM_STR);
        $stmt->bindParam(':entity_id', $entity_id, PDO::PARAM_INT);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);

        // Execute the insert query
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}
