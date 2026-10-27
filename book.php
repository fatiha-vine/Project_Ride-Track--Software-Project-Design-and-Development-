<?php require 'config.php';$u=need();sweep();$id=(int)($_GET['trip']??0);
$t=q("SELECT t.*,r.from_city,r.to_city,r.fare,r.type,b.capacity,b.operator FROM trips t JOIN routes r ON r.id=t.route_id JOIN buses b ON b.id=t.bus_id WHERE t.id=?",[$id])->fetch();
if(!$t||$t['status']=='cancelled'||strtotime($t['depart_date'].' '.$t['depart_time'])<time())exit('This trip is not available.');
$sf=((int)($_GET['from']??0)&&(int)($_GET['to']??0))?seg_fare($t['route_id'],(int)$_GET['from'],(int)$_GET['to']):null;if($sf)$t['fare']=$sf;$fs=$sf?(int)$_GET['from']:null;$ts=$sf?(int)$_GET['to']:null;
$sn=$sf?q('SELECT (SELECT name FROM stops WHERE id=?) a,(SELECT name FROM stops WHERE id=?) b',[$fs,$ts])->fetch():null;
$disc=(is_student($u)&&$t['type']=='local')?round($t['fare']*setting('student_pct',50)/100,2):0;$fare=$t['fare']-$disc;$err='';$local=$t['type']=='local';$cardId=null;$cc=cc_get($u['id'],$t['operator']);
if($_POST){chk();$seat=$local?null:(int)$_POST['seat'];$m=$_POST['method']=='card'?'card':'bkash';
 try{$pdo->beginTransaction();q('SELECT id FROM trips WHERE id=? FOR UPDATE',[$id]);
  if($local){if(q('SELECT COUNT(*) FROM bookings WHERE trip_id=? AND active=1',[$id])->fetchColumn()>=$t['capacity'])throw new Exception('This bus is full.');}elseif($seat<1||$seat>$t['capacity'])throw new Exception('Choose a seat first.');
  if(!$local&&q('SELECT id FROM bookings WHERE trip_id=? AND seat_no=? AND active=1',[$id,$seat])->fetch())throw new Exception('That seat was just taken. Pick another.');
  if($m=='card'){$c=cc_get($u['id'],$t['operator'],true);
   if(!$c)throw new Exception('You do not have a '.$t['operator'].' E-card yet. Get one in My E-cards, recharge it, then pay with it.');
   if($c['blocked'])throw new Exception('Your '.$t['operator'].' E-card is blocked.');
   if($c['balance']<$fare)throw new Exception('Your '.$t['operator'].' E-card balance is too low. Recharge the card or pay with bKash.');
   q('UPDATE company_cards SET balance=balance-? WHERE id=?',[$fare,$c['id']]);q('INSERT INTO card_tx(user_id,amount,type,card_id) VALUES(?,?,?,?)',[$u['id'],-$fare,'fare',$c['id']]);$cardId=$c['id'];$ref='CARD';}
  elseif(bk_on()){q("INSERT INTO bookings(user_id,trip_id,seat_no,fare,discount,status,pay_method,from_stop,to_stop) VALUES(?,?,?,?,?,'pending','bkash',?,?)",[$u['id'],$id,$seat,$fare,$disc,$fs,$ts]);$bid=$pdo->lastInsertId();
   $p=bk_create($fare,"RT$bid",base()."/bkash_callback.php?k=b&id=$bid");if(!$p)throw new Exception('bKash is unavailable right now. Try again or use your travel card.');
   q('UPDATE bookings SET pay_ref=? WHERE id=?',[$p['paymentID'],$bid]);$pdo->commit();header('Location: '.$p['bkashURL']);exit;}
  else{$ref=strtoupper(trim($_POST['ref']));if(!preg_match('/^[A-Z0-9]{6,12}$/',$ref))throw new Exception('Enter your bKash transaction ID (6–12 letters or digits).');}
  q('INSERT INTO bookings(user_id,trip_id,seat_no,fare,discount,pay_method,pay_ref,from_stop,to_stop,card_id) VALUES(?,?,?,?,?,?,?,?,?,?)',[$u['id'],$id,$seat,$fare,$disc,$m,$ref,$fs,$ts,$cardId]);$bid=$pdo->lastInsertId();$pdo->commit();notify($u['id'],$local?'Local bus ticket confirmed.':"Booking confirmed: seat $seat.");header("Location: ticket.php?id=$bid");exit;
 }catch(Exception $x){$pdo->rollBack();$err=$x->getMessage();}}
$taken=array_column(q('SELECT seat_no FROM bookings WHERE trip_id=? AND active=1',[$id])->fetchAll(),'seat_no');
head('Book seat');?>
<h2><?=e($sn?$sn['a'].' to '.$sn['b']:$t['from_city'].' to '.$t['to_city'])?></h2><p class="muted"><?=e($t['depart_date'])?> at <?=substr($t['depart_time'],0,5)?></p>
<?php if($err)echo '<p class="err">'.e($err).'</p>';?>
<form method="post" class="card"><?=tok()?><?php if($local)echo '<h3>Local bus</h3><p class="muted">No seat reservation. Pay the fare and board any free seat, or stand.</p>';else{?><h3>Choose your seat</h3><div class="seats"><?php for($i=1;$i<=$t['capacity'];$i++)echo '<label><input type="radio" name="seat" value="'.$i.'"'.(in_array($i,$taken)?' disabled':'').'><span>'.$i.'</span></label>';?></div>
<p id="sel" class="muted">No seat selected</p><?php }?>
<h3>Fare</h3><p>Original ৳<?=number_format($t['fare'],2)?><?=$disc>0?' · Student discount −৳'.number_format($disc,2):''?> · <strong>Total ৳<?=number_format($fare,2)?></strong></p>
<?php if($t['type']!='local')echo '<p class="muted">Student discount is available on local buses only.</p>';elseif(!is_student($u))echo '<p class="muted">Student? <a href="student.php">Verify your student ID</a> for 50% off on local buses.</p>';?>
<label>Payment method<select name="method" id="method"><option value="bkash">bKash</option><option value="card"><?=e($t['operator'])?> E-card <?=$cc?'(balance ৳'.number_format($cc['balance'],2).')':'(not issued yet)'?></option></select></label>
<?php if(!$cc)echo '<p class="muted">You do not have a '.e($t['operator']).' E-card. <a href="card.php">Get one (free)</a>, recharge it once a month and pay with it on every '.e($t['operator']).' bus.</p>';?>
<?php if(!bk_on()):?><label id="refbox">bKash transaction ID (demo mode)<input name="ref" placeholder="e.g. 8N7A6D5EE1"></label><?php else:?><p class="muted" id="refbox">For bKash you will be sent to bKash to finish the payment. Your seat is held for 10 minutes.</p><?php endif;?>
<button class="btn">Pay and confirm booking</button></form>
<?php foot();
