document.addEventListener("DOMContentLoaded", () => {
  const encodedPayload = new URLSearchParams(window.location.hash.replace(/^#/, "")).get("cv");
  const payload = encodedPayload ? window.CVShare.decodePayload(encodedPayload) : null;
  const errorCard = document.getElementById("public-cv-error");
  const content = document.getElementById("public-cv-content");

  if (!payload || !payload.student) {
    errorCard.style.display = "block";
    content.style.display = "none";
    document.getElementById("download-public-cv-btn").disabled = true;
    return;
  }

  const student = payload.student;
  errorCard.style.display = "none";
  content.style.display = "block";

  document.getElementById("public-cv-avatar").textContent = getInitials(student.fullName);
  document.getElementById("public-cv-name").textContent = student.fullName || "Student";
  document.getElementById("public-cv-title").textContent = student.profession || "Professional title not added";
  document.getElementById("public-cv-email").innerHTML = `<i class="fas fa-envelope" style="margin-right: 4px;"></i>${window.CVShare.escapeHtml(student.email || "No email added")}`;
  document.getElementById("public-cv-phone").innerHTML = `<i class="fas fa-phone" style="margin-right: 4px;"></i>${window.CVShare.escapeHtml(student.phone || "No phone added")}`;
  document.getElementById("public-cv-address").innerHTML = `<i class="fas fa-location-dot" style="margin-right: 4px;"></i>${window.CVShare.escapeHtml(student.address || "No address added")}`;
  document.getElementById("public-cv-summary").textContent = student.summary || "No professional summary added.";
  document.getElementById("public-cv-education").textContent = student.education || "No education details added.";
  document.getElementById("public-cv-experience").textContent = student.experience || "No work experience added.";
  document.getElementById("public-cv-projects").textContent = student.projects || "No projects added.";
  document.getElementById("public-cv-languages").textContent = student.languages || "No languages added.";
  document.getElementById("public-cv-certifications").textContent = student.certifications || "No certifications added.";

  renderSkills("public-cv-technical-skills", window.CVShare.toArray(student.technicalSkills), "rgba(99,102,241,.15)", "var(--primary-700)");
  renderSkills("public-cv-soft-skills", window.CVShare.toArray(student.softSkills), "#ecfdf5", "#065f46");
  renderLinks(student);

  document.getElementById("download-public-cv-btn").addEventListener("click", () => {
    const filename = `${slugify(student.fullName || "student")}-cv.html`;
    window.CVShare.downloadHtmlFile(filename, window.CVShare.buildDownloadHtml(payload));
  });

  document.getElementById("print-public-cv-btn").addEventListener("click", () => {
    window.print();
  });
});

function renderSkills(containerId, items, background, color) {
  const container = document.getElementById(containerId);

  if (!items.length) {
    container.innerHTML = '<span style="color: var(--gray-500);">No items added.</span>';
    return;
  }

  container.innerHTML = items
    .map((item) => `<span class="cv-skill-tag" style="background: ${background}; color: ${color};">${window.CVShare.escapeHtml(item)}</span>`)
    .join("");
}

function renderLinks(student) {
  const container = document.getElementById("public-cv-links");
  const links = [];

  if (student.linkedin) {
    links.push(`<a href="${window.CVShare.escapeHtml(student.linkedin)}" target="_blank" rel="noopener noreferrer">LinkedIn</a>`);
  }

  if (student.portfolio) {
    links.push(`<a href="${window.CVShare.escapeHtml(student.portfolio)}" target="_blank" rel="noopener noreferrer">Portfolio</a>`);
  }

  container.innerHTML = links.length ? links.join(" | ") : "No external links added.";
}

function getInitials(name) {
  return (name || "Student")
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join("");
}

function slugify(value) {
  return (value || "student")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}
