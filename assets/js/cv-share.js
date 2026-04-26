(function () {
  const QR_META_KEY = "digicv_qr_meta";

  function toArray(value) {
    return (value || "")
      .split(/[\n,]/)
      .map((item) => item.trim())
      .filter(Boolean);
  }

  function escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value || "";
    return div.innerHTML;
  }

  function encodePayload(payload) {
    return btoa(unescape(encodeURIComponent(JSON.stringify(payload))));
  }

  function decodePayload(encoded) {
    try {
      return JSON.parse(decodeURIComponent(escape(atob(encoded))));
    } catch (error) {
      return null;
    }
  }

  function buildSharePayload(cv, user, token) {
    return {
      token,
      generatedAt: new Date().toISOString(),
      student: {
        fullName: cv.fullName || user?.fullName || "Student",
        email: cv.email || user?.email || "",
        profession: cv.profession || "",
        phone: cv.phone || "",
        address: cv.address || "",
        department: cv.department || user?.department || "",
        linkedin: cv.linkedin || "",
        portfolio: cv.portfolio || "",
        summary: cv.summary || "",
        education: cv.education || "",
        experience: cv.experience || "",
        technicalSkills: cv.technicalSkills || "",
        softSkills: cv.softSkills || "",
        languages: cv.languages || "",
        projects: cv.projects || "",
        certifications: cv.certifications || "",
        status: cv.status || "Draft",
        updatedAt: cv.updatedAt || ""
      }
    };
  }

  function buildPublicCvUrl(payload) {
    const url = new URL("public-cv.html", window.location.href);
    url.hash = `cv=${encodePayload(payload)}`;
    return url.toString();
  }

  function getQrMeta() {
    try {
      const raw = window.localStorage.getItem(QR_META_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (error) {
      return null;
    }
  }

  function saveQrMeta(meta) {
    window.localStorage.setItem(QR_META_KEY, JSON.stringify(meta));
    return meta;
  }

  function buildDownloadHtml(payload) {
    const student = payload.student || {};
    const technicalSkills = toArray(student.technicalSkills);
    const softSkills = toArray(student.softSkills);

    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>${escapeHtml(student.fullName || "Student")} - CV</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; background: #f4f7fb; color: #1f2937; }
    .page { max-width: 900px; margin: 32px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,.08); }
    .header { background: linear-gradient(135deg, #1e293b, #334155); color: #fff; padding: 32px; }
    .header h1 { margin: 0 0 8px; font-size: 32px; }
    .header p { margin: 6px 0; color: #dbeafe; }
    .body { padding: 32px; }
    .section { margin-bottom: 28px; }
    .section h2 { font-size: 18px; margin: 0 0 12px; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px; }
    .content { white-space: pre-line; line-height: 1.6; }
    .tags { display: flex; flex-wrap: wrap; gap: 8px; }
    .tag { background: #eef2ff; color: #3730a3; padding: 6px 12px; border-radius: 999px; font-size: 14px; }
    .links a { color: #2563eb; text-decoration: none; display: inline-block; margin-right: 12px; margin-top: 8px; }
  </style>
</head>
<body>
  <div class="page">
    <div class="header">
      <h1>${escapeHtml(student.fullName || "Student")}</h1>
      <p>${escapeHtml(student.profession || "Professional title not provided")}</p>
      <p>${escapeHtml(student.email || "")}${student.phone ? " | " + escapeHtml(student.phone) : ""}${student.address ? " | " + escapeHtml(student.address) : ""}</p>
      <p>${escapeHtml(student.department || "")}</p>
    </div>
    <div class="body">
      <div class="section">
        <h2>Professional Summary</h2>
        <div class="content">${escapeHtml(student.summary || "No professional summary provided.")}</div>
      </div>
      <div class="section">
        <h2>Education</h2>
        <div class="content">${escapeHtml(student.education || "No education details provided.")}</div>
      </div>
      <div class="section">
        <h2>Work Experience</h2>
        <div class="content">${escapeHtml(student.experience || "No work experience provided.")}</div>
      </div>
      <div class="section">
        <h2>Projects</h2>
        <div class="content">${escapeHtml(student.projects || "No projects provided.")}</div>
      </div>
      <div class="section">
        <h2>Technical Skills</h2>
        <div class="tags">${technicalSkills.map((item) => `<span class="tag">${escapeHtml(item)}</span>`).join("") || "No technical skills provided."}</div>
      </div>
      <div class="section">
        <h2>Soft Skills</h2>
        <div class="tags">${softSkills.map((item) => `<span class="tag">${escapeHtml(item)}</span>`).join("") || "No soft skills provided."}</div>
      </div>
      <div class="section">
        <h2>Languages</h2>
        <div class="content">${escapeHtml(student.languages || "No languages provided.")}</div>
      </div>
      <div class="section">
        <h2>Certifications</h2>
        <div class="content">${escapeHtml(student.certifications || "No certifications provided.")}</div>
      </div>
      <div class="section links">
        <h2>Links</h2>
        ${student.linkedin ? `<a href="${escapeHtml(student.linkedin)}" target="_blank" rel="noopener noreferrer">LinkedIn</a>` : ""}
        ${student.portfolio ? `<a href="${escapeHtml(student.portfolio)}" target="_blank" rel="noopener noreferrer">Portfolio</a>` : ""}
      </div>
    </div>
  </div>
</body>
</html>`;
  }

  function downloadHtmlFile(filename, html) {
    const blob = new Blob([html], { type: "text/html;charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement("a");
    anchor.href = url;
    anchor.download = filename;
    anchor.click();
    URL.revokeObjectURL(url);
  }

  window.CVShare = {
    buildSharePayload,
    buildPublicCvUrl,
    decodePayload,
    getQrMeta,
    saveQrMeta,
    buildDownloadHtml,
    downloadHtmlFile,
    toArray,
    escapeHtml
  };
})();
