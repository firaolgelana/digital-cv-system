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

  window.DigiCVApp = {
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
    toast: function (message) {
      let toast = document.getElementById("digicv-toast");
      if (!toast) {
        toast = document.createElement("div");
        toast.id = "digicv-toast";
        toast.className = "toast";
        document.body.appendChild(toast);
      }
      toast.textContent = message;
      toast.classList.add("show");
      setTimeout(function () {
        toast.classList.remove("show");
      }, 1800);
    },
  };
})();
