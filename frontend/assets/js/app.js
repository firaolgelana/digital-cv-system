(function () {
  function roleRoute(role) {
    const map = {
      student: "../student/home.html",
      supervisor: "../supervisor/review-queue.html",
      examiner: "../examiner/library.html",
      recruiter: "../recruiter/scan.html",
      admin: "../admin/overview.html",
    };
    return map[role] || "../student/home.html";
  }

  async function authRequest(endpoint, payload) {
    let response;

    try {
      response = await fetch(endpoint, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });
    } catch (error) {
      throw new Error(
        "Unable to reach the server. Check your connection and try again.",
      );
    }

    const data = await response.json().catch(function () {
      return null;
    });

    if (!data) {
      throw new Error("The server returned an unexpected response.");
    }

    if (!response.ok) {
      throw new Error(data.message || "The request could not be completed.");
    }

    return data;
  }

  function ensureToastHost() {
    let host = document.getElementById("digicv-toast-host");
    if (!host) {
      host = document.createElement("div");
      host.id = "digicv-toast-host";
      host.className = "toast-host";
      host.setAttribute("aria-live", "polite");
      host.setAttribute("aria-atomic", "true");
      document.body.appendChild(host);
    }

    // Enforce fixed top-right placement even if CSS is missing/overridden.
    host.style.position = "fixed";
    host.style.top = "18px";
    host.style.right = "18px";
    host.style.left = "auto";
    host.style.bottom = "auto";
    host.style.zIndex = "9999";
    host.style.display = "grid";
    host.style.gap = "10px";
    host.style.width = "min(360px, calc(100vw - 36px))";
    host.style.pointerEvents = "none";

    return host;
  }

  function toast(message, options) {
    const host = ensureToastHost();
    const config = options || {};
    const allowedTypes = ["success", "error", "warning", "info"];
    const type = allowedTypes.includes(config.type) ? config.type : "info";
    const toastNode = document.createElement("div");
    const iconMap = {
      success: '<i class="fas fa-circle-check"></i>',
      error: '<i class="fas fa-triangle-exclamation"></i>',
      warning: '<i class="fas fa-circle-exclamation"></i>',
      info: '<i class="fas fa-circle-info"></i>',
    };
    const defaultTitleMap = {
      success: "Success",
      error: "Error",
      warning: "Warning",
      info: "Notice",
    };

    toastNode.className = "toast toast--" + type;
    toastNode.setAttribute(
      "role",
      type === "error" || type === "warning" ? "alert" : "status",
    );
    toastNode.style.pointerEvents = "auto";

    const iconNode = document.createElement("div");
    iconNode.className = "toast__icon";
    iconNode.setAttribute("aria-hidden", "true");
    iconNode.innerHTML = iconMap[type];

    const bodyNode = document.createElement("div");
    bodyNode.className = "toast__body";

    const titleNode = document.createElement("div");
    titleNode.className = "toast__title";
    titleNode.textContent = config.title || defaultTitleMap[type];

    const messageNode = document.createElement("div");
    messageNode.className = "toast__message";
    messageNode.textContent = String(message || "");

    const closeButton = document.createElement("button");
    closeButton.className = "toast__close";
    closeButton.type = "button";
    closeButton.setAttribute("aria-label", "Dismiss notification");
    closeButton.innerHTML = '<i class="fas fa-xmark" aria-hidden="true"></i>';

    bodyNode.appendChild(titleNode);
    bodyNode.appendChild(messageNode);
    toastNode.appendChild(iconNode);
    toastNode.appendChild(bodyNode);
    toastNode.appendChild(closeButton);

    const removeToast = function () {
      toastNode.classList.remove("is-visible");
      toastNode.classList.add("is-hiding");
      window.setTimeout(function () {
        if (toastNode.parentNode) {
          toastNode.parentNode.removeChild(toastNode);
        }
      }, 240);
    };

    closeButton.addEventListener("click", removeToast);

    host.appendChild(toastNode);
    requestAnimationFrame(function () {
      toastNode.classList.add("is-visible");
    });

    window.setTimeout(removeToast, config.duration || 3800);
  }

  window.DigiCVApp = {
    authRequest: authRequest,
    toast: toast,
    loginMock: function (role) {
      localStorage.setItem("digicv_role", role);
      window.location.href = roleRoute(role);
    },
    registerMock: function () {
      window.location.href = "./login.html";
    },
    setButtonLoading: function (button, loading, text) {
      if (!button) return;
      if (loading) {
        button.dataset.originalText = button.textContent;
        button.disabled = true;
        button.innerHTML =
          '<span class="loader" aria-hidden="true"></span> ' + text;
      } else {
        button.disabled = false;
        button.textContent = button.dataset.originalText || text;
      }
    },
  };
})();
