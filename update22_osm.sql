USE ridetrack;
-- Remembers which map (OpenStreetMap) route each imported route came from, so importing twice never makes duplicates.
ALTER TABLE routes ADD osm_id BIGINT NULL, ADD INDEX(osm_id);
