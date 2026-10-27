<?php require 'config.php';$u=need('admin','police','operator');$f=basename($_GET['f']??'');$p=__DIR__."/uploads/private/$f";
$b=q('SELECT operator_id FROM buses WHERE license_doc=?',[$f])->fetch();$s=q('SELECT id FROM users WHERE student_front=? OR student_back=?',[$f,$f])->fetch();
if($f===''||!is_file($p)||(!$b&&!($s&&$u['role']=='admin'))||($b&&$u['role']=='operator'&&$b['operator_id']!=$u['id']))exit('Not found');
q('INSERT INTO audit_logs(user_id,action) VALUES(?,?)',[$u['id'],"Viewed private document $f"]);
$m=['pdf'=>'application/pdf','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg'][strtolower(pathinfo($f,PATHINFO_EXTENSION))]??'application/octet-stream';
header("Content-Type: $m");header('X-Content-Type-Options: nosniff');readfile($p);
