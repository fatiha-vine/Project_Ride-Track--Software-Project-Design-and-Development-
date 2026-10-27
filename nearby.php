<?php require 'config.php';
/* Nearby stops: passenger allows GPS -> stops near them are found automatically. */
if(isset($_GET['lat'],$_GET['lng'])){
 header('Content-Type: application/json');header('Cache-Control: no-store');
 $la=(float)$_GET['lat'];$ln=(float)$_GET['lng'];
 if(abs($la)>90||abs($ln)>180){http_response_code(400);exit('[]');}
 $rows=q('SELECT s.id,s.name,s.lat,s.lng,MAX(r.type=\'local\') loc,
  (6371*ACOS(LEAST(1,COS(RADIANS(?))*COS(RADIANS(s.lat))*COS(RADIANS(s.lng)-RADIANS(?))+SIN(RADIANS(?))*SIN(RADIANS(s.lat))))) d
  FROM stops s JOIN route_stops rs ON rs.stop_id=s.id JOIN routes r ON r.id=rs.route_id
  WHERE s.lat IS NOT NULL AND s.lng IS NOT NULL GROUP BY s.id,s.name,s.lat,s.lng HAVING d<=8 ORDER BY d LIMIT 15',[$la,$ln,$la])->fetchAll();
 $out=[];foreach($rows as $r)$out[]=['name'=>$r['name'],'lat'=>(float)$r['lat'],'lng'=>(float)$r['lng'],'d'=>round((float)$r['d'],2),'mode'=>$r['loc']?'local':'intercity'];
 echo json_encode($out,JSON_UNESCAPED_UNICODE);exit;}
head('Nearby stops');?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>#nmap{width:100%;height:340px;border:1px solid var(--ln);border-radius:14px;margin:10px 0}</style>
<h2>Bus stops near me</h2>
<p class="muted">Allow location access and the nearest stops are found automatically.</p>
<div class="card"><button class="btn" type="button" id="loc">📍 Find stops near me</button>
<p id="st" class="muted"></p><div id="nmap"></div><div id="list"></div></div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const st=document.getElementById('st'),list=document.getElementById('list');
const map=L.map('nmap').setView([23.78,90.41],11);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap contributors'}).addTo(map);
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let layer=L.layerGroup().addTo(map);
async function run(la,ln){
 st.textContent='Looking for stops near you...';layer.clearLayers();
 L.circleMarker([la,ln],{radius:8,color:'#fff',weight:3,fillColor:'#2563eb',fillOpacity:1}).addTo(layer).bindTooltip('You');
 const b=[[la,ln]];
 try{
  const r=await (await fetch('nearby.php?lat='+la+'&lng='+ln,{cache:'no-store'})).json();
  let h='';
  r.forEach((s,i)=>{b.push([s.lat,s.lng]);
   L.circleMarker([s.lat,s.lng],{radius:7,color:'#0e7490',weight:3,fillColor:'#fff',fillOpacity:1}).addTo(layer).bindTooltip(s.name);
   const dist=s.d<1?Math.round(s.d*1000)+' m':s.d.toFixed(1)+' km';
   h+='<div class="card trip"><div><b>'+esc(s.name)+'</b><div class="muted">'+dist+' away</div></div><a class="btn sm" href="search.php?mode='+s.mode+'&from='+encodeURIComponent(s.name)+'">Buses from here</a></div>'});
  list.innerHTML=h||'<p class="muted">No stops with saved coordinates within 8 km. Admin: run <b>import_stops.php</b> or add lat/lng for the stops.</p>';
  st.textContent=r.length?r.length+' stops found.':'';
 }catch(e){st.textContent='Could not load stops from the server.'}
 // extra: real bus stops from OpenStreetMap around the user (browser calls Overpass directly)
 try{
  const q='[out:json][timeout:20];node["highway"="bus_stop"](around:700,'+la+','+ln+');out 40;';
  const j=await (await fetch('https://overpass-api.de/api/interpreter',{method:'POST',body:'data='+encodeURIComponent(q),headers:{'Content-Type':'application/x-www-form-urlencoded'}})).json();
  (j.elements||[]).forEach(e=>{b.push([e.lat,e.lon]);L.circleMarker([e.lat,e.lon],{radius:5,color:'#6b7280',weight:2,fillColor:'#d1d5db',fillOpacity:1}).addTo(layer).bindTooltip((e.tags&&(e.tags['name:en']||e.tags.name))||'Bus stop (map)')})
 }catch(e){}
 map.fitBounds(b,{padding:[30,30],maxZoom:16});
}
function go(){
 if(!navigator.geolocation){st.textContent='This browser has no GPS support.';return}
 if(!window.isSecureContext){st.textContent='GPS needs HTTPS (or localhost). Open the site with https://.';return}
 st.textContent='Waiting for location permission...';
 navigator.geolocation.getCurrentPosition(p=>run(p.coords.latitude,p.coords.longitude),
  e=>{st.textContent=e.code==1?'Location permission denied. Allow location in your browser settings and try again.':'Could not get your location.'},{enableHighAccuracy:true,timeout:15000});
}
document.getElementById('loc').onclick=go;
// if permission was already granted, start automatically
if(navigator.permissions&&navigator.permissions.query)navigator.permissions.query({name:'geolocation'}).then(r=>{if(r.state==='granted')go()}).catch(()=>{});
</script>
<?php foot();
