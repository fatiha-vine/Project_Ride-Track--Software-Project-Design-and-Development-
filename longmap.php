<?php require 'config.php';require 'fare.php';$u=me();
// Long distance (intercity) map: every intercity route drawn through its stops.
$R=[];
foreach(q("SELECT id,from_city,to_city FROM routes WHERE type='intercity' ORDER BY from_city,to_city")->fetchAll() as $r){
 $pts=q('SELECT s.name,s.lat,s.lng FROM route_stops rs JOIN stops s ON s.id=rs.stop_id WHERE rs.route_id=? AND s.lat IS NOT NULL AND s.lng IS NOT NULL ORDER BY rs.seq',[$r['id']])->fetchAll();
 if(count($pts)<2){$pts=[];foreach([$r['from_city'],$r['to_city']] as $n){$s=q('SELECT name,lat,lng FROM stops WHERE name=? AND lat IS NOT NULL LIMIT 1',[$n])->fetch();if($s)$pts[]=$s;}}
 if(count($pts)<2)continue;
 $bs=q("SELECT b.id,b.reg_number reg,b.operator,TIME_FORMAT(sc.depart_time,'%h:%i %p') t FROM schedules sc JOIN buses b ON b.id=sc.bus_id WHERE sc.route_id=? AND b.status='approved' ORDER BY sc.depart_time",[$r['id']])->fetchAll();
 $R[]=['id'=>(int)$r['id'],'from'=>$r['from_city'],'to'=>$r['to_city'],'pts'=>array_map(fn($p)=>[$p['name'],(float)$p['lat'],(float)$p['lng']],$pts),'buses'=>array_map(fn($b)=>['id'=>(int)$b['id'],'reg'=>$b['reg'],'op'=>$b['operator'],'t'=>$b['t']],$bs)];}
$cfg=['ri'=>fare_rate('intercity'),'mi'=>fare_min('intercity'),'pct'=>(int)setting('student_pct',50),'student'=>($u&&is_student($u))];
head('Long distance map');?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>#lmap{width:100%;height:480px;border:1px solid var(--ln);border-radius:14px}.albl{background:#fff;border:1px solid var(--ln);border-radius:8px;padding:1px 6px;font:600 11px Inter,sans-serif;white-space:nowrap;box-shadow:0 1px 3px rgba(0,0,0,.25);color:var(--dk)}.arow{display:flex;gap:10px;flex-wrap:wrap;margin:10px 0}.arow label{flex:1;min-width:180px}.fbig{font-size:1.6em;font-weight:700}.mt{display:flex;gap:8px;margin-bottom:10px}</style>
<div class="mt"><a class="btn sm ghost" href="areas.php">Dhaka areas</a><a class="btn sm" href="longmap.php">Long distance</a></div>
<h2>Long distance map</h2><p class="muted">All intercity routes on one map. Tap a line, or pick two cities, to see the distance, fare and the buses that run there.</p>
<div class="card"><div class="arow"><label>Route<select id="rs"></select></label><label>Or from city<select id="cf"></select></label><label>to city<select id="ct"></select></label></div>
<div id="lmap"></div><div id="res" style="margin-top:12px"></div></div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const R=<?=json_encode($R,JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)?>,CFG=<?=json_encode($cfg)?>;
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const km=(a,b,c,d)=>{const r=Math.PI/180,x=(c-a)*r,y=(d-b)*r,h=Math.sin(x/2)**2+Math.cos(a*r)*Math.cos(c*r)*Math.sin(y/2)**2;return 12742*Math.asin(Math.sqrt(h))};
const fare=k=>Math.max(CFG.mi,Math.round(k*CFG.ri)),stu=f=>Math.max(5,Math.round(f*(100-CFG.pct))/100);
const map=L.map('lmap').setView([23.8,90.4],7);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap contributors'}).addTo(map);
const COL=['#0e7490','#0891b2','#0284c7','#155e75','#0369a1','#06b6d4'];
const rlen=r=>{let s=0;for(let i=1;i<r.pts.length;i++)s+=km(r.pts[i-1][1],r.pts[i-1][2],r.pts[i][1],r.pts[i][2]);return s*1.2}; // roads bend: ~1.2 x straight line
const lines={},cities={};let sel=null,dash=null,all=[];
R.forEach((r,i)=>{const ll=r.pts.map(p=>[p[1],p[2]]);all=all.concat(ll);
 lines[r.id]=L.polyline(ll,{color:COL[i%COL.length],weight:4,opacity:.8}).addTo(map).on('click',()=>pickRoute(r.id));
 [r.pts[0],r.pts[r.pts.length-1]].forEach(p=>{cities[p[0]]=p});});
Object.values(cities).forEach(p=>L.marker([p[1],p[2]],{icon:L.divIcon({className:'',html:'<div class="albl">'+esc(p[0])+'</div>',iconSize:null})}).addTo(map));
if(all.length)map.fitBounds(all,{padding:[30,30]});
const rs=document.getElementById('rs'),cf=document.getElementById('cf'),ct=document.getElementById('ct'),res=document.getElementById('res');
rs.innerHTML='<option value="">— choose —</option>'+R.map(r=>'<option value="'+r.id+'">'+esc(r.from)+' → '+esc(r.to)+'</option>').join('');
const cn=Object.keys(cities).sort(),co='<option value="">— choose —</option>'+cn.map(n=>'<option>'+esc(n)+'</option>').join('');cf.innerHTML=co;ct.innerHTML=co;
function clear(){if(dash){map.removeLayer(dash);dash=null}R.forEach((r,i)=>lines[r.id].setStyle({weight:4,opacity:.8,color:COL[i%COL.length]}))}
function pickRoute(id){clear();cf.value='';ct.value='';rs.value=id;const r=R.find(x=>x.id===id);sel=r;lines[id].setStyle({weight:8,opacity:1});lines[id].bringToFront();map.fitBounds(lines[id].getBounds(),{padding:[40,40]});
 const d=rlen(r),f=fare(d);
 res.innerHTML='<div class="fbig">'+esc(r.from)+' → '+esc(r.to)+'</div><p>About '+d.toFixed(0)+' km · Fare <strong>৳'+f+'</strong> · Student fare <strong>৳'+stu(f)+'</strong>'+(CFG.student?' (yours)':'')+'</p>'
 +'<p class="muted">Stops: '+r.pts.map(p=>esc(p[0])).join(' → ')+'</p><h4>Buses on this route</h4>'
 +(r.buses.length?r.buses.map(b=>'<div class="muted">🚌 '+esc(b.t)+' · '+esc(b.op)+' · '+esc(b.reg)+' — <a href="track.php?bus='+b.id+'">Track</a></div>').join(''):'<p class="muted">No bus is scheduled on this route yet.</p>')
 +'<p><a class="btn" href="search.php?mode=intercity&from='+encodeURIComponent(r.from)+'&to='+encodeURIComponent(r.to)+'">Book a seat</a></p>'}
function pickCities(){const a=cities[cf.value],b=cities[ct.value];if(!a||!b||a===b)return;clear();rs.value='';
 const d=km(a[1],a[2],b[1],b[2])*1.25,f=fare(d);dash=L.polyline([[a[1],a[2]],[b[1],b[2]]],{color:'#0e7490',weight:4,dashArray:'6 8'}).addTo(map);map.fitBounds(dash.getBounds(),{padding:[50,50]});
 res.innerHTML='<div class="fbig">'+esc(a[0])+' → '+esc(b[0])+'</div><p>About '+d.toFixed(0)+' km by road · Fare <strong>≈ ৳'+f+'</strong> · Student fare <strong>≈ ৳'+stu(f)+'</strong></p><p><a class="btn" href="search.php?mode=intercity&from='+encodeURIComponent(a[0])+'&to='+encodeURIComponent(b[0])+'">Find buses</a></p>'}
rs.onchange=()=>{if(rs.value)pickRoute(+rs.value)};cf.onchange=pickCities;ct.onchange=pickCities;
res.innerHTML=R.length?'<p class="muted">Tap a route line on the map to begin.</p>':'<p class="muted">No intercity route with map positions yet. Add stops with coordinates in Admin → Routes.</p>';
</script>
<?php foot();
