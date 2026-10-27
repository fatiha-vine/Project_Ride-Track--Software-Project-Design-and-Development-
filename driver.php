<?php require 'config.php';$u=need('driver');
if($_POST){chk();$a=$_POST['a']??'';$v=$_POST['v']??'';
 if($a=='status'&&in_array($v,['running','delayed','completed'])){$id=(int)($_POST['id']??0);
  $n=q("UPDATE trips t JOIN buses b ON b.id=t.bus_id SET t.status=? WHERE t.id=? AND b.driver_user_id=? AND t.status NOT IN('cancelled','completed','interrupted')",[$v,$id,$u['id']])->rowCount();
  if($n){q('INSERT INTO audit_logs(user_id,action) VALUES(?,?)',[$u['id'],"Driver set trip $id to $v"]);
   if($v=='delayed')foreach(q("SELECT DISTINCT user_id FROM bookings WHERE trip_id=? AND status='confirmed' AND user_id IS NOT NULL",[$id])->fetchAll() as $k)notify($k['user_id'],'Your bus is running late. Please check live tracking.');
   flash('Trip marked as '.$v.'.');}
  else flash('This trip cannot be changed.');}
 header('Location: driver.php');exit;}
$buses=q('SELECT * FROM buses WHERE driver_user_id=? ORDER BY id',[$u['id']])->fetchAll();
$ok=[];foreach($buses as $b)if($b['status']=='approved')$ok[]=$b;
for($i=0;$i<7;$i++)ensure_trips(date('Y-m-d',strtotime("+$i days")));
$trips=q("SELECT t.*,r.from_city,r.to_city,b.reg_number,b.capacity,(SELECT COUNT(*) FROM bookings k WHERE k.trip_id=t.id AND k.status='confirmed') booked FROM trips t JOIN routes r ON r.id=t.route_id JOIN buses b ON b.id=t.bus_id WHERE b.driver_user_id=? AND t.depart_date>=CURDATE() AND t.status<>'cancelled' ORDER BY t.depart_date,t.depart_time LIMIT 80",[$u['id']])->fetchAll();
$duty=[];foreach($trips as $t)$duty[$t['depart_date']][]=$t;
$next=null;$nowdt=date('Y-m-d H:i');foreach($trips as $t){if(in_array($t['status'],['scheduled','running','delayed'])&&($t['status']=='running'||$t['depart_date'].' '.substr($t['depart_time'],0,5)>=$nowdt)){$next=$t;break;}}
$tid=(int)($_GET['trip']??0);$pt=null;$pax=[];
foreach($trips as $t)if($t['id']==$tid)$pt=$t;
if($pt)$pax=q("SELECT k.seat_no,COALESCE(u.name,k.manual_name) nm FROM bookings k LEFT JOIN users u ON u.id=k.user_id WHERE k.trip_id=? AND k.status='confirmed' ORDER BY k.seat_no",[$tid])->fetchAll();
$nt=q('SELECT msg,created_at FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 5',[$u['id']])->fetchAll();
head('Driver portal');?>
<style>.pill.info{background:#a5f3fc;border-color:#22d3ee;color:#164e63}</style>
<h2>Driver portal</h2><p class="muted">Welcome, <?=e($u['name'])?>. Manage your bus, trips, passengers and live GPS from here.</p>
<?php if(!$buses){echo '<p class="card">No bus is linked to your account yet. Ask your operator to assign you, or sign up again with your bus details.</p>';foot();exit;}?>
<h3>My bus</h3>
<?php foreach($buses as $b){?><div class="card trip"><span><strong><?=e($b['reg_number'])?></strong> · <?=e(($b['kind']??'intercity'))?> · <?=(int)$b['capacity']?> seats<br><small class="muted">Operator: <?=e($b['operator'])?> · Licence: <?=e($b['license_no'])?> · Driver ID: <?=e($b['driver_id_no'])?></small></span><span class="pill <?=$b['status']=='approved'?'verified':'info'?>"><?=e($b['status'])?></span></div><?php }?>
<h3>Duty schedule · next 7 days</h3>
<?php if($next){$dl=$next['depart_date']==date('Y-m-d')?'Today':($next['depart_date']==date('Y-m-d',strtotime('+1 day'))?'Tomorrow':$next['depart_date']);?><div class="card"><span class="muted">Next duty</span><div class="big"><?=e($dl)?> at <?=e(substr($next['depart_time'],0,5))?></div><p><?=e($next['from_city'])?> to <?=e($next['to_city'])?> · bus <?=e($next['reg_number'])?></p></div><?php }else echo '<p class="card">No duty scheduled in the next 7 days.</p>';?>
<div class="scroll"><table><tr><th>Day</th><th>Duty hours</th><th>Trips</th><th>Routes</th></tr>
<?php for($i=0;$i<7;$i++){$dt=date('Y-m-d',strtotime("+$i days"));$ls=$duty[$dt]??[];$lab=$i==0?'Today':($i==1?'Tomorrow':date('D, d M',strtotime($dt)));
 if($ls){$rt=[];foreach($ls as $t)$rt[$t['from_city'].' to '.$t['to_city']]=1;echo '<tr><td><strong>'.e($lab).'</strong></td><td><span class="pill verified">On duty</span> '.e(substr($ls[0]['depart_time'],0,5)).' – '.e(substr(end($ls)['depart_time'],0,5)).'</td><td>'.count($ls).'</td><td>'.e(implode(', ',array_keys($rt))).'</td></tr>';}
 else echo '<tr><td><strong>'.e($lab).'</strong></td><td colspan="3" class="muted">Off duty</td></tr>';}?></table></div>
<p class="muted">Duty hours run from the first to the last departure of your bus that day.</p>
<h3>My trips</h3>
<?php if(!$trips)echo '<p class="card">No upcoming trips for your bus.</p>';else{?><div class="scroll"><table><tr><th>Date</th><th>Time</th><th>Route</th><th>Bus</th><th>Booked</th><th>Status</th><th></th></tr>
<?php foreach($trips as $t){$fin=in_array($t['status'],['completed','interrupted']);?><tr><td><?=e($t['depart_date'])?></td><td><?=e(substr($t['depart_time'],0,5))?></td><td><?=e($t['from_city'])?> to <?=e($t['to_city'])?></td><td><?=e($t['reg_number'])?></td><td><?=(int)$t['booked']?>/<?=(int)$t['capacity']?></td><td><span class="pill <?=$t['status']=='running'?'verified':($t['status']=='delayed'?'info':'')?>"><?=e($t['status'])?></span></td>
<td><a class="btn ghost sm" href="driver.php?trip=<?=$t['id']?>#pax">Passengers</a>
<?php if(!$fin){foreach(['running'=>'Start','delayed'=>'Delayed','completed'=>'Finish'] as $k=>$lb){if($t['status']==$k)continue;?><form method="post" style="display:inline"><?=tok()?><input type="hidden" name="a" value="status"><input type="hidden" name="id" value="<?=$t['id']?>"><input type="hidden" name="v" value="<?=$k?>"><button class="btn sm<?=$k=='running'?'':' ghost'?>"><?=$lb?></button></form> <?php }}?></td></tr><?php }?></table></div><?php }?>
<?php if($pt){?><div class="card" id="pax"><h3>Passengers · <?=e($pt['from_city'])?> to <?=e($pt['to_city'])?> · <?=e($pt['depart_date'])?> <?=e(substr($pt['depart_time'],0,5))?></h3>
<?php if(!$pax)echo '<p class="muted">No confirmed bookings yet.</p>';else{?><div class="scroll"><table><tr><th>Seat</th><th>Passenger</th></tr><?php foreach($pax as $p)echo '<tr><td>'.($p['seat_no']?e($p['seat_no']):'standing').'</td><td>'.e($p['nm']?:'Passenger').'</td></tr>';?></table></div><?php }?></div><?php }?>
<?php if($ok){?><h3>Live GPS</h3><div class="card"><p class="muted">Keep this page open. Your phone's GPS is sent as the bus location and passengers see it on the map.</p>
<label>Bus<select id="bus"><?php foreach($ok as $b)echo '<option value="'.$b['id'].'|'.e($b['gps_key']).'">'.e($b['reg_number']).'</option>';?></select></label>
<label>Crowding<select id="crowd"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High (crowded)</option></select></label>
<button class="btn" id="go" type="button">Start sharing location</button> <button class="btn ghost" id="stop" type="button" disabled>Stop</button>
<p id="st" class="muted">Not sharing.</p></div><?php }else echo '<p class="card">Live GPS will be available after your operator\'s bus is approved.</p>';?>
<h3>Quick actions</h3><div class="card"><a class="btn" href="sos.php">SOS emergency</a> <a class="btn ghost" href="reports.php">Report a road problem</a> <a class="btn ghost" href="complaints.php">Help</a></div>
<h3>Recent alerts</h3><?php if(!$nt)echo '<p class="card">No alerts yet.</p>';foreach($nt as $n)echo '<div class="card"><span class="muted">'.e($n['created_at']).'</span><br>'.e($n['msg']).'</div>';?>
<script>
let wid=null,last=0,wl=null;const st=document.getElementById('st');
const goB=document.getElementById('go');
if(goB){goB.onclick=async()=>{if(!navigator.geolocation){st.textContent='This browser has no GPS.';return}
 try{if(navigator.wakeLock)wl=await navigator.wakeLock.request('screen')}catch(e){}
 const [id,key]=document.getElementById('bus').value.split('|');
 wid=navigator.geolocation.watchPosition(p=>{const n=Date.now();if(n-last<5000)return;last=n;const f=new FormData();f.append('bus',id);f.append('key',key);f.append('lat',p.coords.latitude);f.append('lng',p.coords.longitude);f.append('crowding',document.getElementById('crowd').value);
  fetch('gps_update.php',{method:'POST',body:f}).then(r=>r.json()).then(j=>{st.textContent=j.ok?'Sharing · last sent '+new Date().toLocaleTimeString():'Server rejected the update.'}).catch(()=>st.textContent='No internet, retrying...')},
  ()=>{st.textContent='Please allow location permission.'},{enableHighAccuracy:true});
 goB.disabled=true;document.getElementById('stop').disabled=false;st.textContent='Starting...'};
document.getElementById('stop').onclick=()=>{if(wid!==null)navigator.geolocation.clearWatch(wid);wid=null;if(wl)wl.release();goB.disabled=false;document.getElementById('stop').disabled=true;st.textContent='Stopped.'};}
</script><?php foot();
