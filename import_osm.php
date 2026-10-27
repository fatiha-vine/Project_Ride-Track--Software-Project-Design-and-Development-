<?php require 'config.php';require 'fare.php';require 'areas.inc.php';$u=need('admin');
// Admin only. Pulls real Dhaka bus stops and bus routes from OpenStreetMap (free, no API key), then
// adds the stops, matches them to areas, builds local routes with distance fares and a daily timetable.
@set_time_limit(300);@ini_set('memory_limit','512M');
$BBOX='23.68,90.30,23.92,90.52'; // Dhaka and nearby
$has=q("SHOW COLUMNS FROM routes LIKE 'osm_id'")->fetch();
function hv($a,$b,$c,$d){$p=deg2rad($c-$a);$q=deg2rad($d-$b);$x=sin($p/2)**2+cos(deg2rad($a))*cos(deg2rad($c))*sin($q/2)**2;return 12742*asin(sqrt($x));}
function overpass($ql){$url='https://overpass-api.de/api/interpreter';$body=http_build_query(['data'=>$ql]);
 $raw=function_exists('curl_init')?http($url,['Content-Type: application/x-www-form-urlencoded','User-Agent: RideTrack-student-project'],$body):null;
 if(!$raw)$raw=@file_get_contents($url,false,stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: RideTrack-student-project\r\n",'content'=>$body,'timeout'=>120]]));
 $j=$raw?json_decode($raw,true):null;return(is_array($j)&&isset($j['elements']))?$j['elements']:null;}
function stop_name($t){$n=trim($t['name:en']??'');if($n==='')$n=trim($t['name']??'');
 if($n===''||!preg_match('/[A-Za-z]/',$n)||preg_match('/^bus\s*(stop|stand)?$/i',$n))return null;return mb_substr(preg_replace('/\s+/',' ',$n),0,60);}
$rep=null;
if($_POST&&$has){chk();
 $maxR=max(0,min(150,(int)$_POST['max']));$ret=!empty($_POST['ret']);$every=max(15,min(240,(int)$_POST['every']));$first=$_POST['first']?:'07:00';$last=$_POST['last']?:'21:00';
 $els=overpass('[out:json][timeout:90];(node["highway"="bus_stop"]["name"]('.$BBOX.');node["amenity"="bus_station"]["name"]('.$BBOX.'););out;');
 if(!$els){flash('Could not fetch map data. Check your internet connection (or try again in a minute, the free map server can be busy).');header('Location: import_osm.php');exit;}
 $S=[];$byName=[];
 foreach($els as $e){if(($e['type']??'')!=='node')continue;$n=stop_name($e['tags']??[]);if(!$n)continue;$S[$e['id']]=['n'=>$n,'la'=>(float)$e['lat'],'lo'=>(float)$e['lon']];$k=mb_strtolower($n);if(!isset($byName[$k]))$byName[$k]=$e['id'];}
 $new=0;$upd=0;
 foreach($byName as $k=>$id){$s=$S[$id];$c=q('INSERT INTO stops(name,lat,lng) VALUES(?,?,?) ON DUPLICATE KEY UPDATE lat=COALESCE(lat,VALUES(lat)),lng=COALESCE(lng,VALUES(lng))',[$s['n'],$s['la'],$s['lo']])->rowCount();if($c==1)$new++;elseif($c==2)$upd++;}
 assign_areas();
 $made=[];$warn='';$found=0;
 if($maxR>0){$rels=overpass('[out:json][timeout:120];relation["route"="bus"]('.$BBOX.');out body;');
  if($rels===null)$warn='Stops were imported, but the bus route data could not be fetched. Try again later.';
  else{
   $ids=[];foreach(q('SELECT id,name FROM stops')->fetchAll() as $s)$ids[mb_strtolower($s['name'])]=(int)$s['id'];
   $cand=[];
   foreach($rels as $r){if(($r['type']??'')!=='relation')continue;$seq=[];$seen=[];
    foreach($r['members']??[] as $m){if(($m['type']??'')!=='node'||!isset($S[$m['ref']]))continue;if(!preg_match('/^(stop|platform)/',$m['role']??''))continue;
     $k=mb_strtolower($S[$m['ref']]['n']);if(isset($seen[$k]))continue;$seen[$k]=1;$seq[]=$S[$m['ref']];}
    if(count($seq)>=2)$cand[]=['id'=>(int)$r['id'],'seq'=>$seq];}
   $found=count($cand);usort($cand,fn($a,$b)=>count($b['seq'])<=>count($a['seq']));
   $buses=q("SELECT id FROM buses WHERE status='approved' AND kind='local' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
   if(!$buses)$warn='No approved local bus exists, so routes were added without a timetable. Register a local bus, then use the Routes page to add timetables.';
   $nr=0;
   foreach($cand as $c){if($nr>=$maxR)break;
    foreach([1,-1] as $dir){if($dir==-1&&!$ret)break;if($nr>=$maxR)break;
     $seq=$dir==1?$c['seq']:array_reverse($c['seq']);$oid=$dir*$c['id'];
     if(q('SELECT 1 FROM routes WHERE osm_id=?',[$oid])->fetch())continue;
     $cum=[0];for($i=1;$i<count($seq);$i++)$cum[$i]=$cum[$i-1]+1.3*hv($seq[$i-1]['la'],$seq[$i-1]['lo'],$seq[$i]['la'],$seq[$i]['lo']);
     $tot=$cum[count($seq)-1];if($tot<0.5)continue;$fare=fare_for_km($tot,'local');$lastS=$seq[count($seq)-1];
     try{$pdo->beginTransaction();
      q("INSERT INTO routes(from_city,to_city,fare,type,osm_id) VALUES(?,?,?,'local',?)",[$seq[0]['n'],$lastS['n'],$fare,$oid]);$rid=(int)$pdo->lastInsertId();
      $prev=-1;foreach($seq as $i=>$s){$o=(int)round($cum[$i]*3);if($o<=$prev)$o=$prev+1;$prev=$o;q('INSERT INTO route_stops(route_id,stop_id,seq,offset_min) VALUES(?,?,?,?)',[$rid,$ids[mb_strtolower($s['n'])],$i+1,$o]);}
      if($buses){$k=0;for($t=strtotime($first);$t<=strtotime($last);$t+=$every*60){q('INSERT INTO schedules(route_id,bus_id,depart_time) VALUES(?,?,?)',[$rid,$buses[$k%count($buses)],date('H:i:s',$t)]);$k++;}}
      $pdo->commit();$made[]=$seq[0]['n'].' → '.$lastS['n'].' · ৳'.$fare.' · '.count($seq).' stops';$nr++;
     }catch(Exception $x){if($pdo->inTransaction())$pdo->rollBack();}
    }}
  }}
 $rep=['new'=>$new,'upd'=>$upd,'stops'=>count($byName),'made'=>$made,'warn'=>$warn,'found'=>$found,'areas'=>(int)q('SELECT COUNT(DISTINCT area_id) FROM stops WHERE area_id IS NOT NULL')->fetchColumn()];}
head('Import from map');?>
<h2>Import Dhaka stops and routes from the map</h2>
<p class="muted">One click fetches real bus stops and bus routes for Dhaka from OpenStreetMap, like the maps ride apps use. Stops are matched to their area, routes get distance based fares (students pay the discounted share), and a daily timetable is added. Run it again any time, routes already imported are skipped.</p>
<?php if(!$has)echo '<p class="err">First run <strong>update22_osm.sql</strong> in phpMyAdmin, then reload this page.</p>';?>
<form method="post" class="card"><?=tok()?><div class="grid">
<label>Most routes to add (return trips count too)<input type="number" name="max" value="30" min="0" max="150"></label>
<label>First bus<input type="time" name="first" value="07:00"></label><label>Last bus<input type="time" name="last" value="21:00"></label>
<label>Bus every (minutes)<input type="number" name="every" value="60" min="15" max="240"></label></div>
<label><input type="checkbox" name="ret" value="1" checked style="display:inline;width:auto"> Also add the return trip of each route</label>
<p class="muted">Enter 0 routes to import stops only. Keep the number modest: every route adds a timetable.</p>
<button class="btn"<?=$has?'':' disabled'?>>Fetch from map</button></form>
<?php if($rep){?><div class="card"><h3>Done</h3>
<p><strong><?=$rep['new']?></strong> new stops added, <strong><?=$rep['upd']?></strong> existing stops got coordinates, <?=$rep['stops']?> named stops found in total. Stops now cover <strong><?=$rep['areas']?></strong> Dhaka areas.</p>
<p><strong><?=count($rep['made'])?></strong> routes created<?=$rep['found']?' (the map has '.$rep['found'].' usable bus routes)':''?>.</p>
<?php if($rep['warn'])echo '<p class="err">'.e($rep['warn']).'</p>';
if($rep['made'])echo '<div class="muted">'.e(implode(' | ',array_slice($rep['made'],0,25))).(count($rep['made'])>25?' ...':'').'</div>';
elseif(!$rep['warn']&&$maxR>0)echo '<p class="muted">The map has no usable bus route data for these stops, so only stops were imported. Add routes on the Routes page.</p>';?>
<p><a class="btn" href="areas.php">Open the area map</a> <a class="btn ghost" href="search.php?mode=local">Search local buses</a></p></div><?php }
foot();
