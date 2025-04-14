document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.querySelector(".sidebar");
  const toggleBtn = document.querySelector(".js-sidenav-toggle");
  const icon = toggleBtn.querySelector("span");

  toggleBtn.addEventListener("click", () => {
    if (sidebar.classList.contains("open")) {
      openNavBar();
    } else {
      closeNavBar();
    }
  });

  function openNavBar() {
    sidebar.classList.toggle("open");
    icon.classList.replace("mdi-close", "mdi-menu");
  }

  function closeNavBar() {
    sidebar.classList.toggle("open");
    icon.classList.replace("mdi-menu", "mdi-close");
  }
});
