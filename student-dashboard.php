<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

if (($_SESSION['role'] ?? '') !== 'student') {
    header('Location: index.html');
    exit;
}

$fullName = $_SESSION['full_name'] ?? 'Student';
$firstName = explode(' ', trim($fullName))[0] ?: 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Home</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <div class="page">
    <header class="topbar">
      <div class="container topbar-inner">
        <div class="brand"><span class="brand-mark">CV</span> DigiCV Platform</div>
        <div class="nav-actions">
          <a class="btn" href="create-cv.html">CV Builder</a>
          <a class="btn" href="php_actions/logout.php">Logout</a>
        </div>
      </div>
    </header>

    <main class="main">
      <div class="container section-stack">
        <div class="card">
          <h1 class="card-title">Welcome, <?php echo htmlspecialchars($firstName); ?></h1>
          <p class="card-subtitle">Main action: continue your CV and submit it for supervisor review.</p>
          <div class="status-line" style="margin-top:16px;">
            <span class="badge pending">Pending Review</span>
            <div class="btn-row">
              <a class="btn btn-primary" href="create-cv.html">Continue CV</a>
              <a class="btn" href="cv-preview.html">Open Preview</a>
            </div>
          </div>
        </div>

        <div class="grid-2">
          <div class="card tight">
            <h3 class="card-title">Current CV Status</h3>
            <p class="muted">Draft completed: 78%</p>
            <p class="muted">Last autosave: Today, 10:42 AM</p>
            <p class="muted">Submission state: Pending supervisor feedback</p>
          </div>
          <div class="card tight">
            <h3 class="card-title">Recent Activity</h3>
            <div class="activity-list">
              <div class="activity-item">Updated Experience section</div>
              <div class="activity-item">Uploaded one certificate</div>
              <div class="activity-item">Submitted CV for review</div>
            </div>
          </div>
        </div>

        <div class="card tight">
          <h3 class="card-title">Quick Access</h3>
          <div class="btn-row">
            <a class="btn" href="create-cv.html">Step-by-step CV Builder</a>
            <a class="btn" href="cv-preview.html">A4 Preview</a>
            <a class="btn" href="qr-code.html">QR Code Page</a>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>