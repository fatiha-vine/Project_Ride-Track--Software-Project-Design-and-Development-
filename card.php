<?php require 'config.php';$u=need();
$M=['bkash'=>'bKash','nagad'=>'Nagad','rocket'=>'Rocket','bank'=>'Bank transfer'];
if($_POST){chk();$a=$_POST['a']??'';
 if($a=='issue'){
  $co=trim($_POST['company']??'');
  if($co===''||!q("SELECT 1 FROM buses WHERE operator=? AND status='approved' LIMIT 1",[$co])->fetch())flash('Choose a bus company from the list.');
  elseif(cc_get($u['id'],$co))flash('You already have a '.$co.' E-card.');
  else{$id=cc_issue($u['id'],$co);flash('Your '.$co.' E-card is ready. Recharge it to start paying with it.');header('Location: card.php?c='.$id);exit;}
  header('Location: card.php');exit;
 }
 $c=q('SELECT * FROM company_cards WHERE id=? AND user_id=?',[(int)($_POST['card']??0),$u['id']])->fetch();
 if(!$c){flash('Card not found.');header('Location: card.php');exit;}
 if($a=='block'){q('UPDATE company_cards SET blocked=1-blocked WHERE id=?',[$c['id']]);}
 elseif($a=='recharge'){
  if($c['blocked']){flash('This card is blocked. Unblock it first.');}
  else{$amt=(float)($_POST['amount']??0);$m=$_POST['method']??'bkash';
   if($amt<50||$amt>10000){flash('Enter an amount from ৳50 to ৳10,000.');}
   elseif(bk_on()&&$m=='bkash'){
    q("INSERT INTO card_tx(user_id,amount,type,card_id) VALUES(?,?,'pending',?)",[$u['id'],$amt,$c['id']]);$tid=$pdo->lastInsertId();
    $p=bk_create($amt,"TX$tid",base()."/bkash_callback.php?k=t&id=$tid");
    if($p){q('UPDATE card_tx SET pay_ref=? WHERE id=?',[$p['paymentID'],$tid]);header('Location: '.$p['bkashURL']);exit;}
    q('DELETE FROM card_tx WHERE id=?',[$tid]);flash('bKash is unavailable right now.');
   }else{
    $ref=strtoupper(trim($_POST['ref']??''));
    if(!isset($M[$m])||!preg_match('/^[A-Z0-9]{6,20}$/',$ref)){flash('Choose a method and enter a valid transaction ID (6-20 letters or numbers).');}
    elseif(q('SELECT 1 FROM card_tx WHERE method=? AND pay_ref=?',[$m,$ref])->fetch()){flash('This transaction ID was already submitted.');}
    else{q("INSERT INTO card_tx(user_id,amount,type,pay_ref,method,card_id) VALUES(?,?,'pending',?,?,?)",[$u['id'],$amt,$ref,$m,$c['id']]);flash("Request sent. Your {$c['company']} E-card will be recharged by ৳$amt after staff verify the payment.");}
   }
  }
 }
 header('Location: card.php?c='.$c['id']);exit;
}
$cards=q('SELECT * FROM company_cards WHERE user_id=? ORDER BY blocked,company',[$u['id']])->fetchAll();
$sel=null;$want=(int)($_GET['c']??0);
foreach($cards as $c0)if($c0['id']==$want)$sel=$c0;
if(!$sel&&$cards)$sel=$cards[0];
$mine=array_column($cards,'company');
$cos=array_values(array_filter(array_column(q("SELECT DISTINCT operator FROM buses WHERE status='approved' AND operator<>'' ORDER BY operator")->fetchAll(),'operator'),fn($x)=>!in_array($x,$mine)));
$tx=$sel?q("SELECT * FROM card_tx WHERE card_id=? AND NOT (type='pending' AND method IS NULL) ORDER BY id DESC LIMIT 15",[$sel['id']])->fetchAll():[];
function cc_due($c){$base=$c['last_recharge']?:$c['created_at'];return strtotime($base.' +30 days');}
function ecard($c,$holder,$on){
 $col=cc_color($c['company']);$no=$c['card_no'];$no=substr($no,0,2).' '.substr($no,2,4).' '.substr($no,6,4);
 $late=time()>cc_due($c);
 $state=$c['blocked']?'Blocked':($late?'Recharge due':'Active · recharge by '.date('j M',cc_due($c)));
 return '<a class="ecard'.($on?' on':'').($c['blocked']?' off':'').'" href="card.php?c='.(int)$c['id'].'" style="--e1:'.$col[0].';--e2:'.$col[1].'" aria-label="'.e($c['company']).' E-card, balance '.number_format($c['balance'],2).' taka">'
 .'<span class="ec-co">'.e($c['company']).'</span><span class="ec-chip"></span><span class="ec-no">'.e($no).'</span>'
 .'<span class="ec-row"><span class="ec-holder">'.e($holder).'</span><span class="ec-bal">৳'.number_format($c['balance'],2).'</span></span>'
 .'<span class="ec-state">'.e($state).'</span></a>';
}
head('My E-cards');?>
<h2>My E-cards</h2>
<p class="muted">One E-card for each bus company you ride regularly. Recharge it once a month, then pay for tickets and book seats with it on that company's buses, on the website or in the app.</p>
<?php if(!$cards)echo '<p class="card">You have no E-card yet. Pick the company you travel with most below.</p>';else{?>
<div class="ecards"><?php foreach($cards as $c0)echo ecard($c0,$u['name'],$sel&&$c0['id']==$sel['id']);?></div>
<?php }?>
<?php if($cos){?><div class="card"><h3>Get a company E-card</h3>
<p class="muted">Free and instant. It works only on buses of the company you choose.</p>
<form method="post" class="row"><?=tok()?><input type="hidden" name="a" value="issue">
<label>Bus company<select name="company" required><option value="">Choose a company</option><?php foreach($cos as $co)echo '<option>'.e($co).'</option>';?></select></label>
<button class="btn">Get E-card</button></form></div><?php }?>
<?php if($sel){$late=time()>cc_due($sel);?>
<div class="card"><h3><?=e($sel['company'])?> E-card</h3>
<p>Card no <strong><?=e($sel['card_no'])?></strong> · Balance <strong>৳<?=number_format($sel['balance'],2)?></strong> · <span class="pill <?=$sel['blocked']?'cancelled':($late?'cancelled':'verified')?>"><?=$sel['blocked']?'Blocked':($late?'Recharge due':'Active')?></span></p>
<p class="muted"><?=$sel['last_recharge']?'Last recharged '.e(date('j M Y',strtotime($sel['last_recharge']))).'. ':'Not recharged yet. '?>Next monthly recharge by <strong><?=e(date('j M Y',cc_due($sel)))?></strong>.</p>
<h3>Recharge</h3>
<p class="muted">Pay to <strong><?=e(setting('pay_number','01XXXXXXXXX'))?></strong> by bKash, Nagad, Rocket or bank, then enter the transaction ID. <?=bk_on()?'bKash recharges are instant. Other methods need staff approval.':'Staff will verify your payment and add the balance.'?></p>
<form method="post" class="row"><?=tok()?><input type="hidden" name="a" value="recharge"><input type="hidden" name="card" value="<?=(int)$sel['id']?>">
<label>Method<select name="method"><?php foreach($M as $k=>$v)echo '<option value="'.$k.'">'.$v.'</option>';?></select></label>
<label>Amount (৳)<input type="number" name="amount" min="50" max="10000" required></label>
<label>Transaction ID<?=bk_on()?' (not needed for bKash)':''?><input name="ref"<?=bk_on()?'':' required'?>></label>
<button class="btn">Recharge</button></form>
<form method="post" data-confirm="Change the block status of this card?"><?=tok()?><input type="hidden" name="a" value="block"><input type="hidden" name="card" value="<?=(int)$sel['id']?>"><button class="btn ghost"><?=$sel['blocked']?'Unblock card':'Block lost card'?></button></form></div>
<h3>Recent activity on this card</h3>
<div class="scroll"><table><tr><th>Date</th><th>Type</th><th>Method</th><th>Amount</th></tr><?php foreach($tx as $t){$ty=$t['type']=='pending'?'waiting for approval':$t['type'];echo '<tr><td>'.e($t['created_at']).'</td><td>'.e($ty).'</td><td>'.e($t['method']?($M[$t['method']]??$t['method']):'').'</td><td>৳'.number_format($t['amount'],2).'</td></tr>';}?></table></div>
<?php }?>
<?php if((float)$u['card_balance']>0)echo '<p class="muted">Old Ride Track card balance of ৳'.number_format($u['card_balance'],2).' from before company E-cards is kept safe. Ask the ticket counter to move it to a company card.</p>';?>
<?php foot();
