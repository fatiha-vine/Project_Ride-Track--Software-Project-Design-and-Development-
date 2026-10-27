<?php require 'config.php';require 'fare.php';$u=me();$R=[];
foreach(q("SELECT id,from_city f,to_city t,fare FROM routes WHERE type='intercity' ORDER BY from_city,to_city")->fetchAll() as $r){
 $a=q('SELECT lat,lng FROM stops WHERE name=?',[$r['f']])->fetch();$b=q('SELECT lat,lng FROM stops WHERE name=?',[$r['t']])->fetch();
 if(!$a||!$b||$a['lat']===null||$b['lat']===null)continue;
 $bus=q("SELECT DISTINCT b.operator,b.reg_number reg,TIME_FORMAT(s.depart_time,'%H:%i') tm FROM schedules s JOIN buses b ON b.id=s.bus_id WHERE s.route_id=? AND b.status='approved' ORDER BY s.depart_time",[$r['id']])->fetchAll();
 $R[]=['f'=>$r['f'],'t'=>$r['t'],'fare'=>(float)$r['fare'],'a'=>[(float)$a['lat'],(float)$a['lng']],'b'=>[(float)$b['lat'],(float)$b['lng']],'bus'=>$bus];}
$cfg=['pct'=>(int)setting('student_pct',50),'student'=>($u&&is_student($u))];
head('Long-distance map');?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>#lmap{width:100%;height:480px;border:1px solid var(--ln);border-radius:16px}.lsel{display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end;margin-bottom:12px}@media(max-width:620px){.lsel{grid-template-columns:1fr}}.lres{margin-top:14px}.lres .big{font:700 2.2rem/1.1 'Cormorant Garamond',serif}.bl{display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-top:1px solid var(--ln)}</style>
<h2>Long-distance map</h2><p class="muted">Choose where you start and where you are going. You see the road, the distance, the fare and the buses that run on it.</p>
<div class="card"><div class="lsel"><label>From<select id="lf"></select></label><label>To<select id="lt"></select></label><button class="btn ghost" type="button" id="lsw">Swap</button></div>
<div id="lmap"></div><div id="lres" class="lres muted">Pick a city on the map or from the lists.</div></div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const R=<?=json_encode($R,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP)?>,CFG=<?=json_encode($cfg)?>;
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const map=L.map('lmap').setView([23.8,90.4],7);
L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',{maxZoom:19,subdomains:'abcd',attribution:'© OpenStreetMap contributors © CARTO'}).addTo(map);
const lf=document.getElementById('lf'),lt=document.getElementById('lt'),res=document.getElementById('lres');
const cities={};R.forEach(r=>{cities[r.f]=r.a;cities[r.t]=r.b});
const froms=[...new Set(R.map(r=>r.f))];
lf.innerHTML='<option value="">Choose...</option>'+froms.map(c=>'<option>'+esc(c)+'</option>').join('');
Object.keys(cities).forEach(c=>{L.circleMarker(cities[c],{radius:7,color:'#1d4ed8',fillColor:'#fff',fillOpacity:1,weight:3}).addTo(map).bindTooltip(c,{permanent:true,direction:'top'}).on('click',()=>pick(c))});
R.forEach(r=>L.polyline([r.a,r.b],{color:'#1d4ed8',weight:2,opacity:.35,dashArray:'4 6'}).addTo(map));
function toList(){lt.innerHTML='<option value="">Choose...</option>'+R.filter(r=>r.f===lf.value).map(r=>'<option>'+esc(r.t)+'</option>').join('')}
function pick(c){if(!lf.value||(lf.value&&lt.value)){lf.value=froms.includes(c)?c:'';toList();lt.value='';draw();return}if(R.some(r=>r.f===lf.value&&r.t===c)){lt.value=c;draw()}}
lf.onchange=()=>{toList();lt.value='';draw()};lt.onchange=draw;
document.getElementById('lsw').onclick=()=>{const a=lf.value,b=lt.value;if(!a||!b)return;if(R.some(r=>r.f===b&&r.t===a)){lf.value=b;toList();lt.value=a;draw()}};
let line=null,seq=0;
async function draw(){const my=++seq;if(line){map.removeLayer(line);line=null}
 const r=R.find(x=>x.f===lf.value&&x.t===lt.value);if(!r){res.className='lres muted';res.textContent=lf.value?'Now choose where you are going.':'Pick a city on the map or from the lists.';return}
 let pts=[r.a,r.b],km=null;
 try{const j=await (await fetch('https://router.project-osrm.org/route/v1/driving/'+r.a[1]+','+r.a[0]+';'+r.b[1]+','+r.b[0]+'?overview=full&geometries=geojson')).json();
  if(j.code==='Ok'){pts=j.routes[0].geometry.coordinates.map(c=>[c[1],c[0]]);km=j.routes[0].distance/1000}}catch(e){}
 if(my!==seq)return;line=L.polyline(pts,{color:'#1d4ed8',weight:5,opacity:.9}).addTo(map);map.fitBounds(line.getBounds(),{padding:[40,40]});
 const stu=Math.round(r.fare*(100-CFG.pct))/100,hrs=km?km/50:null;
 let h='<div class="big">৳'+r.fare+'</div><div class="muted">'+(km?km.toFixed(0)+' km · about '+Math.floor(hrs)+' h '+Math.round((hrs%1)*60)+' min':'Distance not available (offline)')+'</div>';
 h+=CFG.student?'<div><b>Your student fare ৳'+stu+'</b></div>':'<div class="muted">Student fare ৳'+stu+' with a verified student ID</div>';
 h+='<h3 style="margin-top:14px">Buses on this route</h3>'+(r.bus.length?r.bus.map(b=>'<div class="bl"><span>'+esc(b.operator)+' · '+esc(b.reg)+'</span><b>'+esc(b.tm)+'</b></div>').join(''):'<div class="muted">No bus scheduled yet.</div>')
 +'<p><a class="btn" href="search.php?mode=intercity&from='+encodeURIComponent(r.f)+'&to='+encodeURIComponent(r.t)+'">See seats and book</a></p>';
 res.className='lres';res.innerHTML=h}
</script>
<?php foot();
