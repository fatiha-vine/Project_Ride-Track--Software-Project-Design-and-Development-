USE ridetrack;
ALTER TABLE users ADD student_session VARCHAR(9) NULL, ADD student_expiry DATE NULL;
UPDATE users SET student_session='2025-26', student_expiry='2027-12-31' WHERE email='student@ridetrack.com';
ALTER TABLE buses ADD driver_id_no VARCHAR(40) NULL, ADD gps_key CHAR(32) NULL;
UPDATE buses SET gps_key=MD5(CONCAT(id,RAND(),NOW())), driver_id_no=CONCAT('NID-',1000+id);
ALTER TABLE report_votes ADD vote TINYINT DEFAULT 1;
ALTER TABLE reports ADD down INT DEFAULT 0;
INSERT INTO settings VALUES('hide_votes','5');
