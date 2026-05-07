/**
 * Auto-Redirect Helper
 * Redirects logged-in users away from auth pages (login/register) to their dashboards.
 */
(async function() {
    try {
        const res = await fetch('php_actions/check_auth.php', {
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        });
        const data = await res.json();
        
        if (data.success && data.user && data.user.role) {
            const role = data.user.role;
            let target = 'index.html';
            
            switch(role) {
                case 'student':    target = 'student-dashboard.html'; break;
                case 'supervisor': 
                case 'examiner':   target = 'supervisor-dashboard.php'; break;
                case 'recruiter':  target = 'recruiter-view.html'; break;
                case 'admin':      target = 'admin-dashboard.php'; break;
                default:           target = 'index.html';
            }
            
            // Only redirect if we are not already at the target
            if (!window.location.pathname.includes(target)) {
                window.location.href = target;
            }
        }
    } catch (err) {
        // Silently fail, let the user stay on the login page if check fails
    }
})();
