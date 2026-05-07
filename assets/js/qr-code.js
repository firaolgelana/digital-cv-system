document.addEventListener("DOMContentLoaded", async () => {
  const menuToggle = document.getElementById("menu-toggle");
  const sidebar = document.getElementById("sidebar");
  const qrListContainer = document.getElementById("qr-list-container");
  const qrEmptyState = document.getElementById("qr-empty-state");

  if (window.innerWidth <= 768 && menuToggle) {
    menuToggle.style.display = "flex";
  }

  if (sidebar && menuToggle) {
    menuToggle.addEventListener("click", () => sidebar.classList.toggle("open"));
  }

  // Load all QR data
  await loadQrPortfolio();

  async function loadQrPortfolio() {
    qrListContainer.innerHTML = `
      <div class="flex justify-center p-xl" style="grid-column: 1 / -1;">
        <i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary-400);"></i>
      </div>
    `;

    const payload = await fetchJson("php_actions/generate_qr.php?action=get_all_qr");
    if (!payload?.success || !payload.qrs || payload.qrs.length === 0) {
      qrListContainer.style.display = "none";
      qrEmptyState.style.display = "block";
      return;
    }

    qrListContainer.style.display = "grid";
    qrEmptyState.style.display = "none";
    qrListContainer.innerHTML = "";

    payload.qrs.forEach((item, index) => {
      const card = createQrCard(item, index);
      qrListContainer.appendChild(card);
    });

    // Update identity if available
    if (payload.user) {
      renderIdentity(payload.user);
    }
  }

  function createQrCard(item, index) {
    const { cv, qr } = item;
    const card = document.createElement("div");
    card.className = "card animate-fade-in";
    card.style.animationDelay = `${index * 0.1}s`;
    card.style.textAlign = "center";
    card.style.display = "flex";
    card.style.flexDirection = "column";

    const isApproved = cv.status === "Approved";
    const statusClass = window.CVStorage.getStatusClass(cv.status);
    const hasQr = qr && qr.qrImageUrl;

    card.innerHTML = `
      <div style="margin-bottom: var(--space-md);">
        <span class="${statusClass}">
          <i class="fas ${isApproved ? 'fa-circle-check' : 'fa-circle-info'}"></i> ${cv.status}
        </span>
      </div>

      <div class="qr-card__image" style="margin: 0 auto var(--space-md); width: 180px; height: 180px; display: flex; align-items: center; justify-content: center; background: var(--surface-elevated); border-radius: var(--radius-lg); position: relative; overflow: hidden;">
        ${hasQr 
          ? `<img src="${qr.qrImageUrl}" alt="QR" style="width: 100%; height: 100%; object-fit: contain;" />`
          : `<div style="color: var(--text-muted); font-size: 0.8rem; padding: 1rem;">${isApproved ? 'No QR generated yet' : 'Waiting for approval'}</div>`
        }
      </div>

      <h3 style="font-family: var(--font-heading); font-size: 1.1rem; margin-bottom: 2px;">${cv.profession || 'Resume #' + cv.id}</h3>
      <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: var(--space-lg);">${cv.department || 'General'}</p>

      <div class="flex flex-col gap-sm" style="margin-top: auto;">
        ${!hasQr && isApproved 
          ? `<button class="btn btn-primary btn-sm generate-btn" data-id="${cv.id}"><i class="fas fa-wand-magic-sparkles"></i> Generate QR</button>`
          : ''
        }
        ${hasQr 
          ? `
            <div class="flex gap-xs justify-center">
              <button class="btn btn-secondary btn-sm download-btn" data-url="${qr.qrImageUrl}" data-name="${cv.fullName}" data-id="${cv.id}" title="Download"><i class="fas fa-download"></i></button>
              <button class="btn btn-outline btn-sm copy-btn" data-link="${qr.shareUrl}" data-id="${cv.id}" title="Copy Link"><i class="fas fa-link"></i></button>
              <a href="${qr.shareUrl}" target="_blank" class="btn btn-ghost btn-sm" title="Open"><i class="fas fa-up-right-from-square"></i></a>
            </div>
            <div style="margin-top: var(--space-sm); font-size: 0.75rem; color: var(--text-muted); display: flex; justify-content: center; gap: 12px;">
              <span><i class="fas fa-eye"></i> ${qr.accessCount || 0}</span>
              <span><i class="fas fa-copy"></i> ${qr.copiedCount || 0}</span>
              <span><i class="fas fa-download"></i> ${qr.downloadedCount || 0}</span>
            </div>
          `
          : ''
        }
        ${!isApproved ? `<p style="font-size: 0.75rem; color: var(--warning);">Approved CV required for QR</p>` : ''}
      </div>
    `;

    // Add event listeners
    const genBtn = card.querySelector(".generate-btn");
    if (genBtn) {
      genBtn.addEventListener("click", async () => {
        genBtn.disabled = true;
        genBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        const res = await postJson("php_actions/generate_qr.php", { action: "generate_qr", id: cv.id });
        if (res?.success) {
          loadQrPortfolio();
        } else {
          alert(res?.message || "Failed to generate QR");
          genBtn.disabled = false;
          genBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Generate QR';
        }
      });
    }

    const copyBtn = card.querySelector(".copy-btn");
    if (copyBtn) {
      copyBtn.addEventListener("click", async () => {
        await navigator.clipboard.writeText(copyBtn.dataset.link);
        alert("Link copied to clipboard!");
        postJson("php_actions/generate_qr.php", { action: "track_copy", id: cv.id });
        loadQrPortfolio(); // Update stats
      });
    }

    const downBtn = card.querySelector(".download-btn");
    if (downBtn) {
      downBtn.addEventListener("click", async () => {
        try {
          const response = await fetch(downBtn.dataset.url);
          const blob = await response.blob();
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement("a");
          a.href = url;
          a.download = `cv-qr-${cv.id}.png`;
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          window.URL.revokeObjectURL(url);
          
          postJson("php_actions/generate_qr.php", { action: "track_download", id: cv.id });
          setTimeout(loadQrPortfolio, 1000);
        } catch (e) {
          alert("Download failed. Try right-click > Save Image As on the QR.");
        }
      });
    }

    return card;
  }

  function renderIdentity(user) {
    const initials = window.CVStorage.getInitials(user.full_name);
    document.getElementById("qr-avatar").textContent = initials;
    document.getElementById("qr-header-avatar").textContent = initials;
    document.getElementById("qr-sidebar-name").textContent = user.full_name;
    document.getElementById("qr-sidebar-email").textContent = user.email;
  }
});

async function fetchJson(url) {
  try {
    const response = await fetch(url, { headers: { Accept: "application/json" } });
    return await response.json();
  } catch (error) {
    return null;
  }
}

async function postJson(url, payload) {
  try {
    const response = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify(payload)
    });
    return await response.json();
  } catch (error) {
    return null;
  }
}
