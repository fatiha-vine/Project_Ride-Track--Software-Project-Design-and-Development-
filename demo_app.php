<?php require 'config.php';$u=me();
$trip=q("SELECT t.id FROM trips t JOIN routes r ON r.id=t.route_id WHERE r.type='local' AND t.status='scheduled' AND CONCAT(t.depart_date,' ',t.depart_time)>NOW() ORDER BY t.depart_date,t.depart_time LIMIT 1")->fetchColumn();
if(!$trip)$trip=q("SELECT t.id FROM trips t WHERE t.status='scheduled' AND CONCAT(t.depart_date,' ',t.depart_time)>NOW() ORDER BY t.depart_date,t.depart_time LIMIT 1")->fetchColumn();
$bus=q("SELECT id FROM buses WHERE status='approved' ORDER BY id LIMIT 1")->fetchColumn();
$S=['P'=>[
 ['Open the app','This is the real RideTrack home page, shown at phone size.','You start at the home screen of the app.','index.php',''],
 ['Find a bus','Type From, To and the date, then tap Search. Here: Uttara to Motijheel.','You see every bus on that route with its time, fare and free seats.','search.php?mode=local&from=Uttara&to=Motijheel',''],
 ['Pick a seat and book','Tap a free seat, choose how to pay (bKash, card or Travel card) and confirm.','Your seat is held and the booking is confirmed. Student half fare is applied if your ID card is valid.',$trip?'book.php?trip='.$trip:'search.php','passenger'],
 ['Student half fare','Type your student ID number and upload the front and back of your ID card.','The session on the card is read. If it is still valid today you get half fare at once.','student.php','passenger'],
 ['Your e-ticket','Open My bookings, tap your ticket to see the QR code. You can cancel any time.','The driver checks the QR code. If you cancel, your money comes back after the cancel fee.','my_bookings.php','passenger'],
 ['Track the bus live','Choose your bus on the live map.','You see where the bus is now, when it was last updated and how crowded it is.',$bus?'track.php?bus='.$bus:'track.php',''],
 ['Dhaka areas and fares','Tap your pickup area, then your drop area. Orange dots are real bus stops.','You see the distance, fare, student fare and the buses that serve both areas.','areas.php',''],
 ['Long distance map','Tap a route line, or pick two cities.','You see the distance, fare, bus times and a Book a seat button.','longmap.php',''],
 ['Road reports','See accidents and bad roads. Add a report with a photo and vote on others.','Reports that many people say are not true are removed.','reports.php',''],
 ['Groups','Join the group for your regular route.','You chat with passengers who travel the same way.','groups.php','passenger'],
 ['SOS','In danger, press SOS.','An alert with your location goes to the admin and the police.','sos.php','passenger']],
'D'=>[
 ['Driver login','Enter your driver email and password.','You enter the Driver portal.','driver_login.php',''],
 ['Next trip and schedule','Read your next trip at the top. Use Today, Tomorrow or Next 7 days for the schedule.','You know which trip to drive and when. Start, Delayed and Finish are here too.','driver.php','driver'],
 ['Verify a ticket','Type the ticket code a passenger shows you.','You see if the ticket is real, and the seat and name.','verify_ticket.php','driver'],
 ['Live GPS','In the Driver portal press Start sharing location.','Passengers see your bus move on their map.',$bus?'track.php?bus='.$bus:'track.php','driver'],
 ['Help and complaints','Pick what the problem is about (bus, passenger, road, schedule) and send it.','The admin sees it with your bus number.','complaints.php','driver']]];
head('Demo app');?>
<style>
.da{display:grid;grid-template-columns:minmax(330px,400px) 1fr;gap:34px;align-items:start;margin-top:10px}
.phone{width:330px;height:690px;margin:0 auto;background:#0b2f3a;border-radius:46px;padding:11px;box-shadow:0 30px 60px -22px var(--sh),0 0 0 2px #164e63 inset;position:relative}
.phone:before{content:"";position:absolute;top:13px;left:50%;transform:translateX(-50%);width:92px;height:22px;background:#0b2f3a;border-radius:0 0 16px 16px;z-index:3}
.scr{width:100%;height:100%;background:#ecfeff;border-radius:36px;overflow:hidden;display:flex;flex-direction:column}
.sb{display:flex;justify-content:space-between;padding:9px 24px 5px;font:600 12px Inter,sans-serif;background:#ecfeff;color:#164e63;flex:none}
.scr iframe{flex:1;width:100%;border:0;background:#ecfeff}
.hb{display:flex;justify-content:center;gap:8px;margin-top:12px}
.side h3{margin:0 0 6px}.sbox{border-radius:14px;padding:13px 15px;margin:10px 0}.sdo{background:#cffafe}.sres{background:#fff;border:1px solid var(--ln)}
.dots{display:flex;gap:6px;flex-wrap:wrap;margin:10px 0}.dots button{width:30px;height:30px;border-radius:50%;border:1px solid var(--ln);background:#fff;color:var(--dk);font-weight:600;cursor:pointer}.dots button.on{background:var(--pri);color:#fff;border-color:var(--pri)}
.stat{padding:9px 13px;border-radius:12px;background:#fff;border:1px solid var(--ln);margin:0 0 10px;font-size:.92rem}
@media(max-width:860px){.da{grid-template-columns:1fr}}
</style>
<h2>Demo app</h2><p class="muted">This is the real RideTrack, shown inside a phone. Use the steps on the right, or tap anything inside the phone yourself.</p>
<div class="da"><div><div class="phone"><div class="scr"><div class="sb"><span id="clk">9:41</span><span>5G ▮▮▮</span></div><iframe id="fr" title="RideTrack on a phone" src="about:blank"></iframe></div></div>
<div class="hb"><button class="btn ghost sm" id="rl" type="button">↻ Reload</button><a class="btn ghost sm" id="op" href="#" target="_blank">Open full size ↗</a></div></div>
<div class="side"><div class="stat"><?php if($u)echo 'Logged in as <strong>'.e($u['name']).'</strong> ('.e($u['role']).').';else echo 'You are not logged in.';?> <a class="btn sm ghost" href="demo_login.php?as=passenger">Log in as demo passenger</a> <a class="btn sm ghost" href="demo_login.php?as=driver">Log in as demo driver</a></div>
<div style="display:flex;gap:8px;flex-wrap:wrap" id="who"></div><div class="dots" id="dots"></div>
<h3 id="st"></h3><div class="sbox sdo"><strong>What you do</strong><br><span id="sd"></span></div><div class="sbox sres"><strong>What happens</strong><br><span id="sr"></span></div><p class="muted" id="nd"></p>
<div style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn ghost" id="pv" type="button">← Back</button><button class="btn" id="nx" type="button">Next →</button></div>
<p class="muted" style="margin-top:14px">Want the same steps as a list? <a href="demo.php">Step-by-step guide</a></p></div></div>
<script>
const S=<?=json_encode($S,JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)?>,ROLE=<?=json_encode($u['role']??'')?>;
const $=id=>document.getElementById(id);let who=(new URLSearchParams(location.search).get('w')==='D')?'D':'P',i=0;
const nm=r=>({passenger:'a passenger',driver:'a driver'}[r]);
function go(){const s=S[who][i];$('fr').src=s[3];$('op').href=s[3];
 $('st').textContent=(i+1)+'. '+s[0];$('sd').textContent=s[1];$('sr').textContent=s[2];
 const need=s[4];$('nd').textContent=(need&&ROLE!==need)?'This step needs you to be logged in as '+nm(need)+'. Press the demo login button above, then come back to this step.':'';
 $('who').innerHTML=[['P','Passenger'],['D','Driver']].map(x=>'<button type="button" class="btn sm'+(x[0]===who?'':' ghost')+'" data-w="'+x[0]+'">'+x[1]+'</button>').join('');
 $('who').querySelectorAll('button').forEach(b=>b.onclick=()=>{who=b.dataset.w;i=0;go()});
 $('dots').innerHTML=S[who].map((x,n)=>'<button type="button" class="'+(n===i?'on':'')+'" data-n="'+n+'">'+(n+1)+'</button>').join('');
 $('dots').querySelectorAll('button').forEach(b=>b.onclick=()=>{i=+b.dataset.n;go()});
 $('pv').disabled=i===0;$('nx').textContent=i===S[who].length-1?'Start again':'Next →'}
$('nx').onclick=()=>{i=(i+1)%S[who].length;go()};$('pv').onclick=()=>{if(i>0){i--;go()}};
$('rl').onclick=()=>{$('fr').contentWindow.location.reload()};
$('clk').textContent=new Date().toLocaleTimeString([], {hour:'numeric',minute:'2-digit'});
go();
</script>
<?php foot();
