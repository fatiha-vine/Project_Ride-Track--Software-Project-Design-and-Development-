<?php require 'config.php';require 'student_check.php';$u=need('passenger');$pct=setting('student_pct',50);
if($_POST){chk();$sid=trim($_POST['sid']);$fr=card_upload($_FILES['front']??[]);$bk=card_upload($_FILES['back']??[]);
 if($sid===''||!$fr||!$bk){foreach([$fr,$bk] as $o)if($o)@unlink(__DIR__.'/uploads/private/'.$o);flash('Enter your student ID number and upload clear JPG or PNG photos of both sides of your card (max 6 MB each).');}
 else{[$stt,$note,$ses,$exp]=verify_card($sid,$fr,$bk);foreach([$u['student_front'],$u['student_back']] as $o)if($o)@unlink(__DIR__.'/uploads/private/'.basename($o));
  q('UPDATE users SET student_id=?,student_front=?,student_back=?,student_session=?,student_expiry=?,student_status=?,student_check=? WHERE id=?',[$sid,$fr,$bk,$ses,$exp,$stt,$note,$u['id']]);
  if($stt=='verified')notify($u['id'],"Student ID verified. You now get $pct% off local bus fares.");flash($note);}
 header('Location: student.php');exit;}
for($i=0;$i<7;$i++)ensure_trips(date('Y-m-d',strtotime("+$i days")));$st=is_student($u);$days=$u['student_expiry']?(int)((strtotime($u['student_expiry'])-strtotime(date('Y-m-d')))/86400):null;
$rows=q("SELECT t.id,t.depart_date,t.depart_time,r.from_city,r.to_city,r.fare,b.capacity,(SELECT COUNT(*) FROM bookings k WHERE k.trip_id=t.id AND k.active=1) taken FROM trips t JOIN routes r ON r.id=t.route_id JOIN buses b ON b.id=t.bus_id WHERE b.status='approved' AND t.status NOT IN('cancelled','completed') AND t.depart_date>=CURDATE() ORDER BY t.depart_date,t.depart_time LIMIT 20")->fetchAll();
head('Student portal');?>
<section class="hero" style="padding:34px 30px"><h1 style="font-size:2.4rem">Student portal</h1><p>Upload both sides of your student ID card. We read the validity dates and check them against today (<?=date('j F Y')?>). Valid card, instant <?=$pct?>% discount.</p></section>
<div class="card"><h3>Your status</h3>
<?php if($st)echo '<p><span class="pill verified">verified</span> Valid until '.e($u['student_expiry']).' ('.$days.' days left). Your half fare applies automatically on local buses (not on long-distance buses).</p>';
elseif($u['student_status']=='verified')echo '<p><span class="pill cancelled">expired</span> Your card expired on '.e($u['student_expiry']).'. Upload your new card below.</p>';
elseif($u['student_status']=='pending')echo '<p><span class="pill pending">pending</span> An admin will check your card.</p>';
else echo '<p><span class="pill">not verified</span> Upload your card to unlock half fare on local buses.</p>';
if($u['student_check'])echo '<p class="muted">Last check: '.e($u['student_check']).'</p>';?>
<form method="post" enctype="multipart/form-data"><?=tok()?><div class="grid"><label>Student ID number<input name="sid" value="<?=e($u['student_id'])?>" required></label>
<label>Card front (JPG or PNG)<input type="file" name="front" accept="image/jpeg,image/png" required></label><label>Card back (JPG or PNG)<input type="file" name="back" accept="image/jpeg,image/png" required></label></div>
<p class="muted">Tips: flat card, good light, no glare, all text sharp. Your photos are private and only an admin can open them.</p><button class="btn"><?=$st?'Check a new card':'Check my card'?></button></form></div>
<h2>Student fares</h2>
<?php if(!$rows)echo '<p class="card">No upcoming trips right now.</p>';
foreach($rows as $r){$sf=round($r['fare']*(100-$pct)/100,2);$free=$r['capacity']-$r['taken'];?>
<div class="card trip"><div><h3><?=e($r['from_city'])?> to <?=e($r['to_city'])?></h3><div class="muted"><?=e($r['depart_date'])?> at <?=substr($r['depart_time'],0,5)?> · <?=$free?> seats left</div></div>
<div><?php if($st)echo '<div class="muted"><s>৳'.number_format($r['fare']).'</s></div><div class="big">৳'.number_format($sf).'</div>';else echo '<div class="big">৳'.number_format($sf).'</div><div class="muted">after verification (now ৳'.number_format($r['fare']).')</div>';?>
<?=$free>0?'<a class="btn" href="book.php?trip='.$r['id'].'">Book</a>':'<span class="pill cancelled">Sold out</span>'?></div></div>
<?php }foot();