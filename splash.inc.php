<style>
.sp{position:fixed;inset:0;z-index:100;display:flex;flex-direction:column;overflow:hidden;color:#164e63;background:radial-gradient(900px 480px at 78% 8%,#fff4c4 0,transparent 62%),linear-gradient(180deg,#ecfeff 0%,#cffafe 58%,#a5f3fc 100%);transition:opacity .7s,visibility .7s}
.sp.out{opacity:0;visibility:hidden}
.sp-top{display:flex;align-items:center;gap:10px;padding:22px 34px;font:700 1.6rem 'Cormorant Garamond',Georgia,serif;animation:spUp .8s both}
.sp-top i{width:36px;height:36px;border-radius:11px;background:#0e7490;display:grid;place-items:center;box-shadow:0 8px 18px -8px #0e7490}
.sp-mid{text-align:center;padding:5vh 20px 0}
.sp-bn{margin:0;font:700 clamp(2.4rem,7.5vw,5rem)/1.3 'Hind Siliguri','Noto Sans Bengali','Nirmala UI',sans-serif;background:linear-gradient(90deg,#164e63,#0e7490 60%,#06b6d4);-webkit-background-clip:text;background-clip:text;color:transparent;animation:spUp 1s .2s both}
.sp-sub{margin:6px 0 24px;font:500 .78rem Inter,sans-serif;letter-spacing:.38em;text-transform:uppercase;color:#4b7f8e;animation:spUp 1s .45s both}
.sp-go{border:0;cursor:pointer;color:#fff;background:#0e7490;padding:13px 30px;border-radius:99px;font:600 1.05rem 'Hind Siliguri','Nirmala UI',sans-serif;box-shadow:0 14px 26px -12px #0e7490;transition:transform .2s,background .2s;animation:spUp 1s .7s both}
.sp-go:hover{background:#164e63;transform:translateY(-2px)}
.sp-demo{display:inline-block;text-decoration:none;background:#fff;color:#0e7490;border:2px solid #0e7490;margin-left:10px;box-shadow:none}.sp-demo:hover{background:#cffafe;color:#164e63}
.sp-art{margin:auto auto 0;display:block;width:min(1100px,100%);height:auto;max-height:50vh}
.sp-bar{position:absolute;left:0;bottom:0;height:3px;background:#0e7490;animation:spBar 120s linear forwards}
.sp-bus{animation:spDrive 3s cubic-bezier(.16,.8,.3,1) both}
.sp-bob{animation:spBob .2s ease-in-out infinite}
.sp-rd{animation:spRd .2s linear infinite}
.sp-wh{transform-box:fill-box;transform-origin:center;animation:spSpin .2s linear infinite}
.sp-c1{animation:spCl 40s linear infinite alternate}.sp-c2{animation:spCl 55s linear infinite alternate-reverse}
@keyframes spDrive{from{transform:translateX(-560px)}to{transform:none}}
@keyframes spBob{50%{transform:translateY(-2px)}}
@keyframes spRd{to{stroke-dashoffset:44}}
@keyframes spSpin{to{transform:rotate(360deg)}}
@keyframes spCl{to{transform:translateX(60px)}}
@keyframes spBar{from{width:0}to{width:100%}}
@keyframes spUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
@media(prefers-reduced-motion:reduce){.sp *{animation:none!important}}
</style>
<div class="sp" id="sp" role="dialog" aria-label="Welcome to Ride Track">
<div class="sp-top"><i><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ecfeff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="12" rx="3"/><path d="M3 11h18M7 17v2M17 17v2"/></svg></i>RideTrack</div>
<div class="sp-mid"><h1 class="sp-bn">স্বপ্ন যাবে বাড়ি আমার</h1><p class="sp-sub">Book · Track · Travel</p><button class="sp-go" type="button">শুরু করুন →</button><a class="sp-go sp-demo" href="demo_app.php">ডেমো অ্যাপ দেখুন</a></div>
<svg class="sp-art" viewBox="0 0 800 300" preserveAspectRatio="xMidYMax meet" aria-hidden="true">
<defs><linearGradient id="spb" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#1596b3"/><stop offset="1" stop-color="#0e7490"/></linearGradient><linearGradient id="spw" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#f0feff"/><stop offset="1" stop-color="#9fe9f7"/></linearGradient></defs>
<circle cx="660" cy="70" r="58" fill="#fde68a" opacity=".35"/><circle cx="660" cy="70" r="34" fill="#fde68a"/>
<g class="sp-c1" fill="#fff" opacity=".85"><ellipse cx="150" cy="62" rx="52" ry="15"/><ellipse cx="190" cy="52" rx="32" ry="13"/></g>
<g class="sp-c2" fill="#fff" opacity=".8"><ellipse cx="430" cy="92" rx="44" ry="12"/><ellipse cx="462" cy="84" rx="26" ry="10"/></g>
<g fill="#bdeaf3"><rect x="30" y="160" width="46" height="70"/><rect x="82" y="120" width="36" height="110"/><rect x="124" y="140" width="54" height="90"/><rect x="184" y="100" width="34" height="130"/><rect x="224" y="150" width="62" height="80"/><rect x="294" y="116" width="40" height="114"/><rect x="340" y="134" width="56" height="96"/><rect x="402" y="92" width="34" height="138"/><rect x="442" y="146" width="60" height="84"/><rect x="508" y="122" width="38" height="108"/><rect x="552" y="140" width="52" height="90"/><rect x="610" y="108" width="40" height="122"/><rect x="656" y="150" width="50" height="80"/><rect x="712" y="126" width="36" height="104"/><rect x="754" y="152" width="46" height="78"/></g>
<g fill="#9fdbe8"><rect x="60" y="190" width="30" height="40"/><rect x="150" y="176" width="40" height="54"/><rect x="260" y="196" width="34" height="34"/><rect x="380" y="184" width="44" height="46"/><rect x="480" y="190" width="32" height="40"/><rect x="590" y="178" width="42" height="52"/><rect x="690" y="192" width="36" height="38"/><rect x="760" y="184" width="30" height="46"/></g>
<rect y="230" width="800" height="70" fill="#164e63"/><rect y="230" width="800" height="6" fill="#0e7490"/>
<line class="sp-rd" x1="0" y1="272" x2="800" y2="272" stroke="#a5f3fc" stroke-width="4" stroke-dasharray="26 18"/>
<g class="sp-bus"><g class="sp-bob">
<ellipse cx="400" cy="262" rx="190" ry="8" fill="#000" opacity=".22"/>
<polygon points="552,206 700,186 700,238 552,218" fill="#fde68a" opacity=".22"/>
<rect x="250" y="140" width="300" height="96" rx="18" fill="url(#spb)"/>
<rect x="262" y="128" width="276" height="16" rx="8" fill="#0c6379"/><rect x="336" y="121" width="96" height="9" rx="4" fill="#a5f3fc"/>
<rect x="250" y="198" width="300" height="12" fill="#a5f3fc"/>
<g fill="url(#spw)"><rect x="266" y="154" width="44" height="36" rx="6"/><rect x="318" y="154" width="44" height="36" rx="6"/><rect x="370" y="154" width="44" height="36" rx="6"/><rect x="422" y="154" width="44" height="36" rx="6"/><rect x="476" y="152" width="28" height="58" rx="5"/><path d="M512 154h24a14 14 0 0 1 14 14v24h-38z"/></g>
<line x1="490" y1="152" x2="490" y2="210" stroke="#0e7490" stroke-width="2"/>
<text x="344" y="229" font-family="Georgia,serif" font-size="13" fill="#ecfeff" letter-spacing="3">RIDE TRACK</text>
<rect x="544" y="206" width="8" height="14" rx="3" fill="#fde68a"/><rect x="248" y="206" width="6" height="12" rx="2" fill="#f87171"/>
<g transform="translate(312 236)"><circle r="26" fill="#0c5f75"/><g class="sp-wh"><circle r="22" fill="#0b3a4a"/><circle r="12" fill="#cffafe"/><path d="M0-10V10M-10 0H10M-7-7L7 7M-7 7L7-7" stroke="#0e7490" stroke-width="2"/></g></g>
<g transform="translate(490 236)"><circle r="26" fill="#0c5f75"/><g class="sp-wh"><circle r="22" fill="#0b3a4a"/><circle r="12" fill="#cffafe"/><path d="M0-10V10M-10 0H10M-7-7L7 7M-7 7L7-7" stroke="#0e7490" stroke-width="2"/></g></g>
</g></g></svg>
<div class="sp-bar"></div></div>
<script>(function(){var s=document.getElementById('sp');document.body.style.overflow='hidden';function go(){s.classList.add('out');document.body.style.overflow='';setTimeout(function(){s.remove()},800)}setTimeout(go,120000);s.addEventListener('click',go);document.addEventListener('keydown',function(e){if(e.key==='Escape'||e.key==='Enter')go()})})();</script>