document.addEventListener("DOMContentLoaded", () => {
  const menuToggle = document.getElementById("menu-toggle");
  const sidebar = document.getElementById("sidebar");
  const form = document.getElementById("cv-form");
  const saveDraftButton = document.getElementById("save-draft-btn");
  const statusField = document.getElementById("status");
  const message = document.getElementById("form-message");
  const sidebarName = document.getElementById("form-sidebar-name");
  const avatar = document.getElementById("form-avatar");
  const sidebarEmail = document.getElementById("form-sidebar-email");
  const currentUser = window.UserSession ? window.UserSession.getUser() : null;

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

  const existingCv = window.CVStorage.getCv();
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
  if (emailField) {
    emailField.addEventListener("input", () => {
      updateIdentity(fullNameField.value, emailField.value);
    });
  }

  saveDraftButton.addEventListener("click", () => {
    statusField.value = "Draft";
    saveCv("Draft saved");
  });

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    if (!validateForSubmit()) {
      return;
    }
    statusField.value = "Pending Review";
    const savedCv = saveCv("CV submitted for review");
    message.textContent = "CV submitted successfully. Redirecting to dashboard...";
    message.style.color = "var(--success)";

    window.setTimeout(() => {
      window.location.href = "student-dashboard.html";
    }, 700);

    return savedCv;
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

  function saveCv(activityLabel) {
    const formData = new FormData(form);
    const payload = {};

    fields.forEach((fieldName) => {
      payload[fieldName] = (formData.get(fieldName) || "").toString().trim();
    });

    const savedCv = window.CVStorage.saveCv(payload, activityLabel);
    message.textContent = savedCv.status === "Draft" ? "Draft saved successfully." : "CV submitted successfully.";
    message.style.color = "var(--success)";
    updateIdentity(savedCv.fullName, savedCv.email);
    return savedCv;
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
});
