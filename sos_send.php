<?php require 'config.php';require_once 'appshell.inc.php';$u=need();header('Content-Type: application/json');chk();
$lat=($_POST['lat']??'')!==''?(float)$_POST['lat']:null;$lng=($_POST['lng']??'')!==''?(float)$_POST['lng']:null;
if($lat===null||$lng===null||abs($lat)>90||abs($lng)>180)$lat=$lng=null;
$last=q('SELECT id,created_at FROM sos WHERE user_id=? AND created_at>DATE_SUB(NOW(),INTERVAL 30 SECOND) ORDER BY id DESC LIMIT 1',[$u['id']])->fetch();
$tr=active_trip($u['id']);$link=$lat!==null?'https://maps.google.com/?q='.round($lat,6).','.round($lng,6):'';
$loc=$lat!==null?'GPS '.round($lat,5).','.round($lng,5):'Location not shared';
if(!$last){q('INSERT INTO sos(user_id,note,location,lat,lng,trip_id) VALUES(?,?,?,?,?,?)',[$u['id'],'App SOS (3-second hold)'.($tr?' · Bus '.$tr['reg']:''),$loc,$lat,$lng,$tr['trip_id']??null]);}
$msg='Ride Track SOS: '.$u['name'].' needs help.'.($link?' Location: '.$link:'').($tr?' Bus: '.$tr['reg'].' ('.$tr['operator'].').':'').($u['phone']?' Phone: '.$u['phone']:'');
$staff=0;$police=0;
if(!$last){foreach(q("SELECT id,role FROM users WHERE role IN('admin','staff','police')")->fetchAll() as $a){notify($a['id'],$msg);if($a['role']=='police')$police++;else $staff++;}
 if($tr&&$tr['operator_id'])notify($tr['operator_id'],$msg);}
$sms=0;$contacts=q('SELECT name,phone FROM emergency_contacts WHERE user_id=?',[$u['id']])->fetchAll();
if(!$last){foreach($contacts as $c){if(send_sms($c['phone'],$msg))$sms++;}
 $ec=$u['emergency_contact']??'';if($ec&&filter_var($ec,FILTER_VALIDATE_EMAIL))send_mail($ec,'Ride Track SOS alert',$msg);}
echo json_encode(['ok'=>true,'duplicate'=>(bool)$last,'staff'=>$staff,'police'=>$police,'contacts'=>count($contacts),'sms'=>$sms,'bus'=>$tr?['reg'=>$tr['reg'],'operator'=>$tr['operator'],'driver'=>$tr['driver_name'],'phone'=>$tr['op_phone']]:null],JSON_UNESCAPED_UNICODE);
