USE ridetrack;
-- Purono Banglish notification gulo English e bodlay (ekbar chalaw).
UPDATE notifications
SET msg = CONCAT('You have been assigned as the driver of bus ', TRIM(SUBSTRING_INDEX(SUBSTRING(msg, 12), ' er driver', 1)), '. Open the Driver portal to start sharing GPS.')
WHERE msg LIKE 'Tomake bus % er driver hishebe assign kora hoyeche.%';
UPDATE notifications
SET msg = CONCAT('Your travel card was recharged with ৳', TRIM(SUBSTRING_INDEX(SUBSTRING(msg, LOCATE('৳', msg) + 1), ' cash', 1)), ' cash at the counter.')
WHERE msg LIKE 'Counter e ৳% cash diye card recharge hoyeche.';
