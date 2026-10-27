<?php require 'config.php';$u=need('staff','admin');$err='';
if($_POST){chk();$tid=(int)$_POST['trip'];$seat=(int)$_POST['seat'];$name=trim($_POST['name']);$m=$_POST['method']=='card'?'card':'cash';
 try{$pdo->beginTransaction();$t=q("SELECT t.*,r.fare,b.capacity,b.operator FROM trips t JOIN routes r ON r.id=t.route_id JOIN buses b ON b.id=t.bus_id WHERE t.id=? FOR UPDATE",[$tid])->fetch();
  if(!$t||$t['status']=='cancelled')throw new Exception('Trip unavailable.');
  if($seat<1||$seat>$t['capacity']||$name==='')throw new Exception('Enter a name and a valid seat number.');
  if(q('SELECT 1 FROM bookings WHERE trip_id=? AND seat_no=? AND active=1',[$tid,$seat])->fetch())throw new Exception('That seat is already taken.');
  $uid=null;$cardId=null;
  if($m=='card'){$cq=trim($_POST['email']);$c=q('SELECT cc.* FROM company_cards cc JOIN users u ON u.id=cc.user_id WHERE cc.company=? AND (u.email=? OR u.phone=? OR cc.card_no=?) FOR UPDATE',[$t['operator'],$cq,$cq,$cq])->fetch();
   if(!$c||$c['blocked']||$c['balance']<$t['fare'])throw new Exception('No '.$t['operator'].' E-card found for this passenger, or it is blocked, or the balance is too low.');
   q('UPDATE company_cards SET balance=balance-? WHERE id=?',[$t['fare'],$c['id']]);q('INSERT INTO card_tx(user_id,amount,type,card_id) VALUES(?,?,?,?)',[$c['user_id'],-$t['fare'],'fare',$c['id']]);$uid=$c['user_id'];$cardId=$c['id'];}
  q('INSERT INTO bookings(user_id,trip_id,seat_no,fare,pay_method,pay_ref,manual_name,card_id) VALUES(?,?,?,?,?,?,?,?)',[$uid,$tid,$seat,$t['fare'],$m,'MANUAL',$name,$cardId]);$id=$pdo->lastInsertId();
  q('INSERT INTO audit_logs(user_id,action) VALUES(?,?)',[$u['id'],"Manual ticket #$id"]);$pdo->commit();flash('Manual ticket RT-'.str_pad($id,6,'0',STR_PAD_LEFT).' issued.');header('Location: staff.php');exit;
 }catch(Exception $x){$pdo->rollBack();$err=$x->getMessage();}}
$trips=q("SELECT t.id,t.depart_date,t.depart_time,r.from_city,r.to_city,r.fare FROM trips t JOIN routes r ON r.id=t.route_id WHERE t.status<>'cancelled' ORDER BY depart_date,depart_time")->fetchAll();
$rec=q('SELECT * FROM bookings WHERE manual_name IS NOT NULL ORDER BY id DESC LIMIT 10')->fetchAll();
head('Ticketing staff');?><h2>Issue a manual ticket</h2><?php if($err)echo '<p class="err">'.e($err).'</p>';?>
<form method="post" class="card"><?=tok()?><label>Trip<select name="trip"><?php foreach($trips as $t)echo '<option value="'.$t['id'].'">'.e($t['from_city'].' to '.$t['to_city'].' · '.$t['depart_date'].' '.substr($t['depart_time'],0,5).' · ৳'.$t['fare']).'</option>';?></select></label>
<label>Passenger name<input name="name" required></label><label>Seat number<input type="number" name="seat" min="1" required></label>
<label>Payment<select name="method"><option value="cash">Cash</option><option value="card">Company E-card (the trip's bus company)</option></select></label>
<label>E-card number, email or phone (card payments only)<input name="email"></label><button class="btn">Issue ticket</button></form>
<h3>Recent manual tickets</h3><div class="scroll"><table><tr><th>Ticket</th><th>Passenger</th><th>Seat</th><th>Fare</th><th>Paid by</th></tr><?php foreach($rec as $r)echo '<tr><td><a href="ticket.php?id='.$r['id'].'">RT-'.str_pad($r['id'],6,'0',STR_PAD_LEFT).'</a></td><td>'.e($r['manual_name']).'</td><td>'.$r['seat_no'].'</td><td>৳'.number_format($r['fare'],2).'</td><td>'.e($r['pay_method']).'</td></tr>';?></table></div><?php foot();
