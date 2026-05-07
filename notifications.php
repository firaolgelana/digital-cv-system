<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();
$user = currentUser();

function n_initials(string $n): string {
    $p = explode(' ', trim($n));
    return strtoupper(substr($p[0], 0, 1) . (isset($p[1]) ? substr($p[1], 0, 1) : ''));
}
$initials = n_initials($user['full_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Notifications — DigiCV</title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <script src="assets/js/auth-guard.js"></script>
  <style>
    .notif-item {
        padding: 1.25rem;
        border-radius: var(--radius-lg);
        background: var(--surface-card);
        border: 1px solid var(--surface-border);
        display: flex;
        gap: var(--space-md);
        transition: all var(--transition-base);
        cursor: pointer;
        position: relative;
    }
    .notif-item:hover {
        background: var(--surface-card-hover);
        border-color: var(--primary-400);
        transform: translateX(4px);
    }
    .notif-item.unread {
        border-left: 4px solid var(--primary-500);
        background: rgba(99,102,241,0.05);
    }
    .notif-item__icon {
        width: 40px;
        height: 40px;
        background: var(--surface-elevated);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: var(--primary-400);
    }
    .notif-item__content { flex: 1; }
    .notif-item__title { font-weight: 600; font-size: 0.95rem; margin-bottom: 2px; color: var(--text-primary); }
    .notif-item__desc { font-size: 0.88rem; color: var(--text-secondary); line-height: 1.5; }
    .notif-item__meta { font-size: 0.75rem; color: var(--text-muted); margin-top: 8px; display: flex; align-items: center; gap: 4px; }
    .notif-item__actions { display: flex; gap: 8px; opacity: 0; transition: opacity .2s; }
    .notif-item:hover .notif-item__actions { opacity: 1; }
  </style>
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
        <?php if ($user['role'] === 'student'): ?>
          <a href="create-cv.html" class="sidebar-link">
            <span class="sidebar-link__icon"><i class="fas fa-file-pen"></i></span> Create CV
          </a>
          <a href="my-resumes.html" class="sidebar-link">
            <span class="sidebar-link__icon"><i class="fas fa-file-lines"></i></span> My Resumes
          </a>
        <?php endif; ?>
        <div class="sidebar-section-title">Account</div>
        <a href="profile-settings.php" class="sidebar-link">
          <span class="sidebar-link__icon"><i class="fas fa-user-gear"></i></span> Profile Settings
        </a>
        <a href="notifications.php" class="sidebar-link active">
          <span class="sidebar-link__icon"><i class="fas fa-bell"></i></span> Notifications
        </a>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar avatar--primary" id="sidebar-avatar"><?= $initials ?></div>
          <div class="sidebar-user__info">
            <div class="sidebar-user__name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="sidebar-user__role"><?= ucfirst($user['role']) ?></div>
          </div>
          <a href="php_actions/logout.php" title="Sign out" style="color:var(--text-muted)"><i class="fas fa-right-from-bracket"></i></a>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <header class="top-bar">
        <button class="btn btn-ghost btn-icon" id="menu-toggle"><i class="fas fa-bars"></i></button>
        <h2 class="top-bar__title">Notifications</h2>
        <div class="top-bar__actions">
          <div class="avatar avatar--sm avatar--primary"><?= $initials ?></div>
        </div>
      </header>

      <div class="page-content">
        <div class="page-header animate-fade-in" style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:1rem;">
          <div>
            <h1 class="page-header__title">Notification Center</h1>
            <p class="page-header__sub">Stay updated with your CV status, reviews, and system alerts.</p>
          </div>
          <div class="flex gap-sm">
            <button class="btn btn-secondary btn-sm" id="mark-all-read-btn"><i class="fas fa-check-double"></i> Mark all as read</button>
            <button class="btn btn-ghost btn-sm" id="clear-all-notif-btn" style="color:var(--danger);"><i class="fas fa-trash"></i> Clear all</button>
          </div>
        </div>

        <div id="notifications-list" class="flex flex-col gap-md animate-fade-in delay-1">
          <div class="flex justify-center p-xl" id="notif-loader">
            <i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary-400);"></i>
          </div>
        </div>

        <div id="notif-empty-state" style="display:none;" class="card animate-fade-in">
           <div class="empty-state">
             <div class="empty-state__icon"><i class="fas fa-bell-slash"></i></div>
             <h3 class="empty-state__title">All caught up!</h3>
             <p class="empty-state__desc">You don't have any new notifications at the moment.</p>
           </div>
        </div>
      </div>
    </main>
  </div>

  <script src="assets/js/mobile-nav.js"></script>
  <script src="assets/js/notifications.js"></script>
</body>
</html>
