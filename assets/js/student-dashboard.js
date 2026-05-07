document.addEventListener("DOMContentLoaded", async () => {
  const currentUser = window.UserSession ? window.UserSession.getUser() : null;
  
  const payload = await fetchJson("php_actions/generate_qr.php?action=get_all_qr");
  if (payload?.success && payload.user && window.UserSession) {
    window.UserSession.saveUser(payload.user);
  }

  const user = payload?.user || currentUser;
  const resumes = payload?.success ? payload.qrs : [];

  renderHeader(user, resumes.length > 0);
  renderDashboardState(resumes.length > 0);
  
  if (resumes.length > 0) {
    renderAggregateStats(resumes);
    renderRecentResumes(resumes);
    renderRecentActivity(resumes);
  } else {
    document.getElementById("stat-cv-count").textContent = "0";
    document.getElementById("stat-approved-qrs").textContent = "0";
    document.getElementById("stat-total-views").textContent = "0";
  }
});

function renderHeader(user, hasCvs) {
  const name = user?.full_name || "Student";
  const initials = window.CVStorage.getInitials(name);
  const subtitle = hasCvs 
    ? "Welcome back! Here is an overview of your resume portfolio."
    : "Create your CV to see your details, completion status, and activity here.";

  document.getElementById("dashboard-greeting").textContent = hasCvs ? `Welcome back, ${name}` : "Welcome to DigiCV";
  document.getElementById("dashboard-subtitle").textContent = subtitle;
  document.getElementById("sidebar-name").textContent = name;
  document.getElementById("sidebar-role").textContent = user?.email || "student@example.com";
  document.getElementById("sidebar-avatar").textContent = initials;
  document.getElementById("header-avatar").textContent = initials;
}

function renderDashboardState(hasCvs) {
  document.getElementById("dashboard-empty-state").style.display = hasCvs ? "none" : "block";
  document.getElementById("dashboard-content").style.display = hasCvs ? "block" : "none";
}

function renderAggregateStats(resumes) {
  const totalCount = resumes.length;
  let approvedCount = 0;
  let totalViews = 0;
  let totalCopies = 0;
  let totalDownloads = 0;
  let latestUpdate = null;

  resumes.forEach(item => {
    if (item.cv.status === "Approved") approvedCount++;
    if (item.qr) {
      totalViews += (item.qr.accessCount || 0);
      totalCopies += (item.qr.copiedCount || 0);
      totalDownloads += (item.qr.downloadedCount || 0);
    }
    const upDate = new Date(item.cv.updatedAt);
    if (!latestUpdate || upDate > latestUpdate) latestUpdate = upDate;
  });

  document.getElementById("stat-cv-count").textContent = totalCount;
  document.getElementById("stat-approved-qrs").textContent = approvedCount;
  document.getElementById("stat-total-views").textContent = totalViews;
  document.getElementById("stat-last-activity").textContent = latestUpdate ? window.CVStorage.formatDate(latestUpdate) : "No Data";

  document.getElementById("dash-total-copies").textContent = totalCopies;
  document.getElementById("dash-total-downloads").textContent = totalDownloads;
}

function renderRecentResumes(resumes) {
  const container = document.getElementById("recent-resumes-list");
  container.innerHTML = resumes.slice(0, 3).map(item => {
    const { cv } = item;
    const statusClass = window.CVStorage.getStatusClass(cv.status);
    return `
      <div class="notif-item animate-fade-in" onclick="window.location.href='cv-preview.html?id=${cv.id}'">
        <div class="notif-item__icon">
          <i class="fas fa-file-lines"></i>
        </div>
        <div class="notif-item__content">
          <div class="notif-item__title" style="display:flex; justify-content:space-between;">
            ${cv.profession || 'Untitled Resume'}
            <span class="${statusClass}" style="font-size:0.7rem; padding: 2px 8px;">${cv.status}</span>
          </div>
          <div class="notif-item__desc">Last updated on ${window.CVStorage.formatDate(cv.updatedAt)}</div>
          <div class="notif-item__meta">
            <i class="fas fa-circle-info"></i> ${cv.department || 'General'}
          </div>
        </div>
        <div class="notif-item__actions">
           <a href="create-cv.html?id=${cv.id}" class="btn btn-ghost btn-icon btn-sm" title="Edit"><i class="fas fa-pen"></i></a>
        </div>
      </div>
    `;
  }).join("");
}

function renderRecentActivity(resumes) {
    // In a multi-CV system, we'll just link Activity to the Notification center for simplicity 
    // unless we want to aggregate all CV activity. For now, the dashboard content redesign 
    // removed the activity block to favor 'Recent Resumes'.
}

async function fetchJson(url) {
  try {
    const response = await fetch(url, { headers: { Accept: "application/json" } });
    return await response.json();
  } catch (error) {
    return null;
  }
}
