/**
 * DigiCV — Auth Guard
 * Verifies session with the server and redirects to login if unauthenticated.
 */
(function () {
  'use strict';

  fetch('php_actions/check_auth.php')
    .then(response => {
      if (!response.ok) throw new Error('Network error');
      return response.json();
    })
    .then(data => {
      if (!data.success) {
        // Clear local session data
        if (window.UserSession) {
          window.UserSession.clearUser();
        } else {
          window.localStorage.removeItem("digicv_current_user");
        }
        // Redirect to login
        window.location.href = 'index.html';
      } else if (window.UserSession && data.user) {
        // Sync local session
        window.UserSession.saveUser(data.user);
      }
    })
    .catch(error => {
      console.error('Auth check failed:', error);
    });
})();
