<?php // Dhaka areas: helpers. Needs config.php first and the areas table (update21_areas.sql).
function area_of($lat,$lng){if($lat===null||$lng===null||$lat<23.55||$lat>23.99||$lng<90.25||$lng>90.62)return null;
 $r=q('SELECT id FROM areas ORDER BY POW(lat-?,2)+POW((lng-?)*0.92,2) LIMIT 1',[$lat,$lng])->fetch();return $r?(int)$r['id']:null;}
// Match every stop inside Dhaka to its nearest area (runs after new stops are added or imported)
function assign_areas(){q('UPDATE stops s SET s.area_id=(SELECT a.id FROM areas a ORDER BY POW(a.lat-s.lat,2)+POW((a.lng-s.lng)*0.92,2) LIMIT 1) WHERE s.lat BETWEEN 23.55 AND 23.99 AND s.lng BETWEEN 90.25 AND 90.62');}
// Areas a bus serves, in route order. Added automatically from the stops of the routes the bus runs on.
function bus_areas($bus_id){return q('SELECT a.id,a.name FROM schedules sc JOIN route_stops rs ON rs.route_id=sc.route_id JOIN stops s ON s.id=rs.stop_id JOIN areas a ON a.id=s.area_id WHERE sc.bus_id=? GROUP BY a.id,a.name ORDER BY MIN(rs.seq),a.name',[$bus_id])->fetchAll();}
