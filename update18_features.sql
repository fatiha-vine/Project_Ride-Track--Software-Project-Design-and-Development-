USE ridetrack;
-- Run ONCE. Group photo, Driver role, Police control, Staff cash card er jonno.
ALTER TABLE group_posts ADD photo VARCHAR(80) NULL;
ALTER TABLE users MODIFY role ENUM('passenger','operator','staff','admin','police','driver') DEFAULT 'passenger';
ALTER TABLE users ADD card_no VARCHAR(16) NULL;
ALTER TABLE buses ADD driver_user_id INT NULL, ADD suspend_reason VARCHAR(160) NULL;
ALTER TABLE card_tx ADD by_staff INT NULL;
