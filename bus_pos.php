<?php require 'config.php';require 'autosim.inc.php';auto_sim();header('Content-Type: application/json');header('Cache-Control: no-store');
$id=(int)($_GET['bus']??0);
$sql='SELECT id,operator,reg_number,lat,lng,gps_updated,crowding,TIMESTAMPDIFF(SECOND,gps_updated,NOW()) AS age FROM buses';
$rows=$id?q($sql.' WHERE id=?',[$id])->fetchAll():q($sql)->fetchAll();
foreach($rows as &$r){$r['id']=(int)$r['id'];$r['lat']=$r['lat']===null?null:(float)$r['lat'];$r['lng']=$r['lng']===null?null:(float)$r['lng'];$r['age']=$r['age']===null?null:(int)$r['age'];}
echo json_encode($id?($rows[0]??null):$rows);
