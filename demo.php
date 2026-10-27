<?php require 'config.php';$u=me();head('Demo');?>
<style>
.dtabs{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 16px}.dwrap{display:grid;grid-template-columns:260px 1fr;gap:16px}
.dlist{display:flex;flex-direction:column;gap:6px;max-height:520px;overflow:auto}.dlist button{text-align:left;border:1px solid var(--ln);background:#fff;border-radius:12px;padding:9px 12px;cursor:pointer;color:var(--dk);font:500 .92rem Inter,sans-serif}
.dlist button.on{background:var(--pri);color:#fff;border-color:var(--pri)}.dlist b{opacity:.7;margin-right:6px}
.dcard{min-height:340px}.dnum{display:inline-grid;place-items:center;width:44px;height:44px;border-radius:50%;background:var(--pri);color:#fff;font-weight:700;font-size:1.2rem}
.dbox{border-radius:12px;padding:12px 14px;margin:10px 0}.ddo{background:var(--c1)}.dres{background:#fff;border:1px solid var(--ln)}.dbar{height:6px;background:var(--c1);border-radius:9px;margin:0 0 14px}.dbar i{display:block;height:100%;background:var(--pri);border-radius:9px;transition:width .25s}
@media(max-width:760px){.dwrap{grid-template-columns:1fr}.dlist{flex-direction:row;overflow-x:auto;max-height:none}.dlist button{white-space:nowrap}}
</style>
<h2>Demo: how to use RideTrack</h2><p class="muted">Pick who you are, then go step by step. Each step says what to do and what happens. Press <strong>Open this page</strong> to try it for real.</p>
<div class="dtabs" id="tabs"></div>
<div class="dwrap"><div class="dlist" id="list"></div>
<div class="card dcard"><div class="dbar"><i id="bar"></i></div><div id="step"></div>
<div style="display:flex;gap:8px;margin-top:14px"><button class="btn ghost" id="prev" type="button">← Back</button><button class="btn" id="next" type="button">Next →</button></div></div></div>
<script>
const D={
 passenger:{name:'Passenger',steps:[
  ['Create your account','Open Sign up. Type your name, email, phone and a password.','You are logged in and land on the passenger home page.','register.php','Sign up'],
  ['Find a bus','Open Buses. Choose Local bus or Intercity. Type From, To and the date.','You see every bus with its departure time and fare.','search.php','Find buses'],
  ['See the map and fare','Dhaka: open Areas & fares and tap pickup, then drop. Other cities: open Long distance and tap a route line.','You see the distance, fare, student fare and the buses that run there.','areas.php','Open the map'],
  ['Choose your seat','Click a bus, then tap a free seat on the seat map.','Your seat is held for you. Seats that are taken cannot be picked.','search.php','Find a seat'],
  ['Pay','Pay with bKash or card (demo payment), or use your Travel card balance.','Your booking is confirmed at once.','card.php','Travel card'],
  ['Get your e-ticket','Open My bookings. Your ticket has a QR code. Show it to the driver or conductor.','The driver scans or checks the ticket. Manual (paper) tickets are also given at the counter.','my_bookings.php','My bookings'],
  ['Track your bus live','Open Live tracking and choose your bus.','You see where the bus is on the map, when it was last updated and how crowded it is.','track.php','Live tracking'],
  ['Student half fare','Open Student portal. Type your student ID number and upload the front and back of your ID card.','The system reads the session years on the card. If the card is still valid today you pay half fare at once. An old card is not accepted.','student.php','Student portal'],
  ['Cancel any time','Open My bookings and press Cancel on a ticket.','Your ticket is cancelled and the money is returned to you after the cancel fee.','my_bookings.php','My bookings'],
  ['If the bus cannot come','Nothing to do. If a bus breaks down or a trip is cancelled, the system tells you.','Your money is returned. If the trip stopped on the way, you are refunded for the part you did not travel.','notifications.php','Alerts'],
  ['Report a road problem','Open Road reports. Add an accident or bad road with a photo.','Other passengers vote. Reports that people say are not true are removed.','reports.php','Road reports'],
  ['Groups and SOS','Join a group for your regular route to chat with other passengers. In danger, press SOS.','Group members see your messages. SOS sends an alert with your location.','groups.php','Groups']]},
 driver:{name:'Driver',steps:[
  ['Log in as driver','Open Driver login. Use the email and password of your driver account. Your bus operator must assign your bus first.','You enter the Driver portal.','driver_login.php','Driver login'],
  ['See your next trip','The top of the Driver portal shows your next trip: route, time and bus.','You see how long is left before departure.','driver.php','Driver portal'],
  ['See your schedule','My schedule lists your trips by day. Use Today, Tomorrow or Next 7 days.','You know which trip to drive and when. Past trips are listed below.','driver.php','My schedule'],
  ['Start the trip','Press Start on the trip when you leave.','The trip shows as running and passengers can follow it.','driver.php','Open portal'],
  ['Share live GPS','Press Start sharing location and allow location in the browser. Keep the page open.','Passengers see your bus moving on the map.','driver.php','Open portal'],
  ['Check passengers and tickets','Press Passengers on a trip, or open Verify ticket and type the ticket code.','You see seat numbers and names, and know if a ticket is real.','verify_ticket.php','Verify ticket'],
  ['Late? Press Delayed','Press Delayed if you are late.','Every passenger with a seat gets a message.','driver.php','Open portal'],
  ['Finish and report','Press Finish at the end. Use Help to report a bus, road or passenger problem.','The trip moves to Past trips. The admin sees your complaint.','complaints.php','Help']]},
 admin:{name:'Admin / Operator',steps:[
  ['Log in','Open Admin / Operator login with your staff account.','You enter the portal for your role (admin, operator, ticketing or police).','admin_login.php','Admin login'],
  ['Approve buses and operators','Open Admin. Check new buses and their driver and licence papers, then approve.','Only approved buses can run trips and be tracked.','admin.php','Admin'],
  ['Set routes and stops','Open Routes. Add a route and its stops on the map.','Areas are matched to each stop and fares are worked out from distance.','admin_routes.php','Routes'],
  ['Assign a driver','Open Assign driver. Type the driver email and choose a bus.','That person can now use Driver login.','assign_driver.php','Assign driver'],
  ['Sell tickets at the counter','Open Ticketing. Sell a manual ticket for a passenger.','The seat is taken and a ticket is printed.','staff.php','Ticketing'],
  ['Handle stopped trips','Open Auto refund when a bus breaks down or cannot come.','Passengers are refunded for the part they did not travel.','interrupt_auto.php','Auto refund'],
  ['Police control','The police portal can check bus papers and suspend a bus.','A suspended bus has its trips cancelled and passengers are refunded.','police_control.php','Police control'],
  ['Read complaints','Open Admin and read open complaints. Press Mark resolved when done.','The complaint is closed for the passenger or driver who sent it.','admin.php','Admin']]}};
let who='passenger',i=0;
const $=id=>document.getElementById(id),esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function tabs(){$('tabs').innerHTML=Object.keys(D).map(k=>'<button type="button" class="btn sm'+(k===who?'':' ghost')+'" data-k="'+k+'">'+D[k].name+'</button>').join('');
 $('tabs').querySelectorAll('button').forEach(b=>b.onclick=()=>{who=b.dataset.k;i=0;show()})}
function show(){const S=D[who].steps,s=S[i];tabs();
 $('list').innerHTML=S.map((x,n)=>'<button type="button" class="'+(n===i?'on':'')+'" data-n="'+n+'"><b>'+(n+1)+'</b>'+esc(x[0])+'</button>').join('');
 $('list').querySelectorAll('button').forEach(b=>b.onclick=()=>{i=+b.dataset.n;show()});
 $('bar').style.width=((i+1)/S.length*100)+'%';
 $('step').innerHTML='<div style="display:flex;gap:12px;align-items:center"><span class="dnum">'+(i+1)+'</span><h3 style="margin:0">'+esc(s[0])+'</h3></div>'
  +'<div class="dbox ddo"><strong>What you do</strong><br>'+esc(s[1])+'</div><div class="dbox dres"><strong>What happens</strong><br>'+esc(s[2])+'</div>'
  +'<a class="btn" href="'+s[3]+'">Open this page: '+esc(s[4])+'</a>';
 $('prev').disabled=i===0;$('next').textContent=i===S.length-1?'Done ✓':'Next →'}
$('prev').onclick=()=>{if(i>0){i--;show()}};
$('next').onclick=()=>{if(i<D[who].steps.length-1){i++;show()}else{i=0;show()}};
show();
</script>
<?php foot();
