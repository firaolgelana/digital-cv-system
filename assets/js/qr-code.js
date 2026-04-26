document.addEventListener("DOMContentLoaded", () => {
  const menuToggle = document.getElementById("menu-toggle");
  const sidebar = document.getElementById("sidebar");
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
  document.getElementById("qr-empty-state").style.display = hasCv ? "none" : "block";
  document.getElementById("qr-content").style.display = hasCv ? "block" : "none";

  if (!hasCv) {
    return;
  }

  document.getElementById("qr-student-name").textContent = cv.fullName || currentUser?.fullName || "Student";
  document.getElementById("qr-student-meta").textContent = [cv.department || currentUser?.department, cv.email || currentUser?.email].filter(Boolean).join(" • ") || "CV details saved";

  const statusBadge = document.getElementById("qr-status-badge");
  const qrImage = document.getElementById("qr-image");
  const qrPlaceholder = document.getElementById("qr-placeholder");
  const shareLinkInput = document.getElementById("share-link-input");
  const qrMessage = document.getElementById("qr-message");
  const copyLinkButton = document.getElementById("copy-link-btn");
  const downloadQrButton = document.getElementById("download-qr-btn");
  const openPublicCvButton = document.getElementById("open-public-cv-btn");
  const generateQrButton = document.getElementById("generate-qr-btn");

  let qrMeta = window.CVShare.getQrMeta() || {
    generatedCount: 0,
    copiedCount: 0,
    downloadedCount: 0,
    lastGeneratedAt: "",
    shareUrl: "",
    qrImageUrl: "",
    publicTitle: ""
  };

  updateStats(qrMeta);
  updateStatusBadge(statusBadge, cv.status || "Draft");

  if (qrMeta.shareUrl && qrMeta.qrImageUrl) {
    applyQrState(qrMeta.shareUrl, qrMeta.qrImageUrl, false);
  }

  generateQrButton.addEventListener("click", () => {
    const token = typeof crypto !== "undefined" && crypto.randomUUID ? crypto.randomUUID() : `qr-${Date.now()}`;
    const payload = window.CVShare.buildSharePayload(cv, currentUser, token);
    const shareUrl = window.CVShare.buildPublicCvUrl(payload);
    const qrImageUrl = `https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=${encodeURIComponent(shareUrl)}`;

    qrMeta = {
      ...qrMeta,
      generatedCount: Number(qrMeta.generatedCount || 0) + 1,
      lastGeneratedAt: payload.generatedAt,
      shareUrl,
      qrImageUrl,
      publicTitle: payload.student.fullName || "Student CV"
    };

    window.CVShare.saveQrMeta(qrMeta);
    updateStats(qrMeta);
    applyQrState(shareUrl, qrImageUrl, true);
  });

  copyLinkButton.addEventListener("click", async () => {
    if (!qrMeta.shareUrl) {
      return;
    }

    try {
      await navigator.clipboard.writeText(qrMeta.shareUrl);
      qrMeta.copiedCount = Number(qrMeta.copiedCount || 0) + 1;
      window.CVShare.saveQrMeta(qrMeta);
      updateStats(qrMeta);
      qrMessage.textContent = "Public CV link copied successfully.";
      qrMessage.style.color = "var(--success)";
    } catch (error) {
      qrMessage.textContent = "Unable to copy the link automatically.";
      qrMessage.style.color = "var(--danger)";
    }
  });

  downloadQrButton.addEventListener("click", () => {
    if (!qrMeta.qrImageUrl) {
      return;
    }

    const anchor = document.createElement("a");
    anchor.href = qrMeta.qrImageUrl;
    anchor.download = `${slugify(cv.fullName || currentUser?.fullName || "student")}-cv-qr.png`;
    anchor.target = "_blank";
    anchor.rel = "noopener";
    anchor.click();

    qrMeta.downloadedCount = Number(qrMeta.downloadedCount || 0) + 1;
    window.CVShare.saveQrMeta(qrMeta);
    updateStats(qrMeta);
  });

  openPublicCvButton.addEventListener("click", () => {
    if (qrMeta.shareUrl) {
      window.open(qrMeta.shareUrl, "_blank", "noopener");
    }
  });

  document.getElementById("share-native-btn").addEventListener("click", async () => {
    if (!qrMeta.shareUrl) {
      return setMessage("Generate a QR code first.", true);
    }

    if (navigator.share) {
      try {
        await navigator.share({
          title: `${cv.fullName || currentUser?.fullName || "Student"} CV`,
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
    if (!qrMeta.shareUrl) {
      return setMessage("Generate a QR code first.", true);
    }

    window.location.href = `mailto:?subject=${encodeURIComponent("My DigiCV Profile")}&body=${encodeURIComponent(`View and download my CV here:\n${qrMeta.shareUrl}`)}`;
  });

  document.getElementById("share-whatsapp-btn").addEventListener("click", () => {
    if (!qrMeta.shareUrl) {
      return setMessage("Generate a QR code first.", true);
    }

    window.open(`https://wa.me/?text=${encodeURIComponent(`View and download my CV: ${qrMeta.shareUrl}`)}`, "_blank", "noopener");
  });

  document.getElementById("share-telegram-btn").addEventListener("click", () => {
    if (!qrMeta.shareUrl) {
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

  function applyQrState(shareUrl, qrImageUrl, generatedNow) {
    shareLinkInput.value = shareUrl;
    qrImage.src = qrImageUrl;
    qrImage.style.display = "block";
    qrPlaceholder.style.display = "none";
    copyLinkButton.disabled = false;
    downloadQrButton.disabled = false;
    openPublicCvButton.disabled = false;
    setMessage(generatedNow ? "Unique QR code generated. Anyone who scans it can open and download your CV." : "Your last generated QR code is ready to share.", false);
  }

  function updateStats(meta) {
    document.getElementById("qr-generated-count").textContent = String(meta.generatedCount || 0);
    document.getElementById("qr-copied-count").textContent = String(meta.copiedCount || 0);
    document.getElementById("qr-downloaded-count").textContent = String(meta.downloadedCount || 0);
    document.getElementById("qr-public-status").textContent = meta.shareUrl ? "Active" : "Not generated";
    document.getElementById("qr-last-generated").textContent = meta.lastGeneratedAt ? window.CVStorage.formatDate(meta.lastGeneratedAt) : "No Data";
    document.getElementById("qr-public-title").textContent = meta.publicTitle || "No CV yet";
  }

  function updateStatusBadge(element, status) {
    element.className = window.CVStorage.getStatusClass(status);
    element.innerHTML = `<i class="fas ${status === "Pending Review" ? "fa-hourglass-half" : "fa-circle-check"}"></i> ${status === "Pending Review" ? "Pending Review" : "Ready to Share"}`;
  }

  function setMessage(text, isError) {
    qrMessage.textContent = text;
    qrMessage.style.color = isError ? "var(--danger)" : "var(--success)";
  }
});

function slugify(value) {
  return (value || "student")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}
