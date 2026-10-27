<?php // Login screen shared by the three separate portals (passenger, driver, admin/operator)
function portal_login($p){$PS=portals();$P=$PS[$p];$err='';$go='';$GLOBALS['portal']=$p;
 $me=me();if($me&&in_array($me['role'],$P['roles'])){header('Location: '.portal_home($me['role']));exit;}
 if($_POST){chk();$u=q('SELECT * FROM users WHERE email=?',[trim($_POST['email'])])->fetch();
  if($u&&password_verify($_POST['password'],$u['password'])){
   if(!in_array($u['role'],$P['roles'])){$o=$PS[portal_of($u['role'])];$err='This account belongs to the '.$o['name'].'.';$go=$o['url'];$gl='Open '.$o['name'].' login';}
   else{session_regenerate_id(true);$_SESSION['uid']=$u['id'];header('Location: '.portal_home($u['role']));exit;}}
  else $err='Email or password is incorrect.';}
 head($P['title']);?>
<section class="card narrow"><h2><?=e($P['h'])?></h2><p class="muted"><?=e($P['sub'])?></p><?php if($err){echo '<p class="err">'.e($err).'</p>';if($go)echo '<p><a class="btn ghost sm" href="'.e($go).'">'.e($gl).'</a></p>';}?>
<form method="post"><?=tok()?><label>Email<input type="email" name="email" required></label><label>Password<input type="password" name="password" required></label><button class="btn">Log in</button></form>
<p class="muted"><a href="forgot.php">Forgot password?</a><?php if($p=='passenger')echo ' · New here? <a href="register.php">Create an account</a>';elseif($p=='driver')echo '<br>New driver? Create a normal account first and give your email to your bus operator. After they assign your bus, this login works.';?></p></section>
<?php foot();}
