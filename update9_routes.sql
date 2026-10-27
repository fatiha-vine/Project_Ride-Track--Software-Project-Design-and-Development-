USE ridetrack;
-- Run ONCE. Buses page (search.php) e notun long-distance route + ticket (seat select) dekhabe.
-- Chattogram, Sylhet, Rajshahi, Cox's Bazar age thekei ache; ekhane Barishal, Comilla, Feni, Dinajpur add hocche.

-- 1) Shohor (stops)
INSERT IGNORE INTO stops(name,lat,lng) VALUES
('Barishal',22.7010,90.3535),('Comilla',23.4607,91.1809),('Feni',23.0159,91.3976),('Dinajpur',25.6217,88.6354);

-- 2) Route + fare (duto dik)
INSERT INTO routes(from_city,to_city,fare,type) VALUES
('Dhaka','Barishal',550,'intercity'),('Barishal','Dhaka',550,'intercity'),
('Dhaka','Comilla',350,'intercity'),('Comilla','Dhaka',350,'intercity'),
('Dhaka','Feni',450,'intercity'),('Feni','Dhaka',450,'intercity'),
('Dhaka','Dinajpur',1000,'intercity'),('Dinajpur','Dhaka',1000,'intercity');

-- 3) Route stops (from = 1st, to = 2nd, offset = travel minute)
INSERT INTO route_stops SELECT r.id,s.id,1,0 FROM routes r JOIN stops s ON s.name=r.from_city
 WHERE r.from_city IN('Barishal','Comilla','Feni','Dinajpur') OR r.to_city IN('Barishal','Comilla','Feni','Dinajpur');
INSERT INTO route_stops SELECT r.id,s.id,2,
 CASE WHEN r.from_city='Comilla' OR r.to_city='Comilla' THEN 180
      WHEN r.from_city='Feni' OR r.to_city='Feni' THEN 240
      WHEN r.from_city='Barishal' OR r.to_city='Barishal' THEN 420 ELSE 600 END
 FROM routes r JOIN stops s ON s.name=r.to_city
 WHERE r.from_city IN('Barishal','Comilla','Feni','Dinajpur') OR r.to_city IN('Barishal','Comilla','Feni','Dinajpur');

-- 4) Notun bus (approved, intercity)
INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) VALUES
('BARISHAL-METRO-BA-13-3001','Barishal Express',3,40,'Nazrul Islam','DL-400101','NID-3001',22.7010,90.3535,NOW(),'approved','intercity'),
('COMILLA-METRO-BA-13-3002','Comilla Paribahan',3,40,'Habib Ullah','DL-400102','NID-3002',23.4607,91.1809,NOW(),'approved','intercity'),
('FENI-METRO-BA-13-3003','Feni Super',3,40,'Shafiq Ahmed','DL-400103','NID-3003',23.0159,91.3976,NOW(),'approved','intercity'),
('DINAJPUR-METRO-BA-13-3004','Dinajpur Link',3,40,'Anwar Hossain','DL-400104','NID-3004',25.6217,88.6354,NOW(),'approved','intercity');
UPDATE buses SET gps_key=MD5(CONCAT(id,RAND(),NOW())) WHERE gps_key IS NULL;

-- 5) Daily schedule (prottek dike 2 ta kore)
INSERT INTO schedules(route_id,bus_id,depart_time)
SELECT r.id,b.id,x.tm FROM routes r
 JOIN (SELECT 'Dhaka' f,'Barishal' t,'BARISHAL-METRO-BA-13-3001' reg,'07:30' tm UNION SELECT 'Dhaka','Barishal','BARISHAL-METRO-BA-13-3001','22:00'
  UNION SELECT 'Barishal','Dhaka','BARISHAL-METRO-BA-13-3001','08:30' UNION SELECT 'Barishal','Dhaka','BARISHAL-METRO-BA-13-3001','21:00'
  UNION SELECT 'Dhaka','Comilla','COMILLA-METRO-BA-13-3002','08:00' UNION SELECT 'Dhaka','Comilla','COMILLA-METRO-BA-13-3002','16:00'
  UNION SELECT 'Comilla','Dhaka','COMILLA-METRO-BA-13-3002','10:00' UNION SELECT 'Comilla','Dhaka','COMILLA-METRO-BA-13-3002','18:00'
  UNION SELECT 'Dhaka','Feni','FENI-METRO-BA-13-3003','09:00' UNION SELECT 'Dhaka','Feni','FENI-METRO-BA-13-3003','21:30'
  UNION SELECT 'Feni','Dhaka','FENI-METRO-BA-13-3003','07:00' UNION SELECT 'Feni','Dhaka','FENI-METRO-BA-13-3003','20:00'
  UNION SELECT 'Dhaka','Dinajpur','DINAJPUR-METRO-BA-13-3004','21:00' UNION SELECT 'Dhaka','Dinajpur','DINAJPUR-METRO-BA-13-3004','22:30'
  UNION SELECT 'Dinajpur','Dhaka','DINAJPUR-METRO-BA-13-3004','20:30' UNION SELECT 'Dinajpur','Dhaka','DINAJPUR-METRO-BA-13-3004','22:00') x
 ON r.from_city=x.f AND r.to_city=x.t
 JOIN buses b ON b.reg_number=x.reg;
