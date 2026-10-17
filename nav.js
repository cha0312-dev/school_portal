// The navigation for every signed-in page.
//   Computer:     a floating maroon sidebar. Brand at the top, page links in the middle, today's date and your account at the bottom.
//                 The round gold button on its edge collapses it to a strip of icons (and remembers your choice).
//   Small tablet: the sidebar is always the narrow strip of icons.
//   Phone:        a slim maroon top bar with your account menu, and a floating tab bar near the bottom.
// It builds itself, hides the old button row, and keeps the page title.
import { get } from "./guard.js";

let user = null;
try { ({ user } = await get("me")); } catch (e) {}

const oldHeader = document.querySelector("header.topbar");
if (user && oldHeader) {
  const h = (tag, cls, text) => { const e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; };
  const initials = n => n.split(/\s+/).map(w => w[0]).slice(0, 2).join("").toUpperCase();
  const roleName = user.role === "teacher" ? "Teacher" : "Student";
  const home = user.role === "teacher" ? "teacher.html" : "student.html";
  const here = location.pathname.split("/").pop() || home;
  const ICON = {
    grades: '<path d="M3 9l9-5 9 5-9 5-9-5z"/><path d="M7 11.5V16c0 1.5 2.5 3 5 3s5-1.5 5-3v-4.5"/>',
    attendance: '<rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4M9 15l2 2 4-4"/>',
    announcements: '<path d="M6 16v-5a6 6 0 1 1 12 0v5l1.5 2h-15L6 16z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
    profile: '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
    print: '<path d="M7 9V3h10v6"/><rect x="4" y="9" width="16" height="8" rx="2"/><path d="M7 14h10v7H7z"/>',
    payments: '<rect x="2.5" y="5.5" width="19" height="13" rx="2.5"/><path d="M2.5 10h19M6.5 15h4"/>',
    logout: '<path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4"/><path d="M16 8l4 4-4 4M20 12H9"/>'
  };
  const icon = (name, size) => {
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    [["viewBox", "0 0 24 24"], ["width", size], ["height", size], ["fill", "none"], ["stroke", "currentColor"], ["stroke-width", "2"],
     ["stroke-linecap", "round"], ["stroke-linejoin", "round"], ["aria-hidden", "true"]].forEach(([k, v]) => svg.setAttribute(k, v));
    svg.innerHTML = ICON[name];
    return svg;
  };
  const PAGES = [["Grades", home, "grades"], ["Attendance", "attendance.html", "attendance"], ["Announcements", "announcements.html", "announcements"],
                 user.role === "teacher" ? ["Fees", "fees.html", "payments"] : ["Payments", "payments.html", "payments"]];

  const style = h("style");
  style.textContent = `
    .legacy-top { display: none !important; }
    body.has-nav::before { display: none; }
    body.has-nav { --sbw: 248px; padding-left: calc(var(--sbw) + 38px); transition: padding-left .25s ease; }

    /* ---------- Sidebar ---------- */
    .nav { --nav-a: #8c1426; --nav-b: #5a0a16; --nav-gold: #e0b12a; --nav-ink: #4a0812;
      position: fixed; top: 12px; left: 12px; bottom: 12px; z-index: 30; width: var(--sbw); padding: 20px 14px; display: flex; flex-direction: column;
      border-radius: 26px; color: #fff; transition: width .25s ease;
      background: radial-gradient(circle at 115% -5%, rgba(255,255,255,.17), transparent 42%), radial-gradient(circle at -15% 106%, rgba(0,0,0,.32), transparent 50%),
                  linear-gradient(180deg, var(--nav-a), var(--nav-b));
      box-shadow: 0 22px 46px -14px rgba(40,10,16,.7), inset 0 0 0 1px rgba(255,255,255,.08), inset -3px 0 0 var(--nav-gold); }
    .navin { display: flex; flex-direction: column; flex: 1; gap: 16px; min-height: 0; }
    .brand { display: flex; align-items: center; gap: 12px; padding: 4px 6px 18px; border-bottom: 1px solid rgba(255,255,255,.2); text-decoration: none; color: #fff; }
    .logo { display: grid; place-items: center; flex: none; width: 44px; height: 44px; border-radius: 14px; font-size: 1rem; font-weight: 700;
      color: var(--nav-ink); background: linear-gradient(135deg, #f6d56a, var(--nav-gold)); box-shadow: 0 6px 14px rgba(0,0,0,.3), inset 0 0 0 2px rgba(255,255,255,.35); }
    .bt { display: flex; flex-direction: column; line-height: 1.2; min-width: 0; }
    .bt b { font-size: 1.1rem; }
    .bt small { font-size: .78rem; letter-spacing: .05em; color: #f2d98a; }
    .sec { margin: 4px 0 -8px; padding: 0 14px; font-size: .72rem; letter-spacing: .16em; text-transform: uppercase; color: rgba(255,255,255,.6); }
    .links { display: flex; flex-direction: column; gap: 6px; margin: 0; padding: 0; list-style: none; }
    .nav .links a.navlink { position: relative; display: flex; align-items: center; gap: 14px; width: 100%; padding: 13px 14px; border-radius: 14px; font-weight: 700;
      text-decoration: none; color: #fff; background: transparent; box-shadow: none; transition: background .15s, transform .15s; }
    .nav .links a.navlink:hover { background: rgba(255,255,255,.15); transform: translateX(3px); }
    .nav .links a.navlink[aria-current=page] { color: var(--nav-ink); background: var(--nav-gold); box-shadow: 0 10px 20px -8px rgba(224,177,42,.7); transform: none; }
    .nav .links svg { flex: none; display: block; }
    .nav .links .navlink .badge { position: static; margin: 0 0 0 auto; border-color: transparent; }
    .nav a:focus-visible, .nav button:focus-visible, .tabbar a:focus-visible { outline: 3px solid #f0b429; outline-offset: 2px; }
    .tabbar .t-label { display: none; }

    .today { margin-top: auto; padding: 12px 14px; border-radius: 16px; background: rgba(255,255,255,.1); }
    .today small { display: block; font-size: .72rem; letter-spacing: .14em; text-transform: uppercase; color: #f2d98a; }
    .today b { display: block; font-size: 1.05rem; }
    .today span { color: rgba(255,255,255,.8); font-size: .9rem; }
    .usr { position: relative; }
    .nav .ubtn { display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px; text-align: left; color: #fff;
      background: rgba(255,255,255,.1); box-shadow: none; border-radius: 16px; transition: background .15s; }
    .nav .ubtn:hover, .nav .ubtn[aria-expanded=true] { background: rgba(255,255,255,.2); }
    .nav .ubtn .uname { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 700; }
    .nav .ubtn .uname small { display: block; font-weight: 400; font-size: .78rem; color: #f2d98a; }
    .av { display: grid; place-items: center; flex: none; width: 40px; height: 40px; border-radius: 50%; font-size: .85rem; font-weight: 700; color: var(--nav-ink); background: var(--nav-gold); }
    .chev { font-size: .8rem; transition: transform .2s; }
    .ubtn[aria-expanded=true] .chev { transform: rotate(180deg); }
    .menu { position: absolute; left: 0; right: 0; bottom: calc(100% + 12px); padding: 8px; border-radius: 16px; color: var(--ink, #2a1a1d);
      background: var(--bg, #f4efe7); box-shadow: 0 18px 40px rgba(40,10,16,.4), 0 2px 8px rgba(40,10,16,.2); }
    .menu::before { content: ""; position: absolute; bottom: -6px; left: 28px; width: 12px; height: 12px; border-radius: 2px; transform: rotate(45deg); background: var(--bg, #f4efe7); }
    .menu .who { display: flex; align-items: center; gap: 12px; padding: 10px 12px 14px; margin-bottom: 6px; border-bottom: 1px solid var(--line, #e3dccf); }
    .menu .who .av { width: 42px; height: 42px; font-size: 1rem; color: #fff; background: var(--nav-a); }
    .menu .who b { display: block; }
    .menu .who small { display: block; color: var(--muted, #5c4f51); }
    .nav .menu button { display: flex; align-items: center; gap: 12px; width: 100%; padding: 12px; text-align: left; font-weight: 700; color: var(--ink, #2a1a1d);
      background: transparent; box-shadow: none; border-radius: 10px; }
    .nav .menu button:hover { background: rgba(122,16,32,.09); }
    .nav .menu button.out { color: var(--bad, #c62828); }

    /* the round button on the sidebar's edge */
    .nav .collapse { position: absolute; top: 38px; right: -14px; display: grid; place-items: center; width: 28px; height: 28px; padding: 0; font-size: 1.1rem; line-height: 1;
      color: var(--nav-ink); background: var(--nav-gold); border-radius: 50%; box-shadow: 0 6px 14px rgba(0,0,0,.35); }
    .nav .collapse:hover { background: #f2c94c; }
    .nav .collapse[hidden] { display: none; }

    .pagehead { padding: 26px 0 14px; }
    .pagehead h1 { margin: 0; font-size: 1.7rem; color: var(--brand, #7a1020); }
    .tabbar { display: none; }
    @media (prefers-reduced-motion: no-preference) {
      .menu:not([hidden]) { animation: navpop .16s ease-out; }
      @keyframes navpop { from { opacity: 0; transform: translateY(6px) scale(.98); } }
    }
    @media (prefers-reduced-motion: reduce) { body.has-nav, .nav, .nav .links a.navlink { transition: none; } }

    /* ---------- Narrow strip of icons (collapsed, or small tablets) ---------- */
    body.rail { --sbw: 80px; }
    body.rail .nav { padding: 20px 10px; }
    body.rail .bt, body.rail .sec, body.rail .today, body.rail .links .navlink > span, body.rail .uname, body.rail .chev { display: none; }
    body.rail .brand { justify-content: center; padding: 4px 0 16px; }
    body.rail .nav .links a.navlink { justify-content: center; padding: 13px 0; }
    body.rail .nav .links a.navlink:hover { transform: none; }
    body.rail .nav .links .navlink .badge { position: absolute; top: 2px; right: 6px; margin: 0; }
    body.rail .nav .ubtn { justify-content: center; padding: 6px; }
    body.rail .menu { right: auto; min-width: 250px; }
    body.rail .nav .links a.navlink:hover::after, body.rail .nav .links a.navlink:focus-visible::after {
      content: attr(data-tip); position: absolute; left: calc(100% + 16px); top: 50%; transform: translateY(-50%); padding: 7px 12px; border-radius: 10px;
      font-size: .85rem; white-space: nowrap; color: #fff; background: #2a0a10; box-shadow: 0 8px 18px rgba(0,0,0,.35); pointer-events: none; }

    /* ---------- Phones: slim top bar and a floating tab bar ---------- */
    @media (max-width: 700px) {
      body.has-nav { padding-left: 12px; }
      .nav { position: sticky; top: 0; left: auto; bottom: auto; width: auto; margin: 0 -12px; padding: 0 12px; display: block; border-radius: 0;
        border-bottom: 3px solid var(--nav-gold); background: linear-gradient(135deg, var(--nav-a), var(--nav-b));
        box-shadow: 0 8px 20px -12px rgba(40,10,16,.6); }
      .navin { flex-direction: row; align-items: center; min-height: 58px; gap: 12px; }
      .brand { padding: 0; border: 0; }
      .links, .uname, .chev, .bt small, .sec, .today, .nav .collapse { display: none; }
      .usr { margin: 0 0 0 auto; }
      .nav .ubtn { width: auto; padding: 4px; background: transparent; }
      .menu { left: auto; right: 0; bottom: auto; top: calc(100% + 12px); min-width: 240px; }
      .menu::before { bottom: auto; top: -6px; left: auto; right: 24px; }
      .pagehead { padding: 16px 0 10px; } .pagehead h1 { font-size: 1.4rem; }
      body { padding-bottom: 104px; }
      .tabbar { display: flex; gap: 4px; position: fixed; left: 12px; right: 12px; bottom: calc(10px + env(safe-area-inset-bottom, 0px)); z-index: 40; padding: 6px;
        border-radius: 22px; background: var(--bg, #f4efe7); box-shadow: 0 12px 30px rgba(40,10,16,.38), 0 2px 8px rgba(40,10,16,.18); }
      .tabbar a.navlink { position: relative; flex: 1; display: flex; flex-direction: column; align-items: center; gap: 3px; min-height: 0; padding: 8px 4px 7px;
        font-size: .78rem; font-weight: 700; text-decoration: none; color: var(--muted, #5c4f51); background: transparent; box-shadow: none; border-radius: 16px; transition: background .15s; }
      .tabbar a.navlink[aria-current=page] { color: var(--nav-ink, #4a0812); background: rgba(224,177,42,.4); }
      .tabbar svg { display: block; }
      .tabbar .t-label { display: block; }
      .tabbar .navlink .badge { top: 0; right: calc(50% - 32px); }
      .toast { bottom: 112px; }
    }
    @media print { .nav, .tabbar { display: none !important; } body.has-nav { padding-left: 0; } }`;
  document.head.appendChild(style);

  const link = (label, href, name, tab) => {
    const a = h("a", "navlink"); a.href = href;
    a.setAttribute("aria-label", label); a.setAttribute("data-tip", label);        // the name stays readable even when only the icon shows
    if (here === href) a.setAttribute("aria-current", "page");
    a.append(icon(name, "24"), h("span", tab ? "t-label" : null, label));
    return a;
  };

  // ---- sidebar ----
  const nav = h("nav", "nav"), inner = h("div", "navin"), brand = h("a", "brand"), list = h("ul", "links"), words = h("span", "bt");
  nav.setAttribute("aria-label", "Main"); brand.href = home;
  words.append(h("b", null, "School Portal"), h("small", null, roleName));
  brand.append(h("span", "logo", "SP"), words);
  PAGES.forEach(([label, href, name]) => { const li = h("li"); li.appendChild(link(label, href, name, false)); list.appendChild(li); });

  const now = new Date(), today = h("div", "today");
  today.append(h("small", null, "Today"), h("b", null, now.toLocaleDateString(undefined, { weekday: "long" })), h("span", null, now.toLocaleDateString(undefined, { month: "long", day: "numeric" })));

  const usr = h("div", "usr"), btn = h("button", "ubtn"), menu = h("div", "menu"), uname = h("span", "uname");
  btn.type = "button"; btn.setAttribute("aria-haspopup", "menu"); btn.setAttribute("aria-expanded", "false");
  uname.append(user.name.split(" ")[0], h("small", null, roleName));
  btn.append(h("span", "av", initials(user.name)), uname, h("span", "chev", "\u25B4"));
  menu.hidden = true; menu.setAttribute("role", "menu");
  const who = h("div", "who"), whoText = h("div");
  whoText.append(h("b", null, user.name), h("small", null, roleName + " account"));
  who.append(h("span", "av", initials(user.name)), whoText); menu.appendChild(who);
  const item = (label, id, name, cls) => {            // runs the page's own (hidden) button, so nothing is duplicated
    if (!document.getElementById(id)) return;
    const b = h("button", cls); b.type = "button"; b.setAttribute("role", "menuitem");
    b.append(icon(name, "20"), h("span", null, label));
    b.addEventListener("click", () => { close(); document.getElementById(id).click(); });
    menu.appendChild(b);
  };
  const goItem = (label, href, name) => {            // opens another page
    const b = h("button"); b.type = "button"; b.setAttribute("role", "menuitem");
    b.append(icon(name, "20"), h("span", null, label));
    b.addEventListener("click", () => { close(); location.href = href; });
    menu.appendChild(b);
  };
  goItem("My profile", "profile.html", "profile"); item("Print report card", "printBtn", "print"); item("Log out", "logoutBtn", "logout", "out");
  usr.append(btn, menu);
  inner.append(brand, h("div", "sec", "Menu"), list, today, usr);

  const collapse = h("button", "collapse"); collapse.type = "button";
  nav.append(inner, collapse);

  const close = () => { menu.hidden = true; btn.setAttribute("aria-expanded", "false"); };
  btn.addEventListener("click", e => { e.stopPropagation(); menu.hidden = !menu.hidden; btn.setAttribute("aria-expanded", String(!menu.hidden)); });
  document.addEventListener("click", e => { if (!usr.contains(e.target)) close(); });
  document.addEventListener("keydown", e => { if (e.key === "Escape") { close(); btn.focus(); } });

  // ---- collapse to icons: your choice is remembered; small tablets always use icons ----
  const KEY = "schoolPortalNavCollapsed";
  let collapsed = false;
  try { collapsed = localStorage.getItem(KEY) === "1"; } catch (e) {}
  const phone = matchMedia("(max-width: 700px)"), tablet = matchMedia("(min-width: 701px) and (max-width: 900px)");
  function applyRail() {
    document.body.classList.toggle("rail", !phone.matches && (collapsed || tablet.matches));
    collapse.hidden = phone.matches || tablet.matches;
    collapse.textContent = collapsed ? "\u203A" : "\u2039";
    collapse.setAttribute("aria-label", collapsed ? "Expand the sidebar" : "Collapse the sidebar");
  }
  collapse.addEventListener("click", () => { collapsed = !collapsed; try { localStorage.setItem(KEY, collapsed ? "1" : "0"); } catch (e) {} applyRail(); });
  phone.addEventListener("change", applyRail); tablet.addEventListener("change", applyRail);

  // ---- floating tab bar for phones ----
  const tabs = h("nav", "tabbar"); tabs.setAttribute("aria-label", "Pages");
  PAGES.forEach(([label, href, name]) => tabs.appendChild(link(label, href, name, true)));

  // ---- put it on the page; keep the old title, hide the old row of buttons ----
  const wrap = oldHeader.parentElement, title = oldHeader.querySelector("h1");
  const head = h("div", "pagehead"); if (title) head.appendChild(title);
  wrap.insertBefore(head, wrap.firstChild);
  oldHeader.classList.add("legacy-top");
  document.body.classList.add("has-nav");
  document.body.insertBefore(nav, document.body.firstChild);
  document.body.appendChild(tabs);
  applyRail();
  document.dispatchEvent(new Event("navready"));
}