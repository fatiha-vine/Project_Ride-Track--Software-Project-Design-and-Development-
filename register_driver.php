<?php require 'config.php';$err='';
if($_POST){chk();$n=trim($_POST['name']??'');$em=trim($_POST['email']??'');$p=$_POST['password']??'';$reg=trim($_POST['reg']??'');$lic=trim($_POST['lic']??'');$did=trim($_POST['did']??'');
 if(!$n||!filter_var($em,FILTER_VALIDATE_EMAIL)||strlen($p)<6||!$reg||!$lic||!$did)$err='Fill in every field. The password needs 6 or more characters.';
 elseif(q('SELECT id FROM users WHERE email=?',[$em])->fetch())$err='This email is already registered. Ask your operator to assign you from the Assign driver page.';
 else{$pdo->beginTransaction();
  $b=q('SELECT * FROM buses WHERE reg_number=? AND license_no=? AND driver_id_no=? FOR UPDATE',[$reg,$lic,$did])->fetch();
  if(!$b){$pdo->rollBack();$err='These details do not match any bus. Type the bus registration, licence number and driver ID exactly as your operator entered them.';}
  elseif($b['driver_user_id']){$pdo->rollBack();$err='This bus already has a driver account. Ask your operator to change it.';}
  else{
   q('INSERT INTO users(name,email,phone,password,role) VALUES(?,?,?,?,?)',[$n,$em,trim($_POST['phone']??''),password_hash($p,PASSWORD_DEFAULT),'driver']);$uid=$pdo->lastInsertId();
   q('UPDATE buses SET driver_user_id=? WHERE id=?',[$uid,$b['id']]);
   q('INSERT INTO audit_logs(user_id,action) VALUES(?,?)',[$uid,'Driver signed up and was linked to bus '.$b['id']]);
   $pdo->commit();notify($b['operator_id'],"Driver $n signed up and was linked to bus ".$b['reg_number'].'.');
   session_regenerate_id(true);$_SESSION['uid']=$uid;header('Location: driver.php');exit;}}}
head('Driver sign up');?>
<section class="card narrow"><h2>Driver sign up</h2><p class="muted">Your operator already registered your bus with your licence and ID number. Enter the same details here and your account is linked to that bus automatically.</p>
<?php if($err)echo '<p class="err">'.e($err).'</p>';?>
<form method="post"><?=tok()?><label>Full name<input name="name" value="<?=e($_POST['name']??'')?>" required></label><label>Email<input type="email" name="email" value="<?=e($_POST['email']??'')?>" required></label><label>Phone<input name="phone" value="<?=e($_POST['phone']??'')?>"></label><label>Password<input type="password" name="password" minlength="6" required></label>
<label>Bus registration number<input name="reg" value="<?=e($_POST['reg']??'')?>" required></label><label>Driving licence number<input name="lic" value="<?=e($_POST['lic']??'')?>" required></label><label>Driver ID number<input name="did" value="<?=e($_POST['did']??'')?>" required></label>
<button class="btn">Create driver account</button></form></section>
<?php foot();
