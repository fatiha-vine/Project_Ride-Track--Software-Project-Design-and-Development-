<?php
/* Signed ticket code: stops anyone from forging a QR code. */
function tcode($id){
 $s=q("SELECT v FROM settings WHERE k='ticket_secret'")->fetchColumn();
 if(!$s){q("INSERT IGNORE INTO settings VALUES('ticket_secret',?)",[bin2hex(random_bytes(8))]);$s=q("SELECT v FROM settings WHERE k='ticket_secret'")->fetchColumn();}
 return substr(hash_hmac('sha256',(string)$id,$s),0,12);}
