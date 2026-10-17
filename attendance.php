<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Attendance</title>
  <link rel="stylesheet" href="portal.css">
  <style>
    /* Styles for this page live here so it looks right even with an older portal.css */
    [hidden] { display: none !important; }
    .s-present { color: var(--good); } .s-late { color: #b7791f; } .s-absent { color: var(--bad); } .s-excused { color: #3b82c4; }
    input.rnote { width: 100%; max-width: 260px; padding: 9px 14px; font: inherit; font-size: .95rem; color: var(--ink); background: var(--bg); border: 0;
                  border-radius: 10px; box-shadow: inset 3px 3px 7px var(--lo), inset -3px -3px 7px var(--hi); }
    .field { width: 100%; padding: 12px 16px; margin-bottom: 12px; font: inherit; color: var(--ink); background: var(--bg); border: 0; border-radius: 10px;
             box-shadow: inset 3px 3px 7px var(--lo), inset -3px -3px 7px var(--hi); }
    .field.auto { width: auto; margin: 0; }
    textarea.field { min-height: 90px; resize: vertical; }
    .actions { display: flex; gap: 10px; justify-content: flex-end; }
    .block { margin-bottom: 24px; }
    .block h2 { margin: 0 0 10px; font-size: 1.2rem; }
    .watch ul { margin: 0; padding-left: 20px; } .watch li { margin: 6px 0; }
    .req { display: flex; justify-content: space-between; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--line); }
    .req p { margin: 4px 0 0; } .reqact { display: flex; gap: 8px; flex-wrap: wrap; }
    .mwrap { overflow-x: auto; }
    table.mt th, table.mt td { padding: 6px 4px; font-size: .85rem; text-align: center; min-width: 26px; }
    table.mt td.nm, table.mt th:first-child { position: sticky; left: 0; min-width: 130px; text-align: left; background: var(--bg); font-weight: 700; }
    table.mt td.pick { cursor: pointer; font-weight: 700; } table.mt td.pick:hover { background: rgba(224,177,42,.25); }
    table.mt .tot { font-weight: 700; border-left: 1px solid var(--line); }
    .cal { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; max-width: 460px; }
    .cal-h { font-size: .8rem; text-align: center; color: var(--muted); }
    .cal-d { display: grid; place-items: center; aspect-ratio: 1; border-radius: 12px; font-weight: 700; color: var(--ink); cursor: pointer; }
    .cal-d.empty { cursor: default; } .cal-d:focus-visible { outline: 3px solid #f0b429; outline-offset: 2px; }
    .c-present { background: rgba(26,127,55,.22); } .c-late { background: rgba(224,177,42,.4); }
    .c-absent { background: rgba(198,40,40,.25); } .c-excused { background: rgba(59,130,196,.25); }
    .legend { display: flex; flex-wrap: wrap; gap: 14px; margin: 12px 0 0; font-size: .9rem; color: var(--muted); }
    .dot { display: inline-block; width: 12px; height: 12px; margin-right: 6px; border-radius: 4px; vertical-align: -1px; }
    dialog { box-shadow: 0 24px 60px rgba(40,10,16,.45), 0 2px 8px rgba(40,10,16,.25); }
    .wk { background: rgba(120,120,120,.16); } .hol { background: rgba(224,177,42,.32); } .off { background: rgba(110,110,170,.24); }
    .c-wk { background: rgba(120,120,120,.16); color: var(--muted); }
    .c-hol { background: rgba(224,177,42,.3); box-shadow: inset 0 0 0 2px #e0b12a; }
    .c-noc { background: rgba(110,110,170,.28); }
    .daybanner { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 14px; padding: 14px 18px; border-radius: 14px; background: rgba(224,177,42,.24); font-weight: 700; }
    .daybanner span { flex: 1 1 220px; }
    @media (max-width: 700px) { input.rnote { max-width: none; } .req { flex-direction: column; align-items: flex-start; } }
    @media print { .noprint { display: none !important; } body { padding: 0; } }
  </style>
  <link rel="stylesheet" href="mobile.css">
</head>
<body>
  <div class="wrap">
    <header class="topbar noprint">
      <h1>Attendance</h1>
      <div class="avatar" id="av"></div>
      <a class="navlink" id="home" href="login.php">Grades</a>
      <a class="navlink" href="announcements.php">Announcements</a>
      <button class="ghost" id="logoutBtn">Log out</button>
    </header>

    <div id="teacherView" hidden>
      <section class="stats noprint" id="tStats"></section>

      <section class="panel block watch noprint" id="watch" hidden>
        <h2>Needs attention</h2>
        <ul id="watchList"></ul>
      </section>

      <section class="panel block noprint" id="reqBox" hidden>
        <h2>Absence explanations waiting for you</h2>
        <div id="reqList"></div>
      </section>

      <section class="panel block noprint">
        <div class="tools">
          <select id="class" aria-label="Class"></select>
          <button class="ghost" id="prevDay" aria-label="Previous day">&larr;</button>
          <input class="field auto" type="date" id="day" aria-label="Date">
          <button class="ghost" id="nextDay" aria-label="Next day">&rarr;</button>
          <button class="ghost" id="todayBtn">Today</button>
          <button class="ghost" id="noClassBtn">Mark as no class</button>
          <button class="ghost" id="allPresent">Mark all present</button>
          <button id="save">Save attendance</button>
        </div>
        <div class="daybanner" id="dayBanner" hidden>
          <span id="dayBannerText"></span>
          <button class="ghost" id="holdBtn">Hold class anyway</button>
          <button class="ghost" id="removeOff" hidden>Remove the no-class mark</button>
        </div>
        <div id="dailyBox">
          <table class="stack">
            <thead><tr><th>Student</th><th>Status</th><th>Note</th></tr></thead>
            <tbody id="rows"></tbody>
          </table>
        </div>
      </section>

      <section class="panel block">
        <div class="tools noprint">
          <h2 style="margin:0;flex:1">Monthly summary</h2>
          <input class="field auto" type="month" id="month" aria-label="Month">
          <button class="ghost" id="csvBtn">Export to Excel</button>
          <button class="ghost" id="printBtn">Print</button>
        </div>
        <p class="note noprint">P present, L late, A absent, E excused, H holiday, &times; no class. Click a day to open and fix it.</p>
        <div class="legend"><span><i class="dot wk"></i>Saturday and Sunday (no class)</span><span><i class="dot hol"></i>Holiday</span><span><i class="dot off"></i>No class, marked by a teacher</span></div>
        <p class="note" id="monthNotes"></p>
        <div class="mwrap" id="monthBox"></div>
      </section>
    </div>

    <div id="studentView" hidden>
      <section class="cards" id="sCards"></section>

      <section class="panel block" id="calPanel" style="margin-top:24px" hidden>
        <div class="tools"><h2 style="margin:0;flex:1">My calendar</h2><input class="field auto" type="month" id="calMonth" aria-label="Month"></div>
        <div class="cal" id="cal"></div>
        <div class="legend">
          <span><i class="dot c-present"></i>Present</span><span><i class="dot c-late"></i>Late</span>
          <span><i class="dot c-absent"></i>Absent</span><span><i class="dot c-excused"></i>Excused</span>
          <span><i class="dot c-wk"></i>Weekend / no class</span><span><i class="dot c-hol"></i>Holiday</span>
        </div>
        <p class="note" id="calDetail">Select a day to see the details.</p>
        <p class="note" id="calNotes"></p>
      </section>

      <section class="panel block" id="sRecent" hidden>
        <h2>Absences, late arrivals and excused days</h2>
        <table><thead><tr><th>Date</th><th>Class</th><th>Status</th><th></th></tr></thead><tbody id="sRows"></tbody></table>
      </section>
    </div>
  </div>

  <dialog id="dlgExplain">
    <h2>Explain this day</h2>
    <p class="note" id="exWhen"></p>
    <form id="formExplain">
      <textarea class="field" id="exReason" placeholder="Why were you absent or late? (for example: I was sick)" maxlength="200" required></textarea>
      <div class="actions"><button type="button" class="ghost" data-close>Cancel</button><button type="submit">Send to my teacher</button></div>
    </form>
  </dialog>
  <dialog id="dlgOff">
    <h2>Mark as no class</h2>
    <p class="note" id="offWhen"></p>
    <form id="formOff">
      <input class="field" id="offReason" list="offReasons" placeholder="Reason, e.g. Class suspension due to weather" maxlength="100" required>
      <datalist id="offReasons">
        <option value="Class suspension due to weather"><option value="School event"><option value="Teachers' training"><option value="Holiday declared by the government">
      </datalist>
      <div class="actions"><button type="button" class="ghost" data-close>Cancel</button><button type="submit">Save</button></div>
    </form>
  </dialog>
  <div class="toast" id="toast" role="status"></div>

  <script type="module">
    import { get, post, logout } from "./guard.js";
    const $ = id => document.getElementById(id);
    const el = (tag, cls, text) => { const e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; };
    const initials = n => n.split(/\s+/).map(w => w[0]).slice(0, 2).join("").toUpperCase();
    const STATUS = [["present", "Present"], ["late", "Late"], ["absent", "Absent"], ["excused", "Excused"]];
    const LETTER = { present: "P", late: "L", absent: "A", excused: "E" };
    const NICE = d => new Date(d + "T00:00").toLocaleDateString();
    const friendly = e => e.message === "Server error." ? "The server could not do that. Check that you imported the latest .sql files in phpMyAdmin." : e.message;
    // Holidays, teacher-marked no-class days and weekends
    const CAL = {}, CLS = { weekend: "wk", holiday: "hol", off: "off" };
    const shortDate = d => new Date(d + "T00:00").toLocaleDateString(undefined, { month: "short", day: "numeric" });
    async function monthInfo(month) {
      if (!CAL[month]) {
        try { const r = await get("calendar", { month }); CAL[month] = { holidays: r.holidays || {}, off: r.off || {} }; }
        catch (e) { return { holidays: {}, off: {} }; }      // older server file: only weekends are marked
      }
      return CAL[month];
    }
    function dayInfo(date, info) {
      const h = info.holidays[date];
      if (h) return { type: "holiday", label: h.name + (h.kind === "special" ? " (special non-working day)" : " (regular holiday)") };
      if (info.off[date]) return { type: "off", label: "No class: " + info.off[date] };
      const wd = new Date(date + "T00:00").getDay();
      return wd === 0 || wd === 6 ? { type: "weekend", label: wd === 0 ? "Sunday, no class" : "Saturday, no class" } : null;
    }
    function notesText(info) {
      const items = [...Object.keys(info.holidays).map(d => [d, info.holidays[d].name]), ...Object.keys(info.off).map(d => [d, "No class: " + info.off[d]])]
        .sort((x, y) => x[0] < y[0] ? -1 : 1).map(([d, n]) => `${shortDate(d)}: ${n}`);
      return items.length ? "This month: " + items.join("; ") + "." : "No holidays or no-class days are marked this month.";
    }
    let toastTimer;
    function toast(text, bad = false) {
      const t = $("toast"); t.textContent = text; t.className = "toast show" + (bad ? " bad" : "");
      clearTimeout(toastTimer); toastTimer = setTimeout(() => t.className = "toast", 3200);
    }
    $("logoutBtn").addEventListener("click", logout);
    document.querySelectorAll("[data-close]").forEach(b => b.addEventListener("click", () => b.closest("dialog").close()));

    let user;
    try { ({ user } = await get("me")); } catch (e) {}
    if (!user || !["teacher", "student"].includes(user.role)) { location.href = "login.php"; throw new Error("not signed in"); }
    $("av").textContent = initials(user.name);
    $("home").href = user.role === "teacher" ? "teacher.php" : "student.php";

    // ================= Teacher =================
    async function teacherView() {
      $("teacherView").hidden = false;
      const rows = $("rows"), classSel = $("class"), dayEl = $("day"), monthEl = $("month");
      const iso = d => d.toLocaleDateString("en-CA"), today = iso(new Date());
      dayEl.value = today; dayEl.max = today; monthEl.value = today.slice(0, 7);
      const list = () => [...rows.querySelectorAll("tr[data-uid]")];
      const pick = tr => { const r = tr.querySelector("input[type=radio]:checked"); return r ? r.value : null; };
      const safe = f => f().catch(() => {});
      let holdFor = "";
      const dayKey = () => classSel.value + "|" + dayEl.value;
      function showBanner(di) {
        $("dayBanner").hidden = !di || holdFor === dayKey();
        $("noClassBtn").hidden = !!di;
        if (!di) return;
        $("dayBannerText").textContent = di.label + ". There are no classes on this day.";
        $("removeOff").hidden = di.type !== "off";
      }

      function counts() {
        const c = { present: 0, late: 0, absent: 0, excused: 0, none: 0 };
        list().forEach(tr => c[pick(tr) || "none"]++);
        const box = $("tStats"); box.innerHTML = "";
        [["Present", c.present], ["Late", c.late], ["Absent", c.absent], ["Excused", c.excused], ["Not marked", c.none]].forEach(([l, n]) => {
          const s = el("div", "stat"); s.append(el("span", null, l), el("b", null, String(n))); box.appendChild(s);
        });
      }

      async function load() {
        rows.innerHTML = "";
        if (!classSel.value) { rows.innerHTML = '<tr><td colspan="3" class="empty">Create a class on the grades page first.</td></tr>'; counts(); return; }
        try {
          const di = dayInfo(dayEl.value, await monthInfo(dayEl.value.slice(0, 7)));
          showBanner(di);
          if (di && holdFor !== dayKey()) { $("dailyBox").hidden = true; counts(); return; }
          $("dailyBox").hidden = false;
          const { students } = await get("attendance_get", { class_id: classSel.value, day: dayEl.value });
          if (!students.length) rows.innerHTML = '<tr><td colspan="3" class="empty">No students in this class yet.</td></tr>';
          students.forEach(s => {
            const tr = el("tr"), t1 = el("td"), t2 = el("td"), t3 = el("td"), who = el("div", "who"), seg = el("div", "seg");
            tr.dataset.uid = s.id;
            who.append(el("div", "avatar", initials(s.name)), el("span", null, s.name)); t1.appendChild(who);
            STATUS.forEach(([v, label]) => {
              const l = el("label"), r = el("input");
              r.type = "radio"; r.name = "st" + s.id; r.value = v; r.checked = s.status === v;
              l.append(r, document.createTextNode(label)); seg.appendChild(l);
            });
            t2.appendChild(seg);
            const note = el("input", "rnote");
            note.type = "text"; note.maxLength = 120; note.placeholder = "Note, e.g. arrived 8:12 or sick";
            note.value = s.note || ""; note.setAttribute("aria-label", "Note for " + s.name);
            note.hidden = !s.status || s.status === "present";
            t3.appendChild(note);
            tr.addEventListener("change", () => { const v = pick(tr); note.hidden = !v || v === "present"; counts(); });
            tr.append(t1, t2, t3); rows.appendChild(tr);
          });
          counts();
        } catch (err) { toast(friendly(err), true); }
      }

      async function loadWatch() {
        const ul = $("watchList"); ul.innerHTML = ""; $("watch").hidden = true;
        if (!classSel.value) return;
        const { watch } = await get("attendance_watch", { class_id: classSel.value });
        watch.forEach(w => { const li = el("li"); li.append(el("b", null, w.name), document.createTextNode(" - " + w.reasons.join(", "))); ul.appendChild(li); });
        $("watch").hidden = watch.length === 0;
      }

      async function loadRequests() {
        const box = $("reqList"); box.innerHTML = ""; $("reqBox").hidden = true;
        const { requests } = await get("excuse_list");
        requests.forEach(r => {
          const item = el("div", "req"), txt = el("div"), acts = el("div", "reqact");
          txt.append(el("b", null, r.student), document.createTextNode(` - ${r.class_name} (${r.subject}), ${NICE(r.day)}`), el("p", "note", "\u201C" + r.reason + "\u201D"));
          const yes = el("button", null, "Approve"), no = el("button", "ghost", "Decline");
          const decide = async approve => {
            try { await post("excuse_decide", { id: r.id, approve }); toast(approve ? "Marked as excused." : "Request declined."); refreshAll(); }
            catch (err) { toast(friendly(err), true); }
          };
          yes.onclick = () => decide(true); no.onclick = () => decide(false);
          acts.append(yes, no); item.append(txt, acts); box.appendChild(item);
        });
        $("reqBox").hidden = requests.length === 0;
      }

      async function loadMonth() {
        const box = $("monthBox"); box.innerHTML = "";
        if (!classSel.value) return;
        const m = await get("attendance_month", { class_id: classSel.value, month: monthEl.value });
        const info = await monthInfo(monthEl.value);
        const table = el("table", "mt"), head = el("tr");
        head.appendChild(el("th", null, "Student"));
        for (let d = 1; d <= m.days; d++) {
          const di = dayInfo(monthEl.value + "-" + String(d).padStart(2, "0"), info), th = el("th", di ? CLS[di.type] : "", String(d));
          if (di) th.title = di.label;
          head.appendChild(th);
        }
        ["P", "L", "A", "E"].forEach(t => head.appendChild(el("th", "tot", t)));
        const thead = el("thead"); thead.appendChild(head); table.appendChild(thead);
        const body = el("tbody");
        m.students.forEach(s => {
          const tr = el("tr"), mk = (m.marks && m.marks[s.id]) || {}, tot = { present: 0, late: 0, absent: 0, excused: 0 };
          tr.appendChild(el("td", "nm", s.name));
          for (let d = 1; d <= m.days; d++) {
            const date = monthEl.value + "-" + String(d).padStart(2, "0"), di = dayInfo(date, info), st = mk[d];
            const td = el("td", st ? "s-" + st : "", st ? LETTER[st] : di ? (di.type === "holiday" ? "H" : di.type === "off" ? "\u00D7" : "\u00B7") : "\u00B7");
            if (di) { td.classList.add(CLS[di.type]); td.title = di.label; }
            if (st) tot[st]++;
            if (date <= today) {
              td.classList.add("pick"); td.title = "Open " + date;
              td.onclick = () => { dayEl.value = date; load(); window.scrollTo({ top: 0, behavior: "smooth" }); };
            }
            tr.appendChild(td);
          }
          ["present", "late", "absent", "excused"].forEach(k => tr.appendChild(el("td", "tot", String(tot[k]))));
          body.appendChild(tr);
        });
        table.appendChild(body); box.appendChild(table);
        $("monthNotes").textContent = notesText(info);
      }

      const refreshAll = () => Promise.all([load(), safe(loadWatch), safe(loadRequests), safe(loadMonth)]);

      const { classes } = await get("classes");
      classes.forEach(c => classSel.add(new Option(`${c.name} - ${c.subject}`, c.id)));
      classSel.addEventListener("change", refreshAll);
      dayEl.addEventListener("change", load);
      monthEl.addEventListener("change", () => safe(loadMonth));
      const shift = n => { const d = new Date(dayEl.value + "T00:00"); d.setDate(d.getDate() + n); const v = iso(d); if (v > today) return; dayEl.value = v; load(); };
      $("prevDay").addEventListener("click", () => shift(-1));
      $("nextDay").addEventListener("click", () => shift(1));
      $("todayBtn").addEventListener("click", () => { dayEl.value = today; load(); });
      $("allPresent").addEventListener("click", () => {
        list().forEach(tr => { tr.querySelector('input[value="present"]').checked = true; tr.querySelector(".rnote").hidden = true; }); counts();
      });
      $("save").addEventListener("click", async () => {
        const marks = list().map(tr => {
          const v = pick(tr);
          return v ? { student_id: Number(tr.dataset.uid), status: v, note: v === "present" ? "" : tr.querySelector(".rnote").value.trim() } : null;
        }).filter(Boolean);
        if (!marks.length) return toast("Mark at least one student first.", true);
        try {
          const { saved } = await post("attendance_save", { class_id: classSel.value, day: dayEl.value, marks });
          toast(`Attendance saved for ${saved} student${saved > 1 ? "s" : ""}.`);
          safe(loadWatch); safe(loadMonth);
        } catch (err) { toast(friendly(err), true); }
      });
      $("csvBtn").addEventListener("click", () => {
        const trs = [...$("monthBox").querySelectorAll("tr")];
        if (!trs.length) return toast("Nothing to export yet.", true);
        const esc = v => { v = String(v).replace(/\u00B7/g, ""); if (/^[=+\-@]/.test(v)) v = "'" + v; return '"' + v.replace(/"/g, '""') + '"'; };
        const csv = trs.map(tr => [...tr.children].map(c => esc(c.textContent)).join(",")).join("\r\n");
        const a = el("a"); a.href = URL.createObjectURL(new Blob(["\ufeff" + csv], { type: "text/csv" }));
        a.download = `attendance-${monthEl.value}-${classSel.options[classSel.selectedIndex].text}.csv`; a.click(); URL.revokeObjectURL(a.href);
      });
      $("printBtn").addEventListener("click", () => window.print());

      const forgetMonth = () => { delete CAL[dayEl.value.slice(0, 7)]; delete CAL[monthEl.value]; };
      $("holdBtn").addEventListener("click", () => { holdFor = dayKey(); load(); });
      $("noClassBtn").addEventListener("click", () => { $("offWhen").textContent = NICE(dayEl.value); $("offReason").value = ""; $("dlgOff").showModal(); });
      $("formOff").addEventListener("submit", async e => {
        e.preventDefault();
        try {
          await post("day_off_set", { day: dayEl.value, off: true, reason: $("offReason").value.trim() });
          $("dlgOff").close(); forgetMonth(); toast("Marked as no class for the whole school."); refreshAll();
        } catch (err) { toast(friendly(err), true); }
      });
      $("removeOff").addEventListener("click", async () => {
        try { await post("day_off_set", { day: dayEl.value, off: false }); forgetMonth(); toast("The no-class mark was removed."); refreshAll(); }
        catch (err) { toast(friendly(err), true); }
      });
      refreshAll();
    }

    // ================= Student =================
    async function studentView() {
      $("studentView").hidden = false;
      const cards = $("sCards");
      let data, RECENT = [], target = null;
      try { data = await get("attendance_mine"); } catch (err) { cards.appendChild(el("p", "empty", err.message)); return; }
      if (!data.summary.length) { cards.appendChild(el("p", "empty", "No attendance has been recorded yet.")); return; }

      data.summary.forEach(s => {
        const p = Number(s.present), l = Number(s.late), a = Number(s.absent), x = Number(s.excused);
        const counted = p + l + a, rate = counted ? ((p + l) / counted) * 100 : 100;       // excused days do not lower the rate
        const card = el("article", "subj"); card.style.cursor = "default";
        const head = el("div", "subj-head"), left = el("div");
        left.append(el("h2", null, s.class_name), el("small", null, s.subject));
        head.append(left, el("div", "big " + (rate >= 90 ? "pass" : "fail"), rate.toFixed(0) + "%"));
        const track = el("div", "track"), fill = el("div", "fill" + (rate < 90 ? " fail" : ""));
        fill.style.width = rate + "%"; track.appendChild(fill);
        card.append(head, track, el("p", "note", `Present ${p} \u00B7 Late ${l} \u00B7 Absent ${a} \u00B7 Excused ${x}`));
        if (rate < 90) card.appendChild(el("span", "flag", "Attendance below 90%"));
        cards.appendChild(card);
      });

      // ---- calendar ----
      const monthEl = $("calMonth"); monthEl.value = data.month; $("calPanel").hidden = false;
      async function renderCalendar(month, days) {
        const info = await monthInfo(month);
        const [y, m] = month.split("-").map(Number), grid = $("cal"); grid.innerHTML = "";
        ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"].forEach(d => grid.appendChild(el("div", "cal-h", d)));
        for (let i = 0; i < new Date(y, m - 1, 1).getDay(); i++) grid.appendChild(el("div", "cal-d empty"));
        const by = {}; days.forEach(r => (by[Number(r.day.slice(8, 10))] ||= []).push(r));
        for (let d = 1; d <= new Date(y, m, 0).getDate(); d++) {
          const date = `${month}-${String(d).padStart(2, "0")}`, di = dayInfo(date, info);
          const rows = by[d] || [], worst = ["absent", "late", "excused", "present"].find(s => rows.some(r => r.status === s));
          const kind = worst ? " c-" + worst : di ? " " + { weekend: "c-wk", holiday: "c-hol", off: "c-noc" }[di.type] : "";
          const cell = el("div", "cal-d" + kind, String(d));
          cell.tabIndex = 0; cell.setAttribute("role", "button");
          const show = () => {
            const parts = [...(di ? [di.label] : []), ...rows.map(r => `${r.subject}: ${r.status}${r.note ? " (" + r.note + ")" : ""}`)];
            $("calDetail").textContent = NICE(date) + " - " + (parts.length ? parts.join("; ") : "no attendance recorded.");
          };
          cell.addEventListener("click", show);
          cell.addEventListener("keydown", e => { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); show(); } });
          grid.appendChild(cell);
        }
        $("calDetail").textContent = "Select a day to see the details.";
        $("calNotes").textContent = notesText(info);
      }
      renderCalendar(data.month, data.days);
      monthEl.addEventListener("change", async () => {
        if (!monthEl.value) return;
        try { const d = await get("attendance_mine", { month: monthEl.value }); renderCalendar(d.month, d.days); } catch (err) { toast(friendly(err), true); }
      });

      // ---- recent list, with "Explain" ----
      RECENT = data.recent;
      function renderRecent() {
        const body = $("sRows"); body.innerHTML = ""; $("sRecent").hidden = RECENT.length === 0;
        RECENT.forEach(r => {
          const tr = el("tr"), when = el("td", null, NICE(r.day)), st = el("td", "s-" + r.status, r.status[0].toUpperCase() + r.status.slice(1)), last = el("td");
          if (Number(r.is_new)) when.appendChild(el("span", "newtag", "NEW"));
          if (r.note) st.appendChild(el("small", "note", " - " + r.note));
          if (r.status !== "excused") {
            if (r.request === "pending") last.textContent = "Waiting for your teacher";
            else if (r.request === "rejected") last.textContent = "Not approved";
            else { const b = el("button", "ghost small", "Explain"); b.onclick = () => explain(r); last.appendChild(b); }
          }
          tr.append(when, el("td", null, r.class_name), st, last); body.appendChild(tr);
        });
      }
      function explain(r) {
        target = r; $("exWhen").textContent = `${r.class_name} (${r.subject}), ${NICE(r.day)}`;
        $("exReason").value = ""; $("dlgExplain").showModal();
      }
      $("formExplain").addEventListener("submit", async e => {
        e.preventDefault();
        const reason = $("exReason").value.trim();
        if (reason.length < 3) return toast("Please write a short reason.", true);
        try {
          await post("excuse_request", { class_id: target.class_id, day: target.day, reason });
          target.request = "pending"; $("dlgExplain").close(); renderRecent(); toast("Sent to your teacher.");
        } catch (err) { toast(friendly(err), true); }
      });
      renderRecent();
    }

    user.role === "teacher" ? teacherView() : studentView();
  </script>
  <script type="module" src="badge.js"></script>
  <script src="logout-confirm.js"></script>
  <script type="module" src="nav.js"></script>
</body>
</html>