USE ridetrack;
INSERT INTO routes(from_city,to_city,fare) VALUES('Chattogram','Dhaka',650),('Sylhet','Dhaka',600),('Rajshahi','Dhaka',550),('Cox''s Bazar','Dhaka',1200);
INSERT INTO schedules(route_id,bus_id,depart_time)
SELECT r.id,x.b,x.tm FROM routes r JOIN
 (SELECT 'Chattogram' f,'Dhaka' t,3 b,'08:00' tm UNION SELECT 'Chattogram','Dhaka',1,'21:00' UNION SELECT 'Sylhet','Dhaka',1,'14:00' UNION SELECT 'Rajshahi','Dhaka',2,'15:00' UNION SELECT 'Cox''s Bazar','Dhaka',3,'09:00') x
 ON r.from_city=x.f AND r.to_city=x.t;
