/**
 * Notifications Badge Helper
 * Periodically checks for unread notifications and updates the UI dots.
 */
(async function() {
    async function checkNotifications() {
        try {
            const res = await fetch('php_actions/notification_action.php');
            const data = await res.json();
            
            if (data.success) {
                const unreadCount = data.unread_count || 0;
                const dots = document.querySelectorAll('#unread-dot');
                
                dots.forEach(dot => {
                    if (unreadCount > 0) {
                        dot.style.display = 'block';
                        // Optionally set title or text for count if dots are larger
                    } else {
                        dot.style.display = 'none';
                    }
                });
            }
        } catch (err) {
            console.error('Badge check failed', err);
        }
    }

    // Initial check
    checkNotifications();
    
    // Poll every 60 seconds
    setInterval(checkNotifications, 60000);
})();
