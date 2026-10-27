CREATE DATABASE IF NOT EXISTS ridetrack CHARACTER SET utf8mb4;
USE ridetrack;
CREATE TABLE users(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(80),email VARCHAR(120) UNIQUE,phone VARCHAR(20),password VARCHAR(255),
 role ENUM('passenger','operator','staff','admin','police') DEFAULT 'passenger',student_id VARCHAR(40),student_status ENUM('none','pending','verified') DEFAULT 'none',
 card_balance DECIMAL(10,2) DEFAULT 0,card_blocked TINYINT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE routes(id INT AUTO_INCREMENT PRIMARY KEY,from_city VARCHAR(60),to_city VARCHAR(60),fare DECIMAL(8,2));
CREATE TABLE buses(id INT AUTO_INCREMENT PRIMARY KEY,reg_number VARCHAR(30),operator VARCHAR(80),capacity INT DEFAULT 40,driver_name VARCHAR(80),license_no VARCHAR(40),
 lat DOUBLE,lng DOUBLE,gps_updated DATETIME,crowding ENUM('low','medium','high') DEFAULT 'low');
CREATE TABLE trips(id INT AUTO_INCREMENT PRIMARY KEY,route_id INT,bus_id INT,depart_date DATE,depart_time TIME,
 status ENUM('scheduled','running','delayed','completed','cancelled') DEFAULT 'scheduled');
CREATE TABLE bookings(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT,trip_id INT,seat_no INT,fare DECIMAL(8,2),discount DECIMAL(8,2) DEFAULT 0,
 status ENUM('confirmed','cancelled') DEFAULT 'confirmed',active TINYINT NULL DEFAULT 1,pay_method VARCHAR(10),pay_ref VARCHAR(30),refund DECIMAL(8,2) DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY one_seat(trip_id,seat_no,active));
CREATE TABLE card_tx(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT,amount DECIMAL(10,2),type VARCHAR(10),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE sos(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT,note VARCHAR(255),location VARCHAR(120),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE reports(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT,category VARCHAR(30),description VARCHAR(500),location VARCHAR(120),
 status ENUM('submitted','verified','resolved','rejected') DEFAULT 'submitted',votes INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE report_votes(report_id INT,user_id INT,PRIMARY KEY(report_id,user_id));
CREATE TABLE pgroups(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(80));
CREATE TABLE group_members(group_id INT,user_id INT,PRIMARY KEY(group_id,user_id));
CREATE TABLE group_posts(id INT AUTO_INCREMENT PRIMARY KEY,group_id INT,user_id INT,body VARCHAR(500),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE audit_logs(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT,action VARCHAR(160),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
-- demo password for every seeded user: password
INSERT INTO users(name,email,password,role,student_status,card_balance) VALUES
('Admin','admin@ridetrack.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin','none',0),
('Officer Rahman','police@ridetrack.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','police','none',0),
('Operator Hasan','operator@ridetrack.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','operator','none',0),
('Demo Passenger','user@ridetrack.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','passenger','none',500),
('Demo Student','student@ridetrack.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','passenger','verified',500);
UPDATE users SET student_id='STU-2026-001' WHERE email='student@ridetrack.com';
INSERT INTO routes(from_city,to_city,fare) VALUES('Dhaka','Chattogram',650),('Dhaka','Sylhet',600),('Dhaka','Rajshahi',550),('Dhaka','Cox''s Bazar',1200);
INSERT INTO buses(reg_number,operator,driver_name,license_no,lat,lng,gps_updated,crowding) VALUES
('DHAKA-METRO-BA-11-2345','Green Line','Karim Uddin','DL-889012',23.81,90.41,NOW(),'medium'),
('DHAKA-METRO-BA-15-7788','Shyamoli','Jamal Hossain','DL-445521',24.37,88.60,NOW(),'low'),
('CTG-METRO-BA-12-1020','S. Alam','Rubel Mia','DL-120934',22.35,91.78,NOW(),'high');
INSERT INTO trips(route_id,bus_id,depart_date,depart_time,status) VALUES
(1,1,DATE_ADD(CURDATE(),INTERVAL 1 DAY),'08:00','scheduled'),(1,3,DATE_ADD(CURDATE(),INTERVAL 1 DAY),'22:00','scheduled'),
(2,1,DATE_ADD(CURDATE(),INTERVAL 2 DAY),'09:30','scheduled'),(3,2,DATE_ADD(CURDATE(),INTERVAL 1 DAY),'07:15','running'),
(4,3,DATE_ADD(CURDATE(),INTERVAL 3 DAY),'20:00','scheduled');
INSERT INTO pgroups(name) VALUES('Dhaka–Chattogram Commuters'),('Dhaka–Sylhet Regulars'),('Dhaka University Students');
INSERT INTO reports(user_id,category,description,location,status,votes) VALUES(4,'Damaged road','Large potholes in the left lane.','Kanchpur Bridge','verified',3);
