
<?php
require_once 'config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$u = me();
$g = (int)($_GET['g'] ?? 0);
$filter = $_GET['t'] ?? '';

if (empty($_SESSION['group_csrf'])) {
    $_SESSION['group_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['group_csrf'];

function group_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = need();

    if (
        !isset($_POST['csrf']) ||
        !hash_equals($_SESSION['group_csrf'] ?? '', (string)$_POST['csrf'])
    ) {
        http_response_code(403);
        exit('Security token invalid. Please reload the page and try again.');
    }

    $action = $_POST['a'] ?? '';
    $gid = (int)($_POST['g'] ?? 0);

    $group = q('SELECT id FROM pgroups WHERE id=?', [$gid])->fetch();
    if (!$group) {
        exit('Group not found.');
    }

    if ($action === 'join') {
        q(
            'INSERT IGNORE INTO group_members (group_id,user_id) VALUES (?,?)',
            [$gid, $u['id']]
        );
    } elseif ($action === 'leave') {
        q(
            'DELETE FROM group_members WHERE group_id=? AND user_id=?',
            [$gid, $u['id']]
        );
    } elseif ($action === 'del' && ($u['role'] ?? '') === 'admin') {
        q(
            'DELETE FROM group_posts WHERE id=? AND group_id=?',
            [(int)($_POST['pid'] ?? 0), $gid]
        );
    } elseif ($action === 'post') {
        $member = q(
            'SELECT 1 FROM group_members WHERE group_id=? AND user_id=?',
            [$gid, $u['id']]
        )->fetch();

        $body = trim($_POST['body'] ?? '');

        if ($member && $body !== '' && !preg_match('~https?://|www\.~i', $body)) {
            q(
                'INSERT INTO group_posts (group_id,user_id,body,photo) VALUES (?,?,?,?)',
                [
                    $gid,
                    $u['id'],
                    mb_substr($body, 0, 500),
                    upload($_FILES['photo'] ?? [], ['jpg', 'jpeg', 'png'])
                ]
            );
        }
    }

    header('Location: groups.php' . ($gid ? '?g=' . $gid : ''));
    exit;
}

head('Groups');
?>

<style>
.group-chat {
    margin-top: 22px;
    padding: 22px;
    background: #fff;
    border: 1px solid #bce8f2;
    border-radius: 20px;
    box-shadow: 0 12px 30px rgba(15, 115, 140, .10);
}
.group-chat h3 {
    margin: 0 0 18px;
}
.chat-messages {
    height: 380px;
    overflow-y: auto;
    padding: 18px;
    margin-bottom: 18px;
    background: #f2fbfd;
    border-radius: 15px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.chat-message {
    align-self: flex-start;
    max-width: 78%;
    padding: 11px 15px;
    background: #fff;
    border: 1px solid #d8edf2;
    border-radius: 15px 15px 15px 4px;
    overflow-wrap: anywhere;
}
.chat-message.mine {
    align-self: flex-end;
    background: #d9f5e7;
    border-color: #b5e8cf;
    border-radius: 15px 15px 4px 15px;
}
.chat-author {
    display: block;
    font-weight: 700;
    margin-bottom: 5px;
    color: #126d86;
}
.chat-text {
    white-space: pre-wrap;
}
.chat-time {
    display: block;
    margin-top: 7px;
    font-size: 12px;
    color: #6a7f87;
}
.chat-form {
    display: flex;
    align-items: flex-end;
    gap: 10px;
}
.chat-form textarea {
    flex: 1;
    min-width: 0;
    resize: vertical;
    min-height: 54px;
    max-height: 150px;
    padding: 14px;
    border: 1px solid #b8e1eb;
    border-radius: 13px;
    font: inherit;
    box-sizing: border-box;
}
.chat-form button {
    flex-shrink: 0;
}
.chat-status {
    display: block;
    margin-top: 8px;
    color: #607d8b;
    font-size: 13px;
}
.group-post {
    margin-top: 14px;
    padding: 16px;
}
@media (max-width: 600px) {
    .group-chat { padding: 12px; }
    .chat-messages { height: 320px; padding: 10px; }
    .chat-message { max-width: 90%; }
    .chat-form { flex-direction: column; align-items: stretch; }
    .chat-form button { align-self: flex-end; }
}
</style>

<?php if ($g): ?>

<?php
$grp = q('SELECT * FROM pgroups WHERE id=?', [$g])->fetch();

if (!$grp) {
    echo '<div class="card">Group not found. <a href="groups.php">Back to groups</a></div>';
    foot();
    exit;
}

$mem = $u && q(
    'SELECT 1 FROM group_members WHERE group_id=? AND user_id=?',
    [$g, $u['id']]
)->fetch();

$posts = q(
    'SELECT p.*,u.name FROM group_posts p
     JOIN users u ON u.id=p.user_id
     WHERE p.group_id=?
     ORDER BY p.id DESC LIMIT 100',
    [$g]
)->fetchAll();
?>

<h2><?= e($grp['name']) ?></h2>
<p><a href="groups.php">← All groups</a></p>

<?php if ($u): ?>
    <form method="post" class="card" style="margin-bottom:18px">
        <input type="hidden" name="csrf" value="<?= group_h($csrf) ?>">
        <input type="hidden" name="g" value="<?= $g ?>">
        <input type="hidden" name="a" value="<?= $mem ? 'leave' : 'join' ?>">
        <button type="submit" class="btn">
            <?= $mem ? 'Leave group' : 'Join group' ?>
        </button>
    </form>
<?php endif; ?>

<?php if ($mem): ?>

<div class="card group-chat">
    <h3>💬 Group Chat</h3>

    <div
        id="chatMessages"
        class="chat-messages"
        data-group="<?= $g ?>"
        data-user="<?= (int)$u['id'] ?>"
        data-csrf="<?= group_h($csrf) ?>"
        aria-live="polite"
    >Loading messages...</div>

    <form id="chatForm" class="chat-form">
        <input type="hidden" name="g" value="<?= $g ?>">
        <input type="hidden" name="csrf" value="<?= group_h($csrf) ?>">

        <textarea
            name="body"
            placeholder="Write a message..."
            maxlength="500"
            rows="2"
            required
        ></textarea>

        <button type="submit" class="btn" id="chatSend">Send</button>
    </form>

    <span id="chatStatus" class="chat-status"></span>
</div>

<script>
(() => {
    const messagesBox = document.getElementById('chatMessages');
    const form = document.getElementById('chatForm');
    const status = document.getElementById('chatStatus');
    const sendButton = document.getElementById('chatSend');

    if (!messagesBox || !form) return;

    const groupId = messagesBox.dataset.group;
    const userId = Number(messagesBox.dataset.user);
    const csrf = messagesBox.dataset.csrf;
    let newestId = 0;
    let loading = false;

    function addMessage(message) {
        const item = document.createElement('div');
        item.className = 'chat-message' +
            (Number(message.user_id) === userId ? ' mine' : '');

        const author = document.createElement('span');
        author.className = 'chat-author';
        author.textContent = message.name || 'User';

        const body = document.createElement('div');
        body.className = 'chat-text';
        body.textContent = message.body || '';

        const time = document.createElement('span');
        time.className = 'chat-time';
        time.textContent = message.created_at || '';

        item.append(author, body, time);
        messagesBox.appendChild(item);
    }

    async function loadMessages(force = false) {
        if (loading) return;
        loading = true;

        try {
            const response = await fetch(
                'group_chat.php?g=' + encodeURIComponent(groupId) +
                '&_=' + Date.now(),
                { cache: 'no-store', credentials: 'same-origin' }
            );

            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.error || 'Could not load messages.');
            }

            const messages = data.messages || [];
            const latest = messages.length
                ? Number(messages[messages.length - 1].id)
                : 0;

            if (force || latest !== newestId) {
                messagesBox.replaceChildren();

                if (messages.length === 0) {
                    const empty = document.createElement('div');
                    empty.textContent = 'No messages yet. Start the conversation!';
                    messagesBox.appendChild(empty);
                } else {
                    messages.forEach(addMessage);
                }

                newestId = latest;
                messagesBox.scrollTop = messagesBox.scrollHeight;
            }

            status.textContent = '';
        } catch (error) {
            status.textContent = error.message || 'Chat connection error.';
        } finally {
            loading = false;
        }
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const textarea = form.querySelector('textarea[name="body"]');
        const body = textarea.value.trim();
        if (!body) return;

        sendButton.disabled = true;
        status.textContent = 'Sending...';

        try {
            const formData = new FormData(form);
            formData.set('csrf', csrf);

            const response = await fetch('group_chat.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.error || 'Message could not be sent.');
            }

            textarea.value = '';
            await loadMessages(true);
            status.textContent = 'Message sent.';
        } catch (error) {
            status.textContent = error.message || 'Send failed. Try again.';
        } finally {
            sendButton.disabled = false;
            textarea.focus();
        }
    });

    loadMessages(true);
    setInterval(() => loadMessages(false), 2500);
})();
</script>

<?php else: ?>

<div class="card">
    <p>Join this group to see the chat and send messages.</p>
</div>

<?php endif; ?>

<h3>Previous group updates</h3>

<?php if (!$posts): ?>
    <div class="card">No previous updates yet.</div>
<?php else: ?>
    <?php foreach ($posts as $p): ?>
        <div class="card group-post">
            <strong><?= e($p['name']) ?></strong>
            <p><?= nl2br(e($p['body'])) ?></p>

            <?php if (!empty($p['photo'])): ?>
                <p>
                    <img
                        src="uploads/<?= rawurlencode($p['photo']) ?>"
                        alt="Post photo"
                        style="max-width:260px;width:100%;border-radius:10px"
                    >
                </p>
            <?php endif; ?>

            <small class="muted"><?= e($p['created_at'] ?? '') ?></small>

            <?php if (($u['role'] ?? '') === 'admin'): ?>
                <form method="post" style="margin-top:10px">
                    <input type="hidden" name="csrf" value="<?= group_h($csrf) ?>">
                    <input type="hidden" name="g" value="<?= $g ?>">
                    <input type="hidden" name="a" value="del">
                    <input type="hidden" name="pid" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn sm ghost">Remove post</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php else: ?>

<h2>Route groups</h2>

<p>
    <a class="btn sm ghost" href="groups.php">All</a>
    <a class="btn sm ghost" href="groups.php?t=local">Local</a>
    <a class="btn sm ghost" href="groups.php?t=long">Long distance</a>
</p>

<?php
$types = [
    'local' => 'Local (inside city)',
    'long' => 'Long distance (intercity)'
];

foreach ($types as $kind => $label):
    if ($filter && $filter !== $kind) continue;

    $groups = q(
        'SELECT g.*,
            (SELECT COUNT(*) FROM group_members m WHERE m.group_id=g.id) AS n
         FROM pgroups g WHERE g.kind=? ORDER BY g.id DESC',
        [$kind]
    )->fetchAll();
?>

<h3><?= e($label) ?></h3>

<div class="grid">
    <?php foreach ($groups as $r):
        $joined = $u && q(
            'SELECT 1 FROM group_members WHERE group_id=? AND user_id=?',
            [$r['id'], $u['id']]
        )->fetch();
    ?>
        <div class="card">
            <h3><?= e($r['name']) ?></h3>
            <p class="muted"><?= (int)$r['n'] ?> members</p>

            <p>
                <a class="btn ghost" href="groups.php?g=<?= (int)$r['id'] ?>">
                    Open group
                </a>
            </p>

            <?php if ($u): ?>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= group_h($csrf) ?>">
                    <input type="hidden" name="g" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="a" value="<?= $joined ? 'leave' : 'join' ?>">
                    <button type="submit" class="btn">
                        <?= $joined ? 'Leave' : 'Join' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php endforeach; ?>

<?php endif; ?>

<?php foot(); ?>
