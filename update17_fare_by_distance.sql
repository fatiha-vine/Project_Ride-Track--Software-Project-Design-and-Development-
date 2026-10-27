USE ridetrack;
-- Local route er stop-er lat/lng theke ashol doori hishab kore:
--   1) route_stops.offset_min  = doori(km) x 3   (20 km/h)
--   2) routes.fare             = max(10, doori(km) x 2.5)
-- Tai Badda -> Kuril (~8 km) = ~৳20, student ৳10, je route diyei jao.
-- AGE import_stops.php chalao, jate stop er lat/lng thik thake. Intercity route chhuy na.
-- Rate bodlate: @rate (nicher line) ar settings table.
SET @rate = 2.5;
REPLACE INTO settings VALUES('fare_per_km_local','2.5'),('fare_min_local','10'),('fare_per_km_intercity','2.6'),('fare_min_intercity','100');

CREATE TABLE seg AS
SELECT b.route_id, b.seq,
 1.3*6371*2*ASIN(SQRT(POW(SIN(RADIANS(sb.lat-sa.lat)/2),2)+COS(RADIANS(sa.lat))*COS(RADIANS(sb.lat))*POW(SIN(RADIANS(sb.lng-sa.lng)/2),2))) AS d
FROM route_stops a
JOIN route_stops b ON b.route_id=a.route_id AND b.seq=a.seq+1
JOIN stops sa ON sa.id=a.stop_id JOIN stops sb ON sb.id=b.stop_id
JOIN routes r ON r.id=a.route_id
WHERE r.type='local' AND sa.lat IS NOT NULL AND sa.lng IS NOT NULL AND sb.lat IS NOT NULL AND sb.lng IS NOT NULL
 AND NOT EXISTS(SELECT 1 FROM route_stops x JOIN stops sx ON sx.id=x.stop_id WHERE x.route_id=r.id AND (sx.lat IS NULL OR sx.lng IS NULL));

UPDATE route_stops rs SET rs.offset_min = ROUND(COALESCE((SELECT SUM(g.d) FROM seg g WHERE g.route_id=rs.route_id AND g.seq<=rs.seq),0)*3)
WHERE rs.route_id IN (SELECT DISTINCT route_id FROM seg);

UPDATE routes r JOIN (SELECT route_id, SUM(d) km FROM seg GROUP BY route_id) t ON t.route_id=r.id
SET r.fare = GREATEST(10, ROUND(t.km*@rate));
DROP TABLE seg;

SELECT r.from_city, r.to_city, r.fare AS full_fare, ROUND(r.fare*0.5,2) AS student_fare FROM routes r WHERE r.type='local' ORDER BY r.from_city, r.to_city;
