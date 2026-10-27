<?php require 'config.php';require 'fare.php';require 'areas.inc.php';$u=me();$adm=$u&&$u['role']=='admin';
if($adm&&!empty($_POST['a'])){chk();
 if($_POST['a']=='reassign'){assign_areas();flash('All stops were matched to their nearest Dhaka area.');}
 if($_POST['a']=='add'){$n=trim($_POST['name']??'');$la=(float)($_POST['lat']??0);$ln=(float)($_POST['lng']??0);$r=max(300,min(5000,(int)($_POST['r']??1200)));
  if($n!==''&&$la&&$ln){q('INSERT IGNORE INTO areas(name,lat,lng,radius_m) VALUES(?,?,?,?)',[$n,$la,$ln,$r]);assign_areas();flash('Area added and stops re-matched.');}}
 header('Location: areas.php');exit;}
$areas=q('SELECT id,name,lat,lng,radius_m r FROM areas ORDER BY name')->fetchAll();
foreach($areas as &$a){$a['id']=(int)$a['id'];$a['lat']=(float)$a['lat'];$a['lng']=(float)$a['lng'];$a['r']=(int)$a['r'];}unset($a);
$buses=q("SELECT id,reg_number reg,operator FROM buses WHERE status='approved' AND kind='local' ORDER BY operator,reg_number")->fetchAll();
foreach($buses as &$b){$b['id']=(int)$b['id'];$b['areas']=array_map('intval',array_column(bus_areas($b['id']),'id'));}unset($b);
$cfg=['rl'=>fare_rate('local'),'ml'=>fare_min('local'),'pct'=>(int)setting('student_pct',50),'student'=>($u&&is_student($u))];
head('Areas and fares');?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>#amap{width:100%;height:460px;border:1px solid var(--ln);border-radius:14px}.albl{background:#fff;border:1px solid var(--ln);border-radius:8px;padding:1px 6px;font:600 11px Inter,sans-serif;white-space:nowrap;box-shadow:0 1px 3px rgba(0,0,0,.25);color:var(--dk)}.albl.f{background:#16a34a;color:#fff}.albl.t{background:#dc2626;color:#fff}.arow{display:flex;gap:10px;flex-wrap:wrap;margin:10px 0}.arow label{flex:1;min-width:180px}.fbig{font-size:1.6em;font-weight:700}.sc{max-height:340px;overflow:auto}</style>
<h2>Dhaka areas and fares</h2><p class="muted">Tap an area on the map for pickup, then another for drop. You see the fare, and every bus that serves both areas. Areas are added to a bus automatically from the stops of its routes.</p>
<div class="card"><div class="arow"><label>Pickup area<select id="sf"></select></label><label>Drop area<select id="st"></select></label></div>
<div class="arow"><button class="btn" type="button" id="gps">📍 Use my location</button><button class="btn ghost" type="button" id="swap">⇄ Swap</button><button class="btn ghost" type="button" id="reset">Reset</button></div>
<div id="amap"></div><p id="shint" class="muted" style="margin:6px 0 0"></p><div id="res" style="margin-top:12px"></div></div>
<?php if($adm){?><div class="card"><h3>Admin: manage areas</h3><form method="post" style="display:inline"><?=tok()?><input type="hidden" name="a" value="reassign"><button class="btn ghost sm">Re-match all stops to areas</button></form>
<form method="post" class="arow"><?=tok()?><input type="hidden" name="a" value="add"><label>New area<input name="name" required></label><label>Latitude<input name="lat" required placeholder="23.78"></label><label>Longitude<input name="lng" required placeholder="90.41"></label><label>Radius (m)<input name="r" value="1200"></label><button class="btn sm">Add area</button></form></div><?php }?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const A=<?=json_encode($areas,JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)?>,B=<?=json_encode($buses,JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)?>,CFG=<?=json_encode($cfg)?>;
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const km=(a,b,c,d)=>{const r=Math.PI/180,x=(c-a)*r,y=(d-b)*r,h=Math.sin(x/2)**2+Math.cos(a*r)*Math.cos(c*r)*Math.sin(y/2)**2;return 12742*Math.asin(Math.sqrt(h))};
const fare=k=>Math.max(CFG.ml,Math.round(k*CFG.rl)),stu=f=>Math.max(5,Math.round(f*(100-CFG.pct))/100);
const byId={};A.forEach(a=>byId[a.id]=a);
const dist=(x,y)=>km(x.lat,x.lng,y.lat,y.lng)*1.3; // road distance ~ 1.3 x straight line (same as Fare map)
const both=(x,y)=>B.filter(b=>b.areas.includes(x)&&b.areas.includes(y));
const map=L.map('amap').setView([23.78,90.40],11);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap contributors'}).addTo(map);
const circ={},lbl={};let F=null,T=null,line=null,near='';
A.forEach(a=>{circ[a.id]=L.circle([a.lat,a.lng],{radius:a.r*0.6,color:'#0f7a8a',weight:1,fillOpacity:.12}).addTo(map).on('click',()=>pick(a.id));
 lbl[a.id]=L.marker([a.lat,a.lng],{icon:L.divIcon({className:'',html:'<div class="albl">'+esc(a.name)+'</div>',iconSize:null})}).addTo(map).on('click',()=>pick(a.id));});
const sf=document.getElementById('sf'),st=document.getElementById('st'),res=document.getElementById('res');
const opts='<option value="">— choose —</option>'+A.map(a=>'<option value="'+a.id+'">'+esc(a.name)+'</option>').join('');sf.innerHTML=opts;st.innerHTML=opts;
function pick(id){if(!F||(F&&T)){F=id;T=null}else if(id!==F){T=id}draw()}
sf.onchange=()=>{F=+sf.value||null;draw()};st.onchange=()=>{T=+st.value||null;draw()};
document.getElementById('swap').onclick=()=>{[F,T]=[T,F];draw()};document.getElementById('reset').onclick=()=>{F=T=null;near='';draw()};
document.getElementById('gps').onclick=function(){const h=document.getElementById('shint'),btn=this;
 if(!navigator.geolocation){h.textContent='Location is not available in this browser.';return}
 if(!window.isSecureContext){h.textContent='Location needs HTTPS or localhost. Open the site with https:// or http://localhost/.';return}
 btn.disabled=true;h.textContent='Finding your location... if the browser asks, tap Allow (top-left of the address bar).';
 navigator.geolocation.getCurrentPosition(p=>{btn.disabled=false;let best=null,bd=1e9;
   A.forEach(a=>{const d=km(p.coords.latitude,p.coords.longitude,a.lat,a.lng);if(d<bd){bd=d;best=a}});
   if(best&&bd<15){F=best.id;T=null;near='';draw();map.setView([p.coords.latitude,p.coords.longitude],15);nearStops(p.coords.latitude,p.coords.longitude);res.scrollIntoView({behavior:'smooth',block:'nearest'})}
   else h.textContent='Your location ('+p.coords.latitude.toFixed(4)+', '+p.coords.longitude.toFixed(4)+') seems to be outside Dhaka.'},
  e=>{btn.disabled=false;h.textContent=e.code==1?'Location is blocked for this site. Click the icon left of the address bar, set Location to Allow, then reload the page.':e.code==3?'Location took too long. Turn on Windows Location (Settings > Privacy & security > Location) and try again.':'Could not get your location. Turn on Windows Location service and try again.'},
  {enableHighAccuracy:false,timeout:12000,maximumAge:60000})};
function busHtml(list){return list.length?list.map(b=>'<div class="muted">🚌 '+esc(b.operator)+' · '+esc(b.reg)+' — <a href="track.php?bus='+b.id+'">Track</a></div>').join(''):'<p class="muted">No local bus serves this pair yet.</p>'}
function draw(){sf.value=F||'';st.value=T||'';
 A.forEach(a=>{const c=a.id===F?'#16a34a':a.id===T?'#dc2626':'#0f7a8a';circ[a.id].setStyle({color:c,fillOpacity:(a.id===F||a.id===T)?.35:.12});
  lbl[a.id].setIcon(L.divIcon({className:'',html:'<div class="albl'+(a.id===F?' f':a.id===T?' t':'')+'">'+esc(a.name)+'</div>',iconSize:null}))});
 if(line){map.removeLayer(line);line=null}
 if(!F){res.innerHTML='<p class="muted">Tap the map to choose your pickup area.</p>';return}
 const f=byId[F];let h='';
 if(T){const t=byId[T],d=dist(f,t),fr=fare(d);line=L.polyline([[f.lat,f.lng],[t.lat,t.lng]],{color:'#0f7a8a',weight:4,dashArray:'6 8'}).addTo(map);map.fitBounds(line.getBounds(),{padding:[40,40]});
  h+='<div class="fbig">'+esc(f.name)+' → '+esc(t.name)+'</div><p>About '+d.toFixed(1)+' km · Fare <strong>৳'+fr+'</strong> · Student fare <strong>৳'+stu(fr)+'</strong>'+(CFG.student?' (yours)':'')+'</p><h4>Buses serving both areas</h4>'+busHtml(both(F,T));}
 else h+='<div class="fbig">'+esc(f.name)+'</div><h4>Buses in this area</h4>'+busHtml(B.filter(b=>b.areas.includes(F)))+'<p class="muted"><b>Now choose your drop area:</b> tap a row in the table below, or tap a circle on the map, or use the Drop area box at the top.</p>';
 const rows=A.filter(a=>a.id!==F).map(a=>({a:a,d:dist(f,a)})).sort((x,y)=>x.d-y.d).map(o=>{const fr=fare(o.d),n=both(F,o.a.id).length;
  return '<tr data-id="'+o.a.id+'" style="cursor:pointer'+(o.a.id===T?';font-weight:700':'')+'"><td>'+esc(o.a.name)+'</td><td>'+o.d.toFixed(1)+' km</td><td>৳'+fr+'</td><td>৳'+stu(fr)+'</td><td>'+n+'</td></tr>'}).join('');
 h+='<h4>Fare from '+esc(f.name)+' to every area <span class="muted">(tap a row to set it as drop)</span></h4><div class="sc"><table><tr><th>Area</th><th>Distance</th><th>Fare</th><th>Student</th><th>Buses</th></tr>'+rows+'</table></div>';
 res.innerHTML=h+near;res.querySelectorAll('tr[data-id]').forEach(r=>{r.onclick=()=>{T=+r.dataset.id;draw();res.scrollIntoView({behavior:'smooth',block:'start'})}})}
draw();
// ---- Real bus stops from the map (OpenStreetMap), loaded automatically for the part of Dhaka you are looking at ----
const OVP='https://overpass-api.de/api/interpreter',stopLayer=L.layerGroup().addTo(map),seen=new Set(),hint=document.getElementById('shint');let stopTimer=null;
const nearestArea=(la,ln)=>{let b=null,bd=1e9;A.forEach(a=>{const d=km(la,ln,a.lat,a.lng);if(d<bd){bd=d;b=a}});return b};
const ovp=q=>fetch(OVP,{method:'POST',body:'data='+encodeURIComponent(q)}).then(r=>r.json());
const sname=e=>(e.tags&&(e.tags['name:en']||e.tags.name))||'Bus stop';
async function loadStops(){
 if(map.getZoom()<14){hint.textContent='Zoom in to see real bus stops from the map (orange dots).';return}
 const b=map.getBounds(),bb=[b.getSouth(),b.getWest(),b.getNorth(),b.getEast()].map(x=>x.toFixed(5)).join(',');
 hint.textContent='Loading bus stops...';
 try{const j=await ovp('[out:json][timeout:25];node["highway"="bus_stop"]('+bb+');out 400;');let n=0;
  j.elements.forEach(e=>{if(seen.has(e.id))return;seen.add(e.id);n++;
   L.circleMarker([e.lat,e.lon],{radius:5,color:'#fff',weight:1.5,fillColor:'#f59e0b',fillOpacity:1}).addTo(stopLayer).bindTooltip(esc(sname(e))).on('click',()=>pick(nearestArea(e.lat,e.lon).id))});
  hint.textContent=seen.size?seen.size+' bus stops loaded from the map. Tap an orange dot to pick its area.':'No bus stops found in this view.'}
 catch(x){hint.textContent='Could not load bus stops. Check your internet connection.'}}
map.on('moveend',()=>{clearTimeout(stopTimer);stopTimer=setTimeout(loadStops,700)});
async function nearStops(la,ln){
 try{const j=await ovp('[out:json][timeout:25];node["highway"="bus_stop"](around:1000,'+la+','+ln+');out 100;');
  const rows=j.elements.map(e=>({n:sname(e),d:km(la,ln,e.lat,e.lon)})).sort((x,y)=>x.d-y.d).slice(0,6);
  near=rows.length?'<h4>Bus stops near you</h4>'+rows.map(r=>'<div class="muted">🚏 '+esc(r.n)+' — '+Math.round(r.d*1000)+' m</div>').join(''):'<p class="muted">No mapped bus stop within 1 km of you.</p>';draw()}
 catch(x){}}
loadStops();
</script>
<?php foot();
