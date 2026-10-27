<?php require 'config.php';$err='';
if($_POST){chk();$n=trim($_POST['name']);$em=trim($_POST['email']);$p=$_POST['password'];
 if(!$n||!filter_var($em,FILTER_VALIDATE_EMAIL)||strlen($p)<6)$err='Enter your name, a valid email and a password of 6+ characters.';
 elseif(q('SELECT id FROM users WHERE email=?',[$em])->fetch())$err='This email is already registered. Try logging in.';
 else{q('INSERT INTO users(name,email,phone,password) VALUES(?,?,?,?)',[$n,$em,trim($_POST['phone']),password_hash($p,PASSWORD_DEFAULT)]);session_regenerate_id(true);$_SESSION['uid']=$pdo->lastInsertId();header('Location: index.php');exit;}}
head('Sign up');?>
<section class="card narrow"><h2>Create your account</h2><?php if($err)echo '<p class="err">'.e($err).'</p>';?>
<form method="post"><?=tok()?><label>Full name<input name="name" required></label><label>Email<input type="email" name="email" required></label><label>Phone<input name="phone"></label><label>Password<input type="password" name="password" minlength="6" required></label><button class="btn">Create account</button></form></section>
<?php foot();
