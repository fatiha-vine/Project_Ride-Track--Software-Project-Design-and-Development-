<?php require 'config.php';$u=need();$drv=$u['role']=='driver';
// Drivers pick what the problem is about; admin sees it in the subject and knows which bus it came from.
$cats=['Bus problem (breakdown, tyre, AC, fuel)','Passenger problem (misbehaviour, fare dispute, overloading)','Road or traffic problem','Schedule or trip problem (wrong time, trip not assigned)','App or account problem','Other'];
if($_POST){chk();$s=trim($_POST['subject']??'');$m=trim($_POST['message']??'');
 if($s!==''&&$m!==''){
  if($drv){$cat=explode(' (',$cats[(int)($_POST['cat']??5)]??'Other')[0];$s='['.$cat.'] '.$s;
   $bus=q('SELECT reg_number FROM buses WHERE driver_user_id=? ORDER BY id LIMIT 1',[$u['id']])->fetchColumn();if($bus)$m='Bus '.$bus.': '.$m;}
  q('INSERT INTO complaints(user_id,subject,message) VALUES(?,?,?)',[$u['id'],mb_substr($s,0,100),mb_substr($m,0,500)]);flash('Complaint sent. An administrator will review it.');}
 header('Location: complaints.php');exit;}
$rows=q('SELECT * FROM complaints WHERE user_id=? ORDER BY id DESC',[$u['id']])->fetchAll();head('Complaints');?>
<h2><?=$drv?'Driver help and complaints':'Complaints and help'?></h2>
<form method="post" class="card"><?=tok()?>
<?php if($drv){?><label>What is it about?<select name="cat"><?php foreach($cats as $i=>$c)echo '<option value="'.$i.'">'.e($c).'</option>';?></select></label><?php }?>
<label>Subject<input name="subject" required></label><label>What happened?<textarea name="message" rows="4" required></textarea></label><button class="btn">Send complaint</button></form>
<?php foreach($rows as $r)echo '<div class="card"><strong>'.e($r['subject']).'</strong> <span class="pill '.($r['status']=='resolved'?'verified':'pending').'">'.e($r['status']).'</span><p>'.e($r['message']).'</p></div>';foot();
