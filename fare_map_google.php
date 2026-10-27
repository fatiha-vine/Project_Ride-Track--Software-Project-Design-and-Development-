<?php require 'config.php';require 'gmaps_key.php';require 'fare.php';$u=me();
$stops=[];foreach(q('SELECT DISTINCT s.name,s.lat,s.lng FROM stops s JOIN route_stops rs ON rs.stop_id=s.id WHERE s.lat IS NOT NULL AND s.lng IS NOT NULL')->fetchAll() as $s)$stops[]=[$s['name'],(float)$s['lat'],(float)$s['lng']];
$cfg=['rl'=>fare_rate('local'),'ml'=>fare_min('local'),'ri'=>fare_rate('intercity'),'mi'=>fare_min('intercity'),'pct'=>(int)setting('student_pct',50),'student'=>($u&&is_student($u)),'pending'=>($u&&($u['student_status']??'')=='pending')];
head('Fare map (Google Maps)');?>
<style>#fmap{width:100%;height:480px;border:1px solid var(--ln);border-radius:14px}.fbox{margin-top:12px}.maptools{display:flex;gap:10px;flex-wrap:wrap;margin:10px 0}</style>
<h2>Fare map</h2>
<?php if(!gmaps_ready()){?><div class="card"><p>Google Maps API key is not set. Open <code>gmaps_key.php</code> and paste your key.</p></div><?php foot();exit;}?>
<p class="muted">Tap the map to set your pickup and drop (or use 📍 for your location). The fare and student fare are calculated automatically from the distance.</p>
<div class="card"><div class="maptools"><button class="btn" type="button" id="gps">📍 Use my location as pickup</button><button class="btn ghost" type="button" id="near">🚏 Show bus stops near the map center</button><button class="btn ghost" type="button" id="swap">⇄ Swap</button><button class="btn ghost" type="button" id="reset">Reset</button></div>
<p id="hint" class="muted">Tap map: pickup (A)</p><div id="fmap"></div><div id="fbox" class="fbox"></div></div>
<script>
const CFG=<?=json_encode($cfg)?>,STOPS=<?=json_encode($stops,JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)?>;
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const km=(a,b,c,d)=>{const r=Math.PI/180,x=(c-a)*r,y=(d-b)*r,h=Math.sin(x/2)**2+Math.cos(a*r)*Math.cos(c*r)*Math.sin(y/2)**2;return 12742*Math.asin(Math.sqrt(h))};
const P=(a,b)=>({lat:a,lng:b});
function initMap(){
const map=new google.maps.Map(document.getElementById('fmap'),{center:P(23.78,90.41),zoom:12,mapTypeControl:false,streetViewControl:false});
let pts=[],mk=[],line=null,seq=0,stopMk=[];
function nearest(p){let b=null,bd=1e9;STOPS.forEach(s=>{const d=km(p[0],p[1],s[1],s[2]);if(d<bd){bd=d;b=s}});return bd<=4?{n:b[0],d:bd}:null}
function put(lat,lng){const i=pts.length;pts.push([lat,lng]);const m=new google.maps.Marker({map,position:P(lat,lng),draggable:true,label:i?'B':'A'});
  m.addListener('dragend',()=>{const p=m.getPosition();pts[i]=[p.lat(),p.lng()];calc()});mk.push(m);
  document.getElementById('hint').textContent=pts.length==1?'Tap map: drop (B)':'Drag A or B to change.';if(pts.length==2)calc()}
map.addListener('click',e=>{if(pts.length<2)put(e.latLng.lat(),e.latLng.lng())});
async function road(a,b){try{const j=await (await fetch('https://router.project-osrm.org/route/v1/driving/'+a[1]+','+a[0]+';'+b[1]+','+b[0]+'?overview=full&geometries=geojson')).json();
  if(j.code==='Ok')return{km:j.routes[0].distance/1000,line:j.routes[0].geometry.coordinates.map(c=>P(c[1],c[0]))}}catch(e){}
  return{km:km(a[0],a[1],b[0],b[1])*1.3,line:[P(a[0],a[1]),P(b[0],b[1])],approx:true}}
async function calc(){if(pts.length<2)return;const my=++seq;const r=await road(pts[0],pts[1]);if(my!==seq)return;
  if(line)line.setMap(null);line=new google.maps.Polyline({map,path:r.line,strokeColor:'#0e7490',strokeWeight:5,strokeOpacity:.85});
  const bb=new google.maps.LatLngBounds();r.line.forEach(p=>bb.extend(p));map.fitBounds(bb,60);
  const long=r.km>40,rate=long?CFG.ri:CFG.rl,min=long?CFG.mi:CFG.ml,full=Math.max(min,Math.round(r.km*rate)),stu=Math.max(5,Math.round(full*(100-CFG.pct))/100);
  const mins=Math.max(1,Math.round(r.km/(long?50:20)*60)),a=nearest(pts[0]),b=nearest(pts[1]);
  let h='<h3>'+r.km.toFixed(1)+' km · about '+mins+' min'+(r.approx?' <span class="muted">(approx)</span>':'')+'</h3>';
  if(CFG.student)h+='<div class="muted"><s>৳'+full+'</s></div><div class="big">৳'+stu+'</div><span class="pill verified">Your student fare</span>';
  else h+='<div class="big">৳'+full+'</div><div class="muted">Student fare ৳'+stu+(CFG.pending?' once verified':' (verified student ID required)')+'</div>';
  if(a&&b&&a.n!==b.n)h+='<p>Nearest stops: <b>'+esc(a.n)+'</b> ('+Math.round(a.d*1000)+' m) → <b>'+esc(b.n)+'</b> ('+Math.round(b.d*1000)+' m)</p><a class="btn" href="search.php?mode='+(long?'intercity':'local')+'&from='+encodeURIComponent(a.n)+'&to='+encodeURIComponent(b.n)+'">Find buses</a>';
  else h+='<p class="muted">No bus stop found nearby. Try another spot on the map.</p>';
  document.getElementById('fbox').innerHTML=h}
function reset(){mk.forEach(m=>m.setMap(null));mk=[];pts=[];if(line)line.setMap(null);line=null;seq++;document.getElementById('fbox').innerHTML='';document.getElementById('hint').textContent='Tap map: pickup (A)'}
document.getElementById('gps').onclick=()=>{if(!navigator.geolocation){alert('Your browser does not support GPS.');return}
  navigator.geolocation.getCurrentPosition(p=>{reset();put(p.coords.latitude,p.coords.longitude);map.setCenter(P(p.coords.latitude,p.coords.longitude));map.setZoom(15);setTimeout(()=>document.getElementById('near').click(),500)},()=>alert('Please allow location access.'),{enableHighAccuracy:true})};
document.getElementById('near').onclick=async()=>{const h=document.getElementById('hint'),old=h.textContent;
  if(map.getZoom()<14){alert('Zoom in a little more (or use your location) to load bus stops.');return}
  h.textContent='Loading bus stops...';
  try{const {Place}=await google.maps.importLibrary('places');const c=map.getCenter();
    const {places}=await Place.searchNearby({fields:['displayName','location'],locationRestriction:{center:c,radius:1500},includedPrimaryTypes:['bus_stop','bus_station'],maxResultCount:20});
    stopMk.forEach(m=>m.setMap(null));stopMk=[];
    places.forEach(pl=>{const m=new google.maps.Marker({map,position:pl.location,title:pl.displayName,icon:{path:google.maps.SymbolPath.CIRCLE,scale:7,fillColor:'#fbbf24',fillOpacity:1,strokeColor:'#b45309',strokeWeight:2}});m.addListener('click',()=>{if(pts.length<2)put(pl.location.lat(),pl.location.lng())});stopMk.push(m)});
    h.textContent=places.length?places.length+' bus stops loaded. Tap a stop to use it as pickup/drop.':'No bus stops found here. Try another area.'}
  catch(err){h.textContent=old;alert('Could not load bus stops. Make sure Places API (New) is enabled for your key.')}};
document.getElementById('reset').onclick=reset;
document.getElementById('swap').onclick=()=>{if(pts.length<2)return;const [a,b]=pts;reset();put(b[0],b[1]);put(a[0],a[1])};
}
</script>
<?=gmaps_script('initMap')?>
<?php foot();
