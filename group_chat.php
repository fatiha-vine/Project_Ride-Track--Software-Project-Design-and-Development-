
<?php
require_once 'config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function chat_response($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $user = me();

    if (!$user) {
        chat_response(401, [
            'ok' => false,
            'error' => 'Please log in to use the group chat.'
        ]);
    }

    $groupId = (int)($_POST['g'] ?? $_GET['g'] ?? 0);

    if ($groupId < 1) {
        chat_response(400, [
            'ok' => false,
            'error' => 'Invalid group ID.'
        ]);
    }

    $member = q(
        'SELECT 1 FROM group_members WHERE group_id=? AND user_id=?',
        [$groupId, $user['id']]
    )->fetch();

    if (!$member) {
        chat_response(403, [
            'ok' => false,
            'error' => 'Join this group before using its chat.'
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sessionToken = $_SESSION['group_csrf'] ?? '';
        $postedToken = $_POST['csrf'] ?? '';

        if (
            !$sessionToken ||
            !$postedToken ||
            !hash_equals($sessionToken, $postedToken)
        ) {
            chat_response(403, [
                'ok' => false,
                'error' => 'Security token expired. Reload the group page.'
            ]);
        }

        $body = trim($_POST['body'] ?? '');

        if ($body === '') {
            chat_response(422, [
                'ok' => false,
                'error' => 'Please type a message.'
            ]);
        }

        if (mb_strlen($body) > 500) {
            chat_response(422, [
                'ok' => false,
                'error' => 'Messages can contain up to 500 characters.'
            ]);
        }

        q(
            'INSERT INTO group_posts (group_id,user_id,body,photo)
             VALUES (?,?,?,?)',
            [$groupId, $user['id'], $body, null]
        );

        chat_response(200, ['ok' => true]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        chat_response(405, [
            'ok' => false,
            'error' => 'Method not allowed.'
        ]);
    }

    $messages = q(
        'SELECT p.id, p.user_id, p.body, p.created_at, u.name
         FROM group_posts p
         JOIN users u ON u.id=p.user_id
         WHERE p.group_id=?
         ORDER BY p.id DESC
         LIMIT 100',
        [$groupId]
    )->fetchAll();

    $messages = array_reverse($messages);

    chat_response(200, [
        'ok' => true,
        'messages' => $messages
    ]);

} catch (Throwable $e) {
    error_log('Group chat error: ' . $e->getMessage());

    chat_response(500, [
        'ok' => false,
        'error' => 'Chat server error. Check your PHP error log.'
    ]);
}
