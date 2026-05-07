/**
 * Notifications JS
 */
document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('notifications-list');
    const emptyState = document.getElementById('notif-empty-state');
    const markAllBtn = document.getElementById('mark-all-read-btn');
    const clearAllBtn = document.getElementById('clear-all-notif-btn');

    async function loadNotifications() {
        try {
            const res = await fetch('php_actions/notification_action.php');
            const data = await res.json();
            
            if (data.success) {
                renderNotifications(data.notifications);
            }
        } catch (err) {
            console.error('Failed to load notifications', err);
        }
    }

    function renderNotifications(notifs) {
        document.getElementById('notif-loader').style.display = 'none';
        
        if (!notifs || notifs.length === 0) {
            list.innerHTML = '';
            emptyState.style.display = 'block';
            return;
        }

        emptyState.style.display = 'none';
        list.innerHTML = notifs.map(n => `
            <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}" data-id="${n.id}">
                <div class="notif-item__icon">
                    <i class="fas ${getIconForTitle(n.title)}"></i>
                </div>
                <div class="notif-item__content">
                    <div class="notif-item__title">${n.title}</div>
                    <div class="notif-item__desc">${n.message}</div>
                    <div class="notif-item__meta">
                        <i class="far fa-clock"></i> ${formatTime(n.created_at)}
                    </div>
                </div>
                <div class="notif-item__actions">
                    ${n.is_read == 0 ? `<button class="btn btn-ghost btn-icon mark-read" title="Mark as read"><i class="fas fa-check"></i></button>` : ''}
                    <button class="btn btn-ghost btn-icon delete-notif" style="color:var(--danger)" title="Delete"><i class="fas fa-trash-can"></i></button>
                </div>
            </div>
        `).join('');

        // Attach listeners
        list.querySelectorAll('.notif-item').forEach(item => {
            const id = item.dataset.id;
            
            item.querySelector('.mark-read')?.addEventListener('click', async (e) => {
                e.stopPropagation();
                await actionNotification('mark_read', id);
                loadNotifications();
            });

            item.querySelector('.delete-notif').addEventListener('click', async (e) => {
                e.stopPropagation();
                await actionNotification('delete', id);
                loadNotifications();
            });

            item.addEventListener('click', async () => {
                if (item.classList.contains('unread')) {
                    await actionNotification('mark_read', id);
                    loadNotifications();
                }
            });
        });
    }

    async function actionNotification(action, id) {
        try {
            await fetch('php_actions/notification_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, id })
            });
        } catch (err) {
            console.error('Notification action failed', err);
        }
    }

    markAllBtn.addEventListener('click', async () => {
        await actionNotification('mark_read', 0);
        loadNotifications();
    });

    clearAllBtn.addEventListener('click', async () => {
        if (confirm('Are you sure you want to delete all notifications?')) {
            await actionNotification('delete', 0);
            loadNotifications();
        }
    });

    function getIconForTitle(title) {
        const t = title.toLowerCase();
        if (t.includes('cv')) return 'fa-file-lines';
        if (t.includes('password')) return 'fa-shield-halved';
        if (t.includes('profile')) return 'fa-user';
        if (t.includes('account')) return 'fa-user-check';
        return 'fa-circle-info';
    }

    function formatTime(dateStr) {
        const date = new Date(dateStr);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);

        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return date.toLocaleDateString();
    }

    loadNotifications();
});
