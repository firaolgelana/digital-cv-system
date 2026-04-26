(function () {
  const STORAGE_KEY = "digicv_current_user";

  function normalizeUser(user) {
    if (!user || typeof user !== "object") {
      return null;
    }

    return {
      fullName: typeof user.fullName === "string" ? user.fullName.trim() : typeof user.full_name === "string" ? user.full_name.trim() : "",
      email: typeof user.email === "string" ? user.email.trim() : "",
      role: typeof user.role === "string" ? user.role.trim() : "",
      department: typeof user.department === "string" ? user.department.trim() : ""
    };
  }

  function saveUser(user) {
    const normalizedUser = normalizeUser(user);
    if (!normalizedUser) {
      return null;
    }

    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(normalizedUser));
    return normalizedUser;
  }

  function getUser() {
    try {
      const raw = window.localStorage.getItem(STORAGE_KEY);
      return raw ? normalizeUser(JSON.parse(raw)) : null;
    } catch (error) {
      return null;
    }
  }

  function clearUser() {
    window.localStorage.removeItem(STORAGE_KEY);
  }

  window.UserSession = {
    saveUser,
    getUser,
    clearUser
  };
})();
