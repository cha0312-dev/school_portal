// Talks to api.php. Pages import protect(), logout(), get() and post() from here.
// It also makes every "Log out" button ask for confirmation, whatever version of the page is open.
async function handle(r) {
  const j = await r.json().catch(() => ({ error: "Server error. Is XAMPP running?" }));
  if (!r.ok) throw new Error(j.error || "Server error.");
  return j;
}
export const get = (action, params = {}) =>
  fetch("api.php?" + new URLSearchParams({ action, ...params })).then(handle);
export const post = (action, data = {}) =>
  fetch("api.php?action=" + action, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(data) }).then(handle);

export async function protect(requiredRole, onReady) {
  try {
    const { user } = await get("me");
    if (!user || user.role !== requiredRole) { location.href = "login.php"; return; }
    onReady(user, user);
  } catch (e) { location.href = "login.php"; }
}

// "5 min ago", "2 hours ago", "3 days ago" (kept so older copies of a page that ask for it still load)
export function ago(stamp) {
  const d = new Date(stamp.replace(" ", "T")), m = Math.floor((Date.now() - d) / 60000);
  if (m < 1) return "just now";
  if (m < 60) return m + " min ago";
  const h = Math.floor(m / 60);
  if (h < 24) return h + " hour" + (h > 1 ? "s" : "") + " ago";
  const days = Math.floor(h / 24);
  return days < 7 ? days + " day" + (days > 1 ? "s" : "") + " ago" : d.toLocaleDateString();
}

// Asks "Log out?" in a small dialog. Resolves true if the user confirms, false otherwise.
export function confirmLogout() {
  return new Promise(resolve => {
    let settled = false, d = null;
    const finish = v => {                       // runs once, however the window gets closed
      if (settled) return;
      settled = true;
      try { if (d && d.open) d.close(); } catch (e) {}
      try { d && d.remove(); } catch (e) {}
      resolve(v);
    };
    try {
      if (typeof HTMLDialogElement !== "function") throw new Error("no dialog support");
      if (!document.getElementById("lo-style")) {
        const s = document.createElement("style");
        s.id = "lo-style";
        s.textContent = `
          .lo-dialog { width: min(380px, 92vw); padding: 26px; border: 0; border-radius: 18px; font-family: inherit;
            color: var(--ink, #2a1a1d); background: var(--bg, #f4efe7);
            box-shadow: 0 24px 60px rgba(40, 10, 16, .45), 0 2px 8px rgba(40, 10, 16, .25); }
          .lo-dialog::backdrop { background: rgba(40, 10, 16, .5); }
          .lo-dialog h2 { margin: 0 0 8px; font-size: 1.3rem; color: var(--brand, #7a1020); }
          .lo-dialog p { margin: 0 0 22px; color: var(--muted, #5c4f51); }
          .lo-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; }
          .lo-actions button { padding: 12px 22px; font: inherit; font-weight: 700; border: 0; border-radius: 999px; cursor: pointer; }
          .lo-cancel { color: var(--ink, #2a1a1d); background: var(--bg, #f4efe7); box-shadow: 5px 5px 11px var(--lo, #d8d0c2), -5px -5px 11px var(--hi, #fff); }
          .lo-ok { color: var(--btn-ink, #fff); background: var(--btn, #7a1020); }
          .lo-actions button:focus-visible { outline: 3px solid #f0b429; outline-offset: 2px; }`;
        document.head.appendChild(s);
      }
      d = document.createElement("dialog");
      d.className = "lo-dialog";
      d.innerHTML = '<h2>Log out?</h2><p>Do you want to log out?</p>' +
        '<div class="lo-actions"><button type="button" class="lo-cancel">No</button><button type="button" class="lo-ok">Yes, log out</button></div>';
      document.body.appendChild(d);
      d.querySelector(".lo-cancel").onclick = () => finish(false);
      d.querySelector(".lo-ok").onclick = () => finish(true);
      d.addEventListener("cancel", e => { e.preventDefault(); finish(false); });      // Esc key
      d.addEventListener("close", () => finish(false));                               // closed any other way
      d.addEventListener("click", e => { if (e.target === d) finish(false); });       // click outside
      d.showModal();
      d.querySelector(".lo-cancel").focus();                                          // "No" is selected first
    } catch (err) {
      finish(confirm("Do you want to log out?"));                                     // plain question as a backup
    }
  });
}

export async function logout() {
  if (!(await confirmLogout())) return;
  await post("logout").catch(() => {});
  location.href = "login.php";
}

// Take over every Log out button on every page that loads this file.
// (logout-confirm.js sets the same flag, so only one of them ever does this.)
if (!window.__loInstalled) {
  window.__loInstalled = true;
  let asking = false;
  document.addEventListener("click", e => {
    const btn = e.target.closest && e.target.closest("#logoutBtn");
    if (!btn) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    if (asking) return;
    asking = true;
    logout().catch(() => {}).then(() => { asking = false; });
  }, true);
}