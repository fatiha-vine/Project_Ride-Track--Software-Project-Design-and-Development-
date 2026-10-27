<?php require 'config.php';require 'gmaps_key.php';$u=me();
$buses=q('SELECT *,TIMESTAMPDIFF(SECOND,gps_updated,NOW()) AS age FROM buses')->fetchAll();$sel=null;foreach($buses as $b)if($b['id']==($_GET['bus']??0))$sel=$b;
$routes=[];
if($sel){foreach(q('SELECT r.id,r.from_city,r.to_city FROM routes r WHERE r.id IN(SELECT route_id FROM schedules WHERE bus_id=?) ORDER BY r.from_city,r.to_city',[$sel['id']])->fetchAll() as $r){
  $st=[];foreach(q('SELECT s.name,s.lat,s.lng FROM route_stops rs JOIN stops s ON s.id=rs.stop_id WHERE rs.route_id=? ORDER BY rs.seq',[$r['id']])->fetchAll() as $s)if($s['lat']!==null&&$s['lng']!==null)$st[]=[$s['name'],(float)$s['lat'],(float)$s['lng']];
  if(count($st)>=2)$routes[]=['id'=>(int)$r['id'],'name'=>$r['from_city'].' → '.$r['to_city'],'stops'=>$st];}}
$all=array_map(fn($b)=>['id'=>(int)$b['id'],'operator'=>$b['operator'],'reg'=>$b['reg_number'],'lat'=>$b['lat']===null?null:(float)$b['lat'],'lng'=>$b['lng']===null?null:(float)$b['lng'],'age'=>$b['age']===null?null:(int)$b['age']],$buses);
head('Live tracking (Google Maps)');?>
<style>#map{width:100%;height:480px;border:1px solid var(--ln);border-radius:14px}.maptools{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:10px 0}.maptools select{max-width:100%}#etabox{margin:8px 0}</style>
<h2>Live bus tracking</h2>
<?php if(!gmaps_ready()){?><div class="card"><p>Google Maps API key is not set. Open <code>gmaps_key.php</code> and paste your key.</p></div><?php foot();exit;}?>
<?php if($sel){$old=($sel['age']===null||$sel['age']>600);?>
<div class="card"><h3><?=e($sel['operator'])?> · <?=e($sel['reg_number'])?></h3><p>Last update: <span id="upd"><?=e($sel['gps_updated'])?></span> <span id="crowd" class="pill <?=e($sel['crowding'])?>">Crowding: <?=e($sel['crowding'])?></span></p>
<p class="err" id="old" style="<?=$old?'':'display:none'?>">GPS data is outdated. The bus may have moved.</p>
<div class="maptools"><button class="btn" type="button" id="meBtn">📍 Show my location</button><label><input type="checkbox" id="follow" checked> Follow bus</label><?php if($routes){?><label>Route <select id="routeSel"></select></label><?php }?></div>
<div id="etabox" class="muted"></div><div id="map"></div></div>
<?php }else{?><div class="card"><p class="muted">Tap a bus on the map or choose one below.</p><div id="map"></div></div><?php }?>
<div class="grid"><?php foreach($buses as $b){?><div class="card"><h3><?=e($b['operator'])?></h3><div class="muted"><?=e($b['reg_number'])?></div><a class="btn ghost" href="track_google.php?bus=<?=$b['id']?>">Track this bus</a></div><?php }?></div>
<script>
const SEL=<?=$sel?(int)$sel['id']:0?>,ROUTES=<?=json_encode($routes,JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)?>,ALL=<?=json_encode($all,JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)?>;
const km=(a,b,c,d)=>{const r=Math.PI/180,x=(c-a)*r,y=(d-b)*r,h=Math.sin(x/2)**2+Math.cos(a*r)*Math.cos(c*r)*Math.sin(y/2)**2;return 12742*Math.asin(Math.sqrt(h))};
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const P=(lat,lng)=>({lat:lat,lng:lng});
function initMap(){
const map=new google.maps.Map(document.getElementById('map'),{center:P(23.78,90.41),zoom:12,mapTypeControl:false,streetViewControl:false});
const busIcon={url:'data:image/svg+xml;charset=UTF-8,'+encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><circle cx="20" cy="20" r="18" fill="#0e7490" stroke="#fff" stroke-width="3"/><text x="20" y="27" font-size="20" text-anchor="middle">🚌</text></svg>'),scaledSize:new google.maps.Size(40,40),anchor:new google.maps.Point(20,20)};
if(!SEL){
  const ms={},b=new google.maps.LatLngBounds(),iw=new google.maps.InfoWindow();
  ALL.forEach(x=>{if(x.lat==null||x.lng==null)return;const m=new google.maps.Marker({map,position:P(x.lat,x.lng),icon:busIcon,title:x.operator});b.extend(m.getPosition());ms[x.id]=m;
    m.addListener('click',()=>{iw.setContent('<b>'+esc(x.operator)+'</b><br>'+esc(x.reg)+'<br><a href="track_google.php?bus='+x.id+'">Track this bus</a>');iw.open(map,m)})});
  if(!b.isEmpty())map.fitBounds(b,60);
  setInterval(async()=>{try{const r=await (await fetch('bus_pos.php',{cache:'no-store'})).json();r.forEach(x=>{if(x.lat!=null&&ms[x.id])ms[x.id].setPosition(P(x.lat,x.lng))})}catch(e){}},10000);
  return}
let bus=ALL.find(x=>x.id===SEL),busM=null,cur=null,meM=null,meLL=null,first=true,layers=[];
if(bus&&bus.lat!=null){busM=new google.maps.Marker({map,position:P(bus.lat,bus.lng),icon:busIcon});map.setCenter(P(bus.lat,bus.lng));map.setZoom(14)}
async function road(p){try{const u='https://router.project-osrm.org/route/v1/driving/'+p.map(x=>x[2]+','+x[1]).join(';')+'?overview=full&geometries=geojson';const j=await (await fetch(u)).json();if(j.code==='Ok')return j.routes[0].geometry.coordinates.map(c=>P(c[1],c[0]))}catch(e){}return p.map(x=>P(x[1],x[2]))}
async function drawRoute(i){cur=ROUTES[i];layers.forEach(l=>l.setMap(null));layers=[];if(!cur)return;
  cur.stops.forEach((s,k)=>{const m=new google.maps.Marker({map,position:P(s[1],s[2]),title:s[0],icon:{path:google.maps.SymbolPath.CIRCLE,scale:6,fillColor:'#fff',fillOpacity:1,strokeColor:'#0e7490',strokeWeight:3}});if(k===0||k===cur.stops.length-1)m.setLabel({text:s[0],className:'',fontSize:'12px'});layers.push(m)});
  const line=await road(cur.stops);if(cur!==ROUTES[i])return;layers.push(new google.maps.Polyline({map,path:line,strokeColor:'#0e7490',strokeWeight:5,strokeOpacity:.85}));
  const b=new google.maps.LatLngBounds();line.forEach(p=>b.extend(p));if(busM)b.extend(busM.getPosition());map.fitBounds(b,50);first=false;eta()}
function eta(){const t=document.getElementById('etabox');if(!t)return;let h='';
  if(bus&&bus.lat!=null&&meLL){const d=km(bus.lat,bus.lng,meLL[0],meLL[1]);h+='Bus is <b>'+d.toFixed(1)+' km</b> from you · about <b>'+Math.max(1,Math.round(d/20*60))+' min</b>. '}
  if(cur&&meLL){let best=null,bd=1e9;cur.stops.forEach(s=>{const d=km(meLL[0],meLL[1],s[1],s[2]);if(d<bd){bd=d;best=s}});if(best)h+='Nearest stop on this route: <b>'+esc(best[0])+'</b> ('+(bd<1?Math.round(bd*1000)+' m':bd.toFixed(1)+' km')+').'}
  t.innerHTML=h}
const sel=document.getElementById('routeSel');
if(sel&&ROUTES.length){let bi=0,bd=1e9;if(bus&&bus.lat!=null)ROUTES.forEach((r,i)=>r.stops.forEach(s=>{const d=km(bus.lat,bus.lng,s[1],s[2]);if(d<bd){bd=d;bi=i}}));
  ROUTES.forEach((r,i)=>{const o=document.createElement('option');o.value=i;o.textContent=r.name;sel.appendChild(o)});sel.value=bi;sel.onchange=()=>drawRoute(+sel.value);drawRoute(bi)}
document.getElementById('meBtn').onclick=()=>{if(!navigator.geolocation){alert('Your browser does not support GPS.');return}
  navigator.geolocation.watchPosition(p=>{meLL=[p.coords.latitude,p.coords.longitude];if(!meM)meM=new google.maps.Marker({map,position:P(meLL[0],meLL[1]),title:'You',icon:{path:google.maps.SymbolPath.CIRCLE,scale:8,fillColor:'#2563eb',fillOpacity:1,strokeColor:'#fff',strokeWeight:3}});else meM.setPosition(P(meLL[0],meLL[1]));eta()},()=>alert('Please allow location access in your browser.'),{enableHighAccuracy:true})};
setInterval(async()=>{try{const b=await (await fetch('bus_pos.php?bus='+SEL,{cache:'no-store'})).json();if(!b)return;bus=b;
  if(b.lat!=null){if(!busM)busM=new google.maps.Marker({map,position:P(b.lat,b.lng),icon:busIcon});else busM.setPosition(P(b.lat,b.lng));if(document.getElementById('follow').checked&&!first)map.panTo(P(b.lat,b.lng))}
  document.getElementById('upd').textContent=b.gps_updated||'';const c=document.getElementById('crowd');c.className='pill '+b.crowding;c.textContent='Crowding: '+b.crowding;
  document.getElementById('old').style.display=(b.age==null||b.age>600)?'block':'none';eta()}catch(e){}},5000);
}
</script>
<?=gmaps_script('initMap')?>
<?php foot();
