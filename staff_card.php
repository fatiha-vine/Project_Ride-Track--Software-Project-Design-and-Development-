<?php require 'config.php';$u=need('staff','admin');$f=null;$qs=trim($_GET['q']??'');
if($_POST){chk();$id=(int)($_POST['uid']??0);$a=$_POST['a']??'';
 $p=q("SELECT * FROM users WHERE id=? AND role='passenger'",[$id])->fetch();
 if(!$p)flash('Passenger not found.');
 elseif($a=='issue'){$co=trim($_POST['company']??'');
  if($co===''||!q("SELECT 1 FROM buses WHERE operator=? AND status='approved' LIMIT 1",[$co])->fetch())flash('Choose a bus company.');
  elseif(cc_get($id,$co))flash('This passenger already has a '.$co.' E-card.');
  else{$cid=cc_issue($id,$co);$no=q('SELECT card_no FROM company_cards WHERE id=?',[$cid])->fetchColumn();q('INSERT INTO audit_logs(user_id,action) VALUES(?,?)',[$u['id'],"E-card $no ($co) issued to user #$id"]);flash("E-card issued: $no ($co)");}}
 elseif($a=='recharge'){$c=q('SELECT * FROM company_cards WHERE id=? AND user_id=?',[(int)($_POST['card']??0),$id])->fetch();$amt=(float)($_POST['amount']??0);
  if(!$c)flash('Card not found.');
  elseif($c['blocked'])flash('Card blocked.');
  elseif($amt<50||$amt>10000)flash('Enter an amount from ৳50 to ৳10,000.');
  else{cc_credit($c['id'],$amt,'recharge',$u['id']);notify($id,"Your {$c['company']} E-card was recharged with ৳$amt cash at the counter.");q('INSERT INTO audit_logs(user_id,action) VALUES(?,?)',[$u['id'],"Cash recharge ৳$amt card {$c['card_no']} user #$id"]);flash("Recharged ৳$amt (cash) on the {$c['company']} card.");}}
 header('Location: staff_card.php?q='.urlencode($_POST['q']??''));exit;}
if($qs!=='')$f=q("SELECT * FROM users WHERE role='passenger' AND (email=? OR phone=? OR id=(SELECT user_id FROM company_cards WHERE card_no=? LIMIT 1))",[$qs,$qs,$qs])->fetch();
$cs=$f?q('SELECT * FROM company_cards WHERE user_id=? ORDER BY company',[$f['id']])->fetchAll():[];
$have=array_column($cs,'company');
$cos=array_values(array_filter(array_column(q("SELECT DISTINCT operator FROM buses WHERE status='approved' AND operator<>'' ORDER BY operator")->fetchAll(),'operator'),fn($x)=>!in_array($x,$have)));
head('Counter E-card');?><h2>Counter · Company E-cards</h2><p class="muted">Find the passenger by email, phone or E-card number. Take cash at the counter to issue a company E-card or to recharge one.</p>
<form class="row card"><label>Email / phone / E-card no<input name="q" value="<?=e($qs)?>" required></label><button class="btn">Find</button></form>
<?php if($qs!==''&&!$f)echo '<p class="card">Passenger not found.</p>';
if($f){?><div class="card"><h3><?=e($f['name'])?></h3><p class="muted"><?=e($f['email'])?><?=$f['phone']?' · '.e($f['phone']):''?></p>
<?php if(!$cs)echo '<p>No company E-card yet.</p>';else{?><div class="scroll"><table><tr><th>Company</th><th>Card no</th><th>Balance</th><th>Last recharge</th><th>Cash recharge (৳50–10,000)</th></tr>
<?php foreach($cs as $c){?><tr><td><?=e($c['company'])?><?=$c['blocked']?' <span class="pill cancelled">blocked</span>':''?></td><td><?=e($c['card_no'])?></td><td>৳<?=number_format($c['balance'],2)?></td><td><?=$c['last_recharge']?e(date('j M Y',strtotime($c['last_recharge']))):'never'?></td>
<td><form method="post" class="row" style="margin:0"><?=tok()?><input type="hidden" name="uid" value="<?=$f['id']?>"><input type="hidden" name="q" value="<?=e($qs)?>"><input type="hidden" name="a" value="recharge"><input type="hidden" name="card" value="<?=(int)$c['id']?>"><input type="number" name="amount" min="50" max="10000" step="1" required aria-label="Amount"><button class="btn sm">Recharge</button></form></td></tr><?php }?></table></div><?php }?>
<?php if($cos){?><form method="post" class="row"><?=tok()?><input type="hidden" name="uid" value="<?=$f['id']?>"><input type="hidden" name="q" value="<?=e($qs)?>"><input type="hidden" name="a" value="issue"><label>Issue an E-card for<select name="company" required><option value="">Choose a company</option><?php foreach($cos as $co)echo '<option>'.e($co).'</option>';?></select></label><button class="btn">Issue E-card</button></form><?php }?></div><?php }
foot();
