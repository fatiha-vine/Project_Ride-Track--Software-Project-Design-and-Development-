<?php require 'config.php';require 'ticketcode.php';$u=need();
$b=q('SELECT k.*,t.depart_date,t.depart_time,r.from_city,r.to_city,(SELECT name FROM stops WHERE id=k.from_stop) fs,(SELECT name FROM stops WHERE id=k.to_stop) ts FROM bookings k JOIN trips t ON t.id=k.trip_id JOIN routes r ON r.id=t.route_id WHERE k.id=? AND (k.user_id=? OR ? IN ("admin","staff"))',[(int)$_GET['id'],$u['id'],$u['role']])->fetch();
if(!$b)exit('Ticket not found.');head('E-ticket');?>
<div class="card ticket"><h2>E-ticket RT-<?=str_pad($b['id'],6,'0',STR_PAD_LEFT)?></h2>
<p><strong><?=e($b['fs']?$b['fs'].' to '.$b['ts']:$b['from_city'].' to '.$b['to_city'])?></strong><br><?=e($b['depart_date'])?> at <?=substr($b['depart_time'],0,5)?></p>
<p><?=$b['seat_no']?'Seat <strong>'.$b['seat_no'].'</strong>':'<strong>Local bus ticket</strong>, no seat'?> · Fare ৳<?=number_format($b['fare'],2)?> · <span class="pill <?=e($b['status'])?>"><?=e($b['status'])?></span></p>
<?php if($b['status']=='confirmed'){$tc='RT-'.str_pad($b['id'],6,'0',STR_PAD_LEFT).'-'.tcode($b['id']);$vu=base().'/verify_ticket.php?id='.$b['id'].'&c='.tcode($b['id']);?><div id="qr" style="margin:14px 0"></div><p class="muted">Ticket code: <strong><?=e($tc)?></strong><br>Show this QR code to the driver or counter staff when you board.</p><script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script><script>new QRCode(document.getElementById("qr"),{text:<?=json_encode($vu)?>,width:170,height:170});</script><?php }?>
<button class="btn" onclick="print()">Print ticket</button> <a class="btn ghost" href="my_bookings.php">My bookings</a></div>
<?php foot();
