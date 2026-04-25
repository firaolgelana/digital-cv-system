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
      throw new Error("Unable to reach the server. Check your connection and try again.");
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
    return host;
  }

  function toast(message, options) {
    const host = ensureToastHost();
    const config = options || {};
    const type = config.type || "info";
    const toastNode = document.createElement("div");
    const iconMarkup =
      type === "success"
        ? '<i class="fas fa-circle-check"></i>'
        : type === "error"
          ? '<i class="fas fa-triangle-exclamation"></i>'
          : '<i class="fas fa-circle-info"></i>';

    toastNode.className = "toast toast--" + type;
    toastNode.setAttribute("role", type === "error" ? "alert" : "status");
    toastNode.innerHTML =
      '<div class="toast__icon" aria-hidden="true">' +
      iconMarkup +
      '</div><div class="toast__body"><div class="toast__title">' +
      (config.title || (type === "success" ? "Success" : type === "error" ? "Error" : "Notice")) +
      '</div><div class="toast__message">' +
      message +
      '</div></div><button class="toast__close" type="button" aria-label="Dismiss notification">&times;</button>';

    const removeToast = function () {
      toastNode.classList.remove("is-visible");
      toastNode.classList.add("is-hiding");
      window.setTimeout(function () {
        if (toastNode.parentNode) {
          toastNode.parentNode.removeChild(toastNode);
        }
      }, 240);
    };

    toastNode.querySelector(".toast__close").addEventListener("click", removeToast);

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
