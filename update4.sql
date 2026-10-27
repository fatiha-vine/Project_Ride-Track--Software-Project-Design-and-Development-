USE ridetrack;
CREATE TABLE schedules(id INT AUTO_INCREMENT PRIMARY KEY,route_id INT,bus_id INT,depart_time TIME);
INSERT INTO schedules(route_id,bus_id,depart_time) VALUES(1,1,'08:00'),(1,3,'22:00'),(2,1,'09:30'),(3,2,'07:15'),(4,3,'20:00');
