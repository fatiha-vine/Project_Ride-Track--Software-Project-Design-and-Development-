-- 1. Insert Dhaka local area stops
INSERT IGNORE INTO `stops` (`name`, `lat`, `lng`) VALUES
('Gulshan 1', 23.778840, 90.415237),
('Gulshan 2', 23.794830, 90.414320),
('Banani', 23.793700, 90.406600),
('Dhanmondi 32', 23.750900, 90.377600),
('Mirpur 10', 23.806900, 90.368700),
('Uttara Sector 7', 23.872200, 90.398000),
('Motijheel', 23.733000, 90.417200),
('UITS Campus (Badda)', 23.792500, 90.428100),
('Farmgate', 23.756100, 90.387200),
('Mohakhali', 23.777200, 90.400500),
('Bashundhara R/A', 23.815100, 90.425500),
('Khilgaon', 23.751600, 90.422300),
('Azimpur', 23.727200, 90.385500),
('Badda Link Road', 23.780800, 90.421000);

-- 2. Map stops using the correct column name (`seq`)
INSERT IGNORE INTO `route_stops` (`route_id`, `stop_id`, `seq`)
SELECT 1, id, id FROM `stops` WHERE `name` IN (
    'Gulshan 1', 'Gulshan 2', 'Banani', 'Dhanmondi 32', 'Mirpur 10', 
    'Uttara Sector 7', 'Motijheel', 'UITS Campus (Badda)', 'Farmgate', 
    'Mohakhali', 'Bashundhara R/A', 'Khilgaon', 'Azimpur', 'Badda Link Road'
);