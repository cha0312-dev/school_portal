// Alerts for students: a gold badge on the Announcements button (new announcements) and on the
// Attendance button (new late, absent or excused marks), plus a count in the browser tab title.
import { get } from "./guard.js";

const baseTitle = document.title;
const here = location.pathname.split("/").pop();

const style = document.createElement("style");
style.textContent = `
  .navlink { position: relative; }
  .navlink .badge { position: absolute; top: -9px; right: -7px; display: grid; place-items: center; min-width: 26px; height: 26px;
    margin: 0; padding: 0 7px; font-size: .85rem; font-weight: 700; line-height: 1; color: #2a0a10; background: #e0b12a;
    border: 2px solid var(--bg, #f4efe7); border-radius: 99px; animation: badgepulse 1.6s ease-out 3; }
  @keyframes badgepulse { 0% { box-shadow: 0 0 0 0 rgba(224,177,42,.85); } 100% { box-shadow: 0 0 0 12px rgba(224,177,42,0); } }
  @media (prefers-reduced-motion: reduce) { .navlink .badge { animation: none; } }`;
document.head.appendChild(style);

function mark(page, n, noun) {
  document.querySelectorAll(`.navlink[href="${page}"]`).forEach(a => {
    a.querySelector(".badge")?.remove();
    if (n > 0 && here !== page) {                       // no badge on the page you are already reading
      const b = document.createElement("span");
      b.className = "badge"; b.textContent = n > 9 ? "9+" : String(n);
      b.setAttribute("aria-label", n + " new " + noun + (n > 1 ? "s" : ""));
      a.appendChild(b);
    }
  });
}

async function refresh() {
  const count = a => get(a).then(r => r.count).catch(() => 0);
  const [ann, att] = await Promise.all([count("ann_unread"), count("att_unread")]);
  mark("announcements.php", ann, "announcement");
  mark("attendance.php", att, "attendance update");
  const total = (here === "announcements.php" ? 0 : ann) + (here === "attendance.php" ? 0 : att);
  document.title = total > 0 ? `(${total}) ${baseTitle}` : baseTitle;
}

refresh();
document.addEventListener("navready", refresh);   // the navigation bar was just built
setInterval(refresh, 60000);   // check again every minute