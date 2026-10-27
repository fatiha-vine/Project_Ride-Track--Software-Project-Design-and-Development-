<?php require 'config.php';$u=need('operator','admin');
if($_POST){chk();$bid=(int)$_POST['bus'];$b=q('SELECT * FROM buses WHERE id=?',[$bid])->fetch();
 if(!$b||($u['role']!='admin'&&$b['operator_id']!=$u['id']))exit('Access denied');
 if($_POST['a']=='assign'){$d=q('SELECT * FROM users WHERE email=?',[trim($_POST['email'])])->fetch();
  if(!$d)flash('No account uses this email. Ask the driver to register first.');
  elseif(!in_array($d['role'],['passenger','driver']))flash('This account cannot be a driver (it has another role).');
  else{q("UPDATE users SET role='driver' WHERE id=?",[$d['id']]);q('UPDATE buses SET driver_user_id=?,driver_name=? WHERE id=?',[$d['id'],$d['name'],$bid]);notify($d['id'],'You have been assigned as the driver of bus '.$b['reg_number'].'. Open the Driver portal to start sharing GPS.');q('INSERT INTO audit_logs(user_id,action) VALUES(?,?)',[$u['id'],'Driver '.$d['id'].' assigned to bus '.$bid]);flash('Driver assigned.');}}
 if($_POST['a']=='remove'&&$b['driver_user_id']){$old=(int)$b['driver_user_id'];q('UPDATE buses SET driver_user_id=NULL WHERE id=?',[$bid]);
  if(!q('SELECT 1 FROM buses WHERE driver_user_id=?',[$old])->fetch())q("UPDATE users SET role='passenger' WHERE id=? AND role='driver'",[$old]);flash('Driver removed.');}
 header('Location: assign_driver.php');exit;}
$buses=$u['role']=='admin'?q('SELECT b.*,d.name dn,d.email de FROM buses b LEFT JOIN users d ON d.id=b.driver_user_id ORDER BY b.id')->fetchAll():q('SELECT b.*,d.name dn,d.email de FROM buses b LEFT JOIN users d ON d.id=b.driver_user_id WHERE b.operator_id=? ORDER BY b.id',[$u['id']])->fetchAll();
head('Assign driver');?><h2>Assign driver</h2><p class="muted">The driver must first register as a passenger. Assign the bus by email and they become a driver who can send the bus GPS from their own account.</p>
<?php foreach($buses as $b){?><div class="card"><h3><?=e($b['reg_number'])?> <span class="pill"><?=e($b['status'])?></span></h3><p class="muted">Driver: <?=$b['dn']?e($b['dn']).' · '.e($b['de']):'nei'?></p>
<form method="post" class="row"><?=tok()?><input type="hidden" name="bus" value="<?=$b['id']?>"><input type="hidden" name="a" value="assign"><label>Driver email<input type="email" name="email" required></label><button class="btn">Assign</button></form>
<?php if($b['driver_user_id']){?><form method="post"><?=tok()?><input type="hidden" name="bus" value="<?=$b['id']?>"><input type="hidden" name="a" value="remove"><button class="btn ghost sm">Remove driver</button></form><?php }?></div><?php }
if(!$buses)echo '<p class="card">You have no buses yet.</p>';foot();
