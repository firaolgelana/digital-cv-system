document.addEventListener("DOMContentLoaded", async () => {
  const menuToggle = document.getElementById("menu-toggle");
  const sidebar = document.getElementById("sidebar");
  const currentUser = window.UserSession ? window.UserSession.getUser() : null;

  if (window.innerWidth <= 768 && menuToggle) {
    menuToggle.style.display = "flex";
  }

  if (menuToggle && sidebar) {
    menuToggle.addEventListener("click", () => sidebar.classList.toggle("open"));
  }

  const latestPayload = await fetchJson("php_actions/cv_actions.php?action=get_cv");
  if (latestPayload?.success && latestPayload.user && window.UserSession) {
    window.UserSession.saveUser(latestPayload.user);
  }
  if (latestPayload?.success && latestPayload.cv) {
    window.CVStorage.setCv(latestPayload.cv);
  } else if (latestPayload?.success) {
    window.CVStorage.clearCv();
  }

  const latestCv = latestPayload?.cv || window.CVStorage.getCv();
  const sessionUser = (latestPayload?.user && window.UserSession ? window.UserSession.getUser() : null) || currentUser;
  const approvedPayload = await fetchJson("php_actions/generate_qr.php?action=get_qr");
  const approvedCv = approvedPayload?.success ? approvedPayload.cv : null;
  let qrMeta = approvedPayload?.success ? approvedPayload.qr : null;

  const hasCv = Boolean(latestCv?.fullName || latestCv?.email || latestCv?.summary || latestCv?.education || latestCv?.experience);

  renderIdentity(latestCv, sessionUser);
  document.getElementById("qr-empty-state").style.display = hasCv ? "none" : "block";
  document.getElementById("qr-content").style.display = hasCv ? "block" : "none";

  if (!hasCv) {
    return;
  }

  const activeCv = approvedCv || latestCv;
  const statusBadge = document.getElementById("qr-status-badge");
  const qrImage = document.getElementById("qr-image");
  const qrPlaceholder = document.getElementById("qr-placeholder");
  const shareLinkInput = document.getElementById("share-link-input");
  const qrMessage = document.getElementById("qr-message");
  const copyLinkButton = document.getElementById("copy-link-btn");
  const downloadQrButton = document.getElementById("download-qr-btn");
  const openPublicCvButton = document.getElementById("open-public-cv-btn");
  const generateQrButton = document.getElementById("generate-qr-btn");

  document.getElementById("qr-student-name").textContent = activeCv.fullName || sessionUser?.fullName || "Student";
  document.getElementById("qr-student-meta").textContent = [activeCv.department || sessionUser?.department, activeCv.email || sessionUser?.email].filter(Boolean).join(" • ") || "CV details saved";

  updateStatusBadge(statusBadge, latestCv.status || "Draft");

  if (latestCv.status !== "Approved") {
    generateQrButton.disabled = true;
    setMessage("Your CV must be approved before a public QR code can be generated.", true);
  } else {
    setMessage(qrMeta ? "Your last generated QR code is ready to share." : "Generate a unique QR code for your approved CV.", false);
  }

  updateStats(qrMeta);

  if (qrMeta?.shareUrl && qrMeta?.qrImageUrl) {
    applyQrState(qrMeta);
  }

  generateQrButton.addEventListener("click", async () => {
    if (latestCv.status !== "Approved") {
      return setMessage("Only approved CVs can be shared publicly.", true);
    }

    generateQrButton.disabled = true;
    setMessage("Generating a server-backed QR code...", false);

    const payload = await postJson("php_actions/generate_qr.php", { action: "generate_qr" });
    generateQrButton.disabled = false;

    if (!payload?.success || !payload.qr) {
      setMessage(payload?.message || "Unable to generate the QR code.", true);
      return;
    }

    qrMeta = payload.qr;
    updateStats(qrMeta);
    applyQrState(qrMeta);
    setMessage("Unique QR code generated. Anyone with the link can open your approved public CV.", false);
  });

  copyLinkButton.addEventListener("click", async () => {
    if (!qrMeta?.shareUrl) {
      return;
    }

    try {
      await navigator.clipboard.writeText(qrMeta.shareUrl);
      const payload = await postJson("php_actions/generate_qr.php", { action: "track_copy" });
      if (payload?.success && payload.qr) {
        qrMeta = payload.qr;
        updateStats(qrMeta);
      }
      setMessage("Public CV link copied successfully.", false);
    } catch (error) {
      setMessage("Unable to copy the link automatically.", true);
    }
  });

  downloadQrButton.addEventListener("click", async () => {
    if (!qrMeta?.qrImageUrl) {
      return;
    }

    const anchor = document.createElement("a");
    anchor.href = qrMeta.qrImageUrl;
    anchor.download = `${slugify(activeCv.fullName || sessionUser?.fullName || "student")}-cv-qr.png`;
    anchor.target = "_blank";
    anchor.rel = "noopener";
    anchor.click();

    const payload = await postJson("php_actions/generate_qr.php", { action: "track_download" });
    if (payload?.success && payload.qr) {
      qrMeta = payload.qr;
      updateStats(qrMeta);
    }
  });

  openPublicCvButton.addEventListener("click", () => {
    if (qrMeta?.shareUrl) {
      window.open(qrMeta.shareUrl, "_blank", "noopener");
    }
  });

  document.getElementById("share-native-btn").addEventListener("click", async () => {
    if (!qrMeta?.shareUrl) {
      return setMessage("Generate a QR code first.", true);
    }

    if (navigator.share) {
      try {
        await navigator.share({
          title: `${activeCv.fullName || sessionUser?.fullName || "Student"} CV`,
          text: "View and download my DigiCV profile.",
          url: qrMeta.shareUrl
        });
      } catch (error) {
        setMessage("Share was cancelled or not supported.", true);
      }
      return;
    }

    setMessage("This device does not support the native share menu.", true);
  });

  document.getElementById("share-email-btn").addEventListener("click", () => {
    if (!qrMeta?.shareUrl) {
      return setMessage("Generate a QR code first.", true);
    }

    window.location.href = `mailto:?subject=${encodeURIComponent("My DigiCV Profile")}&body=${encodeURIComponent(`View and download my CV here:\n${qrMeta.shareUrl}`)}`;
  });

  document.getElementById("share-whatsapp-btn").addEventListener("click", () => {
    if (!qrMeta?.shareUrl) {
      return setMessage("Generate a QR code first.", true);
    }

    window.open(`https://wa.me/?text=${encodeURIComponent(`View and download my CV: ${qrMeta.shareUrl}`)}`, "_blank", "noopener");
  });

  document.getElementById("share-telegram-btn").addEventListener("click", () => {
    if (!qrMeta?.shareUrl) {
      return setMessage("Generate a QR code first.", true);
    }

    window.open(`https://t.me/share/url?url=${encodeURIComponent(qrMeta.shareUrl)}&text=${encodeURIComponent("View and download my CV")}`, "_blank", "noopener");
  });

  function renderIdentity(savedCv, user) {
    const name = savedCv.fullName || user?.fullName || "Student";
    const email = savedCv.email || user?.email || "student@example.com";
    const initials = window.CVStorage.getInitials(name);

    document.getElementById("qr-avatar").textContent = initials;
    document.getElementById("qr-header-avatar").textContent = initials;
    document.getElementById("qr-sidebar-name").textContent = name;
    document.getElementById("qr-sidebar-email").textContent = email;
  }

  function applyQrState(meta) {
    shareLinkInput.value = meta.shareUrl;
    qrImage.src = meta.qrImageUrl;
    qrImage.style.display = "block";
    qrPlaceholder.style.display = "none";
    copyLinkButton.disabled = false;
    downloadQrButton.disabled = false;
    openPublicCvButton.disabled = false;
  }

  function updateStats(meta) {
    document.getElementById("qr-generated-count").textContent = String(meta?.generatedCount || 0);
    document.getElementById("qr-copied-count").textContent = String(meta?.copiedCount || 0);
    document.getElementById("qr-downloaded-count").textContent = String(meta?.downloadedCount || 0);
    document.getElementById("qr-public-status").textContent = meta?.shareUrl ? "Active" : "Not generated";
    document.getElementById("qr-last-generated").textContent = meta?.lastGeneratedAt ? window.CVStorage.formatDate(meta.lastGeneratedAt) : "No Data";
    document.getElementById("qr-public-title").textContent = meta?.publicTitle || "No CV yet";
  }

  function updateStatusBadge(element, status) {
    element.className = window.CVStorage.getStatusClass(status);
    if (status === "Approved") {
      element.innerHTML = '<i class="fas fa-circle-check"></i> Approved for Sharing';
      return;
    }
    if (status === "Pending Review") {
      element.innerHTML = '<i class="fas fa-hourglass-half"></i> Pending Review';
      return;
    }
    if (status === "Changes Requested") {
      element.innerHTML = '<i class="fas fa-pen-to-square"></i> Changes Requested';
      return;
    }
    if (status === "Rejected") {
      element.innerHTML = '<i class="fas fa-circle-xmark"></i> Rejected';
      return;
    }
    element.innerHTML = '<i class="fas fa-file-pen"></i> Draft';
  }

  function setMessage(text, isError) {
    qrMessage.textContent = text;
    qrMessage.style.color = isError ? "var(--danger)" : "var(--success)";
  }
});

async function fetchJson(url) {
  try {
    const response = await fetch(url, {
      headers: { Accept: "application/json" }
    });
    return await response.json();
  } catch (error) {
    return null;
  }
}

async function postJson(url, payload) {
  try {
    const response = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json"
      },
      body: JSON.stringify(payload)
    });
    return await response.json();
  } catch (error) {
    return null;
  }
}

function slugify(value) {
  return (value || "student")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}
