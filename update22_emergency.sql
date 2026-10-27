USE ridetrack;
-- Run ONCE. Emergency contacts (up to 5 per user) + SOS location columns.
CREATE TABLE IF NOT EXISTS emergency_contacts(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT,name VARCHAR(60),phone VARCHAR(20),relation VARCHAR(30),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(user_id));
ALTER TABLE sos ADD lat DOUBLE NULL, ADD lng DOUBLE NULL, ADD trip_id INT NULL;
-- Move the old single emergency number (profile) into the new list
INSERT INTO emergency_contacts(user_id,name,phone,relation)
SELECT u.id,'Emergency contact',u.emergency_contact,'Family' FROM users u
WHERE u.emergency_contact IS NOT NULL AND u.emergency_contact<>'' AND u.emergency_contact NOT LIKE '%@%'
AND NOT EXISTS(SELECT 1 FROM emergency_contacts c WHERE c.user_id=u.id);
