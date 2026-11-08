// Loaded synchronously in <head>: apply the theme before the page renders (no flash),
// and hide avatar images that fail to load (registered before any <img> exists).
(function () {
  var stored = null;
  try {
    stored = localStorage.getItem("udtime-theme");
  } catch (e) {}
  var dark = stored ? stored === "dark" : window.matchMedia("(prefers-color-scheme: dark)").matches;
  document.documentElement.setAttribute("data-bs-theme", dark ? "dark" : "light");

  document.addEventListener(
    "error",
    function (e) {
      if (e.target.matches && e.target.matches("img[data-hide-on-error]")) e.target.remove();
    },
    true
  );
})();
