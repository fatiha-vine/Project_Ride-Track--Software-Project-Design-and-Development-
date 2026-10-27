<?php require 'config.php';require_once 'appshell.inc.php';$u=need();tok();
$contacts=q('SELECT name,phone,relation FROM emergency_contacts WHERE user_id=? ORDER BY id',[$u['id']])->fetchAll();$tr=active_trip($u['id']);
app_head('Emergency');?>
<style>
.sosintro{text-align:center;padding-top:6px}.sosintro h2{color:var(--dk)}
.holdwrap{position:relative;width:250px;height:250px;margin:14px auto 6px}.holdwrap svg{position:absolute;inset:0;transform:rotate(-90deg);pointer-events:none}
.trk{fill:none;stroke:#F3C9CD;stroke-width:7}.prg{fill:none;stroke:var(--sos);stroke-width:7;stroke-linecap:round;stroke-dasharray:339.3;stroke-dashoffset:339.3}
.tick{position:absolute;left:50%;top:0;width:3px;height:18px;margin-left:-1.5px;background:var(--bg);transform-origin:50% 125px;z-index:2;pointer-events:none}
#sosbtn{position:absolute;inset:20px;border-radius:50%;border:0;background:radial-gradient(circle at 32% 24%,#F0505C,#B5121F 78%);color:#fff;font:700 3rem 'Barlow Semi Condensed',sans-serif;letter-spacing:.05em;box-shadow:0 14px 30px rgba(216,33,47,.45),inset 0 -8px 16px rgba(0,0,0,.18);touch-action:none;user-select:none;-webkit-user-select:none;-webkit-touch-callout:none;cursor:pointer;line-height:1}
#sosbtn small{display:block;font:600 .82rem 'Hind Siliguri',sans-serif;letter-spacing:.02em;margin-top:6px;opacity:.95}#sosbtn.hold{transform:scale(.95)}#sosbtn.sent{background:radial-gradient(circle at 32% 24%,#5B8CFF,#1D4ED8 80%);box-shadow:0 14px 30px rgba(29,78,216,.4)}#sosbtn:focus-visible{outline:4px solid #E9B44C;outline-offset:4px}
.crow{display:flex;flex-direction:column;gap:8px;padding:12px 0;border-top:1px solid var(--ln)}.crow:first-child{border-top:0;padding-top:0}
.cbtns{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}.cbtns a{padding:12px 4px;border-radius:14px;background:var(--c1);text-align:center;text-decoration:none;font-weight:700;font-size:.9rem;color:var(--dk)}.cbtns a.wa{background:#DDF3E4;color:#14532D}
.stat{font-size:.95rem;color:var(--mut);text-align:center;min-height:1.5em}.okbox{background:#E3F2E8;border-radius:18px;padding:16px 18px;margin:14px 0;color:#14532D}
.pol{padding:12px 0;border-top:1px solid var(--ln)}.pol:first-child{border-top:0;padding-top:0}
.ghd{display:flex;align-items:center;gap:10px;margin-bottom:10px}.ghd .ic{color:var(--pri)}.ghd h3{margin:0}
</style>
<div class="sosintro"><h2>Emergency help</h2><p class="muted">জরুরি সাহায্য · Press and hold the red button for 3 seconds<br>৩ সেকেন্ড চেপে ধরে রাখুন</p>
<div class="holdwrap"><svg viewBox="0 0 120 120"><circle class="trk" cx="60" cy="60" r="54"/><circle class="prg" id="ring" cx="60" cy="60" r="54"/></svg><i class="tick" style="transform:rotate(0deg)"></i><i class="tick" style="transform:rotate(120deg)"></i><i class="tick" style="transform:rotate(240deg)"></i><button id="sosbtn" type="button" aria-label="Hold for 3 seconds to send SOS">SOS<small>HOLD 3 SEC</small></button></div>
<div class="stat" id="gst">Checking your location...</div></div>
<div id="result"></div>
<a class="abtn red" href="tel:999"><?=ic('phone',22)?>Call 999 · জাতীয় জরুরি সেবা</a>
<div class="grp"><div class="ghd"><?=ic('shield',24)?><h3>Police near you</h3></div><div id="police" class="muted">Tap the button to find police stations near you on the map.</div><button class="abtn ghost" id="pbtn" type="button" style="margin-top:10px">Find nearby police</button></div>
<?php if($tr){?><div class="grp"><div class="ghd"><?=ic('bus',24)?><h3>Your bus company</h3></div><div><b><?=e($tr['operator'])?></b> · <?=e($tr['reg'])?><br><span class="muted">Driver: <?=e($tr['driver_name'])?></span></div>
<?php if($tr['op_phone']){?><a class="abtn ghost" style="margin-top:10px" href="tel:<?=e($tr['op_phone'])?>"><?=ic('phone',20)?>Call bus company</a><?php }?></div><?php }?>
<div class="grp"><div class="ghd"><?=ic('users',24)?><h3>Family &amp; friends</h3></div>
<?php if(!$contacts)echo '<p class="muted">No emergency contacts saved yet.</p>';?><div id="clist"></div>
<a class="abtn ghost" style="margin-top:10px" href="emergency_contacts.php"><?=ic('plus',20)?>Add or edit contacts</a>
<p class="muted" style="font-size:.85rem;margin-bottom:0">Voice message and video call: tap WhatsApp, then use the microphone or video icon in the chat.</p></div>
<div class="grp"><div class="ghd"><?=ic('video',24)?><h3>Video or voice call</h3></div><p class="muted" style="margin-top:0">Start a free call room and invite your family. They open the link in their phone browser, no app needed. Chat is inside the call too.</p>
<div class="cbtns" style="grid-template-columns:1fr 1fr"><a id="vcall" href="#" target="_blank" rel="noopener">Video call</a><a id="acall" href="#" target="_blank" rel="noopener">Voice call</a></div>
<div id="invites"></div><p class="muted" style="font-size:.82rem;margin-bottom:0">The call uses the free Jitsi Meet service. The person who starts it may be asked to sign in once; people who join do not.</p></div>
<div class="grp"><button class="abtn ghost" id="shr" type="button"><?=ic('share',20)?>Share my live location</button></div>
<script>
const T=<?=json_encode($_SESSION['t'])?>,CON=<?=json_encode($contacts,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP)?>,BUS=<?=json_encode($tr?['reg'=>$tr['reg'],'operator'=>$tr['operator']]:null,JSON_UNESCAPED_UNICODE)?>,ME=<?=json_encode($u['name'],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG)?>;
const $=id=>document.getElementById(id),esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let LOC=null,LOCT=0;
const mapUrl=()=>LOC?'https://maps.google.com/?q='+LOC.lat+','+LOC.lng:'';
const msg=()=>'Ride Track SOS: '+ME+' needs help.'+(LOC?' My location: '+mapUrl():'')+(BUS?' Bus: '+BUS.reg+' ('+BUS.operator+').':'');
const wa=p=>{let d=String(p).replace(/[^\d+]/g,'').replace(/^\+/,'');if(d.startsWith('00'))d=d.slice(2);if(d.startsWith('0'))d='88'+d;return d};
function drawContacts(){$('clist').innerHTML=CON.map(c=>'<div class="crow"><div><b>'+esc(c.name)+'</b> <span class="muted">· '+esc(c.relation)+'</span></div><div class="cbtns"><a href="tel:'+esc(c.phone)+'">Call</a><a href="sms:'+esc(c.phone)+'?body='+encodeURIComponent(msg())+'">SMS</a><a class="wa" href="https://wa.me/'+wa(c.phone)+'?text='+encodeURIComponent(msg())+'" target="_blank" rel="noopener">WhatsApp</a></div></div>').join('')}
function getLoc(fast){return new Promise(res=>{if(!navigator.geolocation||!window.isSecureContext)return res(null);
 navigator.geolocation.getCurrentPosition(p=>{LOC={lat:+p.coords.latitude.toFixed(6),lng:+p.coords.longitude.toFixed(6)};LOCT=Date.now();$('gst').textContent='Location ready ✓';drawContacts();if(typeof drawInvites==='function')drawInvites();res(LOC)},
  e=>{$('gst').textContent=e.code===1?'Location is blocked. Allow location in your browser so your family can see where you are.':'Could not get your location yet.';res(null)},{enableHighAccuracy:!fast,timeout:fast?6000:12000,maximumAge:fast?60000:0})})}
drawContacts();getLoc(true);
// ---- hold 3 seconds
const btn=$('sosbtn'),ring=$('ring'),C=339.3,HOLD=3000;let t0=0,raf=0,done=false;
function tick(){const p=Math.min(1,(performance.now()-t0)/HOLD);ring.style.strokeDashoffset=C*(1-p);if(p>=1){endHold(true);return}raf=requestAnimationFrame(tick)}
function startHold(e){if(done||t0)return;if(e&&e.preventDefault)e.preventDefault();t0=performance.now();btn.classList.add('hold');raf=requestAnimationFrame(tick);if(navigator.vibrate)navigator.vibrate(30)}
function endHold(ok){cancelAnimationFrame(raf);t0=0;btn.classList.remove('hold');if(ok){done=true;if(navigator.vibrate)navigator.vibrate([200,100,200]);fire()}else ring.style.strokeDashoffset=C}
btn.addEventListener('pointerdown',startHold);['pointerup','pointerleave','pointercancel'].forEach(ev=>btn.addEventListener(ev,()=>{if(!done&&t0)endHold(false)}));
btn.addEventListener('contextmenu',e=>e.preventDefault());
btn.addEventListener('keydown',e=>{if((e.key===' '||e.key==='Enter')&&!e.repeat)startHold(e)});btn.addEventListener('keyup',e=>{if((e.key===' '||e.key==='Enter')&&!done&&t0)endHold(false)});
async function fire(){btn.innerHTML='Sending...<small>পাঠানো হচ্ছে</small>';if(!LOC||Date.now()-LOCT>120000)await getLoc(true);
 const f=new FormData();f.append('t',T);if(LOC){f.append('lat',LOC.lat);f.append('lng',LOC.lng)}
 try{const j=await (await fetch('sos_send.php',{method:'POST',body:f})).json();if(!j.ok)throw 0;
  btn.classList.add('sent');btn.innerHTML='SENT ✓<small>পাঠানো হয়েছে</small>';
  let h='<div class="okbox"><b>Your SOS was sent.</b><br>';
  h+=(j.staff+j.police)?'✓ Ride Track staff'+(j.police?' and traffic police':'')+' were alerted.<br>':'';
  if(j.bus)h+='✓ Your bus company ('+esc(j.bus.operator)+') was notified.<br>';
  h+=j.contacts?(j.sms?'✓ SMS sent to '+j.sms+' of '+j.contacts+' emergency contacts.<br>':'⚠ SMS could not be sent automatically. Use the buttons below to message your contacts.<br>'):'⚠ No emergency contacts saved. <a href="emergency_contacts.php">Add contacts</a>.<br>';
  h+=LOC?'':'⚠ Your location was not shared. Turn on location.<br>';
  h+='<b>If you are in danger, call 999 now.</b></div>';$('result').innerHTML=h;police();window.scrollTo({top:$('result').offsetTop-20,behavior:'smooth'})}
 catch(e){done=false;ring.style.strokeDashoffset=C;btn.innerHTML='SOS<small>HOLD 3 SEC</small>';$('result').innerHTML='<div class="abanner">Could not send. Check your internet and try again, or call 999.</div>'}}
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
drawInvites();

$('shr').onclick=async()=>{if(!LOC)await getLoc(false);if(!LOC){alert('Location is not available. Please allow location access.');return}
 if(navigator.share){try{await navigator.share({title:'My location',text:msg(),url:mapUrl()})}catch(e){}}else{try{await navigator.clipboard.writeText(msg());alert('Location link copied. Paste it in a message.')}catch(e){prompt('Copy this message:',msg())}}};
</script>
<?php app_foot('sos');
