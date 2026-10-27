<?php
/* ===== EDIT THESE. Leave a service blank and the site falls back to demo mode. ===== */
const SMTP_HOST='smtp.gmail.com', SMTP_PORT=465, SMTP_USER='', SMTP_PASS='';   // Gmail: use an App Password
const SMS_URL='';       // provider URL with {to} and {msg}, e.g. https://example.com/api?key=KEY&number={to}&message={msg}
const BK_URL='https://tokenized.sandbox.bka.sh/v1.2.0-beta';                     // live: your bKash live base URL
const BK_KEY='', BK_SECRET='', BK_USER='', BK_PASS='';                           // from your bKash merchant account
const TESSERACT='C:\Program Files\Tesseract-OCR\tesseract.exe'; // reads student ID cards (install Tesseract OCR)
const CA_BUNDLE='';     // optional, e.g. 'D:/Users/HP/Downloads/apache/bin/curl-ca-bundle.crt' if curl shows an SSL error
/* ================================================================================= */
function base(){return (!empty($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['SCRIPT_NAME']),'/');}
function sweep(){q("UPDATE bookings SET status='cancelled',active=NULL WHERE status='pending' AND created_at<NOW()-INTERVAL 10 MINUTE");}
function http($url,$h=[],$body=null){$c=curl_init($url);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>$h]);if(CA_BUNDLE)curl_setopt($c,CURLOPT_CAINFO,CA_BUNDLE);if($body!==null){curl_setopt($c,CURLOPT_POST,1);curl_setopt($c,CURLOPT_POSTFIELDS,$body);}$o=curl_exec($c);curl_close($c);return $o;}
function send_mail($to,$sub,$body){if(!SMTP_USER||!filter_var($to,FILTER_VALIDATE_EMAIL))return false;
 $s=@stream_socket_client('ssl://'.SMTP_HOST.':'.SMTP_PORT,$en,$es,10);if(!$s)return false;
 $r=function($c=null)use($s){if($c!==null)fwrite($s,$c."\r\n");$o='';while($l=fgets($s,515)){$o.=$l;if(substr($l,3,1)==' ')break;}return $o;};
 $r();$r('EHLO localhost');$r('AUTH LOGIN');$r(base64_encode(SMTP_USER));$a=$r(base64_encode(SMTP_PASS));if(substr($a,0,3)!='235'){fclose($s);return false;}
 $r('MAIL FROM:<'.SMTP_USER.'>');$r("RCPT TO:<$to>");$r('DATA');
 fwrite($s,"From: Ride Track <".SMTP_USER.">\r\nTo: $to\r\nSubject: =?UTF-8?B?".base64_encode($sub)."?=\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n".str_replace("\n.","\n..",$body)."\r\n.\r\n");
 $ok=substr($r(),0,3)=='250';$r('QUIT');fclose($s);return $ok;}
function send_sms($to,$msg){if(!SMS_URL||!$to)return false;return http(strtr(SMS_URL,['{to}'=>urlencode($to),'{msg}'=>urlencode($msg)]))!==false;}
function bk_on(){return BK_KEY&&BK_SECRET&&BK_USER&&BK_PASS;}
function bk($path,$body,$tok=null){$h=['Content-Type: application/json','Accept: application/json'];
 $h=array_merge($h,$tok?['Authorization: '.$tok,'X-APP-Key: '.BK_KEY]:['username: '.BK_USER,'password: '.BK_PASS]);return json_decode((string)http(BK_URL.$path,$h,json_encode($body)),true);}
function bk_token(){$r=bk('/tokenized/checkout/token/grant',['app_key'=>BK_KEY,'app_secret'=>BK_SECRET]);return $r['id_token']??null;}
function bk_create($amt,$inv,$cb){$t=bk_token();if(!$t)return null;$r=bk('/tokenized/checkout/create',['mode'=>'0011','payerReference'=>$inv,'callbackURL'=>$cb,'amount'=>number_format($amt,2,'.',''),'currency'=>'BDT','intent'=>'sale','merchantInvoiceNumber'=>$inv],$t);return ($r['statusCode']??'')=='0000'?$r:null;}
function bk_exec($pid){$t=bk_token();return $t?bk('/tokenized/checkout/execute',['paymentID'=>$pid],$t):null;}
