<?php
// ===== Student ID card auto-check (OCR + validity-date detection) =====
// Needs Tesseract OCR installed on the server.
//   Windows (XAMPP): install from https://github.com/UB-Mannheim/tesseract/wiki and set the full path below.
//   Linux: sudo apt install tesseract-ocr
const TESS_BIN='tesseract'; // Windows example: 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe'

// Upload one image (jpg/png only) into uploads/private. Returns file name or null.
function id_upload($f){
 $n=upload($f,['jpg','jpeg','png']);if(!$n)return null;
 if(!@getimagesize(__DIR__."/uploads/$n")){@unlink(__DIR__."/uploads/$n");return null;} // must be a real image
 @mkdir(__DIR__.'/uploads/private');file_put_contents(__DIR__.'/uploads/private/.htaccess',"Require all denied\n");
 rename(__DIR__."/uploads/$n",__DIR__."/uploads/private/$n");return $n;}

// Is Tesseract really installed and reachable from PHP?
function id_tess_ok(){$o=@shell_exec(escapeshellarg(TESS_BIN).' --version 2>&1');return (bool)preg_match('/tesseract\s+v?\d/i',(string)$o);}

// Read all text on an image. Two page-segmentation modes are tried because ID cards have scattered text.
function id_ocr($path){$out='';
 foreach(['6','11'] as $psm){$o=@shell_exec(escapeshellarg(TESS_BIN).' '.escapeshellarg($path).' stdout -l eng --psm '.$psm.' 2>&1');if($o)$out.="\n".$o;}
 return trim($out);}

function id_last_day($y,$m){return date('Y-m-t',mktime(0,0,0,$m,1,$y));}

// Find every date on the card; the latest one is the card's expiry.
// Understands: "2023-2027 June", "2023-27", "Valid till 30 June 2027", "30/06/2027", "2027-06-30", "June 2027", Bangla digits.
function id_parse($raw,$today=null){
 $today=$today?:date('Y-m-d');$maxY=(int)substr($today,0,4)+10;
 $t=strtr($raw,['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9']);
 $t=str_replace(["\u{2013}","\u{2014}","\u{2212}"],'-',$t);$t=preg_replace('/\s+/',' ',$t);
 $M='(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|june?|july?|aug(?:ust)?|sept?(?:ember)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)';
 $mn=['jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,'jul'=>7,'aug'=>8,'sep'=>9,'oct'=>10,'nov'=>11,'dec'=>12];
 $dates=[];$session=null;$fb=null; // $fb = session-end fallback (used only when the card shows no exact date)
 $add=function($y,$m,$d=null)use(&$dates){$y=(int)$y;$m=(int)$m;if($m<1||$m>12)return;
  if($d===null)$dates[]=id_last_day($y,$m);elseif(checkdate($m,(int)$d,$y))$dates[]=sprintf('%04d-%02d-%02d',$y,$m,$d);};
 // 1) 2027-06-30
 $t=preg_replace_callback('/\b(20\d{2})-(\d{1,2})-(\d{1,2})\b/',function($x)use($add){$add($x[1],$x[2],$x[3]);return ' ';},$t);
 // 2) 30/06/2027  (Bangladesh: day first)
 $t=preg_replace_callback('/\b(\d{1,2})[\/.\-](\d{1,2})[\/.\-](20\d{2})\b/',function($x)use($add){$add($x[3],$x[2],$x[1]);return ' ';},$t);
 // 3) 30 June 2027
 $t=preg_replace_callback('/\b(\d{1,2})(?:st|nd|rd|th)?[\s,.\-]*'.$M.'[\s,.\-]*(20\d{2})\b/i',function($x)use($add,$mn){$add($x[3],$mn[strtolower(substr($x[2],0,3))],$x[1]);return ' ';},$t);
 // 4) June 30, 2027
 $t=preg_replace_callback('/\b'.$M.'[\s,.\-]*(\d{1,2})(?:st|nd|rd|th)?[\s,]+(20\d{2})\b/i',function($x)use($add,$mn){$add($x[3],$mn[strtolower(substr($x[1],0,3))],$x[2]);return ' ';},$t);
 // 5) session 2023-2027 [June]  /  2025-26
 $t=preg_replace_callback('/\b(20\d{2})\s*(?:-|to)\s*(20\d{2}|\d{2})\b(?:[\s,.\-]*'.$M.'\b)?/i',function($x)use($add,$mn,&$session,&$fb){
  $s=(int)$x[1];$e=strlen($x[2])==2?(int)(substr($x[1],0,2).$x[2]):(int)$x[2];if($e<$s||$e-$s>8)return ' ';
  $session=$s.'-'.$e;if(!empty($x[3]))$add($e,$mn[strtolower(substr($x[3],0,3))]);else $fb=$e.'-12-31';return ' ';},$t);
 // 6) June 2027
 preg_match_all('/\b'.$M.'[\s,.\-]*(20\d{2})\b/i',$t,$mm,PREG_SET_ORDER);foreach($mm as $x)$add($x[2],$mn[strtolower(substr($x[1],0,3))]);
 $dates=array_filter($dates,fn($d)=>(int)substr($d,0,4)>=2000&&(int)substr($d,0,4)<=$maxY);
 sort($dates);if(!$dates&&$fb&&(int)$fb<=$maxY)$dates=[$fb]; // exact dates on the card win over the session-year guess
 return ['expiry'=>$dates?end($dates):null,'session'=>$session,'dates'=>array_values($dates)];}

// Decide from OCR text: returns [status, expiry, session, note]
//   verified = date found and today <= expiry     none = card expired or date unreadable (student must re-upload)
function id_decide($text,$today=null){$today=$today?:date('Y-m-d');$r=id_parse($text,$today);
 if(!$r['expiry'])return ['none',null,$r['session'],'Could not read the validity date from your card. Please upload a clear, well-lit photo of both sides. (If this keeps happening, the server may not have Tesseract OCR installed.)'];
 if($r['expiry']<$today)return ['none',$r['expiry'],$r['session'],'Card expired on '.$r['expiry'].'. Not a current student.'];
 return ['verified',$r['expiry'],$r['session'],'Auto-verified. Card valid until '.$r['expiry'].'.'];}
