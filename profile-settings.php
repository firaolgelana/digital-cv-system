<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth(); // Require at least a valid user
$user = currentUser();

function p_initials(string $n): string {
    $p = explode(' ', trim($n));
    return strtoupper(substr($p[0], 0, 1) . (isset($p[1]) ? substr($p[1], 0, 1) : ''));
}
$initials = p_initials($user['full_name']);
$roleName = ucfirst($user['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile Settings — DigiCV</title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <script src="assets/js/auth-guard.js"></script>
</head>
<body>
  <div class="app-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-brand">
        <div class="sidebar-brand__icon"><i class="fas fa-qrcode"></i></div>
        <div>
          <div class="sidebar-brand__text text-gradient">DigiCV</div>
          <div class="sidebar-brand__sub">Digital CV System</div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-section-title">Main</div>
        <a href="<?= dashboardForRole($user['role']) ?>" class="sidebar-link">
          <span class="sidebar-link__icon"><i class="fas fa-house"></i></span> Dashboard
        </a>
        <div class="sidebar-section-title">Account</div>
        <a href="profile-settings.php" class="sidebar-link active">
          <span class="sidebar-link__icon"><i class="fas fa-user-gear"></i></span> Profile Settings
        </a>
        <a href="notifications.php" class="sidebar-link">
          <span class="sidebar-link__icon"><i class="fas fa-bell"></i></span> Notifications
        </a>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar avatar--primary" id="sidebar-avatar"><?= $initials ?></div>
          <div class="sidebar-user__info">
            <div class="sidebar-user__name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="sidebar-user__role"><?= $roleName ?></div>
          </div>
          <a href="php_actions/logout.php" title="Sign out" style="color:var(--text-muted)"><i class="fas fa-right-from-bracket"></i></a>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <header class="top-bar">
        <button class="btn btn-ghost btn-icon" id="menu-toggle"><i class="fas fa-bars"></i></button>
        <h2 class="top-bar__title">Profile Settings</h2>
        <div class="top-bar__actions">
          <button class="btn btn-ghost btn-icon" onclick="window.location.href='notifications.php'">
            <i class="fas fa-bell"></i>
          </button>
          <div class="avatar avatar--sm avatar--primary"><?= $initials ?></div>
        </div>
      </header>

      <div class="page-content">
        <div class="page-header animate-fade-in">
          <h1 class="page-header__title">Manage Account</h1>
          <p class="page-header__sub">Update your personal details, security settings, and profile picture.</p>
        </div>

        <div class="grid grid-3 gap-lg">
          <div class="flex flex-col gap-lg" style="grid-column: span 3;">
            <!-- Personal Info -->
            <div class="card animate-fade-in">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user" style="color:var(--primary-400);margin-right:8px"></i>Personal Information</h3>
              </div>
              <form id="profile-info-form">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-input" id="prof-name" value="<?= htmlspecialchars($user['full_name']) ?>" required />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Email Address (Read-only)</label>
                    <div style="position:relative;">
                      <input type="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" readonly style="background:var(--surface-elevated); color:var(--text-muted); cursor:not-allowed; padding-right:2.5rem;" />
                      <i class="fas fa-lock" style="position:absolute; right:1rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.8rem;"></i>
                    </div>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-input" id="prof-phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+251 ..." />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Role (Locked)</label>
                    <div style="position:relative;">
                      <input type="text" class="form-input" value="<?= $roleName ?>" readonly style="background:var(--surface-elevated); color:var(--text-muted); cursor:not-allowed; padding-right:2.5rem;" />
                      <i class="fas fa-lock" style="position:absolute; right:1rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.8rem;"></i>
                    </div>
                  </div>
                </div>
                <button type="submit" class="btn btn-primary" id="save-info-btn">Save Changes</button>
              </form>
            </div>

            <!-- Security -->
            <div class="card animate-fade-in delay-1">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-shield-halved" style="color:var(--accent-400);margin-right:8px"></i>Security Settings</h3>
              </div>
              <form id="profile-pass-form">
                <div class="form-group">
                  <label class="form-label">Current Password</label>
                  <div class="password-toggle-wrapper">
                    <input type="password" class="form-input" id="pass-current" required />
                    <button type="button" class="password-toggle-icon" tabindex="-1"><i class="fas fa-eye"></i></button>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">New Password</label>
                    <div class="password-toggle-wrapper">
                      <input type="password" class="form-input" id="pass-new" required />
                      <button type="button" class="password-toggle-icon" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <div class="password-toggle-wrapper">
                      <input type="password" class="form-input" id="pass-confirm" required />
                      <button type="button" class="password-toggle-icon" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                  </div>
                </div>
                <button type="submit" class="btn btn-secondary" id="save-pass-btn">Update Password</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <div id="toast" style="position:fixed; bottom:2rem; right:2rem; z-index:1000; display:none; padding:1rem; border-radius:var(--radius-md); font-size:0.9rem;"></div>

  <script src="assets/js/mobile-nav.js"></script>
  <script src="assets/js/password-toggle.js"></script>
  <script src="assets/js/profile-settings.js"></script>
</body>
</html>
