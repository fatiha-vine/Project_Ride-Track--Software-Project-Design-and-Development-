<?php require 'config.php';$u=need('admin');
function route_pos($rid,$lat,$lng){
 $st=q('SELECT rs.offset_min,s.lat,s.lng FROM route_stops rs JOIN stops s ON s.id=rs.stop_id WHERE rs.route_id=? ORDER BY rs.seq',[$rid])->fetchAll();
 if(count($st)<2)return null;
 $k=cos(deg2rad($lat));$best=null;$tot=0;
 foreach($st as $s)$tot=max($tot,(int)$s['offset_min']);
 for($i=0;$i<count($st)-1;$i++){
  $ax=$st[$i]['lng']*$k;$ay=$st[$i]['lat'];$bx=$st[$i+1]['lng']*$k;$by=$st[$i+1]['lat'];$px=$lng*$k;$py=$lat;
  $dx=$bx-$ax;$dy=$by-$ay;$l2=$dx*$dx+$dy*$dy;
  $t=$l2>0?max(0,min(1,(($px-$ax)*$dx+($py-$ay)*$dy)/$l2)):0;
  $d=hypot($px-($ax+$t*$dx),$py-($ay+$t*$dy));
  if($best===null||$d<$best['d'])$best=['d'=>$d,'off'=>$st[$i]['offset_min']+$t*($st[$i+1]['offset_min']-$st[$i]['offset_min'])];
 }
 return ['off'=>$best['off'],'total'=>$tot,'km'=>$best['d']*111];}
function plan($tid){
 $t=q("SELECT t.*,r.from_city,r.to_city,b.lat,b.lng,b.reg_number,TIMESTAMPDIFF(MINUTE,b.gps_updated,NOW()) age FROM trips t JOIN routes r ON r.id=t.route_id JOIN buses b ON b.id=t.bus_id WHERE t.id=? AND t.status IN('running','delayed')",[$tid])->fetch();
 if(!$t)return 'This trip is not running or delayed.';
 if($t['lat']===null||$t['age']===null||$t['age']>30)return 'The bus GPS is older than 30 minutes, so its position cannot be trusted. Use the manual percentage on the Admin page.';
 $p=route_pos($t['route_id'],(float)$t['lat'],(float)$t['lng']);
 if(!$p||$p['total']<=0)return 'This route has no stop timings, so the distance cannot be calculated. Use the manual percentage on the Admin page.';
 if($p['km']>15)return 'The bus is more than 15 km away from its route line. Use the manual percentage on the Admin page.';
 $rows=[];
 foreach(q("SELECT k.*,COALESCE(u.name,k.manual_name) nm FROM bookings k LEFT JOIN users u ON u.id=k.user_id WHERE k.trip_id=? AND k.status='confirmed' ORDER BY k.seat_no",[$tid])->fetchAll() as $k){
  $A=0;$B=$p['total'];
  if($k['from_stop']){$x=q('SELECT offset_min FROM route_stops WHERE route_id=? AND stop_id=?',[$t['route_id'],$k['from_stop']])->fetchColumn();if($x!==false)$A=(int)$x;}
  if($k['to_stop']){$x=q('SELECT offset_min FROM route_stops WHERE route_id=? AND stop_id=?',[$t['route_id'],$k['to_stop']])->fetchColumn();if($x!==false)$B=(int)$x;}
  if($B<=$A)$f=0;elseif($p['off']<=$A)$f=1;elseif($p['off']>=$B)$f=0;else $f=($B-$p['off'])/($B-$A);
  $k['frac']=$f;$k['rf']=round($k['fare']*$f,2);$rows[]=$k;}
 return ['t'=>$t,'p'=>$p,'rows'=>$rows];}
if($_POST){chk();$id=(int)($_POST['id']??0);
 $pdo->beginTransaction();q('SELECT id FROM trips WHERE id=? FOR UPDATE',[$id])->fetch();
 $pl=plan($id);
 if(is_string($pl)){$pdo->rollBack();flash($pl);}
 else{
  q("UPDATE trips SET status='interrupted' WHERE id=?",[$id]);
  foreach($pl['rows'] as $k){
   q('UPDATE bookings SET refund=? WHERE id=?',[$k['rf'],$k['id']]);
   if($k['user_id']){
    if($k['pay_method']=='card')card_refund($k,$k['rf']);
    $pc=round($k['frac']*100);
    notify($k['user_id'],$k['rf']>0?"Your bus stopped before your destination. Refund for the part you did not travel: ৳{$k['rf']} ($pc%) ".($k['pay_method']=='card'?'was added to your card.':'is pending.'):'Your trip was interrupted after your stop, so no refund is due.');}}
  q('INSERT INTO audit_logs(user_id,action) VALUES(?,?)',[$u['id'],"Auto refund applied for trip $id"]);
  $pdo->commit();flash('Refunds were calculated from the bus GPS and applied.');}
 header('Location: interrupt_auto.php');exit;}
$tid=(int)($_GET['trip']??0);$pl=$tid?plan($tid):null;
$trips=q("SELECT t.id,t.depart_date,t.depart_time,r.from_city,r.to_city,b.reg_number FROM trips t JOIN routes r ON r.id=t.route_id JOIN buses b ON b.id=t.bus_id WHERE t.status IN('running','delayed') ORDER BY t.depart_date,t.depart_time")->fetchAll();
head('Auto refund');?>
<h2>Automatic refund for an interrupted trip</h2><p class="muted">Pick a trip that stopped on the road. The system reads the bus GPS, finds how far it got, and refunds each passenger only for the part of their journey they did not travel.</p>
<form class="row card" method="get"><label>Trip<select name="trip"><?php foreach($trips as $t)echo '<option value="'.$t['id'].'"'.($t['id']==$tid?' selected':'').'>'.e($t['depart_date'].' '.substr($t['depart_time'],0,5).' · '.$t['from_city'].' to '.$t['to_city'].' · '.$t['reg_number']).'</option>';?></select></label><button class="btn">Calculate</button></form>
<?php if(!$trips)echo '<p class="card">No trip is running or delayed right now.</p>';
if(is_string($pl))echo '<p class="card err">'.e($pl).'</p>';
elseif($pl){$t=$pl['t'];$p=$pl['p'];$sum=0;foreach($pl['rows'] as $r)$sum+=$r['rf'];?>
<div class="card"><p>Bus <strong><?=e($t['reg_number'])?></strong> is about <strong><?=round($p['off']/$p['total']*100)?>%</strong> of the way from <?=e($t['from_city'])?> to <?=e($t['to_city'])?> (GPS updated <?=(int)$t['age']?> min ago).</p>
<?php if(!$pl['rows'])echo '<p class="muted">No confirmed bookings on this trip.</p>';else{?><div class="scroll"><table><tr><th>Passenger</th><th>Seat</th><th>Fare</th><th>Refund share</th><th>Refund</th></tr>
<?php foreach($pl['rows'] as $r)echo '<tr><td>'.e($r['nm']?:'Passenger').($r['user_id']?'':' <small class="muted">(counter, pay cash)</small>').'</td><td>'.($r['seat_no']?e($r['seat_no']):'standing').'</td><td>৳'.number_format($r['fare'],2).'</td><td>'.round($r['frac']*100).'%</td><td><strong>৳'.number_format($r['rf'],2).'</strong></td></tr>';?></table></div>
<p>Total refund: <strong>৳<?=number_format($sum,2)?></strong></p>
<form method="post" data-confirm="Apply these refunds now? This cannot be undone."><?=tok()?><input type="hidden" name="id" value="<?=$tid?>"><button class="btn">Apply refunds</button></form><?php }?></div><?php }
foot();
