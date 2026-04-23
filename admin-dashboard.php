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
    <button class="hamburger-btn" id="mobile-menu-toggle"><i class="fas fa-bars"></i></button>
    <nav class="nav-links" id="main-nav">
      <a href="admin-dashboard.php" class="active">Overview</a>
      <a href="#users-section">Users Matrix</a>
      <a href="#">System Logs</a>
      <a href="profile.html" class="mobile-only"><i class="fas fa-user-cog"></i> Profile Settings</a>
      <a href="index.html" class="mobile-only" style="color:var(--danger)"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
    <div class="nav-user">
      <a href="profile.html" style="text-decoration:none"><div class="avatar avatar--sm avatar--primary" title="Profile Settings">AD</div></a>
      <a href="php_actions/logout.php" class="btn btn-ghost btn-icon" title="Sign out"><i class="fas fa-sign-out-alt"></i></a>
    </div>
  </header>

  <!-- Page Content -->
  <main class="page-container" style="padding-top:32px">
    <div class="page-header animate-fade-in" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px">
      <div>
         <h1>System Management & Users</h1>
         <p>Global oversight of profiles, scaling QR tracking, and system telemetry.</p>
      </div>
      <!-- Time Range Filter -->
      <div style="display:flex;gap:8px">
         <select class="form-input" style="width:auto; padding:8px 12px"><option>Last 30 Days</option><option>All Time</option></select>
         <button class="btn btn-primary"><i class="fas fa-download"></i> Export CSV</button>
      </div>
    </div>

    <!-- Top Row (Quick Stats) -->
    <section class="section-stack delay-1" style="margin-top:16px; margin-bottom:24px">
      <div class="grid-2" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
         <div class="stat-card card">
           <div class="stat-icon stat-icon--primary"><i class="fas fa-users"></i></div>
           <div class="stat-value">142</div>
           <div class="stat-label">Total Authenticated Users</div>
         </div>
         <div class="stat-card card">
           <div class="stat-icon stat-icon--accent"><i class="fas fa-file-lines"></i></div>
           <div class="stat-value">318</div>
           <div class="stat-label">Verified Resumes</div>
         </div>
         <div class="stat-card card">
           <div class="stat-icon stat-icon--warning"><i class="fas fa-qrcode"></i></div>
           <div class="stat-value">204</div>
           <div class="stat-label">Active QR Endpoints</div>
         </div>
         <div class="stat-card card">
           <div class="stat-icon stat-icon--info"><i class="fas fa-eye"></i></div>
           <div class="stat-value">1,409</div>
           <div class="stat-label">Scans Tracked</div>
         </div>
      </div>
    </section>

    <!-- Main Table Module -->
    <section class="card animate-fade-in delay-2" id="users-section">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:16px">
           <h3 class="card-title" style="margin:0"><i class="fas fa-network-wired" style="color:var(--gray-400);margin-right:8px"></i> Users Matrix</h3>
           <div style="display:flex;gap:8px">
              <input type="text" class="input form-input" placeholder="Search Email or Tag..." style="width:250px" />
              <button class="btn btn-ghost"><i class="fas fa-filter"></i> Filters</button>
              <button class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Invite User</button>
           </div>
        </div>
        
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Identifier Profile</th>
                <th>Role Property</th>
                <th>Assigned Dept.</th>
                <th>Security State</th>
                <th>Joined</th>
                <th>Controls</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <div class="flex items-center gap-sm">
                    <div class="avatar avatar--sm avatar--primary">JD</div>
                    <div>
                      <div style="font-weight:500; font-size:1rem; color:var(--text)">John Doe</div>
                      <div class="muted" style="font-size:0.85rem">john.doe@university.edu</div>
                    </div>
                  </div>
                </td>
                <td><span class="badge badge--info"><i class="fas fa-graduation-cap"></i> Student</span></td>
                <td style="color:var(--gray-300)">Computer Science</td>
                <td><span class="badge" style="background:rgba(16,185,129,0.1); color:var(--success)"><i class="fas fa-shield-check"></i> Active</span></td>
                <td class="muted">Apr 14, 2026</td>
                <td>
                  <div class="flex gap-sm">
                    <button class="btn btn-ghost btn-sm" title="Freeze Account"><i class="fas fa-ban"></i></button>
                    <button class="btn btn-ghost btn-sm" style="color:var(--primary-500)" title="Promote Role"><i class="fas fa-level-up-alt"></i></button>
                    <button class="btn btn-ghost btn-sm" style="color:var(--danger)" title="Nuke Profile"><i class="fas fa-trash"></i></button>
                  </div>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="flex items-center gap-sm">
                    <div class="avatar avatar--sm avatar--primary" style="background:#f59e0b;color:#fff">AS</div>
                    <div>
                      <div style="font-weight:500; font-size:1rem; color:var(--text)">Alice Smith</div>
                      <div class="muted" style="font-size:0.85rem">asmith@university.edu</div>
                    </div>
                  </div>
                </td>
                <td><span class="badge badge--warning"><i class="fas fa-clipboard-check"></i> Supervisor</span></td>
                <td style="color:var(--gray-300)">Software Engineering</td>
                <td><span class="badge" style="background:rgba(16,185,129,0.1); color:var(--success)"><i class="fas fa-shield-check"></i> Active</span></td>
                <td class="muted">Apr 12, 2026</td>
                <td>
                  <div class="flex gap-sm">
                    <button class="btn btn-ghost btn-sm" title="Freeze Account"><i class="fas fa-ban"></i></button>
                    <button class="btn btn-ghost btn-sm" style="color:var(--primary-500)" title="Promote Role"><i class="fas fa-level-up-alt"></i></button>
                    <button class="btn btn-ghost btn-sm" style="color:var(--danger)" title="Nuke Profile"><i class="fas fa-trash"></i></button>
                  </div>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="flex items-center gap-sm">
                    <div class="avatar avatar--sm avatar--primary" style="background:#8b5cf6;color:#fff">RB</div>
                    <div>
                      <div style="font-weight:500; font-size:1rem; color:var(--text)">Robert Blake</div>
                      <div class="muted" style="font-size:0.85rem">r.blake@university.edu</div>
                    </div>
                  </div>
                </td>
                <td><span class="badge" style="background:var(--surface-alt);border:1px solid #8b5cf6;color:#8b5cf6"><i class="fas fa-glasses"></i> Examiner</span></td>
                <td style="color:var(--gray-300)">Cybersecurity</td>
                <td><span class="badge" style="background:rgba(239,68,68,0.1); color:var(--danger)"><i class="fas fa-lock"></i> Frozen</span></td>
                <td class="muted">Jan 06, 2026</td>
                <td>
                  <div class="flex gap-sm">
                    <button class="btn btn-ghost btn-sm" title="Unfreeze Account"><i class="fas fa-unlock"></i></button>
                    <button class="btn btn-ghost btn-sm" style="color:var(--danger)" title="Nuke Profile"><i class="fas fa-trash"></i></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
    </section>

  </main>
  
  <script>
    document.getElementById('mobile-menu-toggle').addEventListener('click', () => {
      document.getElementById('main-nav').classList.toggle('mobile-open');
    });
  </script>
</body>
</html>
