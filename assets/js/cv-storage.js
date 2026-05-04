(function () {
  const STORAGE_KEY = "digicv_student_cv";

  function normalizeStorageSegment(value) {
    return normalizeText(value)
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "_")
      .replace(/^_+|_+$/g, "");
  }

  function getStorageKey() {
    const user = window.UserSession && typeof window.UserSession.getUser === "function"
      ? window.UserSession.getUser()
      : null;

    if (!user) {
      return `${STORAGE_KEY}:anonymous`;
    }

    const role = normalizeStorageSegment(user.role || "student") || "student";
    const identity = normalizeStorageSegment(user.email || user.fullName || "anonymous") || "anonymous";

    return `${STORAGE_KEY}:${role}:${identity}`;
  }

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
      const raw = window.localStorage.getItem(getStorageKey());
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

  function setCv(nextCv) {
    const currentCv = getCv();
    const mergedCv = {
      ...currentCv,
      ...(nextCv || {})
    };

    window.localStorage.setItem(getStorageKey(), JSON.stringify(mergedCv));
    return mergedCv;
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
    if (status === "Approved") {
      return "badge badge--approved";
    }
    if (status === "Rejected") {
      return "badge badge--rejected";
    }
    if (status === "Changes Requested") {
      return "badge badge--warning";
    }
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

    window.localStorage.setItem(getStorageKey(), JSON.stringify(mergedCv));
    return mergedCv;
  }

  function clearCv() {
    window.localStorage.removeItem(getStorageKey());
  }

  window.CVStorage = {
    getCv,
    setCv,
    saveCv,
    clearCv,
    getCompletion,
    buildChecklist,
    getInitials,
    getStatusClass,
    formatDate,
    toArray
  };
})();
