document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.querySelector(".sidebar");
  const toggleBtn = document.querySelector(".js-sidenav-toggle");
  const icon = toggleBtn.querySelector("span");

  toggleBtn.addEventListener("click", () => {
    if (sidebar.classList.contains("open")) {
      closeNavBar();
    } else {
      openNavBar();
    }
  });

  function openNavBar() {
    sidebar.classList.add("open");
    icon.classList.replace("mdi-menu", "mdi-close");
  }

  function closeNavBar() {
    sidebar.classList.remove("open");
    icon.classList.replace("mdi-close", "mdi-menu");
  }

  const modal = document.getElementById("logoutModal");
  const trigger = document.getElementById("logoutTrigger");
  const cancelBtn = document.getElementById("cancelLogout");
  const confirmBtn = document.getElementById("confirmLogout");

  trigger.addEventListener("click", () => {
    modal.classList.add("show");
  });

  cancelBtn.addEventListener("click", () => {
    modal.classList.remove("show");
  });

  confirmBtn.addEventListener("click", () => {
    window.location.href = "php/logout.php";
    modal.classList.remove("show");
  });

  // Optional: ESC key closes modal
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") modal.classList.remove("show");
  });

  // ------------------------------
  // Client-side Idle Timeout Script
  // ------------------------------

  const IDLE_TIMEOUT = 9999999 * 1000; // 3 minutes

  let idleTimer = null;

  function resetIdleTimer() {
    clearTimeout(idleTimer);
    idleTimer = setTimeout(() => {
      console.log("Redirecting...");
      window.location.href = "php/logout.php?timeout=1";
    }, IDLE_TIMEOUT);
  }

  ["mousemove", "mousedown", "keydown", "touchstart", "scroll"].forEach(
    (evt) => {
      document.addEventListener(evt, resetIdleTimer, true);
    }
  );

  resetIdleTimer();

  // ------------------------------
  // Refund Request Form Submission
  // ------------------------------
  const refundForm = document.getElementById("refundRequestForm");
  const confirmationMsg = document.getElementById("confirmationMessage");

  if (refundForm) {
    refundForm.addEventListener("submit", (e) => {
      e.preventDefault();

      const orderNumber = refundForm.orderNumber?.value.trim();
      const reason = refundForm.reasonForRefund?.value.trim();
      const amount = parseFloat(refundForm.refundAmount?.value);

      if (!orderNumber || !reason || isNaN(amount) || amount <= 0) {
        alert("Please complete all fields with valid data.");
        return;
      }

      // Log for debugging – replace with real AJAX in production
      console.log("Refund Request Submitted:", {
        requestId: refundForm.requestId?.value,
        dateRequested: refundForm.dateRequested?.value,
        orderNumber,
        reason,
        amount,
      });

      // Reset & show confirmation
      refundForm.reset();
      if (confirmationMsg) {
        confirmationMsg.style.display = "block";
        setTimeout(() => {
          confirmationMsg.style.display = "none";
        }, 5000);
      }
    });
  }

  const urlParams = new URLSearchParams(window.location.search);
  const isSubmitted = urlParams.get("submitted");

  if (isSubmitted === "1") {
    const modal = document.getElementById("popupModal");
    if (modal) {
      modal.style.display = "flex";
    }
  }

  const closeBtn = document.getElementById("closePopupBtn");
  if (closeBtn) {
    closeBtn.addEventListener("click", function () {
      document.getElementById("popupModal").style.display = "none";
      window.history.replaceState({}, document.title, window.location.pathname);
    });
  }

  const changePwdBtn = document.getElementById("changePwdBtn");
  const changePwdModal = document.getElementById("changePwdModal");
  const closeModalBtn = document.getElementById("closeModalBtn");

  changePwdBtn.addEventListener("click", () => {
    changePwdModal.style.display = "flex";
  });

  closeModalBtn.addEventListener("click", () => {
    changePwdModal.style.display = "none";
  });

  window.addEventListener("click", (event) => {
    if (event.target === changePwdModal) {
      changePwdModal.style.display = "none";
    }
  });
});
