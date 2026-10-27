<?php require 'config.php';require_once 'appshell.inc.php';$u=need();
if($_POST){chk();
 if($_POST['a']=='skip'){$_SESSION['ec_skip']=1;header('Location: app.php');exit;}
 if($_POST['a']=='add'){$d=preg_replace('/[^\d+]/','',$_POST['phone']);$n=mb_substr(trim($_POST['name']),0,60);
  if(q('SELECT COUNT(*) FROM emergency_contacts WHERE user_id=?',[$u['id']])->fetchColumn()>=5)flash('You can save up to 5 contacts.');
  elseif($n===''||strlen(preg_replace('/\D/','',$d))<8||strlen($d)>16)flash('Enter a name and a valid phone number.');
  else{q('INSERT INTO emergency_contacts(user_id,name,phone,relation) VALUES(?,?,?,?)',[$u['id'],$n,$d,mb_substr(trim($_POST['relation']),0,30)]);flash('Contact saved.');if(!empty($_POST['first'])){header('Location: app.php');exit;}}}
 if($_POST['a']=='del'){q('DELETE FROM emergency_contacts WHERE id=? AND user_id=?',[(int)$_POST['id'],$u['id']]);flash('Contact removed.');}
 header('Location: emergency_contacts.php');exit;}
$rows=q('SELECT * FROM emergency_contacts WHERE user_id=? ORDER BY id',[$u['id']])->fetchAll();
app_head('Emergency contacts');$first=!empty($_GET['first']);?>
<?php if($first){?><div class="abanner"><?=ic('shield',22)?><span><b>One quick step for your safety</b><br>Add the people we should contact if you press SOS. You can change this any time.</span></div><?php }?>
<h2>Emergency contacts</h2><p class="muted">জরুরি যোগাযোগ. When you press SOS, these people get your location by SMS. You can also call or message them from the SOS screen.</p>
<?php foreach($rows as $c){?><div class="grp"><b><?=e($c['name'])?></b> <span class="muted">· <?=e($c['relation'])?></span><div><?=e($c['phone'])?></div>
<form method="post" style="margin-top:8px"><?=tok()?><input type="hidden" name="a" value="del"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="abtn ghost" style="padding:10px">Remove</button></form></div><?php }
if(!$rows)echo '<div class="abanner">No contacts yet. Add at least one family member or friend.</div>';
if(count($rows)<5){?><form method="post" class="grp"><?=tok()?><input type="hidden" name="a" value="add"><?php if($first)echo '<input type="hidden" name="first" value="1">';?><h3>Add a contact</h3>
<label>Name<input name="name" required maxlength="60"></label><label>Phone number<input name="phone" type="tel" inputmode="tel" placeholder="01XXXXXXXXX" required></label>
<label>Relation<select name="relation"><option>Family</option><option>Friend</option><option>Spouse</option><option>Parent</option><option>Other</option></select></label><button class="abtn">Save contact</button></form><?php }?>
<div class="grp"><b>Always available</b><div class="muted">999 national emergency · your bus company when you have a ticket today · police stations near you on the map</div></div></div>
<?php if($first){?><form method="post"><?=tok()?><input type="hidden" name="a" value="skip"><button class="abtn ghost">Skip for now</button></form><?php }?>
<?php app_foot('me');
