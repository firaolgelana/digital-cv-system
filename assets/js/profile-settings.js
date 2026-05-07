/**
 * Profile Settings JS
 */
document.addEventListener('DOMContentLoaded', () => {
    const infoForm = document.getElementById('profile-info-form');
    const passForm = document.getElementById('profile-pass-form');

    // 1. Update Personal Info
    infoForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('save-info-btn');
        const originalText = btn.textContent;
        setLoading(btn, true);

        const payload = {
            action: 'update_info',
            full_name: document.getElementById('prof-name').value.trim(),
            phone: document.getElementById('prof-phone').value.trim()
        };

        try {
            const res = await fetch('php_actions/profile_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            showToast(data.message, data.success);
            
            if (data.success) {
                // Update sidebar/header names if needed
                const names = document.querySelectorAll('.sidebar-user__name, .top-bar__title');
                // Note: top-bar title might be "Profile Settings", be careful.
                document.querySelectorAll('.sidebar-user__name').forEach(el => el.textContent = payload.full_name);
            }
        } catch (err) {
            showToast('Network error.', false);
        } finally {
            setLoading(btn, false, originalText);
        }
    });

    // 2. Update Password
    passForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('save-pass-btn');
        const originalText = btn.textContent;
        
        const newPass = document.getElementById('pass-new').value;
        const confirmPass = document.getElementById('pass-confirm').value;

        if (newPass !== confirmPass) {
            showToast('New passwords do not match.', false);
            return;
        }

        setLoading(btn, true);

        const payload = {
            action: 'update_password',
            current_password: document.getElementById('pass-current').value,
            new_password: newPass,
            confirm_password: confirmPass
        };

        try {
            const res = await fetch('php_actions/profile_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            showToast(data.message, data.success);
            if (data.success) passForm.reset();
        } catch (err) {
            showToast('Network error.', false);
        } finally {
            setLoading(btn, false, originalText);
        }
    });
});

function setLoading(btn, loading, text = '') {
    btn.disabled = loading;
    if (loading) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    } else {
        btn.textContent = text;
    }
}

function showToast(msg, success) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.style.display = 'block';
    toast.style.background = success ? 'rgba(34,197,94,0.9)' : 'rgba(239,68,68,0.9)';
    toast.style.color = '#fff';
    setTimeout(() => toast.style.display = 'none', 3000);
}
