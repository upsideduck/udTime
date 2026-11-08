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
  showToasts();
});

function showToasts() {
  document.querySelectorAll("#toasts .toast:not(.shown)").forEach((el) => {
    el.classList.add("shown");
    el.addEventListener("hidden.bs.toast", () => el.remove());
    new bootstrap.Toast(el, { delay: el.classList.contains("text-bg-danger") ? 8000 : 4000 }).show();
  });
}

// Toasts arrive as out-of-band swaps, which htmx.onLoad misses when the main swap is "none"
const toastContainer = document.getElementById("toasts");
if (toastContainer) new MutationObserver(showToasts).observe(toastContainer, { childList: true });

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

// Buttons that empty a container, e.g. "Discard" on the stats preview
document.addEventListener("click", (e) => {
  const button = e.target.closest("[data-clear]");
  if (!button) return;
  const target = document.querySelector(button.dataset.clear);
  if (target) target.innerHTML = "";
});

// Reload after a new avatar was uploaded so every avatar image refreshes
document.body.addEventListener("avatarChanged", () => location.reload());

// Copy buttons, e.g. for a newly created API key
document.addEventListener("click", (e) => {
  const button = e.target.closest("[data-copy]");
  if (!button) return;
  const input = document.querySelector(button.dataset.copy);
  if (!input) return;
  input.select();
  const done = () => (button.innerHTML = '<i class="bi bi-check-lg"></i> Copied');
  if (navigator.clipboard) navigator.clipboard.writeText(input.value).then(done, () => document.execCommand("copy"));
  else if (document.execCommand("copy")) done();
});

// Empty the password form after a successful change

// Work schedule form: days per week = the ticked workdays; keep "standard day" = work week / days
function toMinutes(text) {
  const m = /^\s*(\d+)(?::(\d{1,2}))?\s*$/.exec(text || "");
  return m ? Number(m[1]) * 60 + Number(m[2] || 0) : null;
}
function hhmm(minutes) {
  minutes = Math.round(minutes);
  return pad(Math.floor(minutes / 60)) + ":" + pad(minutes % 60);
}
document.addEventListener("input", (e) => {
  const form = e.target.closest("form[data-schedule]");
  if (!form) return;
  const week = form.querySelector("[data-week]");
  const day = form.querySelector("[data-day]");
  const n = form.querySelectorAll("[data-workday]:checked").length;
  form.querySelector("[data-days]").value = n;
  if (!n) return;
  if (e.target === day) {
    const m = toMinutes(day.value);
    if (m !== null) week.value = hhmm(m * n);
  } else {
    const m = toMinutes(week.value);
    if (m !== null) day.value = hhmm(m / n);
  }
});
