USE ridetrack;
SELECT COUNT(*) AS total_areas FROM areas;
SELECT COUNT(*) AS total_stops, SUM(area_id IS NOT NULL) AS matched_to_area FROM stops;
