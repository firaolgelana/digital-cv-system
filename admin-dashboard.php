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

$cvRows = $pdo->query("SELECT status, COUNT(*) c FROM cvs GROUP BY status")->fetchAll();
$cvStats = ['draft'=>0,'pending'=>0,'approved'=>0,'rejected'=>0];
foreach ($cvRows as $r) $cvStats[$r['status']] = (int)$r['c'];

$roleRows = $pdo->query("SELECT r.name role, COUNT(*) c FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name!='admin' GROUP BY r.name")->fetchAll();
$roleStats = [];
foreach ($roleRows as $r) $roleStats[$r['role']] = (int)$r['c'];

// ── Recent users ─────────────────────────────────────────────
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
  <style>
    .legend-item{display:flex;align-items:center;gap:8px;font-size:.85rem}
    .legend-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
    .log-entry{display:flex;align-items:center;gap:var(--space-md);padding:var(--space-md) 0;border-bottom:1px solid var(--surface-border);font-size:.88rem}
    .log-entry:last-child{border-bottom:none}
    .log-entry__icon{width:32px;height:32px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0}
    .log-entry__time{font-size:.75rem;color:var(--text-muted);white-space:nowrap}
    /* Modal */
    .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;display:none;align-items:center;justify-content:center;padding:1rem}
    .modal-overlay.open{display:flex}
    .modal-box{background:var(--surface);border:1px solid var(--surface-border);border-radius:var(--radius-xl);padding:2rem;width:100%;max-width:480px;position:relative}
    .modal-title{font-size:1.1rem;font-weight:700;margin-bottom:1.5rem}
    .modal-close{position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.1rem}
    #toast{position:fixed;bottom:2rem;right:2rem;padding:.75rem 1.25rem;border-radius:10px;font-size:.88rem;font-weight:500;z-index:2000;display:none;max-width:340px}
    #toast.success{background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.4);color:#4ade80}
    #toast.error{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.4);color:#f87171}
    .badge--pending{background:rgba(99,102,241,.12);color:var(--primary-400)}
    .cv-bar{display:flex;align-items:flex-end;gap:12px;height:140px;margin-top:1rem}
    .cv-bar__col{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px}
    .cv-bar__fill{width:60%;border-radius:4px 4px 0 0;min-height:4px;transition:height .4s}
    .cv-bar__label{font-size:.72rem;color:var(--text-muted)}
    .cv-bar__count{font-size:.82rem;font-weight:600}
  </style>
</head>
<body>
<div class="app-layout">
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="sidebar-brand__icon"><i class="fas fa-qrcode"></i></div>
      <div>
        <div class="sidebar-brand__text text-gradient">DigiCV</div>
        <div class="sidebar-brand__sub">System Management</div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section-title">Management</div>
      <a href="admin-dashboard.php" class="sidebar-link active">
        <span class="sidebar-link__icon"><i class="fas fa-gauge-high"></i></span> Overview
      </a>
      <a href="#" class="sidebar-link" onclick="openSection('users');return false">
        <span class="sidebar-link__icon"><i class="fas fa-users"></i></span> User Management
      </a>
      <a href="#" class="sidebar-link">
        <span class="sidebar-link__icon"><i class="fas fa-file-lines"></i></span> All CVs
      </a>
      <a href="#" class="sidebar-link">
        <span class="sidebar-link__icon"><i class="fas fa-qrcode"></i></span> QR Codes
      </a>
      <div class="sidebar-section-title">System</div>
      <a href="#" class="sidebar-link">
        <span class="sidebar-link__icon"><i class="fas fa-server"></i></span> System Health
      </a>
    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="avatar" style="background:rgba(239,68,68,.2);color:var(--danger);"><?= $initials ?></div>
        <div class="sidebar-user__info">
          <div class="sidebar-user__name"><?= htmlspecialchars($user['full_name']) ?></div>
          <div class="sidebar-user__role">Administrator</div>
        </div>
        <a href="php_actions/logout.php" title="Sign out" style="color:var(--text-muted)"><i class="fas fa-right-from-bracket"></i></a>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <main class="main-content">
    <header class="top-bar">
      <button class="btn btn-ghost btn-icon" id="menu-toggle" style="display:none"><i class="fas fa-bars"></i></button>
      <h2 class="top-bar__title">System Overview</h2>
      <div class="top-bar__actions">
        <div class="search-box" style="width:260px">
          <span class="search-icon"><i class="fas fa-search"></i></span>
          <input type="text" class="form-input" id="global-search" placeholder="Search users…"/>
        </div>
        <div class="avatar avatar--sm" style="background:rgba(239,68,68,.2);color:var(--danger);"><?= $initials ?></div>
      </div>
    </header>

    <div class="page-content">
      <div class="page-header animate-fade-in">
        <h1 class="page-header__title">System Dashboard</h1>
        <p class="page-header__sub">Monitor all activities, users, CVs and QR codes across the platform.</p>
      </div>

      <!-- Stats -->
      <div class="grid grid-4 gap-lg" style="margin-bottom:var(--space-2xl)">
        <div class="stat-card animate-fade-in delay-1">
          <div class="stat-icon stat-icon--primary"><i class="fas fa-users"></i></div>
          <div class="stat-value"><?= $totalUsers ?></div>
          <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card animate-fade-in delay-2">
          <div class="stat-icon stat-icon--accent"><i class="fas fa-file-lines"></i></div>
          <div class="stat-value"><?= $totalCVs ?></div>
          <div class="stat-label">Total CVs</div>
        </div>
        <div class="stat-card animate-fade-in delay-3">
          <div class="stat-icon stat-icon--warning"><i class="fas fa-qrcode"></i></div>
          <div class="stat-value"><?= $activeQR ?></div>
          <div class="stat-label">Active QR Codes</div>
        </div>
        <div class="stat-card animate-fade-in delay-4">
          <div class="stat-icon stat-icon--info"><i class="fas fa-eye"></i></div>
          <div class="stat-value"><?= number_format($totalScans) ?></div>
          <div class="stat-label">Total QR Scans</div>
        </div>
      </div>

      <!-- Charts row -->
      <div class="grid grid-2 gap-lg" style="margin-bottom:var(--space-xl)">
        <!-- CV Status -->
        <div class="card animate-fade-in delay-2">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-bar" style="color:var(--primary-400);margin-right:8px"></i>CV Status Distribution</h3>
          </div>
          <?php
          $maxCV = max(1, max($cvStats));
          $colors = ['draft'=>'var(--gray-600)','pending'=>'var(--warning)','approved'=>'var(--primary-500)','rejected'=>'var(--danger)'];
          ?>
          <div class="cv-bar">
            <?php foreach ($cvStats as $status => $count): ?>
            <div class="cv-bar__col">
              <div class="cv-bar__count"><?= $count ?></div>
              <div class="cv-bar__fill" style="height:<?= round($count/$maxCV*100) ?>%;background:<?= $colors[$status] ?>"></div>
              <div class="cv-bar__label"><?= ucfirst($status) ?></div>
            </div>
            <?php endforeach ?>
          </div>
        </div>

        <!-- User Distribution -->
        <div class="card animate-fade-in delay-3">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-pie" style="color:var(--accent-400);margin-right:8px"></i>User Distribution</h3>
          </div>
          <div style="display:flex;flex-direction:column;gap:var(--space-md);padding:var(--space-lg) 0">
            <?php
            $roleColors=['student'=>'var(--primary-500)','supervisor'=>'var(--accent-500)','examiner'=>'#a78bfa','recruiter'=>'var(--warning)'];
            foreach ($roleColors as $rn => $rc):
              $cnt = $roleStats[$rn] ?? 0;
              $pct = $totalUsers > 0 ? round($cnt/$totalUsers*100) : 0;
            ?>
            <div class="legend-item">
              <div class="legend-dot" style="background:<?= $rc ?>"></div>
              <span><?= ucfirst($rn) ?>s — <?= $cnt ?> (<?= $pct ?>%)</span>
            </div>
            <?php endforeach ?>
          </div>
        </div>
      </div>

      <!-- User Management Table -->
      <div class="card" style="margin-bottom:var(--space-xl)" id="users-section">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-users-gear" style="color:var(--primary-400);margin-right:8px"></i>User Management</h3>
          <div class="flex gap-sm">
            <select class="form-select" id="role-filter" style="width:140px;padding:6px 10px;font-size:.8rem">
              <option value="all">All Roles</option>
              <option value="student">Students</option>
              <option value="supervisor">Supervisors</option>
              <option value="examiner">Examiners</option>
              <option value="recruiter">Recruiters</option>
            </select>
            <button class="btn btn-primary btn-sm" onclick="openModal()"><i class="fas fa-user-plus"></i> Add User</button>
          </div>
        </div>
        <div class="table-wrapper">
          <table class="table" id="user-table">
            <thead>
              <tr>
                <th>User</th><th>Role</th><th>Department</th><th>Status</th><th>Joined</th><th>Actions</th>
              </tr>
            </thead>
            <tbody id="user-tbody">
              <?php foreach ($recentUsers as $u): ?>
              <tr id="row-<?= $u['id'] ?>">
                <td>
                  <div class="flex items-center gap-sm">
                    <div class="avatar avatar--sm avatar--primary"><?= initials($u['full_name']) ?></div>
                    <div>
                      <div style="font-weight:500"><?= htmlspecialchars($u['full_name']) ?></div>
                      <div style="font-size:.75rem;color:var(--text-muted)"><?= htmlspecialchars($u['email']) ?></div>
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
                    <button class="btn btn-ghost btn-sm" title="<?= $u['is_active']?'Deactivate':'Activate' ?>"
                      onclick="toggleUser(<?= $u['id'] ?>, this)">
                      <i class="fas <?= $u['is_active']?'fa-ban':'fa-circle-check' ?>"></i>
                    </button>
                    <button class="btn btn-ghost btn-sm" style="color:var(--danger)" title="Delete"
                      onclick="deleteUser(<?= $u['id'] ?>, '<?= addslashes($u['full_name']) ?>')">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach ?>
              <?php if (empty($recentUsers)): ?>
              <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted)">No users found.</td></tr>
              <?php endif ?>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- /page-content -->
  </main>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="modal-overlay">
  <div class="modal-box animate-fade-in">
    <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
    <div class="modal-title"><i class="fas fa-user-plus" style="color:var(--primary-400);margin-right:8px"></i>Create New User</div>
    <div id="modal-alert" style="display:none;padding:.7rem 1rem;border-radius:10px;font-size:.85rem;margin-bottom:1rem;font-weight:500"></div>
    <form id="create-user-form" autocomplete="off">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" class="form-input" id="cu-name" placeholder="Full Name" required/>
      </div>
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-input" id="cu-email" placeholder="user@university.edu" required/>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Role</label>
          <select class="form-select" id="cu-role">
            <option value="supervisor">Supervisor</option>
            <option value="examiner">Examiner</option>
            <option value="recruiter">Recruiter</option>
            <option value="student">Student</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Phone (optional)</label>
          <input type="text" class="form-input" id="cu-phone" placeholder="+251 91 000 0000"/>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" class="form-input" id="cu-password" placeholder="Min 8 characters" required/>
      </div>
      <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:.5rem" id="cu-btn">
        <i class="fas fa-user-plus"></i>
        <span id="cu-btn-text">Create User</span>
      </button>
    </form>
  </div>
</div>

<!-- Toast -->
<div id="toast"></div>

<script>
const API = 'php_actions/admin_action.php';

// ── Toast ────────────────────────────────────────────────────
function toast(msg, ok=true) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = ok ? 'success' : 'error';
  t.style.display = 'block';
  setTimeout(() => t.style.display = 'none', 3500);
}

// ── Modal ─────────────────────────────────────────────────────
function openModal() { document.getElementById('modal-overlay').classList.add('open'); }
function closeModal() {
  document.getElementById('modal-overlay').classList.remove('open');
  document.getElementById('create-user-form').reset();
  document.getElementById('modal-alert').style.display = 'none';
}
document.getElementById('modal-overlay').addEventListener('click', function(e){
  if (e.target === this) closeModal();
});

function modalAlert(msg, ok) {
  const el = document.getElementById('modal-alert');
  el.textContent = msg;
  el.style.display = 'block';
  el.style.background = ok ? 'rgba(34,197,94,.15)' : 'rgba(239,68,68,.15)';
  el.style.border = ok ? '1px solid rgba(34,197,94,.4)' : '1px solid rgba(239,68,68,.4)';
  el.style.color = ok ? '#4ade80' : '#f87171';
}

// ── Create user ───────────────────────────────────────────────
document.getElementById('create-user-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('cu-btn');
  const txt = document.getElementById('cu-btn-text');
  btn.disabled = true; txt.textContent = 'Creating…';

  const payload = {
    action:     'create_user',
    full_name:  document.getElementById('cu-name').value.trim(),
    email:      document.getElementById('cu-email').value.trim(),
    role:       document.getElementById('cu-role').value,
    phone:      document.getElementById('cu-phone').value.trim(),
    password:   document.getElementById('cu-password').value,
  };

  try {
    const res  = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
    const data = await res.json();
    if (data.success) {
      toast(data.message);
      closeModal();
      reloadUsers();
    } else {
      modalAlert(data.message, false);
    }
  } catch(_) { modalAlert('Network error.', false); }

  btn.disabled = false; txt.textContent = 'Create User';
});

// ── Reload user table via API ─────────────────────────────────
async function reloadUsers(role='all', search='') {
  const tbody = document.getElementById('user-tbody');
  tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted)">Loading…</td></tr>';
  const res  = await fetch(`${API}?action=get_users&role=${encodeURIComponent(role)}&search=${encodeURIComponent(search)}`);
  const data = await res.json();
  if (!data.success || !data.users.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted)">No users found.</td></tr>';
    return;
  }
  const roleClasses = {student:'badge--info',supervisor:'badge--warning',examiner:'badge--pending',recruiter:'badge--draft'};
  tbody.innerHTML = data.users.map(u => {
    const init = u.full_name.split(' ').map(p=>p[0]||'').join('').toUpperCase().slice(0,2);
    const roleBadge = `<span class="badge ${roleClasses[u.role]||''}">${u.role.charAt(0).toUpperCase()+u.role.slice(1)}</span>`;
    const statusBadge = u.is_active=='1' ? '<span class="badge badge--success">Active</span>' : '<span class="badge badge--danger">Inactive</span>';
    const toggleIcon = u.is_active=='1' ? 'fa-ban' : 'fa-circle-check';
    const toggleTitle = u.is_active=='1' ? 'Deactivate' : 'Activate';
    const joined = new Date(u.created_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
    return `<tr id="row-${u.id}">
      <td><div class="flex items-center gap-sm">
        <div class="avatar avatar--sm avatar--primary">${init}</div>
        <div><div style="font-weight:500">${escHtml(u.full_name)}</div>
        <div style="font-size:.75rem;color:var(--text-muted)">${escHtml(u.email)}</div></div>
      </div></td>
      <td>${roleBadge}</td>
      <td>${u.dept ? escHtml(u.dept) : '—'}</td>
      <td>${statusBadge}</td>
      <td>${joined}</td>
      <td><div class="flex gap-sm">
        <button class="btn btn-ghost btn-sm" title="${toggleTitle}" onclick="toggleUser(${u.id},this)"><i class="fas ${toggleIcon}"></i></button>
        <button class="btn btn-ghost btn-sm" style="color:var(--danger)" title="Delete" onclick="deleteUser(${u.id},'${escHtml(u.full_name).replace(/'/g,"\\'")}')"><i class="fas fa-trash"></i></button>
      </div></td>
    </tr>`;
  }).join('');
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Toggle user status ────────────────────────────────────────
async function toggleUser(id, btn) {
  btn.disabled = true;
  const res  = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'toggle_status',user_id:id}) });
  const data = await res.json();
  if (data.success) { toast(data.message); reloadUsers(document.getElementById('role-filter').value, document.getElementById('global-search').value); }
  else { toast(data.message, false); btn.disabled = false; }
}

// ── Delete user ───────────────────────────────────────────────
async function deleteUser(id, name) {
  if (!confirm(`Delete "${name}"? This action cannot be undone.`)) return;
  const res  = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'delete_user',user_id:id}) });
  const data = await res.json();
  if (data.success) { toast(data.message); document.getElementById('row-'+id)?.remove(); }
  else toast(data.message, false);
}

// ── Filters ───────────────────────────────────────────────────
document.getElementById('role-filter').addEventListener('change', function() {
  reloadUsers(this.value, document.getElementById('global-search').value);
});
let searchTimer;
document.getElementById('global-search').addEventListener('input', function() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => reloadUsers(document.getElementById('role-filter').value, this.value), 350);
});

// ── Sidebar toggle ────────────────────────────────────────────
const menuToggle = document.getElementById('menu-toggle');
const sidebar    = document.getElementById('sidebar');
if (window.innerWidth <= 768) menuToggle.style.display = 'flex';
menuToggle.addEventListener('click', () => sidebar.classList.toggle('open'));

function openSection(sec) {
  if (sec === 'users') document.getElementById('users-section').scrollIntoView({behavior:'smooth'});
}
</script>
</body>
</html>
