<?php
/* E-card helpers used by book.php, card.php, staff_card.php */
function cc_get($uid,$company,$lock=false){
  return q('SELECT * FROM company_cards WHERE user_id=? AND company=? LIMIT 1'.($lock?' FOR UPDATE':''),[$uid,$company])->fetch()?:null;
}
function cc_issue($uid,$company){
  for($i=0;$i<20;$i++){
    $no='';for($k=0;$k<10;$k++)$no.=random_int(0,9);
    if(!q('SELECT 1 FROM company_cards WHERE card_no=?',[$no])->fetch())break;
  }
  q('INSERT INTO company_cards(user_id,company,card_no) VALUES(?,?,?)',[$uid,$company,$no]);
  global $pdo;return (int)$pdo->lastInsertId();
}
function cc_credit($cardId,$amt,$type='recharge',$staffId=null){
  $c=q('SELECT user_id FROM company_cards WHERE id=?',[$cardId])->fetch();if(!$c)return false;
  q('UPDATE company_cards SET balance=balance+?,last_recharge=NOW() WHERE id=?',[$amt,$cardId]);
  q('INSERT INTO card_tx(user_id,amount,type,card_id,by_staff) VALUES(?,?,?,?,?)',[$c['user_id'],$amt,$type,$cardId,$staffId]);
  return true;
}
function cc_color($company){
  $p=[['#0f766e','#14b8a6'],['#1d4ed8','#60a5fa'],['#b91c1c','#f87171'],['#7c3aed','#a78bfa'],['#c2410c','#fb923c'],['#047857','#34d399']];
  return $p[abs(crc32($company))%count($p)];
}