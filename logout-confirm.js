// Asks "Log out?" before logging out. This file works on its own: it does not depend on
// guard.js or on any other script on the page, and it takes over the Log out button.
(function () {
  window.__loInstalled = true;   // tells guard.js not to add a second handler
  var asking = false;

  function addStyle() {
    if (document.getElementById("lo-style")) return;
    var s = document.createElement("style");
    s.id = "lo-style";
    s.textContent =
      ".lo-dialog { width: min(380px, 92vw); padding: 26px; border: 0; border-radius: 18px; font-family: inherit;" +
      " color: var(--ink, #2a1a1d); background: var(--bg, #f4efe7);" +
      " box-shadow: 0 24px 60px rgba(40, 10, 16, .45), 0 2px 8px rgba(40, 10, 16, .25); }" +
      ".lo-dialog::backdrop { background: rgba(40, 10, 16, .5); }" +
      ".lo-dialog h2 { margin: 0 0 8px; font-size: 1.3rem; color: var(--brand, #7a1020); }" +
      ".lo-dialog p { margin: 0 0 22px; color: var(--muted, #5c4f51); }" +
      ".lo-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; }" +
      ".lo-actions button { padding: 12px 22px; font: inherit; font-weight: 700; border: 0; border-radius: 999px; cursor: pointer; }" +
      ".lo-cancel { color: var(--ink, #2a1a1d); background: var(--bg, #f4efe7); box-shadow: 5px 5px 11px var(--lo, #d8d0c2), -5px -5px 11px var(--hi, #fff); }" +
      ".lo-ok { color: var(--btn-ink, #fff); background: var(--btn, #7a1020); }" +
      ".lo-actions button:focus-visible { outline: 3px solid #f0b429; outline-offset: 2px; }";
    document.head.appendChild(s);
  }

  function ask() {
    return new Promise(function (resolve) {
      var settled = false, d = null;
      function finish(v) {                       // runs once, however the window gets closed
        if (settled) return;
        settled = true;
        try { if (d && d.open) d.close(); } catch (err) {}
        try { if (d && d.parentNode) d.parentNode.removeChild(d); } catch (err) {}
        resolve(v);
      }
      try {
        if (typeof HTMLDialogElement !== "function") throw new Error("no dialog support");
        addStyle();
        d = document.createElement("dialog");
        d.className = "lo-dialog";
        d.innerHTML = '<h2>Log out?</h2><p>Do you want to log out?</p>' +
          '<div class="lo-actions"><button type="button" class="lo-cancel">No</button>' +
          '<button type="button" class="lo-ok">Yes, log out</button></div>';
        document.body.appendChild(d);
        d.querySelector(".lo-cancel").onclick = function () { finish(false); };
        d.querySelector(".lo-ok").onclick = function () { finish(true); };
        d.addEventListener("cancel", function (e) { e.preventDefault(); finish(false); });   // Esc key
        d.addEventListener("close", function () { finish(false); });                         // closed any other way, e.g. Esc pressed twice
        d.addEventListener("click", function (e) { if (e.target === d) finish(false); });    // click outside
        d.showModal();
        d.querySelector(".lo-cancel").focus();    // "No" is selected first, so Enter will not log you out by accident
      } catch (err) {
        finish(confirm("Do you want to log out?"));   // plain browser question as a backup
      }
    });
  }

  // Runs before any other click handler (capture phase), so it works with every page
  document.addEventListener("click", function (e) {
    var btn = e.target.closest && e.target.closest("#logoutBtn");
    if (!btn) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    if (asking) return;
    asking = true;
    ask().then(function (ok) {
      if (!ok) return;
      return fetch("api.php?action=logout", { method: "POST", headers: { "Content-Type": "application/json" }, body: "{}" })
        .catch(function () {})
        .then(function () { location.href = "login.php"; });
    }).then(function () { asking = false; }, function () { asking = false; });
  }, true);

  // Coming back to the page with the Back button: start clean
  window.addEventListener("pageshow", function () {
    asking = false;
    var old = document.querySelector(".lo-dialog");
    if (old) old.remove();
  });
})();