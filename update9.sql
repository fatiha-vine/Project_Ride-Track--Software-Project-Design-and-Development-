USE ridetrack;
ALTER TABLE route_stops ADD fare_at DECIMAL(8,2) NULL;
ALTER TABLE bookings ADD from_stop INT NULL, ADD to_stop INT NULL;
-- Badda to Mirpur: Badda 0, Kuril 20, Mirpur 40 (student pays half: Kuril 10, Mirpur 20)
UPDATE route_stops rs JOIN routes r ON r.id=rs.route_id JOIN stops s ON s.id=rs.stop_id
 SET rs.fare_at=CASE s.name WHEN 'Badda' THEN 0 WHEN 'Kuril' THEN 20 WHEN 'Mirpur' THEN 40 END
 WHERE r.from_city='Badda' AND r.to_city='Mirpur' AND s.name IN ('Badda','Kuril','Mirpur');
UPDATE route_stops rs JOIN routes r ON r.id=rs.route_id JOIN stops s ON s.id=rs.stop_id
 SET rs.fare_at=CASE s.name WHEN 'Mirpur' THEN 0 WHEN 'Kuril' THEN 20 WHEN 'Badda' THEN 40 END
 WHERE r.from_city='Mirpur' AND r.to_city='Badda' AND s.name IN ('Badda','Kuril','Mirpur');
