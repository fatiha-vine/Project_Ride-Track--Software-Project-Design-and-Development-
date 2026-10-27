<?php // Demo helper (localhost only): logs you in as a demo passenger or demo driver so the Demo app can show real pages.
require 'config.php';
if(!in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1']))exit('Demo login works on localhost only.');
$as=($_GET['as']??'')==='driver'?'driver':'passenger';
if($as=='passenger'){$em='demo@ridetrack.test';
 $d=q('SELECT id FROM users WHERE email=?',[$em])->fetch();
 if(!$d){q("INSERT INTO users(name,email,phone,password,role) VALUES(?,?,?,?,'passenger')",['Demo Passenger',$em,'01800000000',password_hash('demo1234',PASSWORD_DEFAULT)]);$id=$pdo->lastInsertId();}else $id=$d['id'];}
else{$em='driver@ridetrack.test';
 $d=q('SELECT id FROM users WHERE email=?',[$em])->fetch();
 if(!$d){q("INSERT INTO users(name,email,phone,password,role) VALUES(?,?,?,?,'driver')",['Demo Driver',$em,'01700000000',password_hash('driver123',PASSWORD_DEFAULT)]);$id=$pdo->lastInsertId();}
 else{$id=$d['id'];q("UPDATE users SET role='driver' WHERE id=?",[$id]);}
 if(!q('SELECT id FROM buses WHERE driver_user_id=?',[$id])->fetch()){$b=q("SELECT id FROM buses WHERE status='approved' ORDER BY (driver_user_id IS NULL) DESC,id LIMIT 1")->fetch();
  if($b)q('UPDATE buses SET driver_user_id=?,driver_name=? WHERE id=?',[$id,'Demo Driver',$b['id']]);}}
session_regenerate_id(true);$_SESSION['uid']=$id;
header('Location: demo_app.php?w='.($as=='driver'?'D':'P'));
