USE ridetrack;
-- Run ONCE (safe to run again, it skips what already exists).
-- Adds 60 Dhaka areas, 12 local corridors (both directions), 1 local bus per corridor and hourly schedules.
-- Fare = road distance (straight line x 1.3) x 2.5 taka per km, minimum 10 taka. Student pays half.

INSERT IGNORE INTO stops(name,lat,lng) VALUES
('Uttara',23.8759,90.3795),
('Abdullahpur',23.8786,90.4005),
('Tongi',23.8917,90.4046),
('Airport',23.8513,90.4000),
('Khilkhet',23.8296,90.4208),
('Kuril',23.8205,90.4209),
('Bashundhara',23.8198,90.4358),
('Natun Bazar',23.7975,90.4241),
('Badda',23.7805,90.4260),
('Gulshan 2',23.7925,90.4141),
('Gulshan 1',23.7806,90.4167),
('Banani',23.7937,90.4066),
('Mohakhali',23.7780,90.4040),
('Tejgaon',23.7596,90.3917),
('Farmgate',23.7561,90.3872),
('Karwan Bazar',23.7510,90.3934),
('Hatirjheel',23.7510,90.4000),
('Moghbazar',23.7504,90.4044),
('Malibagh',23.7480,90.4137),
('Rampura',23.7618,90.4237),
('Banasree',23.7632,90.4348),
('Aftabnagar',23.7678,90.4400),
('Khilgaon',23.7473,90.4294),
('Basabo',23.7370,90.4320),
('Mugda',23.7314,90.4339),
('Kamalapur',23.7330,90.4270),
('Motijheel',23.7333,90.4172),
('Paltan',23.7343,90.4127),
('Segunbagicha',23.7355,90.4085),
('Gulistan',23.7233,90.4128),
('Sadarghat',23.7081,90.4058),
('Sayedabad',23.7104,90.4350),
('Jatrabari',23.7098,90.4337),
('Demra',23.7248,90.4991),
('Sarulia',23.7080,90.4700),
('Signboard',23.6630,90.4720),
('Narayanganj',23.6238,90.5000),
('Shahbag',23.7389,90.3958),
('Dhaka University',23.7340,90.3930),
('Science Lab',23.7390,90.3829),
('New Market',23.7336,90.3856),
('Azimpur',23.7283,90.3857),
('Lalbagh',23.7191,90.3876),
('Hazaribagh',23.7333,90.3668),
('Rayer Bazar',23.7389,90.3585),
('Dhanmondi',23.7461,90.3742),
('Kalabagan',23.7515,90.3787),
('Asad Gate',23.7603,90.3719),
('Mohammadpur',23.7662,90.3586),
('Shyamoli',23.7739,90.3659),
('Gabtoli',23.7786,90.3434),
('Technical',23.7830,90.3530),
('Agargaon',23.7776,90.3800),
('Shewrapara',23.7915,90.3735),
('Kazipara',23.7968,90.3743),
('Mirpur',23.8069,90.3687),
('Mirpur 1',23.7956,90.3537),
('Mirpur 11',23.8183,90.3659),
('Mirpur 12',23.8280,90.3646),
('Keraniganj',23.6915,90.3845);

CREATE TABLE da_tmp(f VARCHAR(60),t VARCHAR(60),fare DECIMAL(8,2),sn VARCHAR(60),q INT,o INT);
INSERT INTO da_tmp VALUES
('Uttara','Gulistan',65,'Uttara',1,0),
('Uttara','Gulistan',65,'Airport',2,13),
('Uttara','Gulistan',65,'Banani',3,39),
('Uttara','Gulistan',65,'Mohakhali',4,45),
('Uttara','Gulistan',65,'Tejgaon',5,55),
('Uttara','Gulistan',65,'Farmgate',6,57),
('Uttara','Gulistan',65,'Karwan Bazar',7,60),
('Uttara','Gulistan',65,'Shahbag',8,66),
('Uttara','Gulistan',65,'Paltan',9,73),
('Uttara','Gulistan',65,'Gulistan',10,78),
('Mirpur','Sadarghat',41,'Mirpur',1,0),
('Mirpur','Sadarghat',41,'Kazipara',2,5),
('Mirpur','Sadarghat',41,'Shewrapara',3,7),
('Mirpur','Sadarghat',41,'Agargaon',4,14),
('Mirpur','Sadarghat',41,'Asad Gate',5,22),
('Mirpur','Sadarghat',41,'Science Lab',6,32),
('Mirpur','Sadarghat',41,'New Market',7,35),
('Mirpur','Sadarghat',41,'Azimpur',8,37),
('Mirpur','Sadarghat',41,'Lalbagh',9,41),
('Mirpur','Sadarghat',41,'Sadarghat',10,50),
('Mohammadpur','Motijheel',25,'Mohammadpur',1,0),
('Mohammadpur','Motijheel',25,'Asad Gate',2,6),
('Mohammadpur','Motijheel',25,'Kalabagan',3,11),
('Mohammadpur','Motijheel',25,'Science Lab',4,16),
('Mohammadpur','Motijheel',25,'Shahbag',5,21),
('Mohammadpur','Motijheel',25,'Segunbagicha',6,27),
('Mohammadpur','Motijheel',25,'Motijheel',7,30),
('Mohammadpur','Jatrabari',38,'Mohammadpur',1,0),
('Mohammadpur','Jatrabari',38,'Rayer Bazar',2,12),
('Mohammadpur','Jatrabari',38,'Hazaribagh',3,16),
('Mohammadpur','Jatrabari',38,'Lalbagh',4,26),
('Mohammadpur','Jatrabari',38,'Sadarghat',5,35),
('Mohammadpur','Jatrabari',38,'Jatrabari',6,46),
('Gulshan 2','Motijheel',29,'Gulshan 2',1,0),
('Gulshan 2','Motijheel',29,'Gulshan 1',2,5),
('Gulshan 2','Motijheel',29,'Moghbazar',3,19),
('Gulshan 2','Motijheel',29,'Malibagh',4,23),
('Gulshan 2','Motijheel',29,'Kamalapur',5,31),
('Gulshan 2','Motijheel',29,'Motijheel',6,35),
('Bashundhara','Motijheel',38,'Bashundhara',1,0),
('Bashundhara','Motijheel',38,'Kuril',2,6),
('Bashundhara','Motijheel',38,'Natun Bazar',3,16),
('Bashundhara','Motijheel',38,'Badda',4,23),
('Bashundhara','Motijheel',38,'Rampura',5,32),
('Bashundhara','Motijheel',38,'Malibagh',6,39),
('Bashundhara','Motijheel',38,'Motijheel',7,45),
('Aftabnagar','Gulistan',23,'Aftabnagar',1,0),
('Aftabnagar','Gulistan',23,'Banasree',2,3),
('Aftabnagar','Gulistan',23,'Khilgaon',3,10),
('Aftabnagar','Gulistan',23,'Basabo',4,15),
('Aftabnagar','Gulistan',23,'Mugda',5,17),
('Aftabnagar','Gulistan',23,'Kamalapur',6,20),
('Aftabnagar','Gulistan',23,'Gulistan',7,27),
('Tongi','Mohakhali',41,'Tongi',1,0),
('Tongi','Mohakhali',41,'Abdullahpur',2,6),
('Tongi','Mohakhali',41,'Airport',3,18),
('Tongi','Mohakhali',41,'Banani',4,43),
('Tongi','Mohakhali',41,'Mohakhali',5,50),
('Mirpur 12','Gabtoli',21,'Mirpur 12',1,0),
('Mirpur 12','Gabtoli',21,'Mirpur 11',2,4),
('Mirpur 12','Gabtoli',21,'Mirpur 1',3,15),
('Mirpur 12','Gabtoli',21,'Technical',4,21),
('Mirpur 12','Gabtoli',21,'Gabtoli',5,25),
('Keraniganj','Gulistan',15,'Keraniganj',1,0),
('Keraniganj','Gulistan',15,'Sadarghat',2,11),
('Keraniganj','Gulistan',15,'Gulistan',3,18),
('Khilkhet','Farmgate',35,'Khilkhet',1,0),
('Khilkhet','Farmgate',35,'Kuril',2,4),
('Khilkhet','Farmgate',35,'Badda',3,21),
('Khilkhet','Farmgate',35,'Gulshan 1',4,25),
('Khilkhet','Farmgate',35,'Mohakhali',5,30),
('Khilkhet','Farmgate',35,'Tejgaon',6,40),
('Khilkhet','Farmgate',35,'Farmgate',7,42),
('Shyamoli','Dhaka University',18,'Shyamoli',1,0),
('Shyamoli','Dhaka University',18,'Asad Gate',2,6),
('Shyamoli','Dhaka University',18,'Dhanmondi',3,13),
('Shyamoli','Dhaka University',18,'Science Lab',4,17),
('Shyamoli','Dhaka University',18,'Dhaka University',5,22);

INSERT INTO routes(from_city,to_city,fare,type) SELECT DISTINCT x.f,x.t,x.fare,'local' FROM da_tmp x WHERE NOT EXISTS(SELECT 1 FROM routes r WHERE r.from_city=x.f AND r.to_city=x.t AND r.type='local');
INSERT INTO routes(from_city,to_city,fare,type) SELECT DISTINCT x.t,x.f,x.fare,'local' FROM da_tmp x WHERE NOT EXISTS(SELECT 1 FROM routes r WHERE r.from_city=x.t AND r.to_city=x.f AND r.type='local');
-- route stops: forward
INSERT INTO route_stops(route_id,stop_id,seq,offset_min) SELECT r.id,s.id,x.q,x.o FROM da_tmp x
 JOIN routes r ON r.from_city=x.f AND r.to_city=x.t AND r.type='local'
 JOIN stops s ON s.name=x.sn
 WHERE NOT EXISTS(SELECT 1 FROM route_stops z WHERE z.route_id=r.id);
-- route stops: reverse (order flipped)
INSERT INTO route_stops(route_id,stop_id,seq,offset_min) SELECT r.id,s.id,m.n+1-x.q,m.tot-x.o FROM da_tmp x
 JOIN (SELECT f,t,MAX(q) n,MAX(o) tot FROM da_tmp GROUP BY f,t) m ON m.f=x.f AND m.t=x.t
 JOIN routes r ON r.from_city=x.t AND r.to_city=x.f AND r.type='local'
 JOIN stops s ON s.name=x.sn
 WHERE NOT EXISTS(SELECT 1 FROM route_stops z WHERE z.route_id=r.id);
DROP TABLE da_tmp;

-- one local bus per corridor (operator = Operator Hasan)

INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) SELECT 'DHAKA-METRO-BA-21-2101','Uttara Local',(SELECT id FROM users WHERE email='operator@ridetrack.com'),50,'Driver 2101','DL-520101','NID-5201',23.8759,90.3795,NOW(),'approved','local' FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM buses WHERE reg_number='DHAKA-METRO-BA-21-2101');
INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) SELECT 'DHAKA-METRO-BA-21-2102','Mirpur Local',(SELECT id FROM users WHERE email='operator@ridetrack.com'),50,'Driver 2102','DL-520102','NID-5202',23.8069,90.3687,NOW(),'approved','local' FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM buses WHERE reg_number='DHAKA-METRO-BA-21-2102');
INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) SELECT 'DHAKA-METRO-BA-21-2103','Mohammadpur Local',(SELECT id FROM users WHERE email='operator@ridetrack.com'),50,'Driver 2103','DL-520103','NID-5203',23.7662,90.3586,NOW(),'approved','local' FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM buses WHERE reg_number='DHAKA-METRO-BA-21-2103');
INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) SELECT 'DHAKA-METRO-BA-21-2104','Mohammadpur Local',(SELECT id FROM users WHERE email='operator@ridetrack.com'),50,'Driver 2104','DL-520104','NID-5204',23.7662,90.3586,NOW(),'approved','local' FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM buses WHERE reg_number='DHAKA-METRO-BA-21-2104');
INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) SELECT 'DHAKA-METRO-BA-21-2105','Gulshan 2 Local',(SELECT id FROM users WHERE email='operator@ridetrack.com'),50,'Driver 2105','DL-520105','NID-5205',23.7925,90.4141,NOW(),'approved','local' FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM buses WHERE reg_number='DHAKA-METRO-BA-21-2105');
INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) SELECT 'DHAKA-METRO-BA-21-2106','Bashundhara Local',(SELECT id FROM users WHERE email='operator@ridetrack.com'),50,'Driver 2106','DL-520106','NID-5206',23.8198,90.4358,NOW(),'approved','local' FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM buses WHERE reg_number='DHAKA-METRO-BA-21-2106');
INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) SELECT 'DHAKA-METRO-BA-21-2107','Aftabnagar Local',(SELECT id FROM users WHERE email='operator@ridetrack.com'),50,'Driver 2107','DL-520107','NID-5207',23.7678,90.4400,NOW(),'approved','local' FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM buses WHERE reg_number='DHAKA-METRO-BA-21-2107');
INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) SELECT 'DHAKA-METRO-BA-21-2108','Tongi Local',(SELECT id FROM users WHERE email='operator@ridetrack.com'),50,'Driver 2108','DL-520108','NID-5208',23.8917,90.4046,NOW(),'approved','local' FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM buses WHERE reg_number='DHAKA-METRO-BA-21-2108');
INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) SELECT 'DHAKA-METRO-BA-21-2109','Mirpur 12 Local',(SELECT id FROM users WHERE email='operator@ridetrack.com'),50,'Driver 2109','DL-520109','NID-5209',23.8280,90.3646,NOW(),'approved','local' FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM buses WHERE reg_number='DHAKA-METRO-BA-21-2109');
INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) SELECT 'DHAKA-METRO-BA-21-2110','Keraniganj Local',(SELECT id FROM users WHERE email='operator@ridetrack.com'),50,'Driver 2110','DL-520110','NID-5210',23.6915,90.3845,NOW(),'approved','local' FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM buses WHERE reg_number='DHAKA-METRO-BA-21-2110');
INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) SELECT 'DHAKA-METRO-BA-21-2111','Khilkhet Local',(SELECT id FROM users WHERE email='operator@ridetrack.com'),50,'Driver 2111','DL-520111','NID-5211',23.8296,90.4208,NOW(),'approved','local' FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM buses WHERE reg_number='DHAKA-METRO-BA-21-2111');
INSERT INTO buses(reg_number,operator,operator_id,capacity,driver_name,license_no,driver_id_no,lat,lng,gps_updated,status,kind) SELECT 'DHAKA-METRO-BA-21-2112','Shyamoli Local',(SELECT id FROM users WHERE email='operator@ridetrack.com'),50,'Driver 2112','DL-520112','NID-5212',23.7739,90.3659,NOW(),'approved','local' FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM buses WHERE reg_number='DHAKA-METRO-BA-21-2112');
UPDATE buses SET gps_key=MD5(CONCAT(id,RAND(),NOW())) WHERE gps_key IS NULL;

-- schedules: 7:00 to 21:00 every hour; forward on the hour, reverse at :30
INSERT INTO schedules(route_id,bus_id,depart_time)
SELECT r.id,b.id,MAKETIME(h.n,x.m,0) FROM (
SELECT 'Uttara' a,'Gulistan' z,'DHAKA-METRO-BA-21-2101' reg,0 m UNION SELECT 'Gulistan','Uttara','DHAKA-METRO-BA-21-2101',30
 UNION SELECT 'Mirpur' a,'Sadarghat' z,'DHAKA-METRO-BA-21-2102' reg,0 m UNION SELECT 'Sadarghat','Mirpur','DHAKA-METRO-BA-21-2102',30
 UNION SELECT 'Mohammadpur' a,'Motijheel' z,'DHAKA-METRO-BA-21-2103' reg,0 m UNION SELECT 'Motijheel','Mohammadpur','DHAKA-METRO-BA-21-2103',30
 UNION SELECT 'Mohammadpur' a,'Jatrabari' z,'DHAKA-METRO-BA-21-2104' reg,0 m UNION SELECT 'Jatrabari','Mohammadpur','DHAKA-METRO-BA-21-2104',30
 UNION SELECT 'Gulshan 2' a,'Motijheel' z,'DHAKA-METRO-BA-21-2105' reg,0 m UNION SELECT 'Motijheel','Gulshan 2','DHAKA-METRO-BA-21-2105',30
 UNION SELECT 'Bashundhara' a,'Motijheel' z,'DHAKA-METRO-BA-21-2106' reg,0 m UNION SELECT 'Motijheel','Bashundhara','DHAKA-METRO-BA-21-2106',30
 UNION SELECT 'Aftabnagar' a,'Gulistan' z,'DHAKA-METRO-BA-21-2107' reg,0 m UNION SELECT 'Gulistan','Aftabnagar','DHAKA-METRO-BA-21-2107',30
 UNION SELECT 'Tongi' a,'Mohakhali' z,'DHAKA-METRO-BA-21-2108' reg,0 m UNION SELECT 'Mohakhali','Tongi','DHAKA-METRO-BA-21-2108',30
 UNION SELECT 'Mirpur 12' a,'Gabtoli' z,'DHAKA-METRO-BA-21-2109' reg,0 m UNION SELECT 'Gabtoli','Mirpur 12','DHAKA-METRO-BA-21-2109',30
 UNION SELECT 'Keraniganj' a,'Gulistan' z,'DHAKA-METRO-BA-21-2110' reg,0 m UNION SELECT 'Gulistan','Keraniganj','DHAKA-METRO-BA-21-2110',30
 UNION SELECT 'Khilkhet' a,'Farmgate' z,'DHAKA-METRO-BA-21-2111' reg,0 m UNION SELECT 'Farmgate','Khilkhet','DHAKA-METRO-BA-21-2111',30
 UNION SELECT 'Shyamoli' a,'Dhaka University' z,'DHAKA-METRO-BA-21-2112' reg,0 m UNION SELECT 'Dhaka University','Shyamoli','DHAKA-METRO-BA-21-2112',30
) x
 JOIN routes r ON r.from_city=x.a AND r.to_city=x.z AND r.type='local'
 JOIN buses b ON b.reg_number=x.reg
 JOIN (SELECT 7 n UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION SELECT 21) h
 WHERE NOT EXISTS(SELECT 1 FROM schedules sc WHERE sc.route_id=r.id AND sc.bus_id=b.id AND sc.depart_time=MAKETIME(h.n,x.m,0));
