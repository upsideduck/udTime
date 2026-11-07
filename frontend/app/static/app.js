// udTime frontend helpers: modal, toasts, the running clock and the theme toggle.

function modal() {
  return bootstrap.Modal.getOrCreateInstance(document.getElementById("modal"));
}

document.body.addEventListener("openModal", () => modal().show());
document.body.addEventListener("closeModal", () => modal().hide());

function pad(n) {
  return (n < 10 ? "0" : "") + n;
}

function formatElapsed(seconds) {
  seconds = Math.max(0, Math.floor(seconds));
  return Math.floor(seconds / 3600) + ":" + pad(Math.floor((seconds % 3600) / 60)) + ":" + pad(seconds % 60);
}

function tickClocks() {
  document.querySelectorAll(".clock[data-clock-start]").forEach((el) => {
    const now = Date.now() / 1000 + (el._skew || 0);
    const elapsed = now - Number(el.dataset.clockStart) - Number(el.dataset.clockOffset || 0);
    el.textContent = formatElapsed(elapsed);
  });
}
setInterval(tickClocks, 1000);

htmx.onLoad((root) => {
  root.querySelectorAll(".clock[data-server-time]").forEach((el) => {
    el._skew = Number(el.dataset.serverTime) - Date.now() / 1000;
  });
  tickClocks();

  root.querySelectorAll(".toast:not(.shown)").forEach((el) => {
    el.classList.add("shown");
    el.addEventListener("hidden.bs.toast", () => el.remove());
    new bootstrap.Toast(el, { delay: el.classList.contains("text-bg-danger") ? 8000 : 4000 }).show();
  });
});

// Light / dark theme toggle (remembered per browser)
document.addEventListener("click", (e) => {
  const toggle = e.target.closest("[data-theme-toggle]");
  if (!toggle) return;
  e.preventDefault();
  const next = document.documentElement.getAttribute("data-bs-theme") === "dark" ? "light" : "dark";
  document.documentElement.setAttribute("data-bs-theme", next);
  try {
    localStorage.setItem("udtime-theme", next);
  } catch (err) {}
});
