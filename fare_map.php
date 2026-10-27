<?php require 'config.php';require 'fare.php';$u=me();
$stops=[];foreach(q('SELECT DISTINCT s.name,s.lat,s.lng FROM stops s JOIN route_stops rs ON rs.stop_id=s.id WHERE s.lat IS NOT NULL AND s.lng IS NOT NULL')->fetchAll() as $s)$stops[]=[$s['name'],(float)$s['lat'],(float)$s['lng']];
$cfg=['rl'=>fare_rate('local'),'ml'=>fare_min('local'),'ri'=>fare_rate('intercity'),'mi'=>fare_min('intercity'),'pct'=>(int)setting('student_pct',50),'student'=>($u&&is_student($u)),'pending'=>($u&&($u['student_status']??'')=='pending')];
head('Fare map');?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>#fmap{width:100%;height:480px;border:1px solid var(--ln);border-radius:14px}.pin{width:30px;height:30px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.4)}.pin span{transform:rotate(45deg)}.pin.a{background:#16a34a}.pin.b{background:#dc2626}.fbox{margin-top:12px}.maptools{display:flex;gap:10px;flex-wrap:wrap;margin:10px 0}</style>
<h2>Fare map</h2><p class="muted">Tap your pickup and drop on the map (or use 📍 for your location). The fare and student fare are shown automatically by distance.</p>
<div class="card"><div class="maptools"><button class="btn" type="button" id="gps">📍 Use my location as pickup</button><button class="btn ghost" type="button" id="swap">⇄ Swap</button><button class="btn ghost" type="button" id="reset">Reset</button></div>
<p id="hint" class="muted">Tap map: pickup (A)</p><div id="fmap"></div><div id="fbox" class="fbox"></div></div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const CFG=<?=json_encode($cfg)?>,STOPS=<?=json_encode($stops,JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)?>;
const map=L.map('fmap').setView([23.78,90.41],12);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap contributors'}).addTo(map);
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const km=(a,b,c,d)=>{const r=Math.PI/180,x=(c-a)*r,y=(d-b)*r,h=Math.sin(x/2)**2+Math.cos(a*r)*Math.cos(c*r)*Math.sin(y/2)**2;return 12742*Math.asin(Math.sqrt(h))};
let pts=[],mk=[],line=null,seq=0;
const icon=(l,c)=>L.divIcon({className:'',html:'<div class="pin '+c+'"><span>'+l+'</span></div>',iconSize:[30,30],iconAnchor:[15,30]});
function nearest(p){let b=null,bd=1e9;STOPS.forEach(s=>{const d=km(p[0],p[1],s[1],s[2]);if(d<bd){bd=d;b=s}});return bd<=4?{n:b[0],d:bd}:null}
function put(ll){const i=pts.length;pts.push([ll.lat,ll.lng]);const m=L.marker(ll,{draggable:true,icon:icon(i?'B':'A',i?'b':'a')}).addTo(map);m.on('dragend',()=>{pts[i]=[m.getLatLng().lat,m.getLatLng().lng];calc()});mk.push(m);
  document.getElementById('hint').textContent=pts.length==1?'Tap map: drop (B)':'Drag A or B to change.';if(pts.length==2)calc()}
map.on('click',e=>{if(pts.length<2)put(e.latlng)});
async function road(a,b){const t=Date.now();try{const j=await (await fetch('https://router.project-osrm.org/route/v1/driving/'+a[1]+','+a[0]+';'+b[1]+','+b[0]+'?overview=full&geometries=geojson')).json();
  if(j.code==='Ok')return{km:j.routes[0].distance/1000,line:j.routes[0].geometry.coordinates.map(c=>[c[1],c[0]])}}catch(e){}
  return{km:km(a[0],a[1],b[0],b[1])*1.3,line:[a,b],approx:true}}
async function calc(){if(pts.length<2)return;const my=++seq;const r=await road(pts[0],pts[1]);if(my!==seq)return;
  if(line)map.removeLayer(line);line=L.polyline(r.line,{color:'#0e7490',weight:5,opacity:.85}).addTo(map);map.fitBounds(line.getBounds(),{padding:[50,50]});
  const long=r.km>40,rate=long?CFG.ri:CFG.rl,min=long?CFG.mi:CFG.ml,full=Math.max(min,Math.round(r.km*rate)),stu=Math.max(5,Math.round(full*(100-CFG.pct))/100);
  const mins=Math.max(1,Math.round(r.km/(long?50:20)*60)),a=nearest(pts[0]),b=nearest(pts[1]);
  let h='<h3>'+r.km.toFixed(1)+' km · about '+mins+' min'+(r.approx?' <span class="muted">(approx)</span>':'')+'</h3>';
  if(long)h+='<div class="big">৳'+full+'</div><div class="muted">No student fare on long-distance buses</div>';
  else if(CFG.student)h+='<div class="muted"><s>৳'+full+'</s></div><div class="big">৳'+stu+'</div><span class="pill verified">Your student fare</span>';
  else h+='<div class="big">৳'+full+'</div><div class="muted">Student fare ৳'+stu+(CFG.pending?' once verified':' (verified student ID lagbe)')+'</div>';
  if(a&&b&&a.n!==b.n)h+='<p>Nearest stops: <b>'+esc(a.n)+'</b> ('+Math.round(a.d*1000)+' m) → <b>'+esc(b.n)+'</b> ('+Math.round(b.d*1000)+' m)</p><a class="btn" href="search.php?mode='+(long?'intercity':'local')+'&from='+encodeURIComponent(a.n)+'&to='+encodeURIComponent(b.n)+'">Find buses</a>';
  else h+='<p class="muted">No bus stop found nearby. Try another spot on the map.</p>';
  document.getElementById('fbox').innerHTML=h}
document.getElementById('gps').onclick=()=>{if(!navigator.geolocation){alert('Browser e GPS nei.');return}
  navigator.geolocation.getCurrentPosition(p=>{reset();put(L.latLng(p.coords.latitude,p.coords.longitude));map.setView([p.coords.latitude,p.coords.longitude],15)},()=>alert('Location permission den.'),{enableHighAccuracy:true})};
function reset(){mk.forEach(m=>map.removeLayer(m));mk=[];pts=[];if(line)map.removeLayer(line);line=null;seq++;document.getElementById('fbox').innerHTML='';document.getElementById('hint').textContent='Tap map: pickup (A)'}
document.getElementById('reset').onclick=reset;
document.getElementById('swap').onclick=()=>{if(pts.length<2)return;const [a,b]=pts;reset();put(L.latLng(b[0],b[1]));put(L.latLng(a[0],a[1]))};
</script>
<?php foot();
