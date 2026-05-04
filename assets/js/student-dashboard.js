document.addEventListener("DOMContentLoaded", async () => {
  const menuToggle = document.getElementById("menu-toggle");
  const sidebar = document.getElementById("sidebar");
  const searchInput = document.getElementById("search-input");
  await hydrateCv();
  const currentUser = window.UserSession ? window.UserSession.getUser() : null;
  const cv = window.CVStorage.getCv();
  const hasCv = Boolean(cv.fullName || cv.email || cv.summary || cv.education || cv.experience);

  if (window.innerWidth <= 768 && menuToggle) {
    menuToggle.style.display = "flex";
  }

  if (menuToggle && sidebar) {
    menuToggle.addEventListener("click", () => sidebar.classList.toggle("open"));
  }

  renderHeader(cv, currentUser, hasCv);
  renderStats(cv, hasCv);
  renderDashboardState(hasCv);

  if (hasCv) {
    renderChecklist(cv);
    renderReviewNote(cv);
    renderSummary(cv, currentUser);
    renderDetailSections(cv);
    renderActivity(cv);
  }

  searchInput.addEventListener("input", (event) => {
    filterDetails(event.target.value);
  });
});

async function hydrateCv() {
  try {
    const res = await fetch("php_actions/cv_actions.php?action=get_cv", {
      headers: { Accept: "application/json" }
    });
    const data = await res.json();
    if (data.success && data.user && window.UserSession) {
      window.UserSession.saveUser(data.user);
    }
    if (data.success && data.cv) {
      window.CVStorage.setCv(data.cv);
    } else if (data.success) {
      window.CVStorage.clearCv();
    }
  } catch (error) {
    // Fall back to cached data if the request fails.
  }
}

function renderHeader(cv, currentUser, hasCv) {
  const name = hasCv && cv.fullName
    ? cv.fullName
    : currentUser && currentUser.fullName
      ? currentUser.fullName
      : "Student";
  const subtitle = hasCv
    ? "Here is the latest information saved in your digital CV."
    : "Create your CV to see your details, completion status, and activity here.";
  const role = hasCv && cv.profession
    ? cv.profession
    : currentUser && currentUser.email
      ? currentUser.email
      : "student@example.com";
  const initials = window.CVStorage.getInitials(name);

  document.getElementById("dashboard-greeting").textContent = hasCv ? `Welcome back, ${name}` : "Welcome to DigiCV";
  document.getElementById("dashboard-subtitle").textContent = subtitle;
  document.getElementById("sidebar-name").textContent = name;
  document.getElementById("sidebar-role").textContent = role;
  document.getElementById("sidebar-avatar").textContent = initials;
  document.getElementById("header-avatar").textContent = initials;
}

function renderStats(cv, hasCv) {
  document.getElementById("stat-cv-count").textContent = hasCv ? "1" : "0";
  document.getElementById("stat-status").textContent = hasCv ? cv.status || "Draft" : "Not Started";
  document.getElementById("stat-completion").textContent = hasCv ? `${window.CVStorage.getCompletion(cv)}%` : "0%";
  document.getElementById("stat-last-updated").textContent = hasCv ? window.CVStorage.formatDate(cv.updatedAt) : "No Data";
}

function renderDashboardState(hasCv) {
  document.getElementById("dashboard-empty-state").style.display = hasCv ? "none" : "block";
  document.getElementById("dashboard-content").style.display = hasCv ? "block" : "none";
}

function renderChecklist(cv) {
  const checklist = window.CVStorage.buildChecklist(cv);
  const completion = window.CVStorage.getCompletion(cv);
  const checklistContainer = document.getElementById("cv-checklist");
  const statusBadge = document.getElementById("cv-status-badge");

  document.getElementById("cv-completion-label").textContent = `${completion}%`;
  document.getElementById("cv-progress-fill").style.width = `${completion}%`;
  statusBadge.className = window.CVStorage.getStatusClass(cv.status || "Draft");
  statusBadge.innerHTML = `<i class="fas ${cv.status === "Pending Review" ? "fa-hourglass-half" : "fa-pen"}"></i> ${cv.status || "Draft"}`;

  checklistContainer.innerHTML = checklist
    .map((item) => `
      <div class="flex items-center gap-md">
        <i class="fas ${item.completed ? "fa-circle-check" : "fa-circle"}" style="color: ${item.completed ? "var(--success)" : "var(--gray-600)"}; ${item.completed ? "" : "font-size: 0.65rem; margin: 0 3px;"}"></i>
        <span style="font-size: 0.9rem; ${item.completed ? "" : "color: var(--text-secondary);"}">${item.label}</span>
      </div>
    `)
    .join("");
}

function renderSummary(cv, currentUser) {
  document.getElementById("cv-full-name").textContent = cv.fullName || (currentUser && currentUser.fullName) || "-";
  document.getElementById("cv-profession").textContent = cv.profession || "-";
  document.getElementById("cv-email").textContent = cv.email || (currentUser && currentUser.email) || "-";
  document.getElementById("cv-phone").textContent = cv.phone || "-";
  document.getElementById("cv-summary").textContent = cv.summary || "No professional summary added yet.";

  const skills = window.CVStorage.toArray(cv.technicalSkills);
  const skillsContainer = document.getElementById("cv-skills");

  if (!skills.length) {
    skillsContainer.innerHTML = '<span style="color: var(--text-secondary);">No technical skills added yet.</span>';
    return;
  }

  skillsContainer.innerHTML = skills
    .map((skill) => `<span class="cv-skill-tag" style="background: rgba(99,102,241,.15); color: var(--primary-300);">${escapeHtml(skill)}</span>`)
    .join("");
}

function renderReviewNote(cv) {
  const card = document.getElementById("cv-review-note-card");
  const text = document.getElementById("cv-review-note");
  if (!card || !text) {
    return;
  }

  if (!cv.reviewNote) {
    card.style.display = "none";
    return;
  }

  card.style.display = "block";
  text.textContent = cv.reviewNote;
}

function renderDetailSections(cv) {
  const sections = [
    { title: "Education", icon: "fa-graduation-cap", content: cv.education },
    { title: "Experience", icon: "fa-briefcase", content: cv.experience },
    { title: "Soft Skills", icon: "fa-people-group", content: cv.softSkills },
    { title: "Languages", icon: "fa-language", content: cv.languages },
    { title: "Projects", icon: "fa-diagram-project", content: cv.projects },
    { title: "Certificates", icon: "fa-award", content: cv.certifications }
  ];

  const container = document.getElementById("cv-detail-sections");
  container.innerHTML = sections
    .map((section) => `
      <div class="dashboard-detail-card" data-searchable="${escapeHtml(`${section.title} ${section.content || ""}`.toLowerCase())}">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: var(--space-sm);">
          <i class="fas ${section.icon}" style="color: var(--primary-300);"></i>
          <h4 style="font-size: 1rem;">${section.title}</h4>
        </div>
        <p style="color: var(--text-secondary); white-space: pre-line;">${section.content ? escapeHtml(section.content) : "No information added yet."}</p>
      </div>
    `)
    .join("");
}

function renderActivity(cv) {
  const activityList = Array.isArray(cv.activity) ? cv.activity : [];
  const container = document.getElementById("recent-activity-list");

  if (!activityList.length) {
    container.innerHTML = `
      <div class="empty-state" style="padding: var(--space-xl);">
        <div class="empty-state__icon" style="font-size: 2rem;"><i class="fas fa-clock"></i></div>
        <h3 class="empty-state__title">No activity yet</h3>
        <p class="empty-state__desc">Save or submit your CV to start seeing activity here.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = `<div class="timeline">${
    activityList
      .slice(0, 5)
      .map((item) => `
        <div class="timeline-item">
          <div class="timeline-item__dot ${item.label.includes("submitted") ? "timeline-item__dot--warning" : "timeline-item__dot--success"}"></div>
          <div class="timeline-item__content">${escapeHtml(item.label)}</div>
          <div class="timeline-item__time">${window.CVStorage.formatDate(item.time)}</div>
        </div>
      `)
      .join("")
  }</div>`;
}

function filterDetails(searchTerm) {
  const normalizedTerm = searchTerm.trim().toLowerCase();
  const cards = document.querySelectorAll(".dashboard-detail-card");

  cards.forEach((card) => {
    const content = card.getAttribute("data-searchable") || "";
    card.style.display = !normalizedTerm || content.includes(normalizedTerm) ? "block" : "none";
  });
}

function escapeHtml(value) {
  const div = document.createElement("div");
  div.textContent = value || "";
  return div.innerHTML;
}
