<?php // ONE-TIME helper: creates a demo driver account and assigns it to a bus. It deletes itself after running.
require 'config.php';
$email='driver@ridetrack.test';$pass='driver123';
$bus=q("SELECT id,reg_number FROM buses WHERE status='approved' ORDER BY (driver_user_id IS NULL) DESC,id LIMIT 1")->fetch();
if(!$bus){exit('No approved bus found. Approve a bus first (Admin), then open this page again.');}
$d=q('SELECT id FROM users WHERE email=?',[$email])->fetch();
if($d){$id=$d['id'];q("UPDATE users SET role='driver',password=? WHERE id=?",[password_hash($pass,PASSWORD_DEFAULT),$id]);}
else{q("INSERT INTO users(name,email,phone,password,role) VALUES(?,?,?,?,'driver')",['Demo Driver',$email,'01700000000',password_hash($pass,PASSWORD_DEFAULT)]);$id=$pdo->lastInsertId();}
q('UPDATE buses SET driver_user_id=?,driver_name=? WHERE id=?',[$id,'Demo Driver',$bus['id']]);
@unlink(__FILE__);
echo "<h3>Driver account ready</h3><p>Email: <b>$email</b><br>Password: <b>$pass</b><br>Assigned bus: <b>".htmlspecialchars($bus['reg_number'])."</b></p><p><a href='login.php?as=driver'>Go to Driver login</a></p><p>(This file has deleted itself.)</p>";
