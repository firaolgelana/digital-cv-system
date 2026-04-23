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
      <div class="avatar avatar--sm avatar--primary">AD</div>
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
          <div class="stat-value">142</div>
          <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card card animate-fade-in delay-2">
          <div class="stat-icon stat-icon--accent"><i class="fas fa-file-lines"></i></div>
          <div class="stat-value">318</div>
          <div class="stat-label">Total CVs</div>
        </div>
        <div class="stat-card card animate-fade-in delay-3">
          <div class="stat-icon stat-icon--warning"><i class="fas fa-qrcode"></i></div>
          <div class="stat-value">204</div>
          <div class="stat-label">Active QR Codes</div>
        </div>
        <div class="stat-card card animate-fade-in delay-4">
          <div class="stat-icon stat-icon--info"><i class="fas fa-eye"></i></div>
          <div class="stat-value">1,409</div>
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
                <tr>
                  <td>
                    <div class="flex items-center gap-sm">
                      <div class="avatar avatar--sm avatar--primary">JD</div>
                      <div>
                        <div style="font-weight:500">John Doe</div>
                        <div class="muted">john.doe@university.edu</div>
                      </div>
                    </div>
                  </td>
                  <td><span class="badge badge--info">Student</span></td>
                  <td>Computer Science</td>
                  <td><span class="badge badge--success">Active</span></td>
                  <td>Apr 14, 2026</td>
                  <td>
                    <div class="flex gap-sm">
                      <button class="btn btn-ghost btn-sm" title="Toggle Status"><i class="fas fa-ban"></i></button>
                      <button class="btn btn-ghost btn-sm" style="color:var(--danger)" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="flex items-center gap-sm">
                      <div class="avatar avatar--sm avatar--primary">AS</div>
                      <div>
                        <div style="font-weight:500">Alice Smith</div>
                        <div class="muted">asmith@university.edu</div>
                      </div>
                    </div>
                  </td>
                  <td><span class="badge badge--warning">Supervisor</span></td>
                  <td>Software Engineering</td>
                  <td><span class="badge badge--success">Active</span></td>
                  <td>Apr 12, 2026</td>
                  <td>
                    <div class="flex gap-sm">
                      <button class="btn btn-ghost btn-sm" title="Toggle Status"><i class="fas fa-ban"></i></button>
                      <button class="btn btn-ghost btn-sm" style="color:var(--danger)" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>
  </main>

</body>
</html>
