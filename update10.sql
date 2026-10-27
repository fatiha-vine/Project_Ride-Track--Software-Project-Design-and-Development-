USE ridetrack;
CREATE TABLE stop_fares(from_stop INT,to_stop INT,fare DECIMAL(8,2),PRIMARY KEY(from_stop,to_stop));
INSERT IGNORE INTO stop_fares
SELECT a.id,b.id,x.f FROM (SELECT 'Badda' p,'Kuril' q,20 f UNION SELECT 'Kuril','Badda',20 UNION SELECT 'Badda','Mirpur',40 UNION SELECT 'Mirpur','Badda',40 UNION SELECT 'Kuril','Mirpur',20 UNION SELECT 'Mirpur','Kuril',20) x
JOIN stops a ON a.name=x.p JOIN stops b ON b.name=x.q;
