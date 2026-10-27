<?php require 'config.php';require_once 'appshell.inc.php';$u=need();tok();
if($_POST){chk();$loc=mb_substr(trim($_POST['location']??''),0,100);$note=mb_substr(trim($_POST['note']??''),0,250);
 q('INSERT INTO sos(user_id,note,location) VALUES(?,?,?)',[$u['id'],$note,$loc]);
 foreach(q("SELECT id FROM users WHERE role IN('admin','staff','police')")->fetchAll() as $a)notify($a['id'],'SOS from '.$u['name'].' at '.mb_substr($loc,0,60).' · '.mb_substr($note,0,100));
 $mm='Ride Track SOS: '.$u['name'].' needs help at '.mb_substr($loc,0,60).'. '.mb_substr($note,0,100);
 foreach(q('SELECT phone FROM emergency_contacts WHERE user_id=?',[$u['id']])->fetchAll() as $c)send_sms($c['phone'],$mm);
 $ec=$u['emergency_contact']??'';if($ec&&filter_var($ec,FILTER_VALIDATE_EMAIL))send_mail($ec,'Ride Track SOS alert',$mm);
 flash('Your message was sent to Ride Track staff, the police team and your emergency contacts. If you are in danger, call 999 as well.');header('Location: sos.php');exit;}
$contacts=q('SELECT name,phone,relation FROM emergency_contacts WHERE user_id=? ORDER BY id',[$u['id']])->fetchAll();$tr=active_trip($u['id']);

head('SOS');?>
<style>
.sosx{--sos:#D8212F;max-width:1000px;margin:0 auto}
.sosx .top{max-width:560px;margin:0 auto;text-align:center}
.holdwrap{position:relative;width:260px;height:260px;margin:14px auto 6px}.holdwrap svg{position:absolute;inset:0;transform:rotate(-90deg);pointer-events:none}
.trk{fill:none;stroke:#F3C9CD;stroke-width:7}.prg{fill:none;stroke:var(--sos);stroke-width:7;stroke-linecap:round;stroke-dasharray:339.3;stroke-dashoffset:339.3}
.tick{position:absolute;left:50%;top:0;width:3px;height:18px;margin-left:-1.5px;background:#fff;transform-origin:50% 130px;z-index:2;pointer-events:none}
#sosbtn{position:absolute;inset:20px;border-radius:50%;border:0;background:radial-gradient(circle at 32% 24%,#F0505C,#B5121F 78%);color:#fff;font:700 3.2rem 'Cormorant Garamond',Georgia,serif;letter-spacing:.05em;box-shadow:0 14px 30px rgba(216,33,47,.45),inset 0 -8px 16px rgba(0,0,0,.18);touch-action:none;user-select:none;-webkit-user-select:none;-webkit-touch-callout:none;cursor:pointer;line-height:1}
#sosbtn small{display:block;font:600 .8rem Inter,sans-serif;letter-spacing:.06em;margin-top:6px;opacity:.95}#sosbtn.hold{transform:scale(.95)}
#sosbtn.sent{background:radial-gradient(circle at 32% 24%,#5B8CFF,#1D4ED8 80%);box-shadow:0 14px 30px rgba(29,78,216,.4)}#sosbtn:focus-visible{outline:4px solid #E9B44C;outline-offset:4px}
.stat{font-size:.95rem;color:var(--mut);min-height:1.5em}.okbox{background:#E3F2E8;border-radius:14px;padding:14px 16px;margin:12px 0;color:#14532D;text-align:left}
.btn.red{background:var(--sos)!important;border-color:var(--sos)!important;display:block;font-size:1.05rem;padding:14px}
.sg{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:14px;margin-top:18px}.sg .card{margin:0}
.crow{display:flex;flex-direction:column;gap:8px;padding:12px 0;border-top:1px solid var(--ln)}.crow:first-child{border-top:0;padding-top:0}
.cbtns{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}.cbtns a{padding:10px 4px;border-radius:10px;background:var(--c1);text-align:center;text-decoration:none;font-weight:600;font-size:.9rem;color:var(--dk)}.cbtns a.wa{background:#DDF3E4;color:#14532D}
.pol{padding:12px 0;border-top:1px solid var(--ln)}.pol:first-child{border-top:0;padding-top:0}
.sg h3{margin:0 0 8px}.sg .btn{display:block;text-align:center;margin-top:10px}
</style>
<div class="sosx">
<div class="card top"><h2>Emergency help</h2><p class="muted">Press and hold the red button for 3 seconds. Your location goes to Ride Track staff, the police team, your bus company and your emergency contacts.</p>
<div class="holdwrap"><svg viewBox="0 0 120 120"><circle class="trk" cx="60" cy="60" r="54"/><circle class="prg" id="ring" cx="60" cy="60" r="54"/></svg><i class="tick" style="transform:rotate(0deg)"></i><i class="tick" style="transform:rotate(120deg)"></i><i class="tick" style="transform:rotate(240deg)"></i><button id="sosbtn" type="button" aria-label="Hold for 3 seconds to send SOS">SOS<small>HOLD 3 SEC</small></button></div>
<div class="stat" id="gst">Checking your location...</div><div id="result"></div>
<a class="btn red" href="tel:999">📞 Call 999 · National emergency service</a>
<p class="muted" style="margin:10px 0 0">The alert records your time and location. It cannot guarantee a response, so call 999 when in danger.</p></div>
<div class="sg">
<div class="card"><h3>🛡 Police near you</h3><div id="police" class="muted">Find police stations near you on the map.</div><button class="btn ghost" id="pbtn" type="button">Find nearby police</button></div>
<?php if($tr){?><div class="card"><h3>🚌 Your bus company</h3><div><b><?=e($tr['operator'])?></b> · <?=e($tr['reg'])?><br><span class="muted">Driver: <?=e($tr['driver_name'])?></span></div><?php if($tr['op_phone']){?><a class="btn ghost" href="tel:<?=e($tr['op_phone'])?>">Call bus company <?=e($tr['op_phone'])?></a><?php }?><p class="muted" style="margin:8px 0 0">Your bus company is alerted when you send SOS.</p></div><?php }?>
<div class="card"><h3>👪 Family &amp; friends</h3><?php if(!$contacts)echo '<p class="muted">No emergency contacts saved yet. Add your family so they get your location when you press SOS.</p>';?><div id="clist"></div><a class="btn ghost" href="emergency_contacts.php">Add or edit contacts</a></div>
<div class="card"><h3>🎥 Video or voice call</h3><p class="muted" style="margin-top:0">Start a free call room and invite your family. They open the link in their phone browser, no app needed. Chat is inside the call too.</p>
<div class="cbtns" style="grid-template-columns:1fr 1fr"><a id="vcall" href="#" target="_blank" rel="noopener">Video call</a><a id="acall" href="#" target="_blank" rel="noopener">Voice call</a></div><div id="invites"></div></div>
<div class="card"><h3>✉ Send a message</h3><form method="post"><?=tok()?><label>Where are you?<input name="location" id="loc" placeholder="Location or landmark"></label><label>What is happening?<textarea name="note" rows="3" required></textarea></label><button class="btn">Send message to staff and contacts</button></form></div>
<div class="card"><h3>📍 Share my live location</h3><p class="muted" style="margin-top:0">Send your current map link to anyone, in any app.</p><button class="btn ghost" id="shr" type="button">Share my location</button></div>
</div>
</div>
<script>
const T=<?=json_encode($_SESSION['t'])?>,CON=<?=json_encode($contacts,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP)?>,BUS=<?=json_encode($tr?['reg'=>$tr['reg'],'operator'=>$tr['operator']]:null,JSON_UNESCAPED_UNICODE)?>,ME=<?=json_encode($u['name'],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG)?>;
const $=id=>document.getElementById(id),esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let LOC=null,LOCT=0;
const mapUrl=()=>LOC?'https://maps.google.com/?q='+LOC.lat+','+LOC.lng:'';
const msg=()=>'Ride Track SOS: '+ME+' needs help.'+(LOC?' My location: '+mapUrl():'')+(BUS?' Bus: '+BUS.reg+' ('+BUS.operator+').':'');
const wa=p=>{let d=String(p).replace(/[^\d+]/g,'').replace(/^\+/,'');if(d.startsWith('00'))d=d.slice(2);if(d.startsWith('0'))d='88'+d;return d};
function drawContacts(){$('clist').innerHTML=CON.map(c=>'<div class="crow"><div><b>'+esc(c.name)+'</b> <span class="muted">· '+esc(c.relation)+'</span></div><div class="cbtns"><a href="tel:'+esc(c.phone)+'">Call</a><a href="sms:'+esc(c.phone)+'?body='+encodeURIComponent(msg())+'">SMS</a><a class="wa" href="https://wa.me/'+wa(c.phone)+'?text='+encodeURIComponent(msg())+'" target="_blank" rel="noopener">WhatsApp</a></div></div>').join('')}
function getLoc(fast){return new Promise(res=>{if(!navigator.geolocation||!window.isSecureContext){$('gst').textContent='Location needs HTTPS or localhost.';return res(null)}
 navigator.geolocation.getCurrentPosition(p=>{LOC={lat:+p.coords.latitude.toFixed(6),lng:+p.coords.longitude.toFixed(6)};LOCT=Date.now();$('gst').textContent='Location ready ✓';if(!$('loc').value)$('loc').value='GPS '+LOC.lat+','+LOC.lng;drawContacts();drawInvites();res(LOC)},
  e=>{$('gst').textContent=e.code===1?'Location is blocked. Allow location in your browser so your family can see where you are.':'Could not get your location yet.';res(null)},{enableHighAccuracy:!fast,timeout:fast?6000:12000,maximumAge:fast?60000:0})})}
// ---- hold 3 seconds
const btn=$('sosbtn'),ring=$('ring'),C=339.3,HOLD=3000;let t0=0,raf=0,done=false;
function tick(){const p=Math.min(1,(performance.now()-t0)/HOLD);ring.style.strokeDashoffset=C*(1-p);if(p>=1){endHold(true);return}raf=requestAnimationFrame(tick)}
function startHold(e){if(done||t0)return;if(e&&e.preventDefault)e.preventDefault();t0=performance.now();btn.classList.add('hold');raf=requestAnimationFrame(tick);if(navigator.vibrate)navigator.vibrate(30)}
function endHold(ok){cancelAnimationFrame(raf);t0=0;btn.classList.remove('hold');if(ok){done=true;if(navigator.vibrate)navigator.vibrate([200,100,200]);fire()}else ring.style.strokeDashoffset=C}
btn.addEventListener('pointerdown',startHold);['pointerup','pointerleave','pointercancel'].forEach(ev=>btn.addEventListener(ev,()=>{if(!done&&t0)endHold(false)}));
btn.addEventListener('contextmenu',e=>e.preventDefault());
btn.addEventListener('keydown',e=>{if((e.key===' '||e.key==='Enter')&&!e.repeat)startHold(e)});btn.addEventListener('keyup',e=>{if((e.key===' '||e.key==='Enter')&&!done&&t0)endHold(false)});
async function fire(){btn.innerHTML='Sending...<small>PLEASE WAIT</small>';if(!LOC||Date.now()-LOCT>120000)await getLoc(true);
 const f=new FormData();f.append('t',T);if(LOC){f.append('lat',LOC.lat);f.append('lng',LOC.lng)}
 try{const j=await (await fetch('sos_send.php',{method:'POST',body:f})).json();if(!j.ok)throw 0;
  btn.classList.add('sent');btn.innerHTML='SENT ✓<small>HELP IS BEING ALERTED</small>';
  let h='<div class="okbox"><b>Your SOS was sent.</b><br>';
  h+=(j.staff+j.police)?'✓ Ride Track staff'+(j.police?' and traffic police':'')+' were alerted.<br>':'';
  if(j.bus)h+='✓ Your bus company ('+esc(j.bus.operator)+') was notified.<br>';
  h+=j.contacts?(j.sms?'✓ SMS sent to '+j.sms+' of '+j.contacts+' emergency contacts.<br>':'⚠ SMS could not be sent automatically. Use the buttons below to message your contacts.<br>'):'⚠ No emergency contacts saved. <a href="emergency_contacts.php">Add contacts</a>.<br>';
  h+=LOC?'':'⚠ Your location was not shared. Turn on location.<br>';
  h+='<b>If you are in danger, call 999 now.</b></div>';$('result').innerHTML=h;police()}
 catch(e){done=false;ring.style.strokeDashoffset=C;btn.innerHTML='SOS<small>HOLD 3 SEC</small>';$('result').innerHTML='<div class="err">Could not send. Check your internet and try again, or call 999.</div>'}}
// ---- nearby police (OpenStreetMap)
const km=(a,b,c,d)=>{const r=Math.PI/180,x=(c-a)*r,y=(d-b)*r,h=Math.sin(x/2)**2+Math.cos(a*r)*Math.cos(c*r)*Math.sin(y/2)**2;return 12742*Math.asin(Math.sqrt(h))};
async function police(){const box=$('police');if(!LOC)await getLoc(true);if(!LOC){box.innerHTML='Turn on location to find police stations near you. Call <a href="tel:999">999</a> in an emergency.';return}
 box.textContent='Searching the map...';
 const q='[out:json][timeout:25];(node["amenity"="police"](around:6000,'+LOC.lat+','+LOC.lng+');way["amenity"="police"](around:6000,'+LOC.lat+','+LOC.lng+'););out center 15;';
 try{const j=await (await fetch('https://overpass-api.de/api/interpreter',{method:'POST',body:'data='+encodeURIComponent(q)})).json();
  const L=j.elements.map(e=>{const la=e.lat??e.center?.lat,lo=e.lon??e.center?.lon,t=e.tags||{};return la==null?null:{n:t['name:en']||t.name||'Police station',la,lo,ph:t.phone||t['contact:phone']||'',d:km(LOC.lat,LOC.lng,la,lo)}}).filter(Boolean).sort((a,b)=>a.d-b.d).slice(0,3);
  box.innerHTML=L.length?L.map(p=>'<div class="pol"><b>'+esc(p.n)+'</b> <span class="muted">· '+p.d.toFixed(1)+' km</span><div class="cbtns" style="margin-top:6px;grid-template-columns:'+(p.ph?'1fr 1fr':'1fr')+'"><a href="https://www.google.com/maps/dir/?api=1&destination='+p.la+','+p.lo+'" target="_blank" rel="noopener">Directions</a>'+(p.ph?'<a href="tel:'+esc(p.ph.split(';')[0])+'">Call</a>':'')+'</div></div>').join(''):'No police station found on the map nearby. Call <a href="tel:999">999</a>.'}
 catch(e){box.innerHTML='Could not load the map data. Call <a href="tel:999">999</a>.'}}
$('pbtn').onclick=police;
// ---- video / voice call room (Jitsi)
let ROOM=sessionStorage.getItem('rtroom');if(!ROOM){ROOM='RideTrack-SOS-'+Array.from(crypto.getRandomValues(new Uint8Array(9))).map(b=>(b%36).toString(36)).join('');sessionStorage.setItem('rtroom',ROOM)}
const callUrl=v=>'https://meet.jit.si/'+ROOM+(v?'':'#config.startWithVideoMuted=true');
$('vcall').href=callUrl(true);$('acall').href=callUrl(false);
const inv=()=>'Ride Track emergency call. '+ME+' wants to talk. Join here: '+callUrl(true)+(LOC?' · Location: '+mapUrl():'');
function drawInvites(){$('invites').innerHTML=CON.length?'<div class="muted" style="margin-top:10px">Invite to the call:</div>'+CON.map(c=>'<div class="crow"><div><b>'+esc(c.name)+'</b></div><div class="cbtns" style="grid-template-columns:1fr 1fr"><a class="wa" href="https://wa.me/'+wa(c.phone)+'?text='+encodeURIComponent(inv())+'" target="_blank" rel="noopener">WhatsApp invite</a><a href="sms:'+esc(c.phone)+'?body='+encodeURIComponent(inv())+'">SMS invite</a></div></div>').join(''):'<div class="muted" style="margin-top:10px">Add emergency contacts to invite them quickly.</div>'}
$('shr').onclick=async()=>{if(!LOC)await getLoc(false);if(!LOC){alert('Location is not available. Please allow location access.');return}
 if(navigator.share){try{await navigator.share({title:'My location',text:msg(),url:mapUrl()})}catch(e){}}else{try{await navigator.clipboard.writeText(msg());alert('Location link copied. Paste it in a message.')}catch(e){prompt('Copy this message:',msg())}}};
drawContacts();drawInvites();getLoc(true);
</script>
<?php foot();
