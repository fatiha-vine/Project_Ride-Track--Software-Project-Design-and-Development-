USE ridetrack;
-- Sob local route er fare ekই rate e kore dey, jate je route diyei jao, ekই doori = ekই vara.
-- Rate: 30 minute (route offset) = ৳20  =>  Badda -> Kuril = ৳20, student (50%) = ৳10.
-- Route er full fare = (route er shesh stop er offset_min) x 20 / 30, minimum ৳10.
-- Safe to run more than once. Intercity route e kono poribortan hoy na.
-- (offset_min <= 150 shorto: purono Jatrabari-Badda er 360 offset ke chhuy na.)

UPDATE routes r
JOIN (SELECT route_id, MAX(offset_min) tot FROM route_stops GROUP BY route_id) m ON m.route_id=r.id
SET r.fare = GREATEST(10, ROUND(m.tot*20/30))
WHERE r.type='local' AND m.tot<=150;

-- Check: kon route e koto fare holo
SELECT r.from_city, r.to_city, r.fare AS full_fare, ROUND(r.fare*0.5,2) AS student_fare
FROM routes r WHERE r.type='local' ORDER BY r.from_city, r.to_city;
