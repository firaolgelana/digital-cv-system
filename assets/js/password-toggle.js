/**
 * Password Visibility Toggle Helper
 * Attach toggle logic to password fields with the .password-toggle-icon button.
 */
document.addEventListener('DOMContentLoaded', () => {
    initPasswordToggles();
});

function initPasswordToggles() {
    const toggleButtons = document.querySelectorAll('.password-toggle-icon');
    
    toggleButtons.forEach(button => {
        // Prevent duplicate attaching if called multiple times
        if (button.dataset.initialized) return;
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Find the input in the same wrapper
            const container = this.closest('.password-toggle-wrapper');
            if (!container) return;
            
            const input = container.querySelector('input');
            if (!input) return;
            
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        button.dataset.initialized = "true";
    });
}

// Export for manual re-init (e.g. after dynamic content loading)
window.initPasswordToggles = initPasswordToggles;
