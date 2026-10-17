// Smooth slide between Sign in and Sign up.
// Click "Sign up" or "Sign in": the colored panel and the form slide across and swap sides,
// then the other page opens in exactly that layout. Works in every browser.
(function () {
  var box = document.querySelector(".container");
  if (!box) return;

  var style = document.createElement("style");
  style.textContent =
    "@view-transition { navigation: none; }" +
    ".container .overlay, .container .form { transition: transform .55s cubic-bezier(.65, 0, .25, 1), opacity .4s ease; }" +
    ".container.leaving .overlay { z-index: 2; transform: translateX(var(--ox, 0)); }" +
    ".container.leaving .form { transform: translateX(var(--fx, 0)); opacity: 0; }" +
    ".container.leaving .ov { opacity: 0; transition: opacity .3s ease; }" +
    "@keyframes authIn { from { opacity: 0; } to { opacity: 1; } }" +
    ".container .form, .container .ov { animation: authIn .5s ease .08s backwards; }" +
    "@media (prefers-reduced-motion: reduce) { .container .form, .container .ov { animation: none; } }";
  document.head.appendChild(style);

  document.addEventListener("click", function (e) {
    var a = e.target.closest && e.target.closest('a[href="register.html"], a[href="login.html"]');
    if (!a || e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
    if (window.matchMedia && matchMedia("(prefers-reduced-motion: reduce)").matches) return;   // just go there

    e.preventDefault();
    var overlay = box.querySelector(".overlay");
    var wide = overlay && getComputedStyle(overlay).display !== "none";          // phones hide the side panel
    var onRegister = /register\.html$/i.test(location.pathname);
    box.style.setProperty("--ox", wide ? (onRegister ? "100%" : "-100%") : "0");
    box.style.setProperty("--fx", wide ? (onRegister ? "-100%" : "100%") : "0");
    box.classList.add("leaving");
    setTimeout(function () { location.href = a.getAttribute("href"); }, 520);
  });

  // Coming back with the browser's Back button: show the page normally again
  window.addEventListener("pageshow", function (e) { if (e.persisted) box.classList.remove("leaving"); });
})();