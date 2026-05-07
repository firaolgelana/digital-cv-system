document.addEventListener("DOMContentLoaded", async () => {
  const menuToggle = document.getElementById("menu-toggle");
  const sidebar = document.getElementById("sidebar");
  const form = document.getElementById("cv-form");
  const saveDraftButton = document.getElementById("save-draft-btn");
  const statusField = document.getElementById("status");
  const message = document.getElementById("form-message");
  const sidebarName = document.getElementById("form-sidebar-name");
  const avatar = document.getElementById("form-avatar");
  const sidebarEmail = document.getElementById("form-sidebar-email");
  const fileInput = document.getElementById("cv-docs");
  const fileListContainer = document.getElementById("file-list");
  let selectedFiles = [];
  let currentUser = window.UserSession ? window.UserSession.getUser() : null;

  if (window.innerWidth <= 768 && menuToggle) {
    menuToggle.style.display = "flex";
  }

  if (menuToggle && sidebar) {
    menuToggle.addEventListener("click", () => sidebar.classList.toggle("open"));
  }

  const fields = [
    "fullName",
    "profession",
    "email",
    "phone",
    "address",
    "department",
    "linkedin",
    "portfolio",
    "summary",
    "education",
    "experience",
    "technicalSkills",
    "softSkills",
    "languages",
    "projects",
    "certifications",
    "status"
  ];
  const optionalFields = new Set(["linkedin", "portfolio"]);

  let existingCv = window.CVStorage.getCv();

  await hydrateCv();

  currentUser = window.UserSession ? window.UserSession.getUser() : currentUser;
  existingCv = window.CVStorage.getCv();
  const initialValues = {
    ...existingCv,
    fullName: existingCv.fullName || (currentUser && currentUser.fullName) || "",
    email: existingCv.email || (currentUser && currentUser.email) || "",
    department: existingCv.department || (currentUser && currentUser.department) || ""
  };

  fields.forEach((fieldName) => {
    const field = document.getElementById(fieldName);
    if (field && initialValues[fieldName]) {
      field.value = initialValues[fieldName];
    }
  });

  updateIdentity(initialValues.fullName, initialValues.email);

  const fullNameField = document.getElementById("fullName");
  if (fullNameField) {
    fullNameField.addEventListener("input", () => {
      updateIdentity(fullNameField.value, document.getElementById("email").value);
    });
  }

  const emailField = document.getElementById("email");
  if (emailField && fullNameField) {
    emailField.addEventListener("input", () => {
      updateIdentity(fullNameField.value, emailField.value);
    });
  }

  if (fileInput) {
    fileInput.addEventListener("change", () => {
      const files = Array.from(fileInput.files);
      selectedFiles = [...selectedFiles, ...files];
      renderFileList();
    });
  }

  function renderFileList() {
    fileListContainer.innerHTML = selectedFiles.map((file, index) => `
      <div class="flex justify-between items-center p-sm" style="background: var(--surface-elevated); border-radius: var(--radius-md); border: 1px solid var(--surface-border);">
        <div class="flex items-center gap-sm" style="overflow: hidden;">
          <i class="fas ${file.type.includes('pdf') ? 'fa-file-pdf' : 'fa-file-image'}" style="color: var(--primary-400);"></i>
          <span style="font-size: 0.82rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${file.name}</span>
        </div>
        <button type="button" class="btn btn-ghost btn-icon btn-sm" onclick="removeFile(${index})" style="color: var(--danger);">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `).join("");
  }

  window.removeFile = (index) => {
    selectedFiles.splice(index, 1);
    renderFileList();
  };

  saveDraftButton.addEventListener("click", async () => {
    statusField.value = "Draft";
    await saveCv("save_draft");
  });

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!validateForSubmit()) {
      return;
    }
    statusField.value = "Pending Review";
    const savedCv = await saveCv("submit_cv");
    if (!savedCv) {
      return;
    }

    window.setTimeout(() => {
      window.location.href = "student-dashboard.html";
    }, 700);
  });

  function updateIdentity(name, email) {
    const displayName = name && name.trim()
      ? name.trim()
      : currentUser && currentUser.fullName
        ? currentUser.fullName
        : "Student";
    const displayEmail = email && email.trim()
      ? email.trim()
      : currentUser && currentUser.email
        ? currentUser.email
        : "student@example.com";
    sidebarName.textContent = displayName;
    sidebarEmail.textContent = displayEmail;
    avatar.textContent = window.CVStorage.getInitials(displayName);
  }

  async function saveCv(action) {
    const formData = new FormData(form);
    formData.append("action", action);
    
    // Explicitly add files since we might have removed some from the array but not the input
    // Actually, it's easier to just append from selectedFiles
    formData.delete("cv_docs[]"); // Clear any default from input
    selectedFiles.forEach(file => {
      formData.append("cv_docs[]", file);
    });

    try {
      toggleActions(true);
      const res = await fetch("php_actions/cv_actions.php", {
        method: "POST",
        // Do NOT set Content-Type header when using FormData, 
        // the browser will set it with the correct boundary.
        body: formData
      });
      const data = await res.json();

      if (!data.success) {
        message.textContent = data.message || "Unable to save your CV right now.";
        message.style.color = "var(--danger)";
        return null;
      }

      if (data.user && window.UserSession) {
        window.UserSession.saveUser(data.user);
        currentUser = window.UserSession.getUser();
      }

      const savedCv = window.CVStorage.setCv(data.cv);
      message.textContent = data.message;
      message.style.color = "var(--success)";
      updateIdentity(savedCv.fullName, savedCv.email);
      return savedCv;
    } catch (error) {
      message.textContent = "Network error. Please try again.";
      message.style.color = "var(--danger)";
      return null;
    } finally {
      toggleActions(false);
    }
  }

  function collectPayload() {
    const formData = new FormData(form);
    const payload = {};

    fields.forEach((fieldName) => {
      payload[fieldName] = (formData.get(fieldName) || "").toString().trim();
    });

    return payload;
  }

  function validateForSubmit() {
    if (!form.reportValidity()) {
      message.textContent = "Please fill in all CV fields before submitting.";
      message.style.color = "var(--danger)";
      return false;
    }

    const missingFields = fields.filter((fieldName) => {
      if (fieldName === "status" || optionalFields.has(fieldName)) {
        return false;
      }

      const field = document.getElementById(fieldName);
      return !field || !field.value.trim();
    });

    if (missingFields.length) {
      message.textContent = "Please complete every CV section before submitting.";
      message.style.color = "var(--danger)";
      return false;
    }

    return true;
  }

  async function hydrateCv() {
    try {
      const res = await fetch("php_actions/cv_actions.php?action=get_cv", {
        headers: { Accept: "application/json" }
      });
      const data = await res.json();
      if (data.success && data.user && window.UserSession) {
        window.UserSession.saveUser(data.user);
        currentUser = window.UserSession.getUser();
      }
      if (data.success && data.cv) {
        window.CVStorage.setCv(data.cv);
      } else if (data.success) {
        window.CVStorage.clearCv();
      }
    } catch (error) {
      // Keep the local cache as a fallback if the backend is unavailable.
    }
  }

  function toggleActions(loading) {
    saveDraftButton.disabled = loading;
    form.querySelector('button[type="submit"]').disabled = loading;
  }
});
