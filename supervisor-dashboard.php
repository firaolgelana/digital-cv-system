<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth(['supervisor', 'examiner']);
$user = currentUser();

function page_initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = $parts[0][0] ?? 'U';
    $second = $parts[1][0] ?? '';
    return strtoupper($first . $second);
}

$displayRole = $user['role'] === 'examiner' ? 'Examiner' : 'Project Supervisor';
$initials = page_initials($user['full_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($displayRole) ?> Dashboard — DigiCV</title>
  <meta name="description" content="Review and manage submitted student CVs." />
  <link rel="stylesheet" href="styles.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    .review-card{background:var(--surface-card);border:1px solid var(--surface-border);border-radius:var(--radius-lg);padding:var(--space-xl);transition:all var(--transition-base);backdrop-filter:blur(16px)}
    .review-card:hover{border-color:rgba(99,102,241,.25);box-shadow:var(--shadow-md)}
    .review-card__header{display:flex;align-items:flex-start;gap:var(--space-md);margin-bottom:var(--space-lg)}
    .review-card__student{flex:1}
    .review-card__name{font-weight:600;font-size:1rem}
    .review-card__meta{font-size:.82rem;color:var(--text-secondary)}
    .review-card__details{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-md);margin-bottom:var(--space-lg)}
    .review-card__detail{text-align:center;padding:var(--space-md);background:var(--surface-elevated);border-radius:var(--radius-md)}
    .review-card__detail-label{font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em}
    .review-card__detail-value{font-weight:600;font-size:.9rem;margin-top:2px}
    .review-card__actions{display:flex;gap:var(--space-sm);flex-wrap:wrap}
    .review-card__note{margin-top:var(--space-md);padding:var(--space-md);border-radius:var(--radius-md);background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.18);font-size:.86rem;color:var(--text-secondary)}
    .empty-block{padding:2rem;text-align:center;color:var(--text-secondary)}
    .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;display:none;align-items:center;justify-content:center;padding:1rem}
    .modal-overlay.open{display:flex}
    .modal-box{background:var(--surface);border:1px solid var(--surface-border);border-radius:var(--radius-xl);padding:1.5rem;width:100%;max-width:900px;max-height:88vh;overflow:auto;position:relative}
    .modal-close{position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.1rem}
    .preview-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:var(--space-lg)}
    .preview-block{padding:var(--space-lg);border-radius:var(--radius-lg);background:var(--surface-elevated);border:1px solid var(--surface-border)}
    #review-toast{position:fixed;bottom:2rem;right:2rem;padding:.75rem 1.1rem;border-radius:10px;font-size:.88rem;font-weight:500;z-index:2000;display:none;max-width:340px}
    #review-toast.success{background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.4);color:#4ade80}
    #review-toast.error{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.4);color:#f87171}
    @media (max-width: 860px){.review-card__details,.preview-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <div class="app-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-brand">
        <div class="sidebar-brand__icon"><i class="fas fa-qrcode"></i></div>
        <div>
          <div class="sidebar-brand__text text-gradient">DigiCV</div>
          <div class="sidebar-brand__sub"><?= htmlspecialchars($displayRole) ?></div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-section-title">Review</div>
        <a href="supervisor-dashboard.php" class="sidebar-link active">
          <span class="sidebar-link__icon"><i class="fas fa-house"></i></span> Dashboard
        </a>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar avatar--accent"><?= htmlspecialchars($initials) ?></div>
          <div class="sidebar-user__info">
            <div class="sidebar-user__name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="sidebar-user__role"><?= htmlspecialchars($displayRole) ?></div>
          </div>
          <a href="php_actions/logout.php" title="Sign out" style="color:var(--text-muted);"><i class="fas fa-right-from-bracket"></i></a>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <header class="top-bar">
        <button class="btn btn-ghost btn-icon" id="menu-toggle" style="display:none;"><i class="fas fa-bars"></i></button>
        <h2 class="top-bar__title"><?= htmlspecialchars($displayRole) ?> Dashboard</h2>
        <div class="top-bar__actions">
          <div class="search-box" style="width:240px;">
            <span class="search-icon"><i class="fas fa-search"></i></span>
            <input type="text" class="form-input" placeholder="Search students…" id="review-search" />
          </div>
          <div class="avatar avatar--sm avatar--accent"><?= htmlspecialchars($initials) ?></div>
        </div>
      </header>

      <div class="page-content">
        <div class="page-header animate-fade-in">
          <h1 class="page-header__title">Welcome, <?= htmlspecialchars($user['full_name']) ?></h1>
          <p class="page-header__sub">Review submitted CVs, approve strong profiles, and send revisions back with comments.</p>
        </div>

        <div class="grid grid-4 gap-lg" style="margin-bottom: var(--space-2xl);">
          <div class="stat-card animate-fade-in delay-1">
            <div class="stat-icon stat-icon--warning"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-value" id="stat-pending">0</div>
            <div class="stat-label">Pending Review</div>
          </div>
          <div class="stat-card animate-fade-in delay-2">
            <div class="stat-icon stat-icon--accent"><i class="fas fa-circle-check"></i></div>
            <div class="stat-value" id="stat-approved">0</div>
            <div class="stat-label">Approved</div>
          </div>
          <div class="stat-card animate-fade-in delay-3">
            <div class="stat-icon stat-icon--danger"><i class="fas fa-rotate-left"></i></div>
            <div class="stat-value" id="stat-revision">0</div>
            <div class="stat-label">Changes Requested</div>
          </div>
          <div class="stat-card animate-fade-in delay-4">
            <div class="stat-icon stat-icon--primary"><i class="fas fa-users"></i></div>
            <div class="stat-value" id="stat-total-students">0</div>
            <div class="stat-label">Total Students</div>
          </div>
        </div>

        <div class="tabs">
          <button class="tab-btn active" data-status="pending">Pending</button>
          <button class="tab-btn" data-status="approved">Approved</button>
          <button class="tab-btn" data-status="changes_requested">Changes Requested</button>
          <button class="tab-btn" data-status="rejected">Rejected</button>
          <button class="tab-btn" data-status="all">All Submissions</button>
        </div>

        <div id="review-list" class="flex flex-col gap-lg" style="margin-top: var(--space-xl);"></div>

        <div class="card" style="margin-top: var(--space-2xl);">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-clock-rotate-left" style="color: var(--accent-400); margin-right: 8px;"></i>Recent Decisions</h3>
          </div>
          <div class="table-wrapper">
            <table class="table">
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Department</th>
                  <th>Status</th>
                  <th>Submitted</th>
                  <th>Reviewed</th>
                  <th>Comment</th>
                </tr>
              </thead>
              <tbody id="recent-reviewed-body"></tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>

  <div class="modal-overlay" id="cv-modal">
    <div class="modal-box">
      <button class="modal-close" id="close-cv-modal"><i class="fas fa-xmark"></i></button>
      <h3 class="card-title" id="cv-modal-title">CV Review</h3>
      <div id="cv-modal-content" class="preview-grid" style="margin-top: var(--space-xl);"></div>
    </div>
  </div>

  <div id="review-toast"></div>

  <script>
    window.REVIEWER_CONTEXT = <?= json_encode([
      'role' => $user['role'],
      'fullName' => $user['full_name'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script src="assets/js/supervisor-dashboard.js"></script>
</body>
</html>
