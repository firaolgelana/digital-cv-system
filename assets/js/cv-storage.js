(function () {
  const STORAGE_KEY = "digicv_student_cv";

  function normalizeText(value) {
    return typeof value === "string" ? value.trim() : "";
  }

  function toArray(value) {
    return normalizeText(value)
      .split(/[\n,]/)
      .map((item) => item.trim())
      .filter(Boolean);
  }

  function getStoredCv() {
    try {
      const raw = window.localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (error) {
      return null;
    }
  }

  function getDefaultCv() {
    return {
      fullName: "",
      profession: "",
      email: "",
      phone: "",
      address: "",
      department: "",
      linkedin: "",
      portfolio: "",
      summary: "",
      education: "",
      experience: "",
      technicalSkills: "",
      softSkills: "",
      languages: "",
      projects: "",
      certifications: "",
      status: "Draft",
      createdAt: "",
      updatedAt: "",
      activity: []
    };
  }

  function getCv() {
    return { ...getDefaultCv(), ...(getStoredCv() || {}) };
  }

  function buildChecklist(cv) {
    return [
      { label: "Personal Information", completed: Boolean(cv.fullName && cv.email) },
      { label: "Education", completed: Boolean(cv.education) },
      { label: "Experience", completed: Boolean(cv.experience) },
      { label: "Skills", completed: Boolean(cv.technicalSkills || cv.softSkills) },
      { label: "Projects", completed: Boolean(cv.projects) },
      { label: "Certificates", completed: Boolean(cv.certifications) }
    ];
  }

  function getCompletion(cv) {
    const checklist = buildChecklist(cv);
    const done = checklist.filter((item) => item.completed).length;
    return Math.round((done / checklist.length) * 100);
  }

  function getInitials(name) {
    const parts = normalizeText(name).split(/\s+/).filter(Boolean).slice(0, 2);
    return parts.length ? parts.map((part) => part[0].toUpperCase()).join("") : "ST";
  }

  function getStatusClass(status) {
    if (status === "Pending Review") {
      return "badge badge--pending";
    }
    return "badge badge--draft";
  }

  function formatDate(value) {
    if (!value) {
      return "No Data";
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
      return "No Data";
    }

    return date.toLocaleDateString(undefined, {
      month: "short",
      day: "numeric",
      year: "numeric"
    });
  }

  function saveCv(nextCv, activityLabel) {
    const currentCv = getCv();
    const now = new Date().toISOString();
    const activity = Array.isArray(currentCv.activity) ? currentCv.activity.slice(0, 5) : [];

    if (activityLabel) {
      activity.unshift({
        label: activityLabel,
        time: now
      });
    }

    const mergedCv = {
      ...currentCv,
      ...nextCv,
      createdAt: currentCv.createdAt || now,
      updatedAt: now,
      activity
    };

    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(mergedCv));
    return mergedCv;
  }

  window.CVStorage = {
    getCv,
    saveCv,
    getCompletion,
    buildChecklist,
    getInitials,
    getStatusClass,
    formatDate,
    toArray
  };
})();
