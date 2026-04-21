<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

if ($_SESSION['role'] !== 'student') {
    header('Location: index.html');
    exit;
}

$fullName = $_SESSION['full_name'] ?? 'Student User';
$email = $_SESSION['email'] ?? '';
$parts = explode(' ', trim($fullName));
$firstName = $parts[0] ?: 'Student';
$initials = strtoupper(substr($firstName, 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Dashboard — DigiCV</title>
  <meta name="description" content="Manage your digital CV, track approval status, and share your QR code with recruiters." />
  <link rel="stylesheet" href="styles.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="app-layout">
    <!-- ======= Sidebar ======= -->
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
        <a href="student-dashboard.php" class="sidebar-link active">
          <span class="sidebar-link__icon"><i class="fas fa-house"></i></span>
          Dashboard
        </a>
        <a href="create-cv.html" class="sidebar-link">
          <span class="sidebar-link__icon"><i class="fas fa-file-pen"></i></span>
          Create CV
        </a>
        <a href="cv-preview.html" class="sidebar-link">
          <span class="sidebar-link__icon"><i class="fas fa-eye"></i></span>
          View CV
        </a>
        <a href="qr-code.html" class="sidebar-link">
          <span class="sidebar-link__icon"><i class="fas fa-qrcode"></i></span>
          QR Code
          <span class="sidebar-link__badge">New</span>
        </a>

        <div class="sidebar-section-title">Account</div>
        <a href="#" class="sidebar-link">
          <span class="sidebar-link__icon"><i class="fas fa-user-gear"></i></span>
          Profile Settings
        </a>
        <a href="#" class="sidebar-link">
          <span class="sidebar-link__icon"><i class="fas fa-bell"></i></span>
          Notifications
          <span class="sidebar-link__badge">3</span>
        </a>
        <a href="#" class="sidebar-link">
          <span class="sidebar-link__icon"><i class="fas fa-cloud-arrow-up"></i></span>
          Documents
        </a>
      </nav>

      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar avatar--primary"><?= htmlspecialchars($initials) ?></div>
          <div class="sidebar-user__info">
            <div class="sidebar-user__name"><?= htmlspecialchars($fullName) ?></div>
            <div class="sidebar-user__role"><?= htmlspecialchars($email) ?></div>
          </div>
          <a href="php_actions/logout.php" title="Sign out" style="color: var(--text-muted); font-size: 1rem;">
            <i class="fas fa-right-from-bracket"></i>
          </a>
        </div>
      </div>
    </aside>

    <!-- ======= Main Content ======= -->
    <main class="main-content">
      <!-- Top Bar -->
      <header class="top-bar">
        <button class="btn btn-ghost btn-icon" id="menu-toggle" style="display:none;">
          <i class="fas fa-bars"></i>
        </button>
        <h2 class="top-bar__title">Dashboard</h2>
        <div class="top-bar__actions">
          <div class="search-box" style="width:240px;">
            <span class="search-icon"><i class="fas fa-search"></i></span>
            <input type="text" class="form-input" placeholder="Search…" id="search-input" />
          </div>
          <button class="btn btn-ghost btn-icon notification-btn">
            <i class="fas fa-bell"></i>
            <span class="notification-dot"></span>
          </button>
          <div class="avatar avatar--sm avatar--primary"><?= htmlspecialchars($initials) ?></div>
        </div>
      </header>

      <div class="page-content">
        <!-- Page Header -->
        <div class="page-header animate-fade-in">
          <h1 class="page-header__title">Good morning, <?= htmlspecialchars($firstName) ?> 👋</h1>
          <p class="page-header__sub">Here's an overview of your CV progress and activity.</p>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-4 gap-lg" style="margin-bottom: var(--space-2xl);">
          <div class="stat-card animate-fade-in delay-1">
            <div class="stat-icon stat-icon--primary"><i class="fas fa-file-lines"></i></div>
            <div class="stat-value">1</div>
            <div class="stat-label">CV Created</div>
          </div>
          <div class="stat-card animate-fade-in delay-2">
            <div class="stat-icon stat-icon--warning"><i class="fas fa-clock"></i></div>
            <div class="stat-value">Pending</div>
            <div class="stat-label">Approval Status</div>
          </div>
          <div class="stat-card animate-fade-in delay-3">
            <div class="stat-icon stat-icon--accent"><i class="fas fa-qrcode"></i></div>
            <div class="stat-value">—</div>
            <div class="stat-label">QR Code Scans</div>
          </div>
          <div class="stat-card animate-fade-in delay-4">
            <div class="stat-icon stat-icon--info"><i class="fas fa-eye"></i></div>
            <div class="stat-value">0</div>
            <div class="stat-label">Profile Views</div>
          </div>
        </div>

        <!-- Two Column -->
        <div class="grid grid-2 gap-lg">
          <!-- CV Status Card -->
          <div class="card animate-fade-in delay-2">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-file-circle-check" style="color: var(--primary-400); margin-right: 8px;"></i>CV Status</h3>
              <span class="badge badge--pending"><i class="fas fa-hourglass-half"></i> Pending Review</span>
            </div>

            <div style="margin-bottom: var(--space-lg);">
              <div class="flex justify-between items-center" style="margin-bottom: 6px;">
                <span style="font-size: 0.85rem; color: var(--text-secondary);">Completion</span>
                <span style="font-size: 0.85rem; font-weight: 600;">85%</span>
              </div>
              <div class="progress-bar">
                <div class="progress-bar__fill" style="width: 85%;"></div>
              </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: var(--space-md);">
              <div class="flex items-center gap-md">
                <i class="fas fa-circle-check" style="color: var(--success);"></i>
                <span style="font-size: 0.9rem;">Personal Information</span>
              </div>
              <div class="flex items-center gap-md">
                <i class="fas fa-circle-check" style="color: var(--success);"></i>
                <span style="font-size: 0.9rem;">Education</span>
              </div>
              <div class="flex items-center gap-md">
                <i class="fas fa-circle-check" style="color: var(--success);"></i>
                <span style="font-size: 0.9rem;">Skills & Experience</span>
              </div>
              <div class="flex items-center gap-md">
                <i class="fas fa-circle" style="color: var(--gray-600); font-size: 0.65rem; margin: 0 3px;"></i>
                <span style="font-size: 0.9rem; color: var(--text-secondary);">Certificates & Portfolio</span>
              </div>
            </div>

            <div style="margin-top: var(--space-xl); display: flex; gap: var(--space-md);">
              <a href="create-cv.html" class="btn btn-primary btn-sm">
                <i class="fas fa-pen"></i> Edit CV
              </a>
              <a href="cv-preview.html" class="btn btn-secondary btn-sm">
                <i class="fas fa-eye"></i> Preview
              </a>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="card animate-fade-in delay-3">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-clock-rotate-left" style="color: var(--accent-400); margin-right: 8px;"></i>Recent Activity</h3>
              <a href="#" style="font-size: 0.82rem;">View all</a>
            </div>

            <div class="timeline">
              <div class="timeline-item">
                <div class="timeline-item__dot timeline-item__dot--warning"></div>
                <div class="timeline-item__content">CV submitted for supervisor review</div>
                <div class="timeline-item__time">2 hours ago</div>
              </div>
              <div class="timeline-item">
                <div class="timeline-item__dot"></div>
                <div class="timeline-item__content">Updated Skills & Experience section</div>
                <div class="timeline-item__time">5 hours ago</div>
              </div>
              <div class="timeline-item">
                <div class="timeline-item__dot timeline-item__dot--success"></div>
                <div class="timeline-item__content">Added Education details</div>
                <div class="timeline-item__time">Yesterday</div>
              </div>
              <div class="timeline-item">
                <div class="timeline-item__dot"></div>
                <div class="timeline-item__content">Profile created successfully</div>
                <div class="timeline-item__time">2 days ago</div>
              </div>
              <div class="timeline-item">
                <div class="timeline-item__dot timeline-item__dot--success"></div>
                <div class="timeline-item__content">Account registered</div>
                <div class="timeline-item__time">3 days ago</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card" style="margin-top: var(--space-xl);">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-bolt" style="color: var(--warning); margin-right: 8px;"></i>Quick Actions</h3>
          </div>
          <div class="flex gap-md flex-wrap">
            <a href="create-cv.html" class="btn btn-outline">
              <i class="fas fa-plus"></i> Create New CV
            </a>
            <a href="cv-preview.html" class="btn btn-outline">
              <i class="fas fa-file-pdf"></i> Download PDF
            </a>
            <a href="qr-code.html" class="btn btn-outline">
              <i class="fas fa-share-nodes"></i> Share QR Code
            </a>
            <a href="#" class="btn btn-outline">
              <i class="fas fa-cloud-arrow-up"></i> Upload Documents
            </a>
          </div>
        </div>

        <!-- Notifications -->
        <div class="card" style="margin-top: var(--space-xl);">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-bell" style="color: var(--info); margin-right: 8px;"></i>Notifications</h3>
            <span class="badge badge--info">3 new</span>
          </div>
          <div style="display: flex; flex-direction: column; gap: var(--space-md);">
            <div class="flex items-center gap-md" style="padding: var(--space-md); background: rgba(59,130,246,.06); border-radius: var(--radius-md);">
              <div class="avatar avatar--sm" style="background: rgba(59,130,246,.15); color: var(--info);">
                <i class="fas fa-info-circle"></i>
              </div>
              <div style="flex:1;">
                <div style="font-size: 0.9rem; font-weight: 500;">Your CV is under review</div>
                <div style="font-size: 0.78rem; color: var(--text-muted);">Your supervisor is reviewing your submitted CV.</div>
              </div>
              <span style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap;">2h ago</span>
            </div>
            <div class="flex items-center gap-md" style="padding: var(--space-md); border-radius: var(--radius-md);">
              <div class="avatar avatar--sm" style="background: rgba(245,158,11,.15); color: var(--warning);">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
              <div style="flex:1;">
                <div style="font-size: 0.9rem; font-weight: 500;">Complete your portfolio section</div>
                <div style="font-size: 0.78rem; color: var(--text-muted);">Add certificates and portfolio items to strengthen your CV.</div>
              </div>
              <span style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap;">1d ago</span>
            </div>
            <div class="flex items-center gap-md" style="padding: var(--space-md); border-radius: var(--radius-md);">
              <div class="avatar avatar--sm" style="background: rgba(34,197,94,.15); color: var(--success);">
                <i class="fas fa-check-circle"></i>
              </div>
              <div style="flex:1;">
                <div style="font-size: 0.9rem; font-weight: 500;">Welcome to DigiCV!</div>
                <div style="font-size: 0.78rem; color: var(--text-muted);">Your account has been created successfully.</div>
              </div>
              <span style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap;">3d ago</span>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script>
    // Mobile menu toggle
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth <= 768) menuToggle.style.display = 'flex';
    menuToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  </script>
</body>
</html>
