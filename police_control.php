<?php require 'config.php';$u=need('police','admin');
if($_POST){chk();$id=(int)$_POST['bus'];$b=q('SELECT * FROM buses WHERE id=?',[$id])->fetch();
 if($b&&$_POST['a']=='suspend'){$why=mb_substr(trim($_POST['reason']),0,160);
  if($why==='')flash('Please enter a reason.');else{
   q("UPDATE buses SET status='suspended',suspend_reason=? WHERE id=?",[$why,$id]);
   foreach(q("SELECT id FROM trips WHERE bus_id=? AND depart_date>=CURDATE() AND status NOT IN('cancelled','completed')",[$id])->fetchAll() as $t){
    q("UPDATE trips SET status='cancelled' WHERE id=?",[$t['id']]);
    foreach(q("SELECT * FROM bookings WHERE trip_id=? AND status='confirmed'",[$t['id']])->fetchAll() as $k){q("UPDATE bookings SET status='cancelled',active=NULL,refund=fare WHERE id=?",[$k['id']]);notify($k['user_id'],'Your trip was cancelled (bus suspended by traffic police). Full refund issued.');
     if($k['pay_method']=='card')card_refund($k,$k['fare']);}}
   if($b['operator_id'])notify($b['operator_id'],'Bus '.$b['reg_number'].' suspended by traffic police: '.$why);
   q('INSERT INTO audit_logs(user_id,action) VALUES(?,?)',[$u['id'],'Police suspended bus #'.$id.': '.$why]);flash('Bus suspended. Upcoming trips were cancelled and refunded.');}}
 if($b&&$_POST['a']=='reinstate'){q("UPDATE buses SET status='approved',suspend_reason=NULL WHERE id=?",[$id]);if($b['operator_id'])notify($b['operator_id'],'Bus '.$b['reg_number'].' is approved again by traffic police.');q('INSERT INTO audit_logs(user_id,action) VALUES(?,?)',[$u['id'],'Police reinstated bus #'.$id]);flash('Bus reinstated.');}
 header('Location: police_control.php?reg='.urlencode($_POST['reg']??''));exit;}
$qs=trim($_GET['reg']??'');$rows=q('SELECT * FROM buses WHERE reg_number LIKE ? OR ?=\'\' ORDER BY id DESC LIMIT 30',["%$qs%",$qs])->fetchAll();
head('Police control');?><h2>Bus control (traffic police)</h2>
<form class="row card"><label>Registration number<input name="reg" value="<?=e($qs)?>"></label><button class="btn">Search</button></form>
<?php foreach($rows as $b){?><div class="card"><h3><?=e($b['reg_number'])?> · <?=e($b['operator'])?> <span class="pill <?=$b['status']=='approved'?'verified':'cancelled'?>"><?=e($b['status'])?></span></h3>
<p class="muted">Driver: <?=e($b['driver_name'])?> · License: <?=e($b['license_no'])?> · ID: <?=e($b['driver_id_no'])?><?=$b['suspend_reason']?' · Reason: '.e($b['suspend_reason']):''?></p>
<?php if($b['status']=='approved'){?><form method="post" class="row"><?=tok()?><input type="hidden" name="bus" value="<?=$b['id']?>"><input type="hidden" name="reg" value="<?=e($qs)?>"><input type="hidden" name="a" value="suspend"><label>Reason for suspending<input name="reason" required maxlength="160"></label><button class="btn" data-x>Suspend bus</button></form>
<?php }elseif($b['status']=='suspended'){?><form method="post"><?=tok()?><input type="hidden" name="bus" value="<?=$b['id']?>"><input type="hidden" name="reg" value="<?=e($qs)?>"><input type="hidden" name="a" value="reinstate"><button class="btn ghost">Reinstate bus</button></form><?php }?></div><?php }
if(!$rows)echo '<p class="card">No bus found.</p>';foot();
