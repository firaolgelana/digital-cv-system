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
        <div class="brand-icon"><i class="fas fa-file-signature"></i></div>
        <span class="text-gradient">DigiCV Core</span>
      </div>
      <button class="hamburger-btn" id="mobile-menu-toggle"><i class="fas fa-bars"></i></button>
      <nav class="nav-links" id="main-nav">
        <a href="javascript:history.back()"><i class="fas fa-arrow-left"></i> Back</a>
        <a href="profile.html" class="mobile-only"><i class="fas fa-user-cog"></i> Profile Settings</a>
        <a href="index.html" class="mobile-only" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Sign out</a>
      </nav>
      <div class="nav-user">
        <a href="profile.html" style="text-decoration:none"><div class="avatar avatar--sm avatar--primary" title="Profile Settings">ST</div></a>
        <a class="btn btn-ghost btn-icon" href="index.html" title="Sign out"><i class="fas fa-sign-out-alt"></i></a>
      </div>
    </header>

  <main class="page-container">
    <div class="page-header animate-fade-in" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px">
      <div>
        <h1>Welcome back, Graduate</h1>
        <p>Manage multiple CVs, track reviews, and generate distinct QR codes.</p>
      </div>
      <a href="create-cv.html" class="btn btn-primary"><i class="fas fa-plus"></i> Create New Resume</a>
    </div>

    <!-- Layout Split -->
    <div class="layout-split">
      <!-- Left Column (Compact info) -->
      <aside class="section-stack delay-1">
        <div class="card tight">
          <h3 class="card-title">Recent Activity</h3>
          <p class="muted" style="margin-bottom:16px">Log of your recent actions.</p>
          <div class="section-stack gap-sm">
            <div class="list-card">
              <div class="list-head" style="font-size:0.9rem">
                <span><i class="fas fa-check-circle" style="color:var(--success);margin-right:8px"></i> "SWE Analyst" Approved</span>
              </div>
            </div>
            <div class="list-card">
              <div class="list-head" style="font-size:0.9rem">
                <span><i class="fas fa-file-upload" style="color:var(--info);margin-right:8px"></i> Created "Data Science" CV</span>
              </div>
            </div>
            <div class="list-card">
              <div class="list-head" style="font-size:0.9rem">
                <span><i class="fas fa-qrcode" style="color:var(--warning);margin-right:8px"></i> Generated QR for SWE CV</span>
              </div>
            </div>
          </div>
        </div>
      </aside>

      <!-- Right Column (My Resumes) -->
      <section class="section-stack delay-2">
        <div class="card border-0">
          <div class="card-header">
            <h3 class="card-title">My Resumes</h3>
          </div>
          
          <div class="section-stack gap-md">
            
            <!-- Resume 1 -->
            <article class="card tight" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;background:var(--surface-alt)">
              <div>
                <h4 style="margin:0;font-size:1.1rem">Software Engineer CV</h4>
                <div style="margin-top:6px;font-size:0.85rem;color:var(--text-muted)">
                   <span class="badge approved" style="margin-right:8px"><i class="fas fa-check"></i> Approved</span>
                   Last updated: Apr 21, 2026
                </div>
              </div>
              <div class="btn-row">
                <a class="btn btn-sm" href="create-cv.html"><i class="fas fa-edit"></i> Edit</a>
                <a class="btn btn-sm btn-primary" href="qr-code.html?cv=swe"><i class="fas fa-qrcode"></i> Target QR</a>
                <button class="btn btn-sm btn-ghost" style="color:var(--danger)"><i class="fas fa-trash"></i></button>
              </div>
            </article>

            <!-- Resume 2 -->
            <article class="card tight" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;background:var(--surface-alt)">
              <div>
                <h4 style="margin:0;font-size:1.1rem">Data Science & ML Resume</h4>
                <div style="margin-top:6px;font-size:0.85rem;color:var(--text-muted)">
                   <span class="badge pending" style="margin-right:8px"><i class="fas fa-hourglass-half"></i> In Review</span>
                   Last updated: Apr 20, 2026
                </div>
              </div>
              <div class="btn-row">
                <a class="btn btn-sm" href="create-cv.html"><i class="fas fa-edit"></i> Edit</a>
                <button class="btn btn-sm" disabled title="Wait for approval"><i class="fas fa-qrcode"></i> Generating</button>
                <button class="btn btn-sm btn-ghost" style="color:var(--danger)"><i class="fas fa-trash"></i></button>
              </div>
            </article>

            <!-- Resume 3 -->
            <article class="card tight" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;background:var(--surface-alt)">
              <div>
                <h4 style="margin:0;font-size:1.1rem">General Academic CV</h4>
                <div style="margin-top:6px;font-size:0.85rem;color:var(--text-muted)">
                   <span class="badge draft" style="margin-right:8px"><i class="fas fa-file-alt"></i> Draft</span>
                   Last updated: Apr 19, 2026
                </div>
              </div>
              <div class="btn-row">
                <a class="btn btn-sm" href="create-cv.html"><i class="fas fa-edit"></i> Edit</a>
                <button class="btn btn-sm btn-ghost" style="color:var(--danger)"><i class="fas fa-trash"></i></button>
              </div>
            </article>

          </div>
        </div>
      </section>
    </div>
  </main>
  <script>
      // Mobile Menu Nav Toggle
      document.getElementById('mobile-menu-toggle').addEventListener('click', () => {
        document.querySelector('.nav-links').classList.toggle('mobile-open');
      });
  </script>
</body>
</html>