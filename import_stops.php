<?php
// Admin only. Fetches real stop coordinates (lat/lng) from OpenStreetMap.
// Needs internet access (the server calls the Overpass API). One button click.
require 'config.php';require 'areas.inc.php';$u=need('admin');
$BBOX='23.62,90.28,23.93,90.56'; // Dhaka ar kachhakachhi
function norm($s){return preg_replace('/[^a-z0-9]/','',strtolower($s));}
$report=null;
if($_POST){chk();
 $ql='[out:json][timeout:60];(node["highway"="bus_stop"]('.$BBOX.');node["place"~"suburb|neighbourhood|quarter|locality|town"]('.$BBOX.'););out;';
 $ctx=stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: RideTrack-student-project\r\n",'content'=>http_build_query(['data'=>$ql]),'timeout'=>70]]);
 $raw=@file_get_contents('https://overpass-api.de/api/interpreter',false,$ctx);
 $j=$raw?json_decode($raw,true):null;
 if(!$j||empty($j['elements'])){flash('Could not fetch map data. Check your internet connection or the Overpass server and try again.');header('Location: import_stops.php');exit;}
 $place=[];$bus=[];
 foreach($j['elements'] as $e){$t=$e['tags']??[];$names=array_filter([$t['name:en']??'',$t['name']??'',$t['int_name']??'']);
  foreach($names as $n){$k=norm($n);if($k==='')continue;
   if(isset($t['place']))$place[$k]=[$e['lat'],$e['lon']];else $bus[$k][]=[$e['lat'],$e['lon']];}}
 $upd=[];$miss=[];
 foreach(q('SELECT id,name,lat,lng FROM stops')->fetchAll() as $s){$k=norm($s['name']);$pos=null;
  if(isset($place[$k]))$pos=$place[$k];
  elseif(isset($bus[$k])){$la=$lo=0;foreach($bus[$k] as $p){$la+=$p[0];$lo+=$p[1];}$n=count($bus[$k]);$pos=[$la/$n,$lo/$n];}
  if($pos){q('UPDATE stops SET lat=?,lng=? WHERE id=?',[round($pos[0],6),round($pos[1],6),$s['id']]);$upd[]=$s['name'];}else $miss[]=$s['name'];}
 assign_areas();$report=['upd'=>$upd,'miss'=>$miss];}
head('Import stop locations');?>
<h2>Stop location import (map data)</h2>
<p class="muted">Fetches real coordinates of Dhaka stops and areas from OpenStreetMap and updates the stops table when a name matches. It does not change the stop order of any route.</p>
<form method="post" class="card"><?=tok()?><button class="btn">Fetch from map</button></form>
<?php if($report){?><div class="card"><h3>Updated (<?=count($report['upd'])?>)</h3><p><?=e(implode(', ',$report['upd'])?:'none')?></p>
<h3>Not found in map data (<?=count($report['miss'])?>)</h3><p class="muted"><?=e(implode(', ',$report['miss'])?:'none')?></p>
<p class="muted">Stops not found keep their previous (approximate) coordinates. You can correct them in phpMyAdmin.</p></div><?php }
foot();
