<?php require 'config.php';$u=me();$g=(int)($_GET['g']??0);
if($_POST){$u=need();chk();$a=$_POST['a'];$gid=(int)$_POST['g'];$mem=q('SELECT 1 FROM group_members WHERE group_id=? AND user_id=?',[$gid,$u['id']])->fetch();
 if($a=='join')q('INSERT IGNORE INTO group_members VALUES(?,?)',[$gid,$u['id']]);
 if($a=='leave')q('DELETE FROM group_members WHERE group_id=? AND user_id=?',[$gid,$u['id']]);
 if($a=='del'&&$u['role']=='admin')q('DELETE FROM group_posts WHERE id=?',[(int)$_POST['pid']]);if($a=='post'&&$mem&&trim($_POST['body'])!==''&&!preg_match('~https?://|www\.~i',$_POST['body']))q('INSERT INTO group_posts(group_id,user_id,body,photo) VALUES(?,?,?,?)',[$gid,$u['id'],mb_substr(trim($_POST['body']),0,500),upload($_FILES['photo']??[],['jpg','jpeg','png'])]);
 header("Location: groups.php?g=$gid");exit;}
head('Groups');
if($g){$grp=q('SELECT * FROM pgroups WHERE id=?',[$g])->fetch();$mem=$u&&q('SELECT 1 FROM group_members WHERE group_id=? AND user_id=?',[$g,$u['id']])->fetch();
 $ps=q('SELECT p.*,u.name FROM group_posts p JOIN users u ON u.id=p.user_id WHERE group_id=? ORDER BY p.id DESC',[$g])->fetchAll();?>
<h2><?=e($grp['name'])?></h2><a href="groups.php">← All groups</a>
<?php if($mem){?><form method="post" enctype="multipart/form-data" class="card"><?=tok()?><input type="hidden" name="g" value="<?=$g?>"><input type="hidden" name="a" value="post"><label>Share a travel update<textarea name="body" rows="2" required></textarea></label><label>Photo (optional, JPG or PNG, max 2 MB)<input type="file" name="photo" accept=".jpg,.jpeg,.png"></label><button class="btn">Post update</button></form><?php }else echo '<p class="card">Join this group to share updates.</p>';
 foreach($ps as $p)echo '<div class="card"><strong>'.e($p['name']).'</strong> <span class="muted">'.e($p['created_at']).'</span><p>'.e($p['body']).'</p>'.($p['photo']?'<img src="uploads/'.e($p['photo']).'" alt="Photo" style="max-width:260px;border-radius:10px">':'').(($u['role']??'')=='admin'?'<form method="post">'.tok().'<input type="hidden" name="g" value="'.$g.'"><input type="hidden" name="a" value="del"><input type="hidden" name="pid" value="'.$p['id'].'"><button class="btn sm ghost">Remove post</button></form>':'').'</div>';
}else{?><h2>Route groups</h2>
<?php $T=['local'=>'Local (inside city)','long'=>'Long distance (intercity)'];$f=$_GET['t']??'';
echo '<p><a class="btn sm ghost" href="groups.php">All</a> <a class="btn sm ghost" href="groups.php?t=local">Local</a> <a class="btn sm ghost" href="groups.php?t=long">Long distance</a></p>';
foreach($T as $k=>$label){if($f&&$f!=$k)continue;?>
<h3><?=e($label)?></h3><div class="grid"><?php foreach(q('SELECT g.*,(SELECT COUNT(*) FROM group_members m WHERE m.group_id=g.id) n FROM pgroups g WHERE g.kind=?',[$k])->fetchAll() as $r){$mem=$u&&q('SELECT 1 FROM group_members WHERE group_id=? AND user_id=?',[$r['id'],$u['id']])->fetch();?>
<div class="card"><h3><?=e($r['name'])?></h3><p class="muted"><?=$r['n']?> members</p><a class="btn ghost" href="groups.php?g=<?=$r['id']?>">Open</a>
<?php if($u){?><form method="post" style="display:inline"><?=tok()?><input type="hidden" name="g" value="<?=$r['id']?>"><input type="hidden" name="a" value="<?=$mem?'leave':'join'?>"><button class="btn"><?=$mem?'Leave':'Join'?></button></form><?php }?></div><?php }?></div><?php }}
foot();
