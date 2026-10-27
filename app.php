<?php require 'config.php';require_once 'appshell.inc.php';$u=need();
if(portal_of($u['role'])!='passenger'){header('Location: '.portal_home($u['role']));exit;}
$nc=(int)q('SELECT COUNT(*) FROM emergency_contacts WHERE user_id=?',[$u['id']])->fetchColumn();if(!$nc&&empty($_SESSION['ec_skip'])){header('Location: emergency_contacts.php?first=1');exit;}$tr=active_trip($u['id']);
app_head('Home');$first=e(explode(' ',trim($u['name']))[0]);?>
<h2>Hi, <?=$first?></h2><p class="muted" style="margin:0">আজ কোথায় যাবেন?</p>
<a class="where" href="search.php"><?=ic('search',26)?><span>Where to?<small>Find a bus · বাস খুঁজুন</small></span></a>
<?php if($tr){?><div class="stub"><div class="lbl">Your bus today</div><div class="tm"><?=e(substr($tr['depart_time'],0,5))?></div><div class="sm"><?=e($tr['operator'])?> · <?=e($tr['reg'])?></div>
<div class="acts"><a href="track.php"><?=ic('pin',20)?>Track</a><a href="my_bookings.php"><?=ic('ticket',20)?>Ticket</a></div></div><?php }?>
<?php if(!$nc){?><div class="abanner"><?=ic('shield',22)?><span><b>Add someone we can alert.</b> Your family gets your location when you press SOS. <a href="emergency_contacts.php">Add contacts</a></span></div><?php }?>
<div class="qa">
<a href="track.php"><?=ic('pin',30)?><span>Live tracking<small>বাস কোথায়</small></span></a>
<a href="areas.php"><?=ic('map',30)?><span>Area map<small>এলাকা ও ভাড়া</small></span></a>
<a href="nearby.php"><?=ic('stop',30)?><span>Stops near me<small>কাছের স্টপ</small></span></a>
<a href="my_bookings.php"><?=ic('ticket',30)?><span>My tickets<small>আমার টিকিট</small></span></a>
<a href="card.php"><?=ic('card',30)?><span>Travel card<small>ট্রাভেল কার্ড</small></span></a>
<a href="reports.php"><?=ic('cone',30)?><span>Road reports<small>রাস্তার খবর</small></span></a>
<a href="groups.php"><?=ic('users',30)?><span>Groups<small>নিয়মিত যাত্রী</small></span></a>
<a href="student.php"><?=ic('shield',30)?><span>Student fare<small>স্টুডেন্ট ভাড়া</small></span></a>
</div>
<?php app_foot('home');
