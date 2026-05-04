document.addEventListener("DOMContentLoaded", async () => {
  const menuToggle = document.getElementById("menu-toggle");
  const sidebar = document.getElementById("sidebar");
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

  renderIdentity(cv, currentUser);
  document.getElementById("preview-empty-state").style.display = hasCv ? "none" : "block";
  document.getElementById("preview-content").style.display = hasCv ? "block" : "none";

  if (!hasCv) {
    return;
  }

  const status = cv.status || "Draft";
  const badge = document.getElementById("preview-status-badge");
  badge.className = window.CVStorage.getStatusClass(status);
  badge.innerHTML = `<i class="fas ${status === "Pending Review" ? "fa-hourglass-half" : "fa-pen"}"></i> ${status}`;
  document.getElementById("preview-last-updated").textContent = `Last updated: ${window.CVStorage.formatDate(cv.updatedAt)}`;
  renderReviewNote(cv);

  document.getElementById("preview-name").textContent = cv.fullName || currentUser?.fullName || "Student";
  document.getElementById("preview-title").textContent = cv.profession || "Professional title not added";
  document.getElementById("preview-summary").textContent = cv.summary || "No professional summary added.";
  document.getElementById("preview-education").textContent = cv.education || "No education details added.";
  document.getElementById("preview-experience").textContent = cv.experience || "No work experience added.";
  document.getElementById("preview-projects").textContent = cv.projects || "No projects added.";
  document.getElementById("preview-languages").textContent = cv.languages || "No languages added.";
  document.getElementById("preview-certifications").textContent = cv.certifications || "No certifications added.";

  document.getElementById("preview-email").innerHTML = `<i class="fas fa-envelope" style="margin-right: 4px;"></i>${escapeHtml(cv.email || currentUser?.email || "student@example.com")}`;
  document.getElementById("preview-phone").innerHTML = `<i class="fas fa-phone" style="margin-right: 4px;"></i>${escapeHtml(cv.phone || "No phone added")}`;
  document.getElementById("preview-address").innerHTML = `<i class="fas fa-location-dot" style="margin-right: 4px;"></i>${escapeHtml(cv.address || "No address added")}`;

  renderSkills("preview-technical-skills", window.CVStorage.toArray(cv.technicalSkills), "rgba(99,102,241,.15)", "var(--primary-700)");
  renderSkills("preview-soft-skills", window.CVStorage.toArray(cv.softSkills), "#ecfdf5", "#065f46");
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

function renderIdentity(cv, currentUser) {
  const name = cv.fullName || currentUser?.fullName || "Student";
  const email = cv.email || currentUser?.email || "student@example.com";
  const initials = window.CVStorage.getInitials(name);

  document.getElementById("preview-avatar").textContent = initials;
  document.getElementById("preview-document-avatar").textContent = initials;
  document.getElementById("preview-sidebar-name").textContent = name;
  document.getElementById("preview-sidebar-email").textContent = email;
}

function renderSkills(containerId, items, background, color) {
  const container = document.getElementById(containerId);

  if (!items.length) {
    container.innerHTML = '<span style="color: var(--gray-500);">No items added.</span>';
    return;
  }

  container.innerHTML = items
    .map((item) => `<span class="cv-skill-tag" style="background: ${background}; color: ${color};">${escapeHtml(item)}</span>`)
    .join("");
}

function renderReviewNote(cv) {
  const card = document.getElementById("preview-review-note-card");
  const text = document.getElementById("preview-review-note");
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

function escapeHtml(value) {
  const div = document.createElement("div");
  div.textContent = value || "";
  return div.innerHTML;
}
