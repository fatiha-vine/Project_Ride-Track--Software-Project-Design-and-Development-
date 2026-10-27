USE ridetrack;
CREATE TABLE stops(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(60) UNIQUE,lat DOUBLE,lng DOUBLE);
CREATE TABLE route_stops(route_id INT,stop_id INT,seq INT,offset_min INT,PRIMARY KEY(route_id,stop_id));
CREATE TABLE plans(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT,from_stop INT,to_stop INT,go_date DATE,go_time TIME,back_time TIME NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
INSERT INTO stops(name,lat,lng) VALUES('Dhaka',23.7104,90.4074),('Chattogram',22.3569,91.7832),('Sylhet',24.8949,91.8687),('Rajshahi',24.3745,88.6042),('Cox''s Bazar',21.4272,92.0058),
('Jatrabari',23.7104,90.4339),('Motijheel',23.7330,90.4172),('Malibagh',23.7488,90.4125),('Rampura',23.7637,90.4250),('Badda',23.7776,90.4264);
INSERT INTO route_stops SELECT r.id,s.id,1,0 FROM routes r JOIN stops s ON s.name=r.from_city;
INSERT INTO route_stops SELECT r.id,s.id,2,360 FROM routes r JOIN stops s ON s.name=r.to_city;
INSERT INTO routes(from_city,to_city,fare) VALUES('Jatrabari','Badda',30),('Badda','Jatrabari',30);
INSERT INTO route_stops SELECT r.id,s.id,x.q,x.o FROM routes r JOIN
 (SELECT 'Jatrabari' f,'Badda' t,'Jatrabari' sn,1 q,0 o UNION SELECT 'Jatrabari','Badda','Motijheel',2,10 UNION SELECT 'Jatrabari','Badda','Malibagh',3,20 UNION SELECT 'Jatrabari','Badda','Rampura',4,30 UNION SELECT 'Jatrabari','Badda','Badda',5,40
  UNION SELECT 'Badda','Jatrabari','Badda',1,0 UNION SELECT 'Badda','Jatrabari','Rampura',2,10 UNION SELECT 'Badda','Jatrabari','Malibagh',3,20 UNION SELECT 'Badda','Jatrabari','Motijheel',4,30 UNION SELECT 'Badda','Jatrabari','Jatrabari',5,40) x
 ON r.from_city=x.f AND r.to_city=x.t JOIN stops s ON s.name=x.sn;
INSERT INTO schedules(route_id,bus_id,depart_time)
SELECT r.id,x.b,MAKETIME(h.n,x.m,0) FROM routes r JOIN (SELECT 1 b,0 m UNION SELECT 2,30) x JOIN
 (SELECT 7 n UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION SELECT 21) h
WHERE (r.from_city='Jatrabari' AND r.to_city='Badda') OR (r.from_city='Badda' AND r.to_city='Jatrabari');
UPDATE buses SET lat=23.7488,lng=90.4125,gps_updated=NOW() WHERE id=1;
UPDATE buses SET lat=23.7120,lng=90.4300,gps_updated=NOW() WHERE id=2;
UPDATE buses SET lat=23.7776,lng=90.4264,gps_updated=NOW() WHERE id=3;
