<?php
function card_upload($f){if(empty($f['name'])||$f['error']!==0||$f['size']>6000000)return null;$i=@getimagesize($f['tmp_name']);if(!$i||!in_array($i[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG]))return null;
 $d=__DIR__.'/uploads/private';if(!is_dir($d))@mkdir($d,0777,true);file_put_contents($d.'/.htaccess',"Require all denied\n");
 $n=bin2hex(random_bytes(10)).($i[2]==IMAGETYPE_PNG?'.png':'.jpg');return move_uploaded_file($f['tmp_name'],"$d/$n")?$n:null;}
function ocr($file){if(!TESSERACT||!is_file(TESSERACT)||!function_exists('exec'))return null;$t='';
 foreach([6,11] as $m){$o=[];@exec('"'.TESSERACT.'" '.escapeshellarg(__DIR__."/uploads/private/$file").' stdout --psm '.$m.' -l eng 2>&1',$o);$t.="\n".implode("\n",$o);}return $t;}
function parse_card($txt){
 $T=strtolower(preg_replace('/\s+/',' ',$txt));$now=(int)date('Y');$M='(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*';
 $mn=['jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,'jul'=>7,'aug'=>8,'sep'=>9,'oct'=>10,'nov'=>11,'dec'=>12];
 $okY=fn($y)=>$y>=$now-6&&$y<=$now+8;
 $eom=fn($y,$m)=>sprintf('%04d-%02d-%02d',$y,$m,date('t',mktime(0,0,0,(int)$m,1,(int)$y)));
 $date=function($s)use($M,$mn,$okY,$eom){
  if(preg_match('/(\d{1,2})\s*[\/.-]\s*(\d{1,2})\s*[\/.-]\s*(20\d\d)/',$s,$m)&&$okY((int)$m[3])&&$m[2]>=1&&$m[2]<=12)return sprintf('%04d-%02d-%02d',$m[3],$m[2],$m[1]);
  if(preg_match('/(20\d\d)\s*[\/.-]\s*(\d{1,2})\s*[\/.-]\s*(\d{1,2})/',$s,$m)&&$okY((int)$m[1])&&$m[2]>=1&&$m[2]<=12)return sprintf('%04d-%02d-%02d',$m[1],$m[2],$m[3]);
  if(preg_match('/(?:(\d{1,2})\W{0,3})?'.$M.'\W{0,3}(20\d\d)/',$s,$m)&&$okY((int)$m[3]))return $m[1]!==''?sprintf('%04d-%02d-%02d',$m[3],$mn[$m[2]],$m[1]):$eom($m[3],$mn[$m[2]]);
  if(preg_match('/(?<!\d)(\d{1,2})\s*[\/.-]\s*(20\d\d)/',$s,$m)&&$okY((int)$m[2])&&$m[1]>=1&&$m[1]<=12)return $eom($m[2],$m[1]);
  return null;};
 $best=null;
 if(preg_match_all('/(?:valid(?:ity)?|expir\w*|until|upto|up to|till|obdhi)\W{0,15}(.{0,45})/',$T,$ks))foreach($ks[1] as $w){$d=$date($w);if($d&&(!$best||$d>$best))$best=$d;}
 $ses=null;$rng=null;$assumed=false;
 if(preg_match('/(?<!\d)(20\d\d)\s*(?:-|–|—|to)\s*'.$M.'\W{0,3}(20\d\d)(?!\d)/',$T,$r)&&$r[3]>=$r[1]&&$r[3]-$r[1]<=8&&$okY((int)$r[3])){$ses=$r[1].'-'.$r[3];$rng=$eom($r[3],$mn[$r[2]]);}
 elseif(preg_match('/(?<!\d)(20\d\d)\s*(?:-|–|—|to)\s*(20\d\d)(?!\d)(?:\W{0,8}'.$M.')?/',$T,$r)&&$r[2]>=$r[1]&&$r[2]-$r[1]<=8&&$okY((int)$r[2])){$ses=$r[1].'-'.$r[2];if(!empty($r[3]))$rng=$eom($r[2],$mn[$r[3]]);else{$rng=$r[2].'-12-31';$assumed=true;}}
 $exp=$best?:$rng;
 return ['looks'=>(bool)preg_match('/student|university|college|institute|school|id card|identity|roll/',$T),'session'=>$ses,'expiry'=>$exp,'assumed'=>(!$best&&$assumed)];}
function verify_card($sid,$front,$back){
 $a=ocr($front);$b=ocr($back);
 if($a===null)return ['pending','Automatic card reading is not set up on this server, so an admin will check your card.',null,null];
 $txt=$a."\n".$b;$p=parse_card($txt);$want=preg_replace('/\D/','',$sid);$idok=$want!==''&&strpos(preg_replace('/\D/','',$txt),$want)!==false;
 if(!$p['looks'])return ['none','This does not look like a student ID card. Upload clear, straight photos of both sides.',null,null];
 if(!$p['expiry'])return ['pending','We could not read the validity date, so an admin will check your card.',$p['session'],null];
 if($p['expiry']<date('Y-m-d'))return ['none','This card expired on '.$p['expiry'].'. Student discount is not available.',$p['session'],$p['expiry']];
 if(!$idok)return ['pending','The ID number you typed was not found on the card photo, so an admin will check it.',$p['session'],$p['expiry']];
 return ['verified','Card valid until '.$p['expiry'].($p['assumed']?' (month not printed, end of the session year used)':'').'. Student discount is active.',$p['session'],$p['expiry']];}
