<?php require 'config.php';$u=need();$rows=q('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 30',[$u['id']])->fetchAll();q('UPDATE notifications SET seen=1 WHERE user_id=?',[$u['id']]);
head('Notifications');?><h2>Notifications</h2><?php if(!$rows)echo '<p class="card">No notifications yet. Booking, refund and cancellation updates appear here.</p>';
foreach($rows as $r)echo '<div class="card"><span class="muted">'.e($r['created_at']).'</span><br>'.e($r['msg']).'</div>';foot();
