<?php require 'config.php';require 'areas.inc.php';$u=need('admin');
function stop_id($s){global $pdo;$r=q('SELECT id FROM stops WHERE name=?',[$s['n']])->fetch();
 if($r){if($s['lat']!==null)q('UPDATE stops SET lat=?,lng=? WHERE id=? AND lat IS NULL',[$s['lat'],$s['lng'],$r['id']]);return $r['id'];}
 q('INSERT INTO stops(name,lat,lng) VALUES(?,?,?)',[$s['n'],$s['lat'],$s['lng']]);if(function_exists('area_of')&&$s['lat']!==null)q('UPDATE stops SET area_id=? WHERE id=?',[area_of((float)$s['lat'],(float)$s['lng']),$pdo->lastInsertId()]);return $pdo->lastInsertId();}
function mkroute($st,$type,$fare){global $pdo;q('INSERT INTO routes(from_city,to_city,fare,type) VALUES(?,?,?,?)',[$st[0]['n'],$st[count($st)-1]['n'],$fare,$type]);$rid=$pdo->lastInsertId();
 foreach($st as $i=>$s)q('INSERT INTO route_stops(route_id,stop_id,seq,offset_min) VALUES(?,?,?,?)',[$rid,stop_id($s),$i+1,$s['o']]);return $rid;}
function mktt($rid,$type,$first,$last,$every){$every=max(10,min(720,(int)$every));$bs=q("SELECT id FROM buses WHERE status='approved' AND kind=? ORDER BY id",[$type])->fetchAll();if(!$bs)return 0;
 $a=strtotime($first);$b=strtotime($last);$n=0;for($t=$a;$t<=$b&&$n<200;$t+=$every*60){q('INSERT INTO schedules(route_id,bus_id,depart_time) VALUES(?,?,?)',[$rid,$bs[$n%count($bs)]['id'],date('H:i:s',$t)]);$n++;}return $n;}
if(($_POST['a']??'')=='pair'){chk();$x=q('SELECT id FROM stops WHERE name=?',[trim($_POST['from'])])->fetch();$y=q('SELECT id FROM stops WHERE name=?',[trim($_POST['to'])])->fetch();$fv=max(1,(float)$_POST['fare']);
 if($x&&$y&&$x['id']!=$y['id']){q('REPLACE INTO stop_fares VALUES(?,?,?)',[$x['id'],$y['id'],$fv]);if(!empty($_POST['both']))q('REPLACE INTO stop_fares VALUES(?,?,?)',[$y['id'],$x['id'],$fv]);flash('Fare saved.');}else flash('Pick two different existing stops.');header('Location: admin_routes.php');exit;}
if(($_POST['a']??'')=='pairdel'){chk();q('DELETE FROM stop_fares WHERE from_stop=? AND to_stop=?',[(int)$_POST['x'],(int)$_POST['y']]);header('Location: admin_routes.php');exit;}
if(($_POST['a']??'')=='fares'){chk();$rid=(int)$_POST['rid'];$mx=0;foreach(($_POST['f']??[]) as $sid=>$v){$v=trim($v)===''?null:max(0,(float)$v);$mx=max($mx,(float)$v);q('UPDATE route_stops SET fare_at=? WHERE route_id=? AND stop_id=?',[$v,$rid,(int)$sid]);}
 if($mx>0)q('UPDATE routes SET fare=? WHERE id=?',[$mx,$rid]);flash('Fares saved.');header('Location: admin_routes.php');exit;}
if($_POST){chk();$st=[];
 foreach(preg_split('/\R/',$_POST['stops']) as $l){$p=array_map('trim',explode(',',$l));if($p[0]==='')continue;$st[]=['n'=>mb_substr($p[0],0,60),'o'=>max(0,(int)($p[1]??0)),'lat'=>(isset($p[2])&&$p[2]!=='')?(float)$p[2]:null,'lng'=>(isset($p[3])&&$p[3]!=='')?(float)$p[3]:null];}
 $names=array_map(fn($s)=>mb_strtolower($s['n']),$st);$ord=true;foreach($st as $i=>$s)if($i&&$s['o']<$st[$i-1]['o'])$ord=false;
 $type=$_POST['type']=='intercity'?'intercity':'local';$fare=max(1,(float)$_POST['fare']);
 if(count($st)<2||count(array_unique($names))<count($names)||!$ord)flash('Enter at least two different stops, with minutes that never go down.');
 else{try{$pdo->beginTransaction();$st[0]['o']=0;$n=mktt(mkroute($st,$type,$fare),$type,$_POST['first'],$_POST['last'],$_POST['every']);
  if(!empty($_POST['rev'])){$tot=$st[count($st)-1]['o'];$rv=array_reverse($st);foreach($rv as &$s)$s['o']=$tot-$s['o'];unset($s);$n+=mktt(mkroute($rv,$type,$fare),$type,$_POST['first'],$_POST['last'],$_POST['every']);}
  $pdo->commit();flash("Route created with $n timetable entries.".($n?'':' No approved bus of this type was found, so register a bus first.'));}catch(Exception $x){$pdo->rollBack();flash('Could not create the route: '.$x->getMessage());}}
 header('Location: admin_routes.php');exit;}
$routes=q("SELECT r.*,(SELECT GROUP_CONCAT(s.name ORDER BY rs.seq SEPARATOR ' → ') FROM route_stops rs JOIN stops s ON s.id=rs.stop_id WHERE rs.route_id=r.id) path,(SELECT COUNT(*) FROM schedules x WHERE x.route_id=r.id) tt FROM routes r ORDER BY r.id DESC")->fetchAll();
head('Routes and stops');?><h2>Routes and stops</h2><p class="muted">Add a new route with all its stops. Stops are created automatically, and passengers can search any stop on the route.</p>
<form method="post" class="card"><?=tok()?>
<div class="grid"><label>Bus type<select name="type"><option value="local">Local bus</option><option value="intercity">Long-distance bus</option></select></label><label>Fare (৳)<input type="number" name="fare" min="1" required></label></div>
<label>Stops in order, one per line: name, minutes from the first stop, then optional latitude and longitude<textarea name="stops" rows="7" required placeholder="Notunbazar, 0&#10;Kuril, 8&#10;Chorpara, 25"></textarea></label>
<div class="grid"><label>First bus<input type="time" name="first" value="07:00"></label><label>Last bus<input type="time" name="last" value="21:00"></label><label>Every (minutes)<input type="number" name="every" value="30" min="10" max="720"></label></div>
<label><input type="checkbox" name="rev" value="1" checked style="display:inline;width:auto"> Also create the return route</label><button class="btn">Create route</button></form>
<h3>Stop-to-stop fares</h3><p class="muted">One fare for a pair of stops, on every bus and route. It is used before the route fares below.</p>
<form method="post" class="card row"><?=tok()?><input type="hidden" name="a" value="pair"><label>From stop<input name="from" list="stl" required></label><label>To stop<input name="to" list="stl" required></label><label>Fare (৳)<input type="number" name="fare" min="1" step="0.5" required></label>
<label><input type="checkbox" name="both" value="1" checked style="display:inline;width:auto"> Both directions</label><button class="btn">Save fare</button>
<datalist id="stl"><?php foreach(q('SELECT name FROM stops ORDER BY name')->fetchAll() as $s)echo '<option value="'.e($s['name']).'">';?></datalist></form>
<?php foreach(q('SELECT sf.*,a.name an,b.name bn FROM stop_fares sf JOIN stops a ON a.id=sf.from_stop JOIN stops b ON b.id=sf.to_stop ORDER BY a.name,b.name')->fetchAll() as $p)echo '<div class="card trip"><span>'.e($p['an']).' to '.e($p['bn']).' · ৳'.number_format($p['fare'],2).'</span><form method="post">'.tok().'<input type="hidden" name="a" value="pairdel"><input type="hidden" name="x" value="'.$p['from_stop'].'"><input type="hidden" name="y" value="'.$p['to_stop'].'"><button class="btn sm ghost">Remove</button></form></div>';?>
<h3>All routes</h3><p class="muted">Open "Set fares" to give each stop its fare from the first stop. A passenger pays the difference between their two stops, and students pay the discounted share.</p>
<?php foreach($routes as $r){$ss=q('SELECT s.id,s.name,rs.fare_at FROM route_stops rs JOIN stops s ON s.id=rs.stop_id WHERE rs.route_id=? ORDER BY rs.seq',[$r['id']])->fetchAll();
 echo '<div class="card"><span class="pill">'.e($r['type']).'</span> <strong>'.e($r['from_city']).' to '.e($r['to_city']).'</strong> · full fare ৳'.number_format($r['fare']).' · '.$r['tt'].' timetable entries<div class="muted">'.e($r['path']).'</div>';?>
<details><summary>Set fares</summary><form method="post"><?=tok()?><input type="hidden" name="a" value="fares"><input type="hidden" name="rid" value="<?=$r['id']?>"><div class="grid">
<?php foreach($ss as $s)echo '<label>'.e($s['name']).'<input type="number" step="0.5" min="0" name="f['.$s['id'].']" value="'.e($s['fare_at']).'" placeholder="auto"></label>';?></div><button class="btn sm">Save fares</button></form></details></div><?php }foot();
