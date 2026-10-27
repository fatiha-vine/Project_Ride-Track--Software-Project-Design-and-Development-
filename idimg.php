<?php require 'config.php';need('admin');
$r=q('SELECT student_front,student_back FROM users WHERE id=?',[(int)($_GET['u']??0)])->fetch();
$n=$r?basename($r[($_GET['s']??'')==='back'?'student_back':'student_front']??''):'';$p=__DIR__."/uploads/private/$n";
if(!$n||!is_file($p)){http_response_code(404);exit('Not found');}
header('Content-Type: '.(strtolower(pathinfo($p,PATHINFO_EXTENSION))==='png'?'image/png':'image/jpeg'));readfile($p);
