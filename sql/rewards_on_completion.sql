-- One-time cleanup for installations that awarded points at booking creation.
-- It removes reward transactions for bookings that never reached completed,
-- while leaving points from completed services intact.
START TRANSACTION;

UPDATE rewards r
JOIN (
    SELECT orphaned_rewards.user_id,
           SUM(orphaned_rewards.net_points) AS points_to_remove,
           COUNT(*) AS visits_to_remove
    FROM (
        SELECT rt.user_id, rt.booking_id,
               SUM(rt.points_earned - rt.points_redeemed) AS net_points
        FROM reward_transactions rt
        JOIN bookings b ON b.id = rt.booking_id
        WHERE b.status <> 'completed'
        GROUP BY rt.user_id, rt.booking_id
        HAVING SUM(rt.points_earned - rt.points_redeemed) > 0
    ) orphaned_rewards
    GROUP BY orphaned_rewards.user_id
) cleanup ON cleanup.user_id = r.user_id
SET r.points = GREATEST(0, r.points - cleanup.points_to_remove),
    r.visits = GREATEST(0, r.visits - cleanup.visits_to_remove),
    r.tier = CASE
        WHEN GREATEST(0, r.points - cleanup.points_to_remove) >= 200 THEN 'platinum'
        WHEN GREATEST(0, r.points - cleanup.points_to_remove) >= 150 THEN 'gold'
        WHEN GREATEST(0, r.points - cleanup.points_to_remove) >= 100 THEN 'silver'
        ELSE 'bronze'
    END;

DELETE rt
FROM reward_transactions rt
JOIN bookings b ON b.id = rt.booking_id
WHERE b.status <> 'completed';

COMMIT;
