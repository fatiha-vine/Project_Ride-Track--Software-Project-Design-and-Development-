USE ridetrack;
-- Run ONCE. Lets staff/driver mark an e-ticket as boarded so it cannot be reused.
ALTER TABLE bookings ADD boarded_at DATETIME NULL;
