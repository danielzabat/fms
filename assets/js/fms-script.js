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

  // ------------------------------
  // Client-side Idle Timeout Script
  // ------------------------------

  const IDLE_TIMEOUT = 180 * 1000; // 3 minutes

  let idleTimer = null;

  function resetIdleTimer() {
    clearTimeout(idleTimer);
    idleTimer = setTimeout(() => {
      console.log("Redirecting...");
      window.location.href = "php/logout.php?timeout=1";
    }, IDLE_TIMEOUT);
    console.log("Resetting idle timer...");
  }

  ["mousemove", "mousedown", "keydown", "touchstart", "scroll"].forEach(
    (evt) => {
      document.addEventListener(evt, resetIdleTimer, true);
    }
  );

  resetIdleTimer();
});
