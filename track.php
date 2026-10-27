<?php require 'config.php';require 'autosim.inc.php';auto_sim();require 'areas.inc.php';$u=me();
$buses=q('SELECT *,TIMESTAMPDIFF(SECOND,gps_updated,NOW()) AS age FROM buses')->fetchAll();$sel=null;foreach($buses as $b)if($b['id']==($_GET['bus']??0))$sel=$b;
$routes=[];
if($sel){foreach(q('SELECT r.id,r.from_city,r.to_city FROM routes r WHERE r.id IN(SELECT route_id FROM schedules WHERE bus_id=?) ORDER BY r.from_city,r.to_city',[$sel['id']])->fetchAll() as $r){
  $st=[];foreach(q('SELECT s.name,s.lat,s.lng FROM route_stops rs JOIN stops s ON s.id=rs.stop_id WHERE rs.route_id=? ORDER BY rs.seq',[$r['id']])->fetchAll() as $s)if($s['lat']!==null&&$s['lng']!==null)$st[]=[$s['name'],(float)$s['lat'],(float)$s['lng']];
  if(count($st)>=2)$routes[]=['id'=>(int)$r['id'],'name'=>$r['from_city'].' → '.$r['to_city'],'stops'=>$st];}}
$all=array_map(fn($b)=>['id'=>(int)$b['id'],'operator'=>$b['operator'],'reg'=>$b['reg_number'],'lat'=>$b['lat']===null?null:(float)$b['lat'],'lng'=>$b['lng']===null?null:(float)$b['lng'],'age'=>$b['age']===null?null:(int)$b['age']],$buses);
head('Live tracking');?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>#map{width:100%;height:480px;border:1px solid var(--ln);border-radius:14px}.busicon{font-size:26px;line-height:34px;text-align:center;filter:drop-shadow(0 2px 3px rgba(0,0,0,.4))}.me-dot{background:#2563eb;border:3px solid #fff;border-radius:50%;box-shadow:0 0 0 2px #2563eb}.maptools{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:10px 0}.maptools select{max-width:100%}#etabox{margin:8px 0}</style>
<h2>Live bus tracking</h2>
<?php if($sel){$old=($sel['age']===null||$sel['age']>600);?>
<div class="card"><h3><?=e($sel['operator'])?> · <?=e($sel['reg_number'])?></h3><?php $ba=array_column(bus_areas($sel['id']),'name');if($ba)echo '<p class="muted">Areas served: '.e(implode(' · ',$ba)).'</p>';?><p>Last update: <span id="upd"><?=e($sel['gps_updated'])?></span> <span id="crowd" class="pill <?=e($sel['crowding'])?>">Crowding: <?=e($sel['crowding'])?></span></p>
<p class="err" id="old" style="<?=$old?'':'display:none'?>">GPS data is outdated. The bus may have moved.</p>
<div class="maptools"><button class="btn" type="button" id="meBtn">📍 Show my location</button><label><input type="checkbox" id="follow" checked> Follow bus</label><?php if($routes){?><label>Route <select id="routeSel"></select></label><?php }?></div>
<div id="etabox" class="muted"></div><div id="map"></div></div>
<?php }else{?><div class="card"><p class="muted">Tap a bus on the map or choose one below.</p><div id="map"></div></div><?php }?>
<div class="grid"><?php foreach($buses as $b){?><div class="card"><h3><?=e($b['operator'])?></h3><div class="muted"><?=e($b['reg_number'])?></div><a class="btn ghost" href="track.php?bus=<?=$b['id']?>">Track this bus</a></div><?php }?></div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const SEL=<?=$sel?(int)$sel['id']:0?>,ROUTES=<?=json_encode($routes,JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)?>,ALL=<?=json_encode($all,JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)?>;
const map=L.map('map').setView([23.78,90.41],12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap contributors'}).addTo(map);
const busIcon=L.divIcon({className:'busicon',html:'🚌',iconSize:[34,34],iconAnchor:[17,17]});
const km=(a,b,c,d)=>{const r=Math.PI/180,x=(c-a)*r,y=(d-b)*r,h=Math.sin(x/2)**2+Math.cos(a*r)*Math.cos(c*r)*Math.sin(y/2)**2;return 12742*Math.asin(Math.sqrt(h))};
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
if(!SEL){
  const ms={},pts=[];
  ALL.forEach(b=>{if(b.lat==null||b.lng==null)return;pts.push([b.lat,b.lng]);ms[b.id]=L.marker([b.lat,b.lng],{icon:busIcon}).addTo(map).bindPopup('<b>'+esc(b.operator)+'</b><br>'+esc(b.reg)+'<br><a href="track.php?bus='+b.id+'">Track this bus</a>')});
  if(pts.length)map.fitBounds(pts,{padding:[40,40],maxZoom:14});
  setInterval(async()=>{try{const r=await (await fetch('bus_pos.php',{cache:'no-store'})).json();r.forEach(b=>{if(b.lat!=null&&ms[b.id])ms[b.id].setLatLng([b.lat,b.lng])})}catch(e){}},10000);
}else{
  let bus=ALL.find(b=>b.id===SEL),busM=null,routeLayer=L.layerGroup().addTo(map),cur=null,meM=null,meLL=null,first=true;
  if(bus&&bus.lat!=null){busM=L.marker([bus.lat,bus.lng],{icon:busIcon}).addTo(map);map.setView([bus.lat,bus.lng],14)}
  async function road(p){try{const u='https://router.project-osrm.org/route/v1/driving/'+p.map(x=>x[2]+','+x[1]).join(';')+'?overview=full&geometries=geojson';const j=await (await fetch(u)).json();if(j.code==='Ok')return j.routes[0].geometry.coordinates.map(c=>[c[1],c[0]])}catch(e){}return p.map(x=>[x[1],x[2]])}
  async function drawRoute(i){cur=ROUTES[i];routeLayer.clearLayers();if(!cur)return;
    cur.stops.forEach((s,k)=>{L.circleMarker([s[1],s[2]],{radius:6,color:'#0e7490',fillColor:'#fff',fillOpacity:1,weight:3}).addTo(routeLayer).bindTooltip(s[0],{permanent:k===0||k===cur.stops.length-1,direction:'top'})});
    const line=await road(cur.stops);if(cur!==ROUTES[i])return;L.polyline(line,{color:'#0e7490',weight:5,opacity:.8}).addTo(routeLayer);
    const b=L.latLngBounds(line);if(busM)b.extend(busM.getLatLng());map.fitBounds(b,{padding:[40,40]});first=false;eta()}
  function eta(){const t=document.getElementById('etabox');if(!t)return;let h='';
    if(bus&&bus.lat!=null&&meLL){const d=km(bus.lat,bus.lng,meLL[0],meLL[1]);h+='Bus is <b>'+d.toFixed(1)+' km</b> from you · about <b>'+Math.max(1,Math.round(d/20*60))+' min</b>. '}
    if(cur&&meLL){let best=null,bd=1e9;cur.stops.forEach(s=>{const d=km(meLL[0],meLL[1],s[1],s[2]);if(d<bd){bd=d;best=s}});if(best)h+='Nearest stop on this route: <b>'+esc(best[0])+'</b> ('+(bd<1?Math.round(bd*1000)+' m':bd.toFixed(1)+' km')+').'}
    t.innerHTML=h}
  const sel=document.getElementById('routeSel');
  if(sel&&ROUTES.length){let bi=0,bd=1e9;if(bus&&bus.lat!=null)ROUTES.forEach((r,i)=>r.stops.forEach(s=>{const d=km(bus.lat,bus.lng,s[1],s[2]);if(d<bd){bd=d;bi=i}}));
    ROUTES.forEach((r,i)=>{const o=document.createElement('option');o.value=i;o.textContent=r.name;sel.appendChild(o)});sel.value=bi;sel.onchange=()=>drawRoute(+sel.value);drawRoute(bi)}
  document.getElementById('meBtn').onclick=()=>{if(!navigator.geolocation){alert('Your browser has no GPS support.');return}
    navigator.geolocation.watchPosition(p=>{meLL=[p.coords.latitude,p.coords.longitude];if(!meM)meM=L.marker(meLL,{icon:L.divIcon({className:'',html:'<div class="me-dot" style="width:16px;height:16px"></div>',iconSize:[16,16],iconAnchor:[8,8]})}).addTo(map).bindTooltip('You');else meM.setLatLng(meLL);eta()},()=>alert('Location permission denied. Allow location access in your browser.'),{enableHighAccuracy:true})};
  setInterval(async()=>{try{const b=await (await fetch('bus_pos.php?bus='+SEL,{cache:'no-store'})).json();if(!b)return;bus=b;
    if(b.lat!=null){if(!busM)busM=L.marker([b.lat,b.lng],{icon:busIcon}).addTo(map);else busM.setLatLng([b.lat,b.lng]);if(document.getElementById('follow').checked&&!first)map.panTo([b.lat,b.lng])}
    document.getElementById('upd').textContent=b.gps_updated||'';const c=document.getElementById('crowd');c.className='pill '+b.crowding;c.textContent='Crowding: '+b.crowding;
    document.getElementById('old').style.display=(b.age==null||b.age>600)?'block':'none';eta()}catch(e){}},5000);
}
</script>
<?php foot();
