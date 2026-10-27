<?php require 'config.php';header('Content-Type: application/json');
$b=q('SELECT id FROM buses WHERE id=? AND gps_key=?',[(int)($_POST['bus']??0),$_POST['key']??''])->fetch();
$la=(float)($_POST['lat']??999);$ln=(float)($_POST['lng']??999);
if(!$b||abs($la)>90||abs($ln)>180){http_response_code(403);exit('{"ok":false}');}
$c=in_array($_POST['crowding']??'',['low','medium','high'])?$_POST['crowding']:null;
q('UPDATE buses SET lat=?,lng=?,gps_updated=NOW(),crowding=COALESCE(?,crowding) WHERE id=?',[$la,$ln,$c,$b['id']]);echo '{"ok":true}';
