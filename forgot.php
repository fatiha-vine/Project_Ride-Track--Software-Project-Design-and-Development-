<?php require 'config.php';$link='';$tk=preg_replace('/[^a-f0-9]/','',$_GET['token']??'');
$row=$tk?q('SELECT * FROM password_resets WHERE token=? AND used=0 AND expires>NOW()',[hash('sha256',$tk)])->fetch():null;
if($_POST){chk();
 if($row&&strlen($_POST['password']??'')>=6){q('UPDATE users SET password=? WHERE id=?',[password_hash($_POST['password'],PASSWORD_DEFAULT),$row['user_id']]);q('UPDATE password_resets SET used=1 WHERE id=?',[$row['id']]);flash('Password updated. Log in with your new password.');header('Location: login.php');exit;}
 if(!$tk){$x=q('SELECT id FROM users WHERE email=?',[trim($_POST['email'])])->fetch();if($x){$t=bin2hex(random_bytes(16));q('INSERT INTO password_resets(user_id,token,expires) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 30 MINUTE))',[$x['id'],hash('sha256',$t)]);$link=send_mail(trim($_POST['email']),'Ride Track password reset',"Open this link within 30 minutes to reset your password:\n".base().'/forgot.php?token='.$t)?'':((in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1']))?'forgot.php?token='.$t:'');}}}
head('Reset password');?><section class="card narrow"><h2>Reset your password</h2>
<?php if($tk&&!$row)echo '<p class="err">This reset link is invalid or expired. Request a new one.</p>';
if($row){?><form method="post"><?=tok()?><label>New password<input type="password" name="password" minlength="6" required></label><button class="btn">Save new password</button></form>
<?php }else{if($_POST)echo '<p class="flash">If that email is registered, a reset link was sent.'.($link?' Demo mode (no email server): <a href="'.e($link).'">open your reset link</a>.':'').'</p>';?>
<form method="post"><?=tok()?><label>Email<input type="email" name="email" required></label><button class="btn">Create reset link</button></form><?php }?></section><?php foot();
