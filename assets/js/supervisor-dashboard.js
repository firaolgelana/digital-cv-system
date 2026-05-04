document.addEventListener("DOMContentLoaded", () => {
  const menuToggle = document.getElementById("menu-toggle");
  const sidebar = document.getElementById("sidebar");
  const searchInput = document.getElementById("review-search");
  const reviewList = document.getElementById("review-list");
  const recentBody = document.getElementById("recent-reviewed-body");
  const modal = document.getElementById("cv-modal");
  const modalContent = document.getElementById("cv-modal-content");
  const modalTitle = document.getElementById("cv-modal-title");
  const toastEl = document.getElementById("review-toast");
  const tabs = Array.from(document.querySelectorAll("[data-status]"));
  const state = {
    status: "pending",
    search: "",
    submissions: [],
  };

  if (window.innerWidth <= 768 && menuToggle) {
    menuToggle.style.display = "flex";
  }
  if (menuToggle && sidebar) {
    menuToggle.addEventListener("click", () => sidebar.classList.toggle("open"));
  }

  document.getElementById("close-cv-modal").addEventListener("click", closeModal);
  modal.addEventListener("click", (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });

  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      state.status = tab.dataset.status || "pending";
      tabs.forEach((btn) => btn.classList.toggle("active", btn === tab));
      loadDashboard();
    });
  });

  let searchTimer = null;
  searchInput.addEventListener("input", () => {
    clearTimeout(searchTimer);
    state.search = searchInput.value.trim();
    searchTimer = window.setTimeout(loadDashboard, 300);
  });

  loadDashboard();

  async function loadDashboard() {
    reviewList.innerHTML = '<div class="empty-block">Loading submissions…</div>';

    try {
      const params = new URLSearchParams({
        action: "get_dashboard",
        status: state.status,
        search: state.search
      });
      const res = await fetch(`php_actions/approval_action.php?${params.toString()}`, {
        headers: { Accept: "application/json" }
      });
      const data = await parseJsonResponse(res);

      if (!data.success) {
        reviewList.innerHTML = `<div class="empty-block">${escapeHtml(data.message || "Unable to load submissions.")}</div>`;
        return;
      }

      state.submissions = Array.isArray(data.submissions) ? data.submissions : [];
      renderStats(data.stats || {});
      renderReviewList(state.submissions);
      renderRecentReviewed(Array.isArray(data.recent_reviewed) ? data.recent_reviewed : []);
    } catch (error) {
      reviewList.innerHTML = `<div class="empty-block">${escapeHtml(error.message || "Network error. Please try again.")}</div>`;
    }
  }

  function renderStats(stats) {
    document.getElementById("stat-pending").textContent = String(stats.pending || 0);
    document.getElementById("stat-approved").textContent = String(stats.approved || 0);
    document.getElementById("stat-revision").textContent = String(stats.changes_requested || 0);
    document.getElementById("stat-total-students").textContent = String(stats.total_students || 0);
  }

  function renderReviewList(items) {
    if (!items.length) {
      reviewList.innerHTML = '<div class="card"><div class="empty-block">No submissions match the current filter.</div></div>';
      return;
    }

    reviewList.innerHTML = items.map((item) => {
      const badge = statusBadge(item.status);
      const note = item.review_note
        ? `<div class="review-card__note"><strong>Reviewer note:</strong> ${escapeHtml(item.review_note)}</div>`
        : "";

      return `
        <div class="review-card animate-fade-in" data-id="${item.id}">
          <div class="review-card__header">
            <div class="avatar avatar--primary">${escapeHtml(initials(item.full_name))}</div>
            <div class="review-card__student">
              <div class="review-card__name">${escapeHtml(item.full_name)}</div>
              <div class="review-card__meta">
                ${escapeHtml(item.department)} • ${escapeHtml(item.student_number)} • ${escapeHtml(item.profession || "No title")}
              </div>
              <div class="review-card__meta">
                Submitted ${formatDate(item.submitted_at)}${item.reviewed_at ? ` • Reviewed ${formatDate(item.reviewed_at)}` : ""}
              </div>
            </div>
            ${badge}
          </div>
          <div class="review-card__details">
            <div class="review-card__detail">
              <div class="review-card__detail-label">Completion</div>
              <div class="review-card__detail-value" style="color:${completionColor(item.completion)}">${item.completion}%</div>
            </div>
            <div class="review-card__detail">
              <div class="review-card__detail-label">Sections</div>
              <div class="review-card__detail-value">${item.completed_sections} / ${item.total_sections}</div>
            </div>
            <div class="review-card__detail">
              <div class="review-card__detail-label">Documents</div>
              <div class="review-card__detail-value">${item.documents_count} file${item.documents_count === 1 ? "" : "s"}</div>
            </div>
          </div>
          <div class="review-card__actions">
            <button class="btn btn-secondary btn-sm" data-view="${item.id}"><i class="fas fa-eye"></i> View CV</button>
            <button class="btn btn-success btn-sm" data-review="${item.id}" data-decision="approve"><i class="fas fa-check"></i> Approve</button>
            <button class="btn btn-outline btn-sm" data-review="${item.id}" data-decision="request_changes"><i class="fas fa-rotate-left"></i> Request Changes</button>
            <button class="btn btn-danger btn-sm" data-review="${item.id}" data-decision="reject"><i class="fas fa-xmark"></i> Reject</button>
          </div>
          ${note}
        </div>
      `;
    }).join("");

    reviewList.querySelectorAll("[data-view]").forEach((button) => {
      button.addEventListener("click", () => {
        const cvId = Number(button.dataset.view);
        const record = state.submissions.find((item) => item.id === cvId);
        if (record) {
          openModal(record);
        }
      });
    });

    reviewList.querySelectorAll("[data-review]").forEach((button) => {
      button.addEventListener("click", async () => {
        const cvId = Number(button.dataset.review);
        const decision = button.dataset.decision;
        const promptLabel = decision === "approve"
          ? "Optional approval note:"
          : decision === "request_changes"
            ? "What changes are required?"
            : "Reason for rejection:";
        const note = window.prompt(promptLabel, "");
        if (note === null) {
          return;
        }
        await submitReview(cvId, decision, note);
      });
    });
  }

  function renderRecentReviewed(items) {
    if (!items.length) {
      recentBody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--text-secondary); padding:1rem;">No review decisions yet.</td></tr>';
      return;
    }

    recentBody.innerHTML = items.map((item) => `
      <tr>
        <td>
          <div class="flex items-center gap-sm">
            <div class="avatar avatar--sm avatar--accent">${escapeHtml(initials(item.full_name))}</div>
            <span>${escapeHtml(item.full_name)}</span>
          </div>
        </td>
        <td>${escapeHtml(item.department)}</td>
        <td>${statusBadge(item.status)}</td>
        <td>${escapeHtml(formatDate(item.submitted_at))}</td>
        <td>${escapeHtml(formatDate(item.reviewed_at))}</td>
        <td>${item.review_note ? escapeHtml(item.review_note) : '<span style="color: var(--text-muted);">No comment</span>'}</td>
      </tr>
    `).join("");
  }

  async function submitReview(cvId, decision, note) {
    try {
      const res = await fetch("php_actions/approval_action.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json"
        },
        body: JSON.stringify({
          action: "review_cv",
          cv_id: cvId,
          decision,
          note
        })
      });
      const data = await parseJsonResponse(res);
      if (!data.success) {
        toast(data.message || "Unable to save review action.", true);
        return;
      }

      toast(data.message, false);
      loadDashboard();
    } catch (error) {
      toast(error.message || "Network error. Please try again.", true);
    }
  }

  async function parseJsonResponse(response) {
    const contentType = response.headers.get("content-type") || "";
    if (!contentType.includes("application/json")) {
      if (response.status === 401) {
        throw new Error("Your session has expired. Please sign in again.");
      }
      if (response.status === 403) {
        throw new Error("You do not have permission to access this page.");
      }
      throw new Error("The server returned an unexpected response.");
    }

    const raw = await response.text();
    if (!raw.trim()) {
      throw new Error("The server returned an empty response.");
    }

    let data = null;
    try {
      data = JSON.parse(raw);
    } catch (error) {
      throw new Error("The server returned invalid JSON.");
    }

    if (!response.ok && !data.success) {
      throw new Error(data.message || "Request failed.");
    }
    return data;
  }

  function openModal(record) {
    modalTitle.textContent = `${record.full_name} — CV Review`;
    modalContent.innerHTML = `
      ${previewBlock("Profile", `
        <strong>${escapeHtml(record.full_name)}</strong><br />
        ${escapeHtml(record.profession || "No professional title")}<br />
        ${escapeHtml(record.email)}${record.phone ? ` • ${escapeHtml(record.phone)}` : ""}<br />
        ${escapeHtml(record.address || "No address")}
      `)}
      ${previewBlock("Summary", nl2br(record.summary || "No summary provided."))}
      ${previewBlock("Education", nl2br(record.education || "No education details provided."))}
      ${previewBlock("Experience", nl2br(record.experience || "No experience details provided."))}
      ${previewBlock("Technical Skills", nl2br(record.technical_skills || "No technical skills provided."))}
      ${previewBlock("Soft Skills", nl2br(record.soft_skills || "No soft skills provided."))}
      ${previewBlock("Languages", nl2br(record.languages || "No languages provided."))}
      ${previewBlock("Projects", nl2br(record.projects || "No projects provided."))}
      ${previewBlock("Certifications", nl2br(record.certifications || "No certifications provided."))}
      ${previewBlock("Links", buildLinks(record))}
    `;
    modal.classList.add("open");
  }

  function closeModal() {
    modal.classList.remove("open");
  }

  function buildLinks(record) {
    const links = [];
    if (record.linkedin) {
      links.push(`<a href="${escapeAttr(record.linkedin)}" target="_blank" rel="noopener noreferrer">LinkedIn</a>`);
    }
    if (record.portfolio) {
      links.push(`<a href="${escapeAttr(record.portfolio)}" target="_blank" rel="noopener noreferrer">Portfolio</a>`);
    }
    return links.length ? links.join(" | ") : "No external links provided.";
  }

  function previewBlock(title, body) {
    return `
      <div class="preview-block">
        <div style="font-weight:600; margin-bottom:8px;">${escapeHtml(title)}</div>
        <div style="color:var(--text-secondary); white-space:normal;">${body}</div>
      </div>
    `;
  }

  function statusBadge(status) {
    const cls = status === "Approved"
      ? "badge badge--approved"
      : status === "Rejected"
        ? "badge badge--rejected"
        : status === "Changes Requested"
          ? "badge badge--warning"
          : "badge badge--pending";
    const icon = status === "Approved"
      ? "fa-check"
      : status === "Rejected"
        ? "fa-xmark"
        : status === "Changes Requested"
          ? "fa-rotate-left"
          : "fa-clock";
    return `<span class="${cls}"><i class="fas ${icon}"></i> ${escapeHtml(status)}</span>`;
  }

  function formatDate(value) {
    if (!value) {
      return "No data";
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
      return "No data";
    }
    return date.toLocaleDateString(undefined, {
      month: "short",
      day: "numeric",
      year: "numeric"
    });
  }

  function completionColor(value) {
    if (value >= 85) return "var(--success)";
    if (value >= 60) return "var(--warning)";
    return "var(--danger)";
  }

  function initials(name) {
    return (name || "Student")
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part[0].toUpperCase())
      .join("");
  }

  function nl2br(value) {
    return escapeHtml(value || "").replace(/\n/g, "<br />");
  }

  function toast(message, isError) {
    toastEl.textContent = message;
    toastEl.className = isError ? "error" : "success";
    toastEl.style.display = "block";
    window.clearTimeout(toastEl._timer);
    toastEl._timer = window.setTimeout(() => {
      toastEl.style.display = "none";
    }, 2600);
  }

  function escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value || "";
    return div.innerHTML;
  }

  function escapeAttr(value) {
    return escapeHtml(value).replace(/"/g, "&quot;");
  }
});
