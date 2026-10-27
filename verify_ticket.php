<?php require 'config.php';require 'ticketcode.php';$u=need('staff','admin','police','driver','operator');
$id=(int)($_GET['id']??$_POST['id']??0);$c=trim($_GET['c']??$_POST['c']??'');
if(!empty($_GET['code'])&&preg_match('/^RT-?0*(\d+)-([a-f0-9]{12})$/i',trim($_GET['code']),$m)){$id=(int)$m[1];$c=strtolower($m[2]);}
if($_POST&&($_POST['a']??'')=='board'){chk();
 if($id&&hash_equals(tcode($id),$c)){$n=q("UPDATE bookings SET boarded_at=NOW() WHERE id=? AND status='confirmed' AND boarded_at IS NULL",[$id])->rowCount();flash($n?'Marked as boarded.':'Could not mark this ticket.');}
 header('Location: verify_ticket.php?id='.$id.'&c='.urlencode($c));exit;}
$b=null;
if($id)$b=q('SELECT k.*,COALESCE(us.name,k.manual_name) nm,t.depart_date,t.depart_time,t.status tstatus,r.from_city,r.to_city,bu.reg_number,bu.driver_user_id,bu.operator_id,(SELECT name FROM stops WHERE id=k.from_stop) fs,(SELECT name FROM stops WHERE id=k.to_stop) ts FROM bookings k LEFT JOIN users us ON us.id=k.user_id JOIN trips t ON t.id=k.trip_id JOIN routes r ON r.id=t.route_id JOIN buses bu ON bu.id=t.bus_id WHERE k.id=?',[$id])->fetch();
$sig=$b&&hash_equals(tcode($id),$c);
$other=$b&&(($u['role']=='driver'&&$b['driver_user_id']!=$u['id'])||($u['role']=='operator'&&$b['operator_id']!=$u['id']));
$msg='';$cls='cancelled';
if(!$b||!$sig){$msg='Invalid ticket code.';}
elseif($other){$msg='This ticket is for another operator\'s bus.';}
elseif($b['status']!='confirmed'){$msg='Ticket is '.$b['status'].'. Do not accept.';}
elseif($b['tstatus']=='cancelled'){$msg='This trip was cancelled.';}
elseif($b['boarded_at']){$msg='Already used. Boarded at '.$b['boarded_at'].'.';$cls='pending';}
elseif($b['depart_date']!=date('Y-m-d')){$msg='Ticket is real but for '.$b['depart_date'].', not today.';$cls='pending';}
else{$msg='Valid ticket.';$cls='verified';}
head('Verify ticket');?>
<h2>Verify ticket</h2><p class="muted">Scan the passenger's QR code with your phone camera, or type the ticket code printed under it.</p>
<form class="row card" method="get"><label>Ticket code<input name="code" placeholder="RT-000123-abcdef123456"></label><button class="btn">Check</button></form>
<?php if($id){?><div class="card"><span class="pill <?=$cls?>"><?=e($msg)?></span>
<?php if($b&&$sig&&!$other){?><h3><?=e($b['fs']?$b['fs'].' to '.$b['ts']:$b['from_city'].' to '.$b['to_city'])?></h3>
<p>Passenger: <strong><?=e($b['nm']?:'Passenger')?></strong><br><?=e($b['depart_date'])?> at <?=e(substr($b['depart_time'],0,5))?> · bus <?=e($b['reg_number'])?><br><?=$b['seat_no']?'Seat <strong>'.(int)$b['seat_no'].'</strong>':'Local bus ticket, no seat'?> · Fare ৳<?=number_format($b['fare'],2)?></p>
<?php if($cls=='verified'){?><form method="post"><?=tok()?><input type="hidden" name="a" value="board"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="c" value="<?=e($c)?>"><button class="btn">Mark as boarded</button></form><?php }}?></div><?php }
foot();
