<?php require 'config.php';
/* Ride Track - GPS simulator (DEMO ONLY, works from localhost only).
   Moves every bus along a route so Live tracking looks alive. */
if(!in_array($_SERVER['REMOTE_ADDR'],['127.0.0.1','::1'])){http_response_code(403);exit('Demo simulator works only on localhost.');}

function sim_pos($pts,$p){
  $p=fmod($p,1.0);if($p<0)$p+=1.0;
  $d=$p<0.5?$p*2:(1-$p)*2;           // go to the end, then come back
  $len=[];$tot=0;
  for($i=0;$i<count($pts)-1;$i++){$l=hypot($pts[$i+1][0]-$pts[$i][0],$pts[$i+1][1]-$pts[$i][1]);$len[]=$l;$tot+=$l;}
  $x=$d*$tot;
  for($i=0;$i<count($len);$i++){
    if($x<=$len[$i]||$i==count($len)-1){
      $f=$len[$i]>0?min(1,$x/$len[$i]):0;
      return [$pts[$i][0]+($pts[$i+1][0]-$pts[$i][0])*$f,$pts[$i][1]+($pts[$i+1][1]-$pts[$i][1])*$f];
    }
    $x-=$len[$i];
  }
  return $pts[0];
}

if(isset($_GET['tick'])){
  header('Content-Type: application/json');
  $inter=[[23.7104,90.4350],[23.5420,90.7200],[23.4607,91.1809],[23.0159,91.3976],[22.3569,91.7832]]; // Dhaka -> Chattogram
  $local=[[23.8759,90.3795],[23.8513,90.4000],[23.7780,90.4040],[23.7330,90.4172],[23.7104,90.4350]];   // Uttara -> Sayedabad
  $period=max(60,(int)($_GET['period']??300));
  try{$rows=q('SELECT id,kind FROM buses')->fetchAll();}catch(Exception $ex){$rows=q('SELECT id FROM buses')->fetchAll();}
  $crowd=['low','medium','high'];$n=0;
  foreach($rows as $r){
    $pts=(($r['kind']??'intercity')=='local')?$local:$inter;
    $p=time()/$period+$r['id']*0.137;
    list($la,$ln)=sim_pos($pts,$p);
    $c=$crowd[((int)floor(time()/90)+(int)$r['id'])%3];
    q('UPDATE buses SET lat=?,lng=?,gps_updated=NOW(),crowding=? WHERE id=?',[round($la,6),round($ln,6),$c,$r['id']]);
    $n++;
  }
  echo json_encode(['ok'=>true,'buses'=>$n,'time'=>date('H:i:s')]);exit;
}
head('GPS simulator');?>
<h2>GPS simulator (demo)</h2>
<div class="card">
<p class="muted">Press Start and keep this page open. Every 3 seconds all buses move along their route and their "last update" time refreshes, so Live tracking stays live.</p>
<label>Trip speed
<select id="per"><option value="120">Fast (2 min per trip)</option><option value="300" selected>Normal (5 min per trip)</option><option value="900">Slow (15 min per trip)</option></select></label>
<button class="btn" type="button" id="go">Start simulation</button>
<a class="btn ghost" href="track.php" target="_blank">Open Live tracking</a>
<p id="st" class="muted">Stopped.</p>
</div>
<script>
var t=null,b=document.getElementById('go'),s=document.getElementById('st');
function tick(){fetch('simulate_gps.php?tick=1&period='+document.getElementById('per').value).then(function(r){return r.json()}).then(function(d){s.textContent='Running. '+d.buses+' buses updated at '+d.time}).catch(function(){s.textContent='Could not reach the server.'})}
b.onclick=function(){if(t){clearInterval(t);t=null;b.textContent='Start simulation';s.textContent='Stopped.';}else{tick();t=setInterval(tick,3000);b.textContent='Stop simulation';}};
</script>
<?php foot();
