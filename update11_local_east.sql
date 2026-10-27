USE ridetrack;
-- Run ONCE. Banasree, Hatirjheel, Tejgaon shoho notun local route (duto dik).
-- Rampura, Badda, Malibagh, Motijheel age thekei stops table e ache.

INSERT IGNORE INTO stops(name,lat,lng) VALUES
('Banasree',23.7632,90.4348),('Hatirjheel',23.7510,90.4000),('Tejgaon',23.7596,90.3917);

CREATE TABLE lr_tmp(f VARCHAR(60),t VARCHAR(60),fare DECIMAL(8,2),sn VARCHAR(60),q INT,o INT);
INSERT INTO lr_tmp VALUES
('Badda','Tejgaon',30,'Badda',1,0),('Badda','Tejgaon',30,'Rampura',2,10),('Badda','Tejgaon',30,'Hatirjheel',3,20),('Badda','Tejgaon',30,'Tejgaon',4,30),
('Banasree','Motijheel',30,'Banasree',1,0),('Banasree','Motijheel',30,'Rampura',2,10),('Banasree','Motijheel',30,'Hatirjheel',3,20),('Banasree','Motijheel',30,'Malibagh',4,28),('Banasree','Motijheel',30,'Motijheel',5,40),
('Badda','Banasree',30,'Badda',1,0),('Badda','Banasree',30,'Rampura',2,10),('Badda','Banasree',30,'Banasree',3,20);

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
('DHAKA-METRO-BA-21-2006','Badda Local',3,50,'Tariq Aziz','DL-500106','NID-4006',23.7776,90.4264,NOW(),'approved','local'),
('DHAKA-METRO-BA-21-2007','Banasree Local',3,50,'Faruk Mia','DL-500107','NID-4007',23.7632,90.4348,NOW(),'approved','local'),
('DHAKA-METRO-BA-21-2008','Rampura Local',3,50,'Delwar Hossain','DL-500108','NID-4008',23.7637,90.4250,NOW(),'approved','local');
UPDATE buses SET gps_key=MD5(CONCAT(id,RAND(),NOW())) WHERE gps_key IS NULL;

INSERT INTO schedules(route_id,bus_id,depart_time)
SELECT r.id,b.id,MAKETIME(h.n,0,0) FROM routes r
 JOIN (SELECT 'Badda' a,'Tejgaon' z,'DHAKA-METRO-BA-21-2006' reg UNION SELECT 'Tejgaon','Badda','DHAKA-METRO-BA-21-2006'
  UNION SELECT 'Banasree','Motijheel','DHAKA-METRO-BA-21-2007' UNION SELECT 'Motijheel','Banasree','DHAKA-METRO-BA-21-2007'
  UNION SELECT 'Badda','Banasree','DHAKA-METRO-BA-21-2008' UNION SELECT 'Banasree','Badda','DHAKA-METRO-BA-21-2008') x
 ON r.from_city=x.a AND r.to_city=x.z AND r.type='local'
 JOIN buses b ON b.reg_number=x.reg
 JOIN (SELECT 7 n UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION SELECT 21) h;
