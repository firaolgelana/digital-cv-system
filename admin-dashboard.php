<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth(['admin']);
$user = currentUser();
$pdo  = getDB();

// ── Real stats ───────────────────────────────────────────────
$totalUsers  = (int)$pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name!='admin'")->fetchColumn();
$totalCVs    = (int)$pdo->query("SELECT COUNT(*) FROM cvs")->fetchColumn();
$activeQR    = (int)$pdo->query("SELECT COUNT(*) FROM qr_codes")->fetchColumn();
$totalScans  = (int)$pdo->query("SELECT COUNT(*) FROM qr_access_logs")->fetchColumn();

$recentUsers = $pdo->query("
    SELECT u.id, u.full_name, u.email, u.is_active, u.created_at, r.name role, d.name dept
    FROM users u JOIN roles r ON r.id=u.role_id
    LEFT JOIN students s ON s.user_id=u.id
    LEFT JOIN departments d ON d.id=s.department_id
    WHERE r.name!='admin' ORDER BY u.created_at DESC LIMIT 20
")->fetchAll();

function initials(string $n): string {
    $p = explode(' ', trim($n));
    return strtoupper(substr($p[0],0,1) . (isset($p[1]) ? substr($p[1],0,1) : ''));
}
function roleBadge(string $r): string {
    $map = ['student'=>'badge--info','supervisor'=>'badge--warning','examiner'=>'badge--pending','recruiter'=>'badge--draft'];
    return '<span class="badge '.($map[$r]??'').'">' . ucfirst($r) . '</span>';
}
$initials = initials($user['full_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>System Manager — DigiCV</title>
  <meta name="description" content="System administration dashboard for DigiCV."/>
  <link rel="stylesheet" href="styles.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
</head>
<body>

  <!-- Top Navbar -->
  <header class="top-navbar">
    <div class="brand-area">
      <div class="brand-icon"><i class="fas fa-qrcode"></i></div>
      <span class="text-gradient">DigiCV Admin</span>
    </div>
    <nav class="nav-links">
      <a href="admin-dashboard.php" class="active">Overview</a>
      <a href="#users-section">Users</a>
      <a href="#">CV Storage</a>
    </nav>
    <div class="nav-user">
      <div class="avatar avatar--sm avatar--primary"><?= $initials ?></div>
      <a href="php_actions/logout.php" class="btn btn-ghost btn-icon" title="Sign out"><i class="fas fa-sign-out-alt"></i></a>
    </div>
  </header>

  <!-- Page Content -->
  <main class="page-container">
    <div class="page-header animate-fade-in">
      <h1>System Overview</h1>
      <p>Monitor platform statistics and manage user relationships.</p>
    </div>

    <div class="layout-split">
      <!-- Left side (Quick Stats) -->
      <aside class="section-stack">
        <div class="stat-card card animate-fade-in delay-1">
          <div class="stat-icon stat-icon--primary"><i class="fas fa-users"></i></div>
          <div class="stat-value"><?= $totalUsers ?></div>
          <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card card animate-fade-in delay-2">
          <div class="stat-icon stat-icon--accent"><i class="fas fa-file-lines"></i></div>
          <div class="stat-value"><?= $totalCVs ?></div>
          <div class="stat-label">Total CVs</div>
        </div>
        <div class="stat-card card animate-fade-in delay-3">
          <div class="stat-icon stat-icon--warning"><i class="fas fa-qrcode"></i></div>
          <div class="stat-value"><?= $activeQR ?></div>
          <div class="stat-label">Active QR Codes</div>
        </div>
        <div class="stat-card card animate-fade-in delay-4">
          <div class="stat-icon stat-icon--info"><i class="fas fa-eye"></i></div>
          <div class="stat-value"><?= number_format($totalScans) ?></div>
          <div class="stat-label">Total QR Scans</div>
        </div>
      </aside>

      <!-- Right side (Main content) -->
      <section class="section-stack">
        <div class="card animate-fade-in delay-2" id="users-section">
          <div class="card-header">
            <div>
              <h3 class="card-title">User Management</h3>
              <p class="card-subtitle">Manage accounts across the platform.</p>
            </div>
            <div class="flex gap-sm">
              <button class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Add User</button>
            </div>
          </div>
          
          <div class="table-wrapper">
            <table class="table">
              <thead>
                <tr>
                  <th>User</th><th>Role</th><th>Department</th><th>Status</th><th>Joined</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentUsers as $u): ?>
                <tr>
                  <td>
                    <div class="flex items-center gap-sm">
                      <div class="avatar avatar--sm avatar--primary"><?= initials($u['full_name']) ?></div>
                      <div>
                        <div style="font-weight:500"><?= htmlspecialchars($u['full_name']) ?></div>
                        <div class="muted"><?= htmlspecialchars($u['email']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?= roleBadge($u['role']) ?></td>
                  <td><?= $u['dept'] ? htmlspecialchars($u['dept']) : '—' ?></td>
                  <td>
                    <span class="badge <?= $u['is_active'] ? 'badge--success' : 'badge--danger' ?>">
                      <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                  </td>
                  <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                  <td>
                    <div class="flex gap-sm">
                      <button class="btn btn-ghost btn-sm" title="Toggle Status"><i class="fas <?= $u['is_active']?'fa-ban':'fa-circle-check' ?>"></i></button>
                      <button class="btn btn-ghost btn-sm" style="color:var(--danger)" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                  </td>
                </tr>
                <?php endforeach ?>
                <?php if (empty($recentUsers)): ?>
                <tr><td colspan="6" style="text-align:center;padding:2rem;" class="muted">No users found.</td></tr>
                <?php endif ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>
  </main>

</body>
</html>
