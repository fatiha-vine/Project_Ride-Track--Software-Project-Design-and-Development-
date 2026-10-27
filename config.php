<?php
session_start();
require_once __DIR__.'/services.php';

// Language Toggle Handle (ENG / BN)
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'bn'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
$lang = $_SESSION['lang'] ?? 'en'; // Default English

// Translations Array for Navigation & UI
$t_arr = [
    'en' => [
        'demo' => '📱 Demo app',
        'buses' => 'Buses',
        'maps' => 'MAPS',
        'dhaka_areas' => 'Dhaka areas & fares',
        'long_map' => 'Long distance map',
        'live_track' => 'Live tracking',
        'more' => 'MORE',
        'road_reports' => 'Road reports',
        'groups' => 'Groups',
        'step_guide' => 'Step-by-step guide',
        'login' => 'Log in',
        'signup' => 'Sign up',
        'logout' => 'Log out',
        'profile' => 'Profile',
        'sos' => 'SOS',
        'notifications' => 'Alerts',
        'my_bookings' => 'My bookings',
        'student_portal' => 'Student portal',
        'plan_trip' => 'Plan trip',
        'travel_card' => 'Travel card',
        'help' => 'Help',
        'lang_btn' => 'বাংলা' // Switch to Bangla text
    ],
    'bn' => [
        'demo' => '📱 ডেমো অ্যাপ',
        'buses' => 'বাস',
        'maps' => 'মানচিত্র',
        'dhaka_areas' => 'ঢাকা এলাকা ও ভাড়া',
        'long_map' => 'দূরপাল্লার মানচিত্র',
        'live_track' => 'লাইভ ট্র্যাকিং',
        'more' => 'আরও',
        'road_reports' => 'সড়ক রিপোর্ট',
        'groups' => 'গ্রুপ',
        'step_guide' => 'ধাপে ধাপে গাইড',
        'login' => 'লগ ইন',
        'signup' => 'সাইন আপ',
        'logout' => 'লগ আউট',
        'profile' => 'প্রোফাইল',
        'sos' => 'জরুরি',
        'notifications' => 'সতর্কতা',
        'my_bookings' => 'আমার বুকিং',
        'student_portal' => 'শিক্ষার্থী পোর্টাল',
        'plan_trip' => 'ভ্রমণ পরিকল্পনা',
        'travel_card' => 'ট্রাভেল কার্ড',
        'help' => 'সহায়তা',
        'lang_btn' => 'English' // Switch to English text
    ]
];
$__ = $t_arr[$lang];

$pdo=new PDO('mysql:host=localhost;dbname=ridetrack;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES);}
function q($sql,$p=[]){global $pdo;$s=$pdo->prepare($sql);$s->execute($p);return $s;}
function me(){if(empty($_SESSION['uid']))return null;return q('SELECT * FROM users WHERE id=?',[$_SESSION['uid']])->fetch();}
function portals(){return ['passenger'=>['name'=>'Passenger portal','title'=>'Passenger login','h'=>'Passenger Portal','sub'=>'Book tickets, track your bus live and manage your trips.','roles'=>['passenger'],'url'=>'login.php'],
 'driver'=>['name'=>'Driver portal','title'=>'Driver login','h'=>'Driver portal','sub'=>'See your bus, when each trip runs, your passengers, and share live GPS.','roles'=>['driver'],'url'=>'driver_login.php'],
 'admin'=>['name'=>'Admin / Operator portal','title'=>'Admin / Operator login','h'=>'Admin / Operator portal','sub'=>'Manage buses, routes, ticketing, refunds, reports and police control. Only authorised staff can use this portal.','roles'=>['admin','operator','staff','police'],'url'=>'admin_login.php']];}
function portal_of($r){return $r=='passenger'?'passenger':($r=='driver'?'driver':'admin');}
function portal_home($r){return ['admin'=>'admin.php','operator'=>'operator.php','staff'=>'staff.php','police'=>'police.php','driver'=>'driver.php'][$r]??'index.php';}
function need(...$roles){$u=me();if(!$u){$to='login.php';if($roles){if(!array_diff($roles,['driver']))$to='driver_login.php';elseif(!array_diff($roles,['admin','operator','staff','police']))$to='admin_login.php';}header('Location: '.$to);exit;}if($roles&&!in_array($u['role'],$roles)){http_response_code(403);exit('Access denied');}return $u;}
function tok(){$_SESSION['t']??=bin2hex(random_bytes(16));return '<input type="hidden" name="t" value="'.$_SESSION['t'].'">';}
function chk(){if($_SERVER['REQUEST_METHOD']==='POST'&&!hash_equals($_SESSION['t']??'',$_POST['t']??''))exit('Invalid form token');}
function flash($m){$_SESSION['f']=$m;}
function setting($k,$d){static $c;if($c===null)$c=array_column(q('SELECT k,v FROM settings')->fetchAll(),'v','k');return $c[$k]??$d;}
function notify($uid,$m){if($uid)q('INSERT INTO notifications(user_id,msg) VALUES(?,?)',[$uid,$m]);}
function ensure_trips($d){$x=date_create_from_format('Y-m-d',$d);if(!$x||$x->format('Y-m-d')!==$d||$d<date('Y-m-d')||$d>date('Y-m-d',strtotime('+365 days')))return;
 q("INSERT INTO trips(route_id,bus_id,depart_date,depart_time) SELECT s.route_id,s.bus_id,?,s.depart_time FROM schedules s JOIN buses b ON b.id=s.bus_id AND b.status='approved' WHERE NOT EXISTS(SELECT 1 FROM trips t WHERE t.route_id=s.route_id AND t.bus_id=s.bus_id AND t.depart_date=? AND t.depart_time=s.depart_time)",[$d,$d]);}
function seg_fare($rid,$a,$b){$o=q('SELECT fare FROM stop_fares WHERE from_stop=? AND to_stop=?',[$a,$b])->fetchColumn();if($o!==false)return max(1,round((float)$o));$r=q('SELECT fare FROM routes WHERE id=?',[$rid])->fetch();$tot=(int)q('SELECT MAX(offset_min) FROM route_stops WHERE route_id=?',[$rid])->fetchColumn();$x=[];
 foreach(q('SELECT stop_id,seq,offset_min,fare_at FROM route_stops WHERE route_id=? AND stop_id IN(?,?)',[$rid,$a,$b])->fetchAll() as $s)$x[$s['stop_id']]=$s;
 if(!$r||count($x)<2||$x[$a]['seq']>=$x[$b]['seq'])return null;
 $f=fn($s)=>$s['fare_at']!==null?(float)$s['fare_at']:($tot?$r['fare']*$s['offset_min']/$tot:0);return max(1,round($f($x[$b])-$f($x[$a])));}
function is_student($u){return $u['student_status']=='verified'&&!empty($u['student_expiry'])&&$u['student_expiry']>=date('Y-m-d');}
function upload_private($f){$n=upload($f,['jpg','jpeg','png','pdf']);if($n){@mkdir(__DIR__.'/uploads/private');file_put_contents(__DIR__.'/uploads/private/.htaccess',"Require all denied\n");rename(__DIR__."/uploads/$n",__DIR__."/uploads/private/$n");}return $n;}
function upload($f,$ok){if(empty($f['name'])||$f['error']!==0||$f['size']>2000000)return null;$x=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));if(!in_array($x,$ok))return null;if(!is_dir(__DIR__.'/uploads'))mkdir(__DIR__.'/uploads');$n=bin2hex(random_bytes(8)).".$x";move_uploaded_file($f['tmp_name'],__DIR__."/uploads/$n");return $n;}
function cancel_trip($id){foreach(q("SELECT * FROM bookings WHERE trip_id=? AND status='confirmed'",[$id])->fetchAll() as $b){q("UPDATE bookings SET status='cancelled',active=NULL,refund=fare WHERE id=?",[$b['id']]);
 if($b['user_id']){if($b['pay_method']=='card'){q('UPDATE users SET card_balance=card_balance+? WHERE id=?',[$b['fare'],$b['user_id']]);q('INSERT INTO card_tx(user_id,amount,type) VALUES(?,?,?)',[$b['user_id'],$b['fare'],'refund']);}notify($b['user_id'],'Your trip was cancelled by the operator. Full refund issued.');}}}
function head($t){
    global $__, $lang;
    $u=me();$r=$u['role']??'';$po=$u?portal_of($r):($GLOBALS['portal']??'passenger');$th=$po;if($po=='passenger'&&$u&&($u['student_status']??'')=='verified')$th='student';$tag=['driver'=>'Driver','admin'=>'Admin','student'=>'Student','passenger'=>'Passenger'][$th]??'';$tc=['passenger'=>'#0e7490','student'=>'#6d4fc2','driver'=>'#047857','admin'=>'#1e2a4a'][$th]??'#0e7490';
    $home=$u?portal_home($r):(['driver'=>'driver_login.php','admin'=>'admin_login.php'][$po]??'index.php');$nn=$u?(int)q('SELECT COUNT(*) FROM notifications WHERE user_id=? AND seen=0',[$u['id']])->fetchColumn():0;?>
<!doctype html><html lang="<?=$lang?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($t)?> · Ride Track</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600&family=Hind+Siliguri:wght@500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="style.css?v=<?=filemtime(__DIR__.'/style.css')?>"><link rel="manifest" href="manifest.webmanifest"><meta name="theme-color" content="<?=$tc?>"><link rel="icon" href="icon-192.png"></head><body class="p-<?=$th?>">
<header class="nav"><a class="logo" href="<?=$home?>">Ride<span>Track</span><?php if($tag)echo ' <small class="ptag">'.$tag.'</small>';?></a>
<a class="demopill" href="?lang=<?=$lang=='en'?'bn':'en'?>">🌐 <?=$__['lang_btn']?></a>
<a class="demopill" href="demo_app.php" title="See how the app looks on a phone"><?=$__['demo']?></a><button class="sbtog" type="button" aria-label="Collapse menu" title="Collapse / expand menu" onclick="document.body.classList.toggle('mini');try{localStorage.sb=document.body.classList.contains('mini')?1:0}catch(e){}">⇤</button><script>try{if(localStorage.sb==='1')document.body.classList.add('mini')}</script><button class="burger" aria-label="Menu" onclick="document.body.classList.toggle('open')">☰</button><nav>
<?php if($po=='driver'){if($u){?>
<a href="driver.php">My schedule</a><a href="verify_ticket.php">Verify ticket</a><a href="track.php">Live tracking</a><a href="reports.php">Road reports</a><a href="notifications.php">Alerts<?=$nn?" ($nn)":''?></a><a href="complaints.php">Help</a><a href="demo.php">Guide</a><a href="sos.php" class="sos">SOS</a><a href="profile.php"><?=e($u['name'])?></a><a href="logout.php"><?=$__['logout']?></a>
<?php }else echo '<a href="driver_login.php">Driver login</a>';}
elseif($po=='admin'){if($u){?>
<?php if($r=='admin')echo '<a href="admin.php">Admin</a>';if($r=='staff'||$r=='admin')echo '<a href="staff.php">Ticketing</a>';if($r=='operator'||$r=='admin')echo '<a href="operator.php">Operator</a>';if($r=='police'||$r=='admin')echo '<a href="police.php">Police</a>';?>
<a href="verify_ticket.php">Verify ticket</a><a href="track.php">Live tracking</a>
<div class="dd"><a href="#" tabindex="-1">More</a><div class="ddm"><?php if($r=='admin')echo '<a href="admin_routes.php">Routes</a><a href="interrupt_auto.php">Auto refund</a>';if($r=='staff'||$r=='admin')echo '<a href="staff_card.php">Counter card</a><a href="card_approve.php">Card approvals</a>';if($r=='operator'||$r=='admin')echo '<a href="assign_driver.php">Assign driver</a>';if($r=='police'||$r=='admin')echo '<a href="police_control.php">Police control</a>';?><a href="reports.php">Road reports</a><a href="demo.php">Guide</a></div></div>
<a href="notifications.php">Alerts<?=$nn?" ($nn)":''?></a><a href="profile.php"><?=e($u['name'])?></a><a href="logout.php"><?=$__['logout']?></a>
<?php }else echo '<a href="admin_login.php">Admin / Operator login</a>';}
else{?>
<a href="search.php"><?=$__['buses']?></a>
<div class="dd"><a href="#" tabindex="-1"><?=$__['maps']?></a><div class="ddm"><a href="areas.php"><?=$__['dhaka_areas']?></a><a href="longmap.php"><?=$__['long_map']?></a></div></div>
<a href="track.php"><?=$__['live_track']?></a>
<?php if($u){?><a href="my_bookings.php"><?=$__['my_bookings']?></a><a href="student.php"><?=$__['student_portal']?></a>
<div class="dd"><a href="#" tabindex="-1"><?=$__['more']?></a><div class="ddm"><a href="plan.php"><?=$__['plan_trip']?></a><a href="reports.php"><?=$__['road_reports']?></a><a href="groups.php"><?=$__['groups']?></a><a href="card.php"><?=$__['travel_card']?></a><a href="complaints.php"><?=$__['help']?></a><a href="demo.php"><?=$__['step_guide']?></a></div></div>
<a href="sos.php" class="sos"><?=$__['sos']?></a><a href="notifications.php"><?=$__['notifications']?><?=$nn?" ($nn)":''?></a><a href="profile.php"><?=e($u['name'])?></a><a href="logout.php"><?=$__['logout']?></a>
<?php }else{?>
<div class="dd"><a href="#" tabindex="-1"><?=$__['more']?></a><div class="ddm"><a href="reports.php"><?=$__['road_reports']?></a><a href="groups.php"><?=$__['groups']?></a><a href="demo.php"><?=$__['step_guide']?></a></div></div>
<a href="login.php"><?=$__['login']?></a><a class="btn sm" href="register.php"><?=$__['signup']?></a><?php }}?></nav></header>
<main class="wrap"><?php if(!empty($_SESSION['f'])){echo '<div class="flash">'.e($_SESSION['f']).'</div>';unset($_SESSION['f']);}}
function foot(){
    global $__;
    ?></main><footer>Ride Track — safer, easier bus travel across Bangladesh.<?php if(!me())echo '<br><a href="driver_login.php">Driver portal</a> · <a href="admin_login.php">Admin / Operator portal</a> · <a href="login.php">Passenger portal</a>';?></footer><script>if("serviceWorker" in navigator)navigator.serviceWorker.register("sw.js");</script><script src="app.js?v=<?=filemtime(__DIR__.'/app.js')?>"></script></body></html><?php }