USE ridetrack;
ALTER TABLE routes ADD type ENUM('local','intercity') DEFAULT 'intercity';
ALTER TABLE buses ADD kind ENUM('local','intercity') DEFAULT 'intercity';
UPDATE routes SET type='local' WHERE (from_city='Jatrabari' AND to_city='Badda') OR (from_city='Badda' AND to_city='Jatrabari');
INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) VALUES
('DHAKA-METRO-BA-21-1001','Dhaka City Line',3,50,'Abul Kashem','DL-300101','NID-2001',23.7488,90.4125,NOW(),'approved','local'),
('DHAKA-METRO-BA-21-1002','Metro Local',3,50,'Selim Reza','DL-300102','NID-2002',23.7120,90.4300,NOW(),'approved','local'),
('DHAKA-METRO-BA-21-1003','Rapid Local',3,50,'Monir Hossain','DL-300103','NID-2003',23.7776,90.4264,NOW(),'approved','local');
UPDATE buses SET gps_key=MD5(CONCAT(id,RAND(),NOW())) WHERE gps_key IS NULL;
DELETE FROM schedules WHERE route_id IN (SELECT id FROM routes WHERE type='local');
DELETE FROM trips WHERE route_id IN (SELECT id FROM routes WHERE type='local') AND id NOT IN (SELECT trip_id FROM bookings);
INSERT INTO schedules(route_id,bus_id,depart_time)
SELECT r.id,x.b,MAKETIME(h.n,x.m,0) FROM routes r
 JOIN (SELECT id b,0 m FROM buses WHERE reg_number='DHAKA-METRO-BA-21-1001' UNION SELECT id,20 FROM buses WHERE reg_number='DHAKA-METRO-BA-21-1002' UNION SELECT id,40 FROM buses WHERE reg_number='DHAKA-METRO-BA-21-1003') x
 JOIN (SELECT 7 n UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION SELECT 21) h
WHERE r.type='local';
