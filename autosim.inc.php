<?php // Demo auto-GPS: no need to keep simulate_gps.php open.
// When someone opens Live tracking, any bus that sent no GPS for 20+ seconds is moved along its route.
// A driver who is really sharing GPS keeps his bus fresh, so the simulation never touches it.
// Works from localhost only. Set to false when the system goes live with real drivers.
const AUTO_GPS_DEMO=true;
if(!function_exists('sim_pos')){
function sim_pos($pts,$p){
  $p=fmod($p,1.0);if($p<0)$p+=1.0;
  $d=$p<0.5?$p*2:(1-$p)*2;           // go to the end, then come back
  $len=[];$tot=0;
  for($i=0;$i<count($pts)-1;$i++){$l=hypot($pts[$i+1][0]-$pts[$i][0],$pts[$i+1][1]-$pts[$i][1]);$len[]=$l;$tot+=$l;}
  $x=$d*$tot;
  for($i=0;$i<count($len);$i++){
    if($x<=$len[$i]||$i==count($len)-1){
      $f=$len[$i]>0?min(1,$x/$len[$i]):0;
      return [$pts[$i][0]+($pts[$i+1][0]-$pts[$i][0])*$f,$pts[$i][1]+($pts[$i+1][1]-$pts[$i][1])*$f];
    }
    $x-=$len[$i];
  }
  return $pts[0];
}
}
function auto_sim(){
 if(!AUTO_GPS_DEMO||!in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1']))return;
 $inter=[[23.7104,90.4350],[23.5420,90.7200],[23.4607,91.1809],[23.0159,91.3976],[22.3569,91.7832]]; // Dhaka -> Chattogram
 $local=[[23.8759,90.3795],[23.8513,90.4000],[23.7780,90.4040],[23.7330,90.4172],[23.7104,90.4350]];   // Uttara -> Sayedabad
 $w=' WHERE gps_updated IS NULL OR TIMESTAMPDIFF(SECOND,gps_updated,NOW())>20';
 try{$rows=q('SELECT id,kind FROM buses'.$w)->fetchAll();}catch(Exception $ex){$rows=q('SELECT id FROM buses'.$w)->fetchAll();}
 $crowd=['low','medium','high'];
 foreach($rows as $r){
  $pts=(($r['kind']??'intercity')=='local')?$local:$inter;
  list($la,$ln)=sim_pos($pts,time()/300+$r['id']*0.137);
  q('UPDATE buses SET lat=?,lng=?,gps_updated=NOW(),crowding=? WHERE id=?',[round($la,6),round($ln,6),$crowd[((int)floor(time()/90)+(int)$r['id'])%3],$r['id']]);
 }
}
