document.addEventListener("DOMContentLoaded", () => {
  const monthlyRadio = document.getElementById("monthly");
  const annualRadio = document.getElementById("annual");
  const fromInput = document.getElementById("from");
  const toInput = document.getElementById("to");

  function setMonthlyDefaults() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, "0");
    const firstDay = `${year}-${month}-01`;
    const lastDay = new Date(year, today.getMonth() + 1, 0)
      .toISOString()
      .split("T")[0];
    fromInput.value = firstDay;
    toInput.value = lastDay;
  }

  function setAnnualDefaults() {
    const today = new Date();
    const year = today.getFullYear();
    fromInput.value = `${year}-01-01`;
    toInput.value = `${year}-12-31`;
  }

  monthlyRadio.addEventListener("change", () => {
    if (monthlyRadio.checked) {
      setMonthlyDefaults();
    }
  });

  annualRadio.addEventListener("change", () => {
    if (annualRadio.checked) {
      setAnnualDefaults();
    }
  });

  // Optionally set default values on initial load based on checked radio
  if (monthlyRadio.checked) {
    setMonthlyDefaults();
  } else if (annualRadio.checked) {
    setAnnualDefaults();
  }
});

const pdfModal = document.getElementById("pdfModal");

document.getElementById("openPdfModal").addEventListener("click", () => {
  pdfModal.style.display = "block";
});

document.getElementById("closePdfModal").addEventListener("click", () => {
  pdfModal.style.display = "none";
});

window.onclick = function (event) {
  if (event.target === pdfModal) {
    pdfModal.style.display = "none";
  }
};

// Clear password input after successful PDF generation
document.getElementById("pdfForm").addEventListener("submit", function () {
  setTimeout(() => {
    document.getElementById("pdf_password").value = "";
    pdfModal.style.display = "none"; // Close modal after submission
  }, 1000); // delay to allow PDF generation to start
});
