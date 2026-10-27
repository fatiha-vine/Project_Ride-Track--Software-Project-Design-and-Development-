<?php 
require 'config.php';
$u = me();

// Database connection helper - Intercity routes & cities
$routes = q("SELECT r.*, 
            (SELECT GROUP_CONCAT(s.name ORDER BY rs.seq SEPARATOR ' ➔ ') 
             FROM route_stops rs JOIN stops s ON s.id=rs.stop_id WHERE rs.route_id=r.id) AS path,
            (SELECT COUNT(*) FROM schedules sc WHERE sc.route_id=r.id) AS bus_count 
            FROM routes r WHERE r.type='intercity' ORDER BY r.from_city, r.to_city")->fetchAll();

head('Long distance map');
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
:root {
    --rt-teal: #085d71;
    --rt-teal-dark: #053e4c;
    --rt-bg: #eaf8f9;
    --rt-card-bg: #ffffff;
    --rt-text: #0f3742;
    --rt-muted: #537d89;
    --rt-ln: #c2ebf0;
}

.map-container {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 20px;
    margin-top: 15px;
}

#long-map {
    width: 100%;
    height: 520px;
    border-radius: 18px;
    border: 1px solid var(--rt-ln);
    box-shadow: 0 10px 25px rgba(8, 93, 113, 0.08);
}

.route-sidebar {
    background: #fff;
    border: 1px solid var(--rt-ln);
    border-radius: 18px;
    padding: 18px;
    max-height: 520px;
    overflow-y: auto;
}

.route-card {
    background: var(--rt-bg);
    border: 1px solid var(--rt-ln);
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.route-card:hover, .route-card.active {
    border-color: var(--rt-teal);
    background: #fff;
    box-shadow: 0 4px 12px rgba(8, 93, 113, 0.12);
}

.route-title {
    font-weight: 700;
    font-size: 15px;
    color: var(--rt-text);
    display: flex;
    justify-content: space-between;
}

.route-path {
    font-size: 12px;
    color: var(--rt-muted);
    margin: 6px 0;
}

.route-badge {
    display: inline-block;
    background: var(--rt-teal);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 10px;
}

@media(max-width: 860px) {
    .map-container { grid-template-columns: 1fr; }
    #long-map { height: 350px; }
}
</style>

<h2>Long Distance Routes & Map</h2>
<p class="muted">Explore intercity highway bus routes across Bangladesh. Tap any route to highlight it on the map and check available buses.</p>

<div class="map-container">
    <div id="long-map"></div>

    <div class="route-sidebar">
        <h3 style="margin-top:0; font-size:18px; color:var(--rt-text);">Intercity Routes</h3>
        <?php if (empty($routes)): ?>
            <p class="muted">No intercity routes found in database.</p>
        <?php else: ?>
            <?php foreach ($routes as $r): ?>
                <div class="route-card" onclick="highlightRoute('<?= e($r['from_city']) ?>', '<?= e($r['to_city']) ?>')">
                    <div class="route-title">
                        <span><?= e($r['from_city']) ?> ➔ <?= e($r['to_city']) ?></span>
                        <span style="color:var(--rt-teal);">৳<?= number_format($r['fare']) ?></span>
                    </div>
                    <div class="route-path"><?= e($r['path'] ?: 'Direct Highway Route') ?></div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
                        <span class="route-badge"><?= $r['bus_count'] ?> Scheduled Buses</span>
                        <a href="search.php?mode=intercity&from=<?= urlencode($r['from_city']) ?>&to=<?= urlencode($r['to_city']) ?>" class="btn sm" style="padding: 4px 10px; font-size: 11px;">Book Bus</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Major city coordinates for Bangladesh Intercity Highway Map
const cityCoords = {
    "Dhaka": [23.8103, 90.4125],
    "Chittagong": [22.3569, 91.7832],
    "Sylhet": [24.8949, 91.8687],
    "Rajshahi": [24.3636, 88.6241],
    "Khulna": [22.8456, 89.5403],
    "Barisal": [22.7010, 90.3535],
    "Rangpur": [25.7439, 89.2752],
    "Mymensingh": [24.7471, 90.4203],
    "Cox's Bazar": [21.4272, 92.0058],
    "Bogura": [24.8481, 89.3730],
    "Comilla": [23.4607, 91.1809]
};

// Initialize Map centered on Bangladesh
const map = L.map('long-map').setView([23.6850, 90.3563], 7);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Add City Markers
Object.keys(cityCoords).forEach(city => {
    const coords = cityCoords[city];
    L.circleMarker(coords, {
        radius: 6,
        color: '#085d71',
        fillColor: '#ffffff',
        fillOpacity: 1,
        weight: 2
    }).addTo(map).bindTooltip(city, {permanent: true, direction: 'top', className: 'city-lbl'});
});

let currentPolyline = null;

function highlightRoute(from, to) {
    if (currentPolyline) {
        map.removeLayer(currentPolyline);
    }

    const start = cityCoords[from];
    const end = cityCoords[to];

    if (start && end) {
        currentPolyline = L.polyline([start, end], {
            color: '#dc2626',
            weight: 5,
            dashArray: '8, 8'
        }).addTo(map);

        map.fitBounds(currentPolyline.getBounds(), {padding: [50, 50]});
    }
}
</script>

<?php foot(); ?>