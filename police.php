<?php require 'config.php';$u=need('police','admin');
if($_POST&&($_POST['a']??'')=='handled'){chk();$id=(int)$_POST['id'];q('INSERT INTO audit_logs(user_id,action) VALUES(?,?)',[$u['id'],'SOS handled #'.$id]);flash('SOS marked as handled.');header('Location: police.php');exit;}
$qs=trim($_GET['reg']??'');$found=[];
if($qs!==''){$l="%$qs%";$found=q('SELECT b.*,o.phone op_phone,TIMESTAMPDIFF(MINUTE,b.gps_updated,NOW()) age FROM buses b LEFT JOIN users o ON o.id=b.operator_id WHERE b.reg_number LIKE ? OR b.driver_name LIKE ? OR b.driver_id_no LIKE ? OR b.license_no LIKE ? ORDER BY b.id DESC LIMIT 10',[$l,$l,$l,$l])->fetchAll();
 q('INSERT INTO audit_logs(user_id,action) VALUES(?,?)',[$u['id'],'Police lookup: '.mb_substr($qs,0,60)]);}
$st=q("SELECT COUNT(*) n,SUM(status='approved') ok,SUM(status='suspended') sus,SUM(license_doc IS NULL OR license_doc='') nolic FROM buses")->fetch();
$sos24=(int)q('SELECT COUNT(*) FROM sos WHERE created_at>NOW()-INTERVAL 1 DAY')->fetchColumn();
$run=q("SELECT t.status,t.depart_time,b.id bid,b.reg_number,b.operator,b.lat,b.lng,TIMESTAMPDIFF(MINUTE,b.gps_updated,NOW()) age,r.from_city,r.to_city FROM trips t JOIN buses b ON b.id=t.bus_id JOIN routes r ON r.id=t.route_id WHERE t.depart_date=CURDATE() AND t.status IN('running','delayed') ORDER BY t.depart_time")->fetchAll();
$sos=q('SELECT s.*,u.name,u.phone,b.reg_number reg FROM sos s LEFT JOIN users u ON u.id=s.user_id LEFT JOIN trips t ON t.id=s.trip_id LEFT JOIN buses b ON b.id=t.bus_id ORDER BY s.id DESC LIMIT 10')->fetchAll();
$done=[];foreach(q("SELECT action FROM audit_logs WHERE action LIKE 'SOS handled #%'")->fetchAll() as $a)$done[(int)substr($a['action'],strrpos($a['action'],'#')+1)]=1;
$all=q("SELECT id,reg_number,operator,driver_name,status,license_doc FROM buses ORDER BY (status='suspended') DESC,(license_doc IS NULL OR license_doc='') DESC,id DESC LIMIT 40")->fetchAll();
$mine=q("SELECT action,created_at FROM audit_logs WHERE user_id=? ORDER BY id DESC LIMIT 8",[$u['id']])->fetchAll();
head('Traffic police');?>
<h2>Traffic police dashboard</h2>
<div class="stats"><div><b><?=(int)$st['n']?></b><span>Buses</span></div><div><b><?=(int)$st['ok']?></b><span>Approved</span></div><div><b><?=(int)$st['sus']?></b><span>Suspended</span></div><div><b><?=(int)$st['nolic']?></b><span>No licence copy</span></div><div><b><?=count($run)?></b><span>Running now</span></div><div><b><?=$sos24?></b><span>SOS in 24 hours</span></div></div>

<form class="row card"><label>Search a bus by number plate, driver name, driver ID or licence number<input name="reg" value="<?=e($qs)?>" placeholder="e.g. DHAKA-METRO or Rubel or NID-1003" required></label><button class="btn">Search</button></form>
<?php if($qs!==''){if(!$found)echo '<p class="card">No bus found for that search.</p>';foreach($found as $r){
 $tt=q('SELECT t.depart_time,t.status,r.from_city,r.to_city FROM trips t JOIN routes r ON r.id=t.route_id WHERE t.bus_id=? AND t.depart_date=CURDATE() ORDER BY t.depart_time',[$r['id']])->fetchAll();?>
<div class="card"><h3><?=e($r['reg_number'])?> <span class="pill <?=$r['status']=='approved'?'verified':'cancelled'?>"><?=e($r['status'])?></span></h3>
<p>Operator: <?=e($r['operator'])?><?=$r['op_phone']?' · <a href="tel:'.e($r['op_phone']).'">'.e($r['op_phone']).'</a>':''?><br>Capacity: <?=(int)$r['capacity']?> seats<br>Driver: <?=e($r['driver_name'])?><br>Driver ID: <?=e($r['driver_id_no'])?><br>Licence no.: <?=e($r['license_no'])?><br>Licence copy: <?=$r['license_doc']?'<a href="doc.php?f='.e($r['license_doc']).'" target="_blank">view document</a>':'<b>not uploaded</b>'?>
<?=$r['suspend_reason']?'<br>Suspended because: '.e($r['suspend_reason']):''?><br>Last GPS: <?=$r['lat']!==null&&$r['age']!==null?(int)$r['age'].' min ago · <a href="https://maps.google.com/?q='.e($r['lat']).','.e($r['lng']).'" target="_blank" rel="noopener">see on map</a>':'no GPS signal yet'?></p>
<?php if($tt){echo '<div class="muted">Today: ';foreach($tt as $t)echo e(substr($t['depart_time'],0,5)).' '.e($t['from_city']).' to '.e($t['to_city']).' ('.e($t['status']).') · ';echo '</div>';}?>
<p style="margin-bottom:0"><a class="btn sm" href="police_control.php?reg=<?=urlencode($r['reg_number'])?>">Suspend or reinstate</a> <a class="btn sm ghost" href="track.php?bus=<?=(int)$r['id']?>">Track live</a></p></div>
<?php }}?>

<h3 style="margin-top:26px">SOS alerts</h3>
<?php if(!$sos)echo '<p class="card muted">No SOS alerts yet.</p>';foreach($sos as $s){$new=empty($done[(int)$s['id']]);$map=$s['lat']!==null?'https://maps.google.com/?q='.round($s['lat'],6).','.round($s['lng'],6):'';?>
<div class="card trip"><div><b><?=e($s['name']?:'Unknown user')?></b> <span class="pill <?=$new?'cancelled':'verified'?>"><?=$new?'NEW':'handled'?></span><div class="muted"><?=e($s['created_at'])?> · <?=e($s['location'])?> — <?=e($s['note'])?><?=$s['reg']?' · Bus '.e($s['reg']):''?></div>
<div><?php if($s['phone'])echo '<a href="tel:'.e($s['phone']).'">Call '.e($s['phone']).'</a> ';if($map)echo '<a href="'.e($map).'" target="_blank" rel="noopener">Open map</a>';?></div></div>
<?php if($new){?><form method="post"><?=tok()?><input type="hidden" name="a" value="handled"><input type="hidden" name="id" value="<?=(int)$s['id']?>"><button class="btn sm">Mark handled</button></form><?php }?></div>
<?php }?>

<h3 style="margin-top:26px">Buses running now</h3>
<?php if(!$run)echo '<p class="card muted">No bus is running right now.</p>';else{?><div class="card scroll"><table><tr><th>Bus</th><th>Route</th><th>Status</th><th>GPS</th><th></th></tr>
<?php foreach($run as $r)echo '<tr><td>'.e($r['reg_number']).'<div class="muted">'.e($r['operator']).'</div></td><td>'.e($r['from_city']).' to '.e($r['to_city']).'<div class="muted">'.e(substr($r['depart_time'],0,5)).'</div></td><td><span class="pill '.($r['status']=='running'?'running':'delayed').'">'.e($r['status']).'</span></td><td>'.($r['lat']!==null&&$r['age']!==null?(int)$r['age'].' min ago':'no signal').'</td><td><a href="track.php?bus='.(int)$r['bid'].'">Track</a></td></tr>';?></table></div><?php }?>

<h3 style="margin-top:26px">All buses <span class="muted">(suspended and missing licence first)</span></h3>
<div class="card scroll"><table><tr><th>Number plate</th><th>Operator</th><th>Driver</th><th>Status</th><th>Licence copy</th></tr>
<?php foreach($all as $b)echo '<tr><td><a href="police.php?reg='.urlencode($b['reg_number']).'">'.e($b['reg_number']).'</a></td><td>'.e($b['operator']).'</td><td>'.e($b['driver_name']).'</td><td><span class="pill '.($b['status']=='approved'?'verified':'cancelled').'">'.e($b['status']).'</span></td><td>'.($b['license_doc']?'✓':'<b>missing</b>').'</td></tr>';?></table></div>

<h3 style="margin-top:26px">Your recent activity</h3>
<div class="card muted"><?php if(!$mine)echo 'Nothing yet.';foreach($mine as $m)echo e($m['created_at']).' · '.e($m['action']).'<br>';?></div>
<p class="muted">Every lookup, suspension and SOS action is recorded in the audit log with your name.</p>
<?php foot();
