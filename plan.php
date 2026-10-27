<?php require 'config.php';$u=need();
function km($a,$b,$c,$d){$p=deg2rad($c-$a);$q=deg2rad($d-$b);$x=sin($p/2)**2+cos(deg2rad($a))*cos(deg2rad($c))*sin($q/2)**2;return 12742*asin(sqrt($x));}
function stp($n){return q('SELECT * FROM stops WHERE name=?',[trim($n)])->fetch();}
function fmt($m){$m=(($m%1440)+1440)%1440;return sprintf('%02d:%02d',intdiv($m,60),$m%60);}
if($_POST){chk();
 if(($_POST['a']??'')=='del')q('DELETE FROM plans WHERE id=? AND user_id=?',[(int)$_POST['id'],$u['id']]);
 else{$f=stp($_POST['from']);$t=stp($_POST['to']);$d=$_POST['date'];$ok=date_create_from_format('Y-m-d',$d);
  if($f&&$t&&$f['id']!=$t['id']&&$ok&&$d>=date('Y-m-d')&&$_POST['go']!==''){q('INSERT INTO plans(user_id,from_stop,to_stop,go_date,go_time,back_time) VALUES(?,?,?,?,?,?)',[$u['id'],$f['id'],$t['id'],$d,$_POST['go'],$_POST['back']!==''?$_POST['back']:null]);flash('Trip scheduled.');}
  else flash('Pick two different stops from the list, a future date and a departure time.');}
 header('Location: plan.php');exit;}
function leg($label,$f,$t,$d,$tm){ensure_trips($d);$m=(int)substr($tm,0,2)*60+(int)substr($tm,3,2);$now=(int)date('G')*60+(int)date('i');
 $rows=q("SELECT t.id,t.depart_time,ROUND(COALESCE((SELECT sf.fare FROM stop_fares sf WHERE sf.from_stop=a.stop_id AND sf.to_stop=z.stop_id),GREATEST(1,COALESCE(z.fare_at,r.fare*z.offset_min/NULLIF((SELECT MAX(m.offset_min) FROM route_stops m WHERE m.route_id=r.id),0),0)-COALESCE(a.fare_at,r.fare*a.offset_min/NULLIF((SELECT MAX(m.offset_min) FROM route_stops m WHERE m.route_id=r.id),0),0)))) fare,b.reg_number,b.operator,b.capacity,b.lat,b.lng,b.gps_updated,a.offset_min o1,(SELECT COUNT(*) FROM bookings k WHERE k.trip_id=t.id AND k.active=1) taken
  FROM trips t JOIN routes r ON r.id=t.route_id JOIN buses b ON b.id=t.bus_id AND b.status='approved' JOIN route_stops a ON a.route_id=r.id AND a.stop_id=? JOIN route_stops z ON z.route_id=r.id AND z.stop_id=? AND z.seq>a.seq
  WHERE t.depart_date=? AND t.status NOT IN('cancelled','completed') AND TIME_TO_SEC(t.depart_time)/60+a.offset_min BETWEEN ? AND ? ORDER BY TIME_TO_SEC(t.depart_time)+a.offset_min*60",[$f['id'],$t['id'],$d,$m-30,$m+30])->fetchAll();
 $live=$d==date('Y-m-d')&&abs($now-$m)<=30;$past=$d<date('Y-m-d')||($d==date('Y-m-d')&&$now>$m+30);
 echo '<h3>'.$label.': '.e($f['name']).' to '.e($t['name']).' · '.date('D j M',strtotime($d)).' '.substr($tm,0,5).' <span class="pill '.($live?'running':($past?'':'pending')).'">'.($live?'Live now':($past?'Done':'Scheduled')).'</span></h3>';
 if(!$rows){echo '<p class="muted">No bus reaches '.e($f['name']).' between '.fmt($m-30).' and '.fmt($m+30).' heading to '.e($t['name']).'.</p>';return;}
 echo '<p>'.count($rows).' bus'.(count($rows)>1?'es':'').' can reach <strong>'.e($f['name']).'</strong> between '.fmt($m-30).' and '.fmt($m+30).', heading toward <strong>'.e($t['name']).'</strong>.'.($live||$past?'':' Live locations appear from '.fmt($m-30).'.').'</p>';
 foreach($rows as $r){$arr=(int)substr($r['depart_time'],0,2)*60+(int)substr($r['depart_time'],3,2)+$r['o1'];$free=$r['capacity']-$r['taken'];
  echo '<div class="card trip"><div><strong>'.e($r['operator']).'</strong> · '.e($r['reg_number']).'<div class="muted">Reaches your stop about '.fmt($arr).' · '.$free.' seats left · ৳'.number_format($r['fare']).'</div>';
  if($live){if($r['lat']&&strtotime($r['gps_updated'])>time()-600){$dk=km($f['lat'],$f['lng'],$r['lat'],$r['lng']);echo '<div><span class="pill running">Live</span> '.number_format($dk,1).' km from '.e($f['name']).' · about '.max(1,round($dk/20*60)).' min · <a target="_blank" href="https://www.openstreetmap.org/?mlat='.$r['lat'].'&mlon='.$r['lng'].'#map=15/'.$r['lat'].'/'.$r['lng'].'">see on map</a></div>';}else echo '<div><span class="pill pending">No recent GPS</span></div>';}
  echo '</div>'.(!$past&&$free>0?'<a class="btn" href="book.php?trip='.$r['id'].'&from='.$f['id'].'&to='.$t['id'].'">Book seat</a>':'').'</div>';}}
$plans=q('SELECT p.*,a.name an,a.lat alat,a.lng alng,b.name bn,b.lat blat,b.lng blng FROM plans p JOIN stops a ON a.id=p.from_stop JOIN stops b ON b.id=p.to_stop WHERE p.user_id=? AND p.go_date>=CURDATE() ORDER BY p.go_date,p.go_time',[$u['id']])->fetchAll();
head('Plan a trip');?><h2>Plan a trip</h2><p class="muted">Pick your pickup and drop stops, the day and your time, and a return time if you want one. We show which buses can reach your stop around then, and their live location when the time comes.</p>
<form method="post" class="card row"><?=tok()?><label>From stop<input name="from" list="stops" required placeholder="Jatrabari"></label><label>To stop<input name="to" list="stops" required placeholder="Badda"></label>
<label>Date<input type="date" name="date" required></label><label>Go at<input type="time" name="go" required></label><label>Return at (optional)<input type="time" name="back"></label><button class="btn">Schedule trip</button>
<datalist id="stops"><?php foreach(q('SELECT name FROM stops ORDER BY name')->fetchAll() as $s)echo '<option value="'.e($s['name']).'">';?></datalist></form>
<?php if(!$plans)echo '<p class="card">No scheduled trips yet.</p>';
foreach($plans as $p){$f=['id'=>$p['from_stop'],'name'=>$p['an'],'lat'=>$p['alat'],'lng'=>$p['alng']];$t=['id'=>$p['to_stop'],'name'=>$p['bn'],'lat'=>$p['blat'],'lng'=>$p['blng']];?>
<div class="card"><?php leg('Going',$f,$t,$p['go_date'],$p['go_time']);if($p['back_time'])leg('Return',$t,$f,$p['go_date'],$p['back_time']);?>
<form method="post" data-confirm="Remove this scheduled trip?"><?=tok()?><input type="hidden" name="a" value="del"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="btn sm ghost">Remove plan</button></form></div><?php }foot();
