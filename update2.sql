USE ridetrack;
ALTER TABLE bookings MODIFY status ENUM('pending','confirmed','cancelled') DEFAULT 'confirmed', MODIFY pay_ref VARCHAR(60);
ALTER TABLE card_tx ADD pay_ref VARCHAR(60) NULL;
