<?php require 'config.php';
function lan_ip(){$s=@stream_socket_client('udp://8.8.8.8:53',$en,$es,1);if($s){$n=stream_socket_get_name($s,false);fclose($s);if($n&&strpos($n,':')!==false){$ip=substr($n,0,strrpos($n,':'));if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))return $ip;}}
 $h=gethostbyname(gethostname());return filter_var($h,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)?$h:'';}
$ip=lan_ip();$c=@fsockopen('127.0.0.1',443,$en,$es,0.6);$ssl=(bool)$c;if($c)fclose($c);
$base=rtrim(dirname($_SERVER['SCRIPT_NAME']),'/\\');$sch=$ssl?'https':'http';
$home=$ip?"$sch://$ip$base/index.php":'';$drv=$ip?"$sch://$ip$base/driver_login.php":'';$map=$ip?"$sch://$ip$base/areas.php":'';
head('Open on phone');?>
<h2>Open Ride Track on your phone</h2>
<?php if(!$ip){echo '<p class="err">Could not find this computer\'s Wi-Fi address. Connect the PC to Wi-Fi and reload.</p>';foot();exit;}?>
<p class="muted">Your PC's address right now: <strong><?=e($ip)?></strong>. It is found automatically, so you never type it. Phone and PC must be on the same Wi-Fi.</p>
<div class="grid"><div class="card"><h3>Scan with your phone camera</h3><div id="qr" style="margin:10px 0"></div><p class="muted"><?=e($home)?></p></div>
<div class="card"><h3>Quick links</h3><p><a class="btn sm" href="<?=e($home)?>">Passenger home</a></p><p><a class="btn sm ghost" href="<?=e($map)?>">Area map</a></p><p><a class="btn sm ghost" href="<?=e($drv)?>">Driver login</a></p>
<p class="muted">Copy any link and send it to your phone on WhatsApp.</p></div></div>
<div class="card"><h3>GPS and "My location" on the phone</h3>
<?php if($ssl){?><p>Secure mode (HTTPS) is <strong>ON</strong>. The links above already use it.</p>
<ol><li>The first time, the phone says <em>"Your connection is not private"</em>. This is normal for a home computer. Tap <strong>Advanced</strong>, then <strong>Proceed to <?=e($ip)?> (unsafe)</strong>.</li><li>Open the Driver portal or Area map and tap the location button. When the phone asks, tap <strong>Allow</strong>.</li><li>Turn on Location in the phone's quick settings.</li></ol>
<?php }else{?><p class="err">HTTPS (port 443) is <strong>OFF</strong>, so the phone will block GPS. In the XAMPP Control Panel, stop Apache and start it again. If it shows "Port 443 in use", close Skype or VMware and try again.</p><?php }?></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script><script>new QRCode(document.getElementById('qr'),{text:<?=json_encode($home)?>,width:190,height:190});</script>
<?php foot();
