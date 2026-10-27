USE ridetrack;
-- Run ONCE. Kuril, 300 Feet, Bashundhara, Natun Bazar, Banani, Mirpur shoho notun local route (duto dik).
-- Stop gulo age thekei thakle INSERT IGNORE skip kore dibe.

INSERT IGNORE INTO stops(name,lat,lng) VALUES
('Uttara',23.8759,90.3795),('Airport',23.8513,90.4000),('Mirpur',23.8069,90.3687),('Banani',23.7937,90.4066),
('Kuril',23.8183,90.4210),('300 Feet',23.8212,90.4300),('Bashundhara',23.8193,90.4336),('Natun Bazar',23.7984,90.4236);

CREATE TABLE lr_tmp(f VARCHAR(60),t VARCHAR(60),fare DECIMAL(8,2),sn VARCHAR(60),q INT,o INT);
INSERT INTO lr_tmp VALUES
('Uttara','Rampura',50,'Uttara',1,0),('Uttara','Rampura',50,'Airport',2,10),('Uttara','Rampura',50,'Kuril',3,20),('Uttara','Rampura',50,'300 Feet',4,28),('Uttara','Rampura',50,'Bashundhara',5,35),('Uttara','Rampura',50,'Natun Bazar',6,45),('Uttara','Rampura',50,'Badda',7,55),('Uttara','Rampura',50,'Rampura',8,62),
('Mirpur','Badda',40,'Mirpur',1,0),('Mirpur','Badda',40,'Kuril',2,25),('Mirpur','Badda',40,'Bashundhara',3,35),('Mirpur','Badda',40,'Natun Bazar',4,45),('Mirpur','Badda',40,'Badda',5,55),
('Mirpur','Natun Bazar',40,'Mirpur',1,0),('Mirpur','Natun Bazar',40,'Banani',2,20),('Mirpur','Natun Bazar',40,'Natun Bazar',3,35),
('Kuril','Bashundhara',20,'Kuril',1,0),('Kuril','Bashundhara',20,'300 Feet',2,8),('Kuril','Bashundhara',20,'Bashundhara',3,15);

INSERT INTO routes(from_city,to_city,fare,type) SELECT DISTINCT f,t,fare,'local' FROM lr_tmp;
INSERT INTO routes(from_city,to_city,fare,type) SELECT DISTINCT t,f,fare,'local' FROM lr_tmp;

INSERT INTO route_stops SELECT r.id,s.id,x.q,x.o FROM lr_tmp x
 JOIN routes r ON r.from_city=x.f AND r.to_city=x.t AND r.type='local'
 JOIN stops s ON s.name=x.sn
 WHERE NOT EXISTS(SELECT 1 FROM route_stops z WHERE z.route_id=r.id);
INSERT INTO route_stops SELECT r.id,s.id,m.n+1-x.q,m.tot-x.o FROM lr_tmp x
 JOIN (SELECT f,t,MAX(q) n,MAX(o) tot FROM lr_tmp GROUP BY f,t) m ON m.f=x.f AND m.t=x.t
 JOIN routes r ON r.from_city=x.t AND r.to_city=x.f AND r.type='local'
 JOIN stops s ON s.name=x.sn
 WHERE NOT EXISTS(SELECT 1 FROM route_stops z WHERE z.route_id=r.id);
DROP TABLE lr_tmp;

INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) VALUES
('DHAKA-METRO-BA-21-2009','Turag Local',3,50,'Babul Mia','DL-500109','NID-4009',23.8759,90.3795,NOW(),'approved','local'),
('DHAKA-METRO-BA-21-2010','Akik Local',3,50,'Shahin Alam','DL-500110','NID-4010',23.8069,90.3687,NOW(),'approved','local'),
('DHAKA-METRO-BA-21-2011','Trust Local',3,50,'Ismail Hossain','DL-500111','NID-4011',23.8069,90.3687,NOW(),'approved','local'),
('DHAKA-METRO-BA-21-2012','Bashundhara Shuttle',3,50,'Mahbub Alam','DL-500112','NID-4012',23.8183,90.4210,NOW(),'approved','local');
UPDATE buses SET gps_key=MD5(CONCAT(id,RAND(),NOW())) WHERE gps_key IS NULL;

INSERT INTO schedules(route_id,bus_id,depart_time)
SELECT r.id,b.id,MAKETIME(h.n,0,0) FROM routes r
 JOIN (SELECT 'Uttara' a,'Rampura' z,'DHAKA-METRO-BA-21-2009' reg UNION SELECT 'Rampura','Uttara','DHAKA-METRO-BA-21-2009'
  UNION SELECT 'Mirpur','Badda','DHAKA-METRO-BA-21-2010' UNION SELECT 'Badda','Mirpur','DHAKA-METRO-BA-21-2010'
  UNION SELECT 'Mirpur','Natun Bazar','DHAKA-METRO-BA-21-2011' UNION SELECT 'Natun Bazar','Mirpur','DHAKA-METRO-BA-21-2011'
  UNION SELECT 'Kuril','Bashundhara','DHAKA-METRO-BA-21-2012' UNION SELECT 'Bashundhara','Kuril','DHAKA-METRO-BA-21-2012') x
 ON r.from_city=x.a AND r.to_city=x.z AND r.type='local'
 JOIN buses b ON b.reg_number=x.reg
 JOIN (SELECT 7 n UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION SELECT 21) h;
