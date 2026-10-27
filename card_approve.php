<?php require 'config.php';$u=need('staff','admin');
if($_POST){chk();$id=(int)($_POST['id']??0);$a=$_POST['a']??'';
 $pdo->beginTransaction();
 $t=q("SELECT * FROM card_tx WHERE id=? AND type='pending' AND method IS NOT NULL FOR UPDATE",[$id])->fetch();
 if(!$t||!in_array($a,['approve','reject'])){$pdo->rollBack();flash('This request was already handled.');}
 elseif($a=='approve'){
  if(!empty($t['card_id']))q('UPDATE company_cards SET balance=balance+?,last_recharge=NOW() WHERE id=?',[$t['amount'],$t['card_id']]);else q('UPDATE users SET card_balance=card_balance+? WHERE id=?',[$t['amount'],$t['user_id']]);
  q("UPDATE card_tx SET type='recharge',by_staff=? WHERE id=?",[$u['id'],$id]);
  q('INSERT INTO audit_logs(user_id,action) VALUES(?,?)',[$u['id'],"Approved card recharge #$id of ".$t['amount']." for user #".$t['user_id']]);
  $pdo->commit();notify($t['user_id'],'Your card recharge of ৳'.$t['amount'].' was approved and added to your balance.');flash('Approved and credited.');
 }else{
  q("UPDATE card_tx SET type='rejected',by_staff=? WHERE id=?",[$u['id'],$id]);
  q('INSERT INTO audit_logs(user_id,action) VALUES(?,?)',[$u['id'],"Rejected card recharge #$id for user #".$t['user_id']]);
  $pdo->commit();notify($t['user_id'],'Your card recharge request of ৳'.$t['amount'].' was rejected because the payment could not be verified.');flash('Rejected.');
 }
 header('Location: card_approve.php');exit;}
$rows=q("SELECT t.*,u.name,u.email,u.phone,cc.company,cc.card_no FROM card_tx t JOIN users u ON u.id=t.user_id LEFT JOIN company_cards cc ON cc.id=t.card_id WHERE t.type='pending' AND t.method IS NOT NULL ORDER BY t.id")->fetchAll();
$M=['bkash'=>'bKash','nagad'=>'Nagad','rocket'=>'Rocket','bank'=>'Bank transfer'];
head('Card approvals');?>
<h2>Card recharge approvals</h2><p class="muted">Check the transaction ID in your <?=e(setting('pay_number','01XXXXXXXXX'))?> account or bank statement, then approve. Approving adds the amount to the passenger's card.</p>
<?php if(!$rows)echo '<p class="card">No requests waiting.</p>';else{?><div class="scroll"><table><tr><th>Date</th><th>Passenger</th><th>Method</th><th>Transaction ID</th><th>Amount</th><th></th></tr>
<?php foreach($rows as $r){?><tr><td><?=e($r['created_at'])?></td><td><?=e($r['name'])?><br><small class="muted"><?=e($r['email'])?><?=$r['phone']?' · '.e($r['phone']):''?><?=$r['company']?'<br><strong>'.e($r['company']).' E-card</strong> '.e($r['card_no']):''?></small></td><td><?=e($M[$r['method']]??$r['method'])?></td><td><strong><?=e($r['pay_ref'])?></strong></td><td>৳<?=number_format($r['amount'],2)?></td>
<td><form method="post" style="display:inline"><?=tok()?><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="a" value="approve"><button class="btn sm">Approve</button></form>
<form method="post" style="display:inline" data-confirm="Reject this request?"><?=tok()?><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="a" value="reject"><button class="btn ghost sm">Reject</button></form></td></tr><?php }?></table></div><?php }
foot();
