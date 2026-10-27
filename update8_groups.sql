-- Groups e Local / Long distance type add kora
ALTER TABLE pgroups ADD kind ENUM('local','long') DEFAULT 'long';

UPDATE pgroups SET kind='long'  WHERE name IN ('Dhaka–Chattogram Commuters','Dhaka–Sylhet Regulars');
UPDATE pgroups SET kind='local' WHERE name='Dhaka University Students';

-- Notun local group gulo
INSERT INTO pgroups(name,kind) VALUES
('Uttara–Sayedabad Local Riders','local'),
('Mirpur–Motijheel Daily Commuters','local');
