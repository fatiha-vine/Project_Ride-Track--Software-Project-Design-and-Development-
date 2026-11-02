
(() => {
    const box = document.getElementById('chatMessages');
    const form = document.getElementById('chatForm');

    if (!box || !form) return;

    const groupId = box.dataset.group;
    const currentUserId = Number(box.dataset.user);
    const textarea = form.querySelector('textarea');
    const sendButton = form.querySelector('button[type="submit"]');
    const status = document.getElementById('chatStatus');

    if (!groupId || !textarea || !sendButton) {
        console.error('Group chat HTML is missing required attributes.');
        return;
    }

    let isLoading = false;
    let isSending = false;
    let lastMessageId = 0;

    function showStatus(message) {
        if (status) status.textContent = message;
    }

    async function loadMessages() {
        if (isLoading || document.hidden) return;

        isLoading = true;

        try {
            const response = await fetch(
                'group_chat.php?g=' + encodeURIComponent(groupId),
                {
                    cache: 'no-store',
                    headers: { Accept: 'application/json' }
                }
            );

            const data = await response.json();

            if (!response.ok || !data.ok) {
                throw new Error(data.error || 'Could not load messages.');
            }

            const messages = data.messages || [];
            const newestId = messages.reduce(
                (max, message) => Math.max(max, Number(message.id) || 0),
                0
            );

            if (newestId === lastMessageId) return;

            const wasNearBottom =
                box.scrollHeight - box.scrollTop - box.clientHeight < 100;

            const fragment = document.createDocumentFragment();

            messages.forEach(message => {
                const item = document.createElement('div');

                item.className =
                    Number(message.user_id) === currentUserId
                        ? 'chat-message mine'
                        : 'chat-message';

                const name = document.createElement('strong');
                name.textContent = message.name || 'Member';

                const body = document.createElement('p');
                body.textContent = message.body || '';

                const time = document.createElement('small');
                time.textContent = message.created_at || '';

                item.append(name, body, time);
                fragment.appendChild(item);
            });

            box.replaceChildren(fragment);
            lastMessageId = newestId;

            if (wasNearBottom) {
                box.scrollTop = box.scrollHeight;
            }

            showStatus('');
        } catch (error) {
            showStatus(error.message || 'Chat connection failed.');
        } finally {
            isLoading = false;
        }
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();

        if (isSending || !textarea.value.trim()) return;

        isSending = true;
        sendButton.disabled = true;
        showStatus('Sending...');

        try {
            const formData = new FormData(form);

            const response = await fetch('group_chat.php', {
                method: 'POST',
                body: formData,
                headers: { Accept: 'application/json' }
            });

            const data = await response.json();

            if (!response.ok || !data.ok) {
                throw new Error(data.error || 'Could not send message.');
            }

            textarea.value = '';
            await loadMessages();

            box.scrollTop = box.scrollHeight;
            showStatus('');
            textarea.focus();
        } catch (error) {
            showStatus(error.message || 'Message sending failed.');
        } finally {
            isSending = false;
            sendButton.disabled = false;
        }
    });

    loadMessages();
    setInterval(loadMessages, 3000);
})();
