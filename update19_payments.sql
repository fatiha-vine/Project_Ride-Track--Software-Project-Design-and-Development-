USE ridetrack;
-- Run ONCE (phpMyAdmin > ridetrack > Import).
-- Manual payment requests (bKash / Nagad / Rocket / Bank), staff approval, report auto-expiry.
ALTER TABLE card_tx MODIFY type VARCHAR(12), ADD method VARCHAR(10) NULL;
REPLACE INTO settings VALUES('report_hours','12'),('pay_number','01XXXXXXXXX');
