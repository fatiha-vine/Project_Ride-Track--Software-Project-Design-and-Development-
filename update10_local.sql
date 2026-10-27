USE ridetrack;
-- Run ONCE. Dhaka er bhitorer local bus route, stop, bus ar schedule add kore (duto dik shoho).
-- Age thekei ache: Jatrabari <-> Badda (Motijheel, Malibagh, Rampura hoye).

-- 1) Area / stop gulo
INSERT IGNORE INTO stops(name,lat,lng) VALUES
('Uttara',23.8759,90.3795),('Airport',23.8513,90.4000),('Banani',23.7937,90.4066),('Mohakhali',23.7780,90.4040),('Sayedabad',23.7104,90.4350),
('Mirpur',23.8069,90.3687),('Kazipara',23.7968,90.3743),('Agargaon',23.7776,90.3800),('Farmgate',23.7561,90.3872),('Shahbag',23.7389,90.3958),('Paltan',23.7343,90.4127),
('Gabtoli',23.7786,90.3434),('Technical',23.7830,90.3530),('Asad Gate',23.7603,90.3719),('Dhanmondi',23.7461,90.3742),('Gulistan',23.7233,90.4128),
('Demra',23.7248,90.4991),('Sarulia',23.7080,90.4700),('Narayanganj',23.6238,90.5000),('Signboard',23.6630,90.4720);

-- 2) Route (forward = from -> to), fare ekhane
CREATE TABLE lr_tmp(f VARCHAR(60),t VARCHAR(60),fare DECIMAL(8,2),sn VARCHAR(60),q INT,o INT);
INSERT INTO lr_tmp VALUES
('Uttara','Sayedabad',50,'Uttara',1,0),('Uttara','Sayedabad',50,'Airport',2,10),('Uttara','Sayedabad',50,'Banani',3,25),('Uttara','Sayedabad',50,'Mohakhali',4,30),('Uttara','Sayedabad',50,'Malibagh',5,45),('Uttara','Sayedabad',50,'Motijheel',6,55),('Uttara','Sayedabad',50,'Sayedabad',7,65),
('Mirpur','Motijheel',40,'Mirpur',1,0),('Mirpur','Motijheel',40,'Kazipara',2,8),('Mirpur','Motijheel',40,'Agargaon',3,18),('Mirpur','Motijheel',40,'Farmgate',4,28),('Mirpur','Motijheel',40,'Shahbag',5,38),('Mirpur','Motijheel',40,'Paltan',6,45),('Mirpur','Motijheel',40,'Motijheel',7,50),
('Gabtoli','Gulistan',30,'Gabtoli',1,0),('Gabtoli','Gulistan',30,'Technical',2,8),('Gabtoli','Gulistan',30,'Asad Gate',3,18),('Gabtoli','Gulistan',30,'Dhanmondi',4,28),('Gabtoli','Gulistan',30,'Shahbag',5,38),('Gabtoli','Gulistan',30,'Gulistan',6,48),
('Demra','Gulistan',40,'Demra',1,0),('Demra','Gulistan',40,'Sarulia',2,12),('Demra','Gulistan',40,'Jatrabari',3,30),('Demra','Gulistan',40,'Gulistan',4,45),
('Narayanganj','Gulistan',40,'Narayanganj',1,0),('Narayanganj','Gulistan',40,'Signboard',2,15),('Narayanganj','Gulistan',40,'Jatrabari',3,35),('Narayanganj','Gulistan',40,'Gulistan',4,50);

INSERT INTO routes(from_city,to_city,fare,type) SELECT DISTINCT f,t,fare,'local' FROM lr_tmp;
INSERT INTO routes(from_city,to_city,fare,type) SELECT DISTINCT t,f,fare,'local' FROM lr_tmp;

-- 3) Route stops: forward
INSERT INTO route_stops SELECT r.id,s.id,x.q,x.o FROM lr_tmp x
 JOIN routes r ON r.from_city=x.f AND r.to_city=x.t AND r.type='local'
 JOIN stops s ON s.name=x.sn
 WHERE NOT EXISTS(SELECT 1 FROM route_stops z WHERE z.route_id=r.id);
-- reverse (stop order ulta)
INSERT INTO route_stops SELECT r.id,s.id,m.n+1-x.q,m.tot-x.o FROM lr_tmp x
 JOIN (SELECT f,t,MAX(q) n,MAX(o) tot FROM lr_tmp GROUP BY f,t) m ON m.f=x.f AND m.t=x.t
 JOIN routes r ON r.from_city=x.t AND r.to_city=x.f AND r.type='local'
 JOIN stops s ON s.name=x.sn
 WHERE NOT EXISTS(SELECT 1 FROM route_stops z WHERE z.route_id=r.id);
DROP TABLE lr_tmp;

-- 4) Notun local bus (prottek route er jonno 1 ta)
INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) VALUES
('DHAKA-METRO-BA-21-2001','Uttara Local',3,50,'Rafiq Mia','DL-500101','NID-4001',23.8759,90.3795,NOW(),'approved','local'),
('DHAKA-METRO-BA-21-2002','Mirpur Local',3,50,'Kabir Hossain','DL-500102','NID-4002',23.8069,90.3687,NOW(),'approved','local'),
('DHAKA-METRO-BA-21-2003','Gabtoli Local',3,50,'Jahangir Alam','DL-500103','NID-4003',23.7786,90.3434,NOW(),'approved','local'),
('DHAKA-METRO-BA-21-2004','Demra Local',3,50,'Sohel Rana','DL-500104','NID-4004',23.7248,90.4991,NOW(),'approved','local'),
('DHAKA-METRO-BA-21-2005','Narayanganj Local',3,50,'Mizanur Rahman','DL-500105','NID-4005',23.6238,90.5000,NOW(),'approved','local');
UPDATE buses SET gps_key=MD5(CONCAT(id,RAND(),NOW())) WHERE gps_key IS NULL;

-- 5) Schedule: sokal 7 ta theke raat 9 ta porjonto prottek ghonta e (duto dik)
INSERT INTO schedules(route_id,bus_id,depart_time)
SELECT r.id,b.id,MAKETIME(h.n,0,0) FROM routes r
 JOIN (SELECT 'Uttara' a,'Sayedabad' z,'DHAKA-METRO-BA-21-2001' reg UNION SELECT 'Sayedabad','Uttara','DHAKA-METRO-BA-21-2001'
  UNION SELECT 'Mirpur','Motijheel','DHAKA-METRO-BA-21-2002' UNION SELECT 'Motijheel','Mirpur','DHAKA-METRO-BA-21-2002'
  UNION SELECT 'Gabtoli','Gulistan','DHAKA-METRO-BA-21-2003' UNION SELECT 'Gulistan','Gabtoli','DHAKA-METRO-BA-21-2003'
  UNION SELECT 'Demra','Gulistan','DHAKA-METRO-BA-21-2004' UNION SELECT 'Gulistan','Demra','DHAKA-METRO-BA-21-2004'
  UNION SELECT 'Narayanganj','Gulistan','DHAKA-METRO-BA-21-2005' UNION SELECT 'Gulistan','Narayanganj','DHAKA-METRO-BA-21-2005') x
 ON r.from_city=x.a AND r.to_city=x.z AND r.type='local'
 JOIN buses b ON b.reg_number=x.reg
 JOIN (SELECT 7 n UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION SELECT 21) h;
