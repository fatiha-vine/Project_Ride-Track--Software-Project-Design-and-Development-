USE ridetrack;
-- Dhaka areas (like Pathao): every stop is matched to its nearest area automatically.
CREATE TABLE IF NOT EXISTS areas(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(60) UNIQUE,lat DOUBLE,lng DOUBLE,radius_m INT DEFAULT 1500);
ALTER TABLE stops ADD area_id INT NULL;
INSERT IGNORE INTO areas(name,lat,lng,radius_m) VALUES
('Uttara',23.8759,90.3795,2200),('Abdullahpur',23.8800,90.4020,1200),('Tongi',23.8900,90.4000,1800),('Airport',23.8513,90.4000,1400),('Kuril',23.8200,90.4210,1300),
('Bashundhara',23.8135,90.4240,1500),('Baridhara',23.8040,90.4220,1200),('Banani',23.7937,90.4066,1200),('Gulshan',23.7806,90.4162,1300),('Mohakhali',23.7780,90.4040,1100),
('Badda',23.7808,90.4258,1400),('Rampura',23.7614,90.4252,1300),('Banasree',23.7630,90.4330,1200),('Khilgaon',23.7510,90.4290,1300),('Malibagh',23.7503,90.4126,1100),
('Mogbazar',23.7490,90.4045,900),('Pallabi',23.8250,90.3650,1400),('Mirpur',23.8069,90.3687,1700),('Mirpur 1',23.7956,90.3537,1200),('Kazipara',23.7968,90.3743,1000),
('Shewrapara',23.7905,90.3735,900),('Agargaon',23.7776,90.3800,1100),('Shyamoli',23.7743,90.3654,1100),('Technical',23.7830,90.3530,1000),('Gabtoli',23.7786,90.3434,1400),
('Mohammadpur',23.7662,90.3586,1500),('Asad Gate',23.7603,90.3719,900),('Dhanmondi',23.7461,90.3742,1600),('Lalmatia',23.7550,90.3680,900),('Farmgate',23.7561,90.3872,1000),
('Tejgaon',23.7639,90.3939,1200),('Karwan Bazar',23.7510,90.3930,900),('Shahbag',23.7389,90.3958,1000),('New Market',23.7340,90.3850,900),('Paltan',23.7343,90.4127,900),
('Motijheel',23.7330,90.4172,1000),('Kamalapur',23.7316,90.4264,1000),('Gulistan',23.7233,90.4128,900),('Old Dhaka',23.7190,90.3930,1500),('Sadarghat',23.7080,90.4070,1000),
('Sayedabad',23.7104,90.4350,1500),('Postogola',23.6950,90.4200,1200),('Keraniganj',23.6990,90.3550,1800),('Demra',23.7248,90.4991,1800),('Sarulia',23.7080,90.4700,1500),
('Signboard',23.6630,90.4720,1500),('Narayanganj',23.6238,90.5000,2500);
-- Match every stop that is inside Dhaka to its nearest area
UPDATE stops s SET s.area_id=(SELECT a.id FROM areas a ORDER BY POW(a.lat-s.lat,2)+POW((a.lng-s.lng)*0.92,2) LIMIT 1)
 WHERE s.lat BETWEEN 23.55 AND 23.99 AND s.lng BETWEEN 90.25 AND 90.62;
