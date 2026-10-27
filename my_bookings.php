<?php require 'config.php';$u=need();
if($_POST){chk();$b=q("SELECT * FROM bookings WHERE id=? AND user_id=? AND status='confirmed'",[(int)$_POST['id'],$u['id']])->fetch();
 if($b){$ref=round($b['fare']*(100-setting('cancel_fee_pct',10))/100,2);$pdo->beginTransaction();q("UPDATE bookings SET status='cancelled',active=NULL,refund=? WHERE id=?",[$ref,$b['id']]);
  if($b['pay_method']=='card')card_refund($b,$ref);
  $pdo->commit();notify($u['id'],"Booking cancelled. Refund ৳$ref.");flash("Booking cancelled. Refund of ৳$ref ".($b['pay_method']=='card'?'was added to your travel card.':'is pending processing.'));}
 header('Location: my_bookings.php');exit;}
$rows=q('SELECT k.*,t.depart_date,t.depart_time,r.from_city,r.to_city,(SELECT name FROM stops WHERE id=k.from_stop) fs,(SELECT name FROM stops WHERE id=k.to_stop) ts FROM bookings k JOIN trips t ON t.id=k.trip_id JOIN routes r ON r.id=t.route_id WHERE k.user_id=? ORDER BY k.id DESC',[$u['id']])->fetchAll();
head('My bookings');?><h2>My bookings</h2><p class="muted">Cancelling keeps a <?=setting('cancel_fee_pct',10)?>% cancellation fee. The rest is refunded.</p>
<?php if(!$rows)echo '<p class="card">No bookings yet. <a href="search.php">Find a bus</a>.</p>';
foreach($rows as $b){?><div class="card trip"><div><h3><?=e($b['fs']?$b['fs'].' to '.$b['ts']:$b['from_city'].' to '.$b['to_city'])?></h3><div class="muted"><?=e($b['depart_date'])?> <?=substr($b['depart_time'],0,5)?> · <?=$b['seat_no']?'Seat '.$b['seat_no']:'No seat (local)'?> · ৳<?=number_format($b['fare'],2)?></div>
<span class="pill <?=e($b['status'])?>"><?=e($b['status'])?></span><?=$b['status']=='cancelled'?' <span class="pill">Refund ৳'.number_format($b['refund'],2).'</span>':''?></div>
<div><a class="btn ghost" href="ticket.php?id=<?=$b['id']?>">E-ticket</a>
<?php if($b['status']=='confirmed'){?><form method="post" style="display:inline" data-confirm="Cancel this booking? A <?=setting('cancel_fee_pct',10)?>% fee (৳<?=round($b['fare']*setting('cancel_fee_pct',10)/100,2)?>) applies. Refund: ৳<?=round($b['fare']*(100-setting('cancel_fee_pct',10))/100,2)?>."><?=tok()?><input type="hidden" name="id" value="<?=$b['id']?>"><button class="btn">Cancel</button></form><?php }?></div></div>
<?php }foot();
