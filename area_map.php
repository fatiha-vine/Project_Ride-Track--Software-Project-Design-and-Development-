<?php require 'config.php';require 'fare.php';$u=me();
/* Dhaka area map (OpenStreetMap + Leaflet, no API key).
   Areas = rows of the stops table inside the Dhaka box. Teal = a local bus serves it, grey = no bus yet. */
function local_buses($rid){return (int)q("SELECT COUNT(DISTINCT s.bus_id) FROM schedules s JOIN buses b ON b.id=s.bus_id AND b.status='approved' WHERE s.route_id=?",[$rid])->fetchColumn();}

// Area tapped: buses serving it + fare from it to every area further along those routes
if(isset($_GET['info'])){header('Content-Type: application/json');header('Cache-Control: no-store');
 $a=(int)$_GET['info'];$out=['routes'=>[],'to'=>[]];$best=[];
 foreach(q("SELECT r.id,r.from_city,r.to_city,rs.seq FROM routes r JOIN route_stops rs ON rs.route_id=r.id WHERE rs.stop_id=? AND r.type='local'",[$a])->fetchAll() as $r){
  $out['routes'][]=['name'=>$r['from_city'].' → '.$r['to_city'],'buses'=>local_buses($r['id'])];
  foreach(q('SELECT s.id,s.name FROM route_stops rs JOIN stops s ON s.id=rs.stop_id WHERE rs.route_id=? AND rs.seq>? ORDER BY rs.seq',[$r['id'],$r['seq']])->fetchAll() as $d){
   $f=seg_fare($r['id'],$a,$d['id']);if($f===null)continue;
   if(!isset($best[$d['id']])||$f<$best[$d['id']]['fare'])$best[$d['id']]=['id'=>(int)$d['id'],'name'=>$d['name'],'fare'=>$f];}}
 usort($best,function($x,$y){return $x['fare']<=>$y['fare'];});
 foreach($best as $b){$b['stu']=student_fare_amount($b['fare']);$out['to'][]=$b;}
 echo json_encode($out,JSON_UNESCAPED_UNICODE);exit;}

// Pickup + drop chosen: real fare if one local bus route links them
if(isset($_GET['pair'])){header('Content-Type: application/json');header('Cache-Control: no-store');
 $a=(int)$_GET['pair'];$b=(int)($_GET['to']??0);$res=['direct'=>null];
 foreach(q("SELECT r.id,r.from_city,r.to_city FROM routes r JOIN route_stops x ON x.route_id=r.id AND x.stop_id=? JOIN route_stops y ON y.route_id=r.id AND y.stop_id=? WHERE r.type='local' AND x.seq<y.seq",[$a,$b])->fetchAll() as $r){
  $f=seg_fare($r['id'],$a,$b);if($f===null)continue;
  if($res['direct']===null||$f<$res['direct']['fare'])$res['direct']=['fare'=>$f,'stu'=>student_fare_amount($f),'route'=>$r['from_city'].' → '.$r['to_city'],'buses'=>local_buses($r['id'])];}
 echo json_encode($res,JSON_UNESCAPED_UNICODE);exit;}

$onbus=[];foreach(q("SELECT DISTINCT rs.stop_id FROM route_stops rs JOIN routes r ON r.id=rs.route_id WHERE r.type='local'")->fetchAll() as $x)$onbus[(int)$x['stop_id']]=1;
$stops=[];foreach(q('SELECT id,name,lat,lng FROM stops WHERE lat BETWEEN ? AND ? AND lng BETWEEN ? AND ? ORDER BY name',[23.62,23.93,90.28,90.56])->fetchAll() as $s)
 $stops[]=[(int)$s['id'],$s['name'],(float)$s['lat'],(float)$s['lng'],isset($onbus[(int)$s['id']])?1:0];
$lines=[];$seen=[];foreach(q("SELECT id FROM routes WHERE type='local'")->fetchAll() as $r){$pts=[];
 foreach(q('SELECT s.lat,s.lng FROM route_stops rs JOIN stops s ON s.id=rs.stop_id WHERE rs.route_id=? AND s.lat IS NOT NULL AND s.lng IS NOT NULL ORDER BY rs.seq',[$r['id']])->fetchAll() as $p)$pts[]=[(float)$p['lat'],(float)$p['lng']];
 if(count($pts)<2)continue;$k=json_encode($pts);$kr=json_encode(array_reverse($pts));if(isset($seen[$k])||isset($seen[$kr]))continue;$seen[$k]=1;$lines[]=$pts;}
$cfg=['rl'=>fare_rate('local'),'ml'=>fare_min('local'),'pct'=>(int)setting('student_pct',50),'student'=>($u&&is_student($u)),'pending'=>($u&&($u['student_status']??'')=='pending')];
head('Dhaka area map');?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>#amap{width:100%;height:460px;border:1px solid var(--ln);border-radius:14px}.maptools{display:flex;gap:10px;flex-wrap:wrap;margin:10px 0}.frow{display:flex;justify-content:space-between;gap:10px;padding:6px 0;border-bottom:1px solid var(--ln)}.dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin:0 4px 0 10px;border:2px solid #0e7490;background:#fff}.dot.g{border-color:#9ca3af;background:#e5e7eb}</style>
<h2>Dhaka area map</h2>
<p class="muted">Tap an area to see the buses and fares from it. Choose a pickup and a drop area to see the fare between them.</p>
<div class="card"><div class="maptools"><input id="q" list="al" placeholder="Search area (e.g. Mirpur)"><datalist id="al"><?php foreach($stops as $s)echo '<option value="'.e($s[1]).'">';?></datalist>
<button class="btn" type="button" id="gps">📍 My area</button><button class="btn ghost" type="button" id="reset">Reset</button></div>
<p class="muted"><span class="dot"></span>Local bus available<span class="dot g"></span>No bus yet</p>
<p id="st" class="muted"></p><div id="amap"></div></div>
<div class="card" id="abox"><p class="muted">Pickup: <b>-</b> · Drop: <b>-</b></p></div>
<div class="card" id="ainfo"><p class="muted">Tap an area on the map.</p></div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const CFG=<?=json_encode($cfg)?>,STOPS=<?=json_encode($stops,JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)?>,LINES=<?=json_encode($lines)?>;
const map=L.map('amap',{minZoom:10}).setView([23.78,90.41],12);map.setMaxBounds([[23.5,90.15],[24.05,90.7]]);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap contributors'}).addTo(map);
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const km=(a,b,c,d)=>{const r=Math.PI/180,x=(c-a)*r,y=(d-b)*r,h=Math.sin(x/2)**2+Math.cos(a*r)*Math.cos(c*r)*Math.sin(y/2)**2;return 12742*Math.asin(Math.sqrt(h))};
const money=(f,s)=>CFG.student?'<s class="muted">৳'+f+'</s> <b>৳'+s+'</b> <span class="pill verified">Student</span>':'<b>৳'+f+'</b> <span class="muted">student ৳'+s+(CFG.pending?' once verified':'')+'</span>';
const stuOf=f=>Math.max(5,Math.round(f*(100-CFG.pct))/100);
LINES.forEach(l=>L.polyline(l,{color:'#0e7490',weight:3,opacity:.35}).addTo(map));
const byId={},byName={};let A=null,B=null,pl=null;
STOPS.forEach(s=>{byId[s[0]]=s;byName[s[1].toLowerCase()]=s[0];
 s.m=L.circleMarker([s[2],s[3]],{radius:s[4]?7:5,color:s[4]?'#0e7490':'#9ca3af',weight:2,fillColor:s[4]?'#fff':'#e5e7eb',fillOpacity:1}).addTo(map);
 s.m.bindTooltip(esc(s[1]));s.m.on('click',()=>openArea(s[0]))});
function paint(){STOPS.forEach(s=>{const c=s[0]===A?'#16a34a':s[0]===B?'#dc2626':(s[4]?'#fff':'#e5e7eb');s.m.setStyle({fillColor:c});if(s[0]===A||s[0]===B)s.m.setRadius(9);else s.m.setRadius(s[4]?7:5)})}
async function openArea(id){const s=byId[id];if(!s)return;map.panTo([s[2],s[3]]);const box=document.getElementById('ainfo');
 box.innerHTML='<h3>'+esc(s[1])+'</h3><p class="muted">Loading...</p>';
 let j;try{j=await (await fetch('area_map.php?info='+id,{cache:'no-store'})).json()}catch(e){box.innerHTML='<p class="muted">Could not load this area.</p>';return}
 let h='<h3>'+esc(s[1])+'</h3><div class="maptools"><button class="btn sm" type="button" onclick="setA('+id+')">Pickup here</button><button class="btn sm ghost" type="button" onclick="setB('+id+')">Drop here</button></div>';
 if(!j.routes.length)h+='<p class="muted">No local bus route serves this area yet.</p>';
 else{h+='<p><b>Buses:</b> '+j.routes.map(r=>esc(r.name)+' ('+r.buses+' bus)').join(' · ')+'</p><h4>Fare from '+esc(s[1])+'</h4>';
  j.to.forEach(d=>{h+='<div class="frow"><span>'+esc(d.name)+'</span><span>'+money(d.fare,d.stu)+'</span></div>'});
  h+='<p><a class="btn sm" href="search.php?mode=local&from='+encodeURIComponent(s[1])+'">See buses from here</a></p>'}
 box.innerHTML=h}
function setA(id){A=id;if(B===id)B=null;paint();calc()}
function setB(id){B=id;if(A===id)A=null;paint();calc()}
async function calc(){const box=document.getElementById('abox');if(pl){map.removeLayer(pl);pl=null}
 const nm=i=>i?esc(byId[i][1]):'-';let h='<p class="muted">Pickup: <b>'+nm(A)+'</b> · Drop: <b>'+nm(B)+'</b></p>';
 if(!A||!B){box.innerHTML=h;return}
 const a=byId[A],b=byId[B];pl=L.polyline([[a[2],a[3]],[b[2],b[3]]],{color:'#dc2626',weight:4,dashArray:'6 8'}).addTo(map);map.fitBounds(pl.getBounds(),{padding:[60,60]});
 const d=km(a[2],a[3],b[2],b[3])*1.3;let j={direct:null};
 try{j=await (await fetch('area_map.php?pair='+A+'&to='+B,{cache:'no-store'})).json()}catch(e){}
 if(j.direct)h+='<h3>'+money(j.direct.fare,j.direct.stu)+'</h3><p class="muted">'+esc(j.direct.route)+' · '+j.direct.buses+' bus · about '+d.toFixed(1)+' km</p><a class="btn" href="search.php?mode=local&from='+encodeURIComponent(a[1])+'&to='+encodeURIComponent(b[1])+'">Find buses</a>';
 else{const f=Math.max(CFG.ml,Math.round(d*CFG.rl));h+='<h3>No direct bus yet</h3><p class="muted">Distance about '+d.toFixed(1)+' km. Estimated fare by distance: '+money(f,stuOf(f))+'</p>'}
 box.innerHTML=h}
document.getElementById('q').onchange=function(){const id=byName[this.value.trim().toLowerCase()];if(id)openArea(id)};
document.getElementById('reset').onclick=()=>{A=B=null;paint();calc();document.getElementById('ainfo').innerHTML='<p class="muted">Tap an area on the map.</p>';map.setView([23.78,90.41],12)};
document.getElementById('gps').onclick=()=>{const st=document.getElementById('st');
 if(!navigator.geolocation){st.textContent='This browser has no GPS support.';return}
 if(!window.isSecureContext){st.textContent='GPS needs HTTPS (or localhost).';return}
 navigator.geolocation.getCurrentPosition(p=>{let best=null,bd=1e9;STOPS.forEach(s=>{const d=km(p.coords.latitude,p.coords.longitude,s[2],s[3])*(s[4]?1:1.5);if(d<bd){bd=d;best=s}});
  if(best&&bd<=6){st.textContent='Your nearest area: '+best[1];setA(best[0]);openArea(best[0])}else st.textContent='No Dhaka area found near you.'},
  e=>{st.textContent=e.code==1?'Location permission denied. Allow it in your browser settings.':'Could not get your location.'},{enableHighAccuracy:true,timeout:15000})};
paint();
</script>
<?php foot();
