<?php

/**
 * Stores an auditable record of a successful administrator action.
 * Logging must never prevent the original action from completing.
 */
function logAdminActivity(PDO $pdo, int $adminId, string $action, string $entityType, ?int $entityId = null, ?string $details = null): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO admin_activity_logs (admin_id, action, entity_type, entity_id, details)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$adminId, $action, $entityType, $entityId, $details]);
    } catch (PDOException $exception) {
        error_log('Admin activity logging error: ' . $exception->getMessage());
    }
}

/**
 * The activity table's `admin_id` column is a legacy name for an ID in the
 * shared users table. Use this wrapper for customer-initiated actions so the
 * activity log can identify the customer who made the change.
 */
function logCustomerActivity(PDO $pdo, int $userId, string $action, string $entityType, ?int $entityId = null, ?string $details = null): void
{
    logAdminActivity($pdo, $userId, $action, $entityType, $entityId, $details);
}
