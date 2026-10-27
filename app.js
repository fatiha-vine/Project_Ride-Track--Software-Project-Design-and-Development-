document.querySelectorAll('input[type=date]').forEach(d=>{if(!d.min)d.min=new Date().toISOString().slice(0,10)});
document.querySelectorAll('[data-confirm]').forEach(f=>f.addEventListener('submit',ev=>{if(!confirm(f.dataset.confirm))ev.preventDefault()}));
const pm=document.getElementById('method'),rf=document.getElementById('refbox');
if(pm&&rf){const t=()=>rf.style.display=pm.value==='bkash'?'block':'none';pm.addEventListener('change',t);t()}
document.querySelectorAll('.seats input').forEach(i=>i.addEventListener('change',()=>{const s=document.getElementById('sel');if(s)s.textContent='Seat '+i.value}));
