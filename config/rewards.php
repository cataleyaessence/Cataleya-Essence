<?php
declare(strict_types=1);

/**
 * Rewards are earned only after the appointment is completed.
 * Call these helpers while the caller's database transaction is open.
 */
function rewardPointsForCompletedService(float $price): int
{
    if ($price <= 400) {
        return 10;
    }
    if ($price <= 799) {
        return 15;
    }
    return 20;
}

function rewardTierForPoints(int $points): string
{
    if ($points >= 200) {
        return 'platinum';
    }
    if ($points >= 150) {
        return 'gold';
    }
    if ($points >= 100) {
        return 'silver';
    }
    return 'bronze';
}

/**
 * Awards one set of points for a completed booking. Returns the newly awarded
 * points, or 0 if the booking was already rewarded.
 */
function awardCompletedBookingRewards(PDO $pdo, int $bookingId): int
{
    $bookingStmt = $pdo->prepare(
        "SELECT b.id, b.user_id, COALESCE(s.price, b.total_amount) AS service_price
         FROM bookings b
         LEFT JOIN services s ON s.id = b.service_id
         WHERE b.id = ? AND b.status = 'completed'
         FOR UPDATE"
    );
    $bookingStmt->execute([$bookingId]);
    $booking = $bookingStmt->fetch();
    if (!$booking) {
        return 0;
    }

    $userId = (int) $booking['user_id'];
    $transactionStmt = $pdo->prepare(
        'SELECT id, description, points_earned, points_redeemed
         FROM reward_transactions
         WHERE booking_id = ? AND user_id = ?
         FOR UPDATE'
    );
    $transactionStmt->execute([$bookingId, $userId]);
    $transactions = $transactionStmt->fetchAll();

    foreach ($transactions as $transaction) {
        if ((int) $transaction['points_earned'] > 0 && (int) $transaction['points_redeemed'] === 0) {
            // A legacy booking may already have points from before the
            // completion-only policy. Keep it once, but label it correctly.
            if ($transaction['description'] !== 'Completed service reward points') {
                $labelStmt = $pdo->prepare(
                    "UPDATE reward_transactions
                     SET description = 'Completed service reward points'
                     WHERE id = ?"
                );
                $labelStmt->execute([(int) $transaction['id']]);
            }
            return 0;
        }
    }

    $pointsToAdd = rewardPointsForCompletedService((float) $booking['service_price']);

    $rewardsStmt = $pdo->prepare('SELECT points, visits FROM rewards WHERE user_id = ? FOR UPDATE');
    $rewardsStmt->execute([$userId]);
    $rewards = $rewardsStmt->fetch();

    if ($rewards) {
        $newPoints = (int) $rewards['points'] + $pointsToAdd;
        $newVisits = (int) $rewards['visits'] + 1;
        $updateRewards = $pdo->prepare('UPDATE rewards SET points = ?, visits = ?, tier = ? WHERE user_id = ?');
        $updateRewards->execute([$newPoints, $newVisits, rewardTierForPoints($newPoints), $userId]);
    } else {
        $newTier = rewardTierForPoints($pointsToAdd);
        $createRewards = $pdo->prepare('INSERT INTO rewards (user_id, points, visits, tier) VALUES (?, ?, 1, ?)');
        $createRewards->execute([$userId, $pointsToAdd, $newTier]);
    }

    $insertTransaction = $pdo->prepare(
        "INSERT INTO reward_transactions (user_id, points_earned, points_redeemed, description, booking_id)
         VALUES (?, ?, 0, 'Completed service reward points', ?)"
    );
    $insertTransaction->execute([$userId, $pointsToAdd, $bookingId]);

    return $pointsToAdd;
}

/**
 * Removes any legacy reward transaction attached to a booking before it is
 * cancelled. New active bookings have no points, so this normally changes 0.
 */
function removeBookingRewardPoints(PDO $pdo, int $bookingId, int $userId): int
{
    $transactionStmt = $pdo->prepare(
        'SELECT points_earned, points_redeemed
         FROM reward_transactions
         WHERE booking_id = ? AND user_id = ?
         FOR UPDATE'
    );
    $transactionStmt->execute([$bookingId, $userId]);

    $netPoints = 0;
    $hasAwardedPoints = false;
    foreach ($transactionStmt->fetchAll() as $transaction) {
        $earned = (int) $transaction['points_earned'];
        $netPoints += $earned - (int) $transaction['points_redeemed'];
        $hasAwardedPoints = $hasAwardedPoints || $earned > 0;
    }

    if ($netPoints > 0) {
        $rewardsStmt = $pdo->prepare('SELECT points, visits FROM rewards WHERE user_id = ? FOR UPDATE');
        $rewardsStmt->execute([$userId]);
        $rewards = $rewardsStmt->fetch();
        if ($rewards) {
            $newPoints = max(0, (int) $rewards['points'] - $netPoints);
            $newVisits = $hasAwardedPoints ? max(0, (int) $rewards['visits'] - 1) : (int) $rewards['visits'];
            $updateRewards = $pdo->prepare('UPDATE rewards SET points = ?, visits = ?, tier = ? WHERE user_id = ?');
            $updateRewards->execute([$newPoints, $newVisits, rewardTierForPoints($newPoints), $userId]);
        }
    }

    $deleteTransactions = $pdo->prepare('DELETE FROM reward_transactions WHERE booking_id = ? AND user_id = ?');
    $deleteTransactions->execute([$bookingId, $userId]);

    return max(0, $netPoints);
}
