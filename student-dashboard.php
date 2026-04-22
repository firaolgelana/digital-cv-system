<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

if (($_SESSION['role'] ?? '') !== 'student') {
    header('Location: index.html');
    exit;
}

$fullName = $_SESSION['full_name'] ?? 'Student';
$firstName = explode(' ', trim($fullName))[0] ?: 'Student';
$initials = strtoupper(substr($firstName, 0, 1) . substr(explode(' ', trim($fullName))[1] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Dashboard — DigiCV</title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
</head>
<body>
  <!-- Top Navbar -->
  <header class="top-navbar">
    <div class="brand-area">
      <div class="brand-icon"><i class="fas fa-file-alt"></i></div>
      <span class="text-gradient">DigiCV Core</span>
    </div>
    <nav class="nav-links">
      <a href="student-dashboard.php" class="active">My Dashboard</a>
      <a href="create-cv.html">CV Builder</a>
      <a href="qr-code.html">QR Center</a>
    </nav>
    <div class="nav-user">
      <div class="avatar avatar--sm avatar--primary"><?= $initials ?></div>
      <a class="btn btn-ghost btn-icon" href="php_actions/logout.php" title="Sign out"><i class="fas fa-sign-out-alt"></i></a>
    </div>
  </header>

  <main class="page-container">
    <div class="page-header animate-fade-in">
      <h1>Welcome back, <?php echo htmlspecialchars($firstName); ?></h1>
      <p>Manage your CV, track reviews, and view access analytics.</p>
    </div>

    <!-- Layout Split -->
    <div class="layout-split">
      <!-- Left Column (Compact info) -->
      <aside class="section-stack delay-1">
        <div class="card tight">
          <h3 class="card-title">Current Status</h3>
          <p class="muted">Your CV is currently pending review.</p>
          <div style="margin-top:16px;">
            <span class="badge pending" style="margin-bottom:16px;display:inline-block">Pending Supervisor Review</span>
            <div class="grid" style="gap:8px">
              <a class="btn btn-primary" href="create-cv.html" style="width:100%"><i class="fas fa-pen"></i> Edit CV</a>
              <a class="btn" href="cv-preview.html" style="width:100%"><i class="fas fa-print"></i> Print Preview</a>
            </div>
          </div>
        </div>
        
        <div class="card tight">
          <h3 class="card-title">Quick Action</h3>
          <p class="muted" style="margin-bottom:16px">Access your verified QR code.</p>
          <a class="btn" href="qr-code.html" style="width:100%"><i class="fas fa-qrcode"></i> View QR Code</a>
        </div>
      </aside>

      <!-- Right Column (Main detail) -->
      <section class="section-stack delay-2">
        <div class="card">
          <div class="card-header">
            <div>
              <h3 class="card-title">Recent Activity</h3>
              <p class="card-subtitle">Log of your recent actions and CV milestones.</p>
            </div>
          </div>
          <div class="section-stack gap-sm">
            <div class="list-card">
              <div class="list-head">
                <strong><i class="fas fa-check-circle" style="color:var(--success);margin-right:8px"></i> Submitted CV for review</strong>
                <span class="muted">Apr 21, 2026</span>
              </div>
            </div>
            <div class="list-card">
              <div class="list-head">
                <strong><i class="fas fa-file-upload" style="color:var(--info);margin-right:8px"></i> Uploaded "BSc Certificate"</strong>
                <span class="muted">Apr 20, 2026</span>
              </div>
            </div>
            <div class="list-card">
              <div class="list-head">
                <strong><i class="fas fa-user-edit" style="color:var(--warning);margin-right:8px"></i> Updated "Experience" section</strong>
                <span class="muted">Apr 19, 2026</span>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div>
              <h3 class="card-title">Profile Completion</h3>
              <p class="card-subtitle">Complete your profile to increase CV quality.</p>
            </div>
          </div>
          <div style="background:var(--surface-alt);border-radius:99px;height:12px;width:100%;overflow:hidden;margin-top:16px">
            <div style="background:linear-gradient(90deg, var(--primary-500), var(--primary-400));width:78%;height:100%;"></div>
          </div>
          <div class="flex" style="justify-content:space-between;margin-top:12px">
            <span class="muted">78% Complete</span>
            <span class="badge badge--draft">Draft Autosaved</span>
          </div>
        </div>
      </section>
    </div>
  </main>
</body>
</html>