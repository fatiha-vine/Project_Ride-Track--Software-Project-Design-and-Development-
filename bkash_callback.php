<?php require 'config.php';$u=need();$k=$_GET['k']??'';$id=(int)($_GET['id']??0);$pid=$_GET['paymentID']??'';$st=$_GET['status']??'';
if($k=='b'){$b=q("SELECT * FROM bookings WHERE id=? AND user_id=? AND status='pending' AND pay_ref=?",[$id,$u['id'],$pid])->fetch();$amt=$b['fare']??0;}
else{$k='t';$b=q("SELECT * FROM card_tx WHERE id=? AND user_id=? AND type='pending' AND pay_ref=?",[$id,$u['id'],$pid])->fetch();$amt=$b['amount']??0;}
$ok=false;$r=null;
if($b&&$st=='success'){$r=bk_exec($pid);$ok=$r&&($r['statusCode']??'')=='0000'&&($r['transactionStatus']??'')=='Completed'&&abs((float)($r['amount']??0)-(float)$amt)<0.01;}
if($b){
 if($ok&&$k=='b'){q("UPDATE bookings SET status='confirmed',pay_ref=? WHERE id=?",[$r['trxID'],$id]);notify($u['id'],'bKash payment received. Booking confirmed.');}
 elseif($ok){q("UPDATE card_tx SET type='recharge',pay_ref=? WHERE id=?",[$r['trxID'],$id]);if(!empty($b['card_id']))q('UPDATE company_cards SET balance=balance+?,last_recharge=NOW() WHERE id=?',[$amt,$b['card_id']]);else q('UPDATE users SET card_balance=card_balance+? WHERE id=?',[$amt,$u['id']]);notify($u['id'],"E-card recharged with ৳$amt via bKash.");}
 elseif($k=='b')q("UPDATE bookings SET status='cancelled',active=NULL WHERE id=?",[$id]);
 else q('DELETE FROM card_tx WHERE id=?',[$id]);}
flash($ok?'bKash payment successful.':'bKash payment was not completed. You were not charged by Ride Track.');
header('Location: '.($k=='b'?($ok?"ticket.php?id=$id":'search.php'):'card.php'));
