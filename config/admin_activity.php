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
