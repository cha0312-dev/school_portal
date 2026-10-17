<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>School Fees</title>
  <link rel="stylesheet" href="portal.css">
  <style>
    /* Styles for this page live here so it looks right even with an older portal.css */
    [hidden] { display: none !important; }
    .lead { margin: 0 0 20px; color: var(--muted); }
    .toolbar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 18px; }
    .toolbar .grow { flex: 1; }
    .chips { display: flex; flex-wrap: wrap; gap: 8px; margin: 0; }
    button.chip { padding: 8px 18px; font-weight: 400; color: var(--ink); background: var(--bg); cursor: pointer; box-shadow: 3px 3px 7px var(--lo), -3px -3px 7px var(--hi); }
    button.chip.on { color: var(--btn-ink); background: var(--btn); box-shadow: none; font-weight: 700; }
    .count { display: inline-block; min-width: 22px; margin-left: 6px; padding: 0 7px; font-size: .8rem; font-weight: 700; color: #2a0a10; background: #e0b12a; border-radius: 99px; }
    .stat b { font-size: 1.7rem; }
    .stat small { display: block; margin-top: 4px; font-size: .85rem; color: var(--muted); }

    .fees { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
    .fee { display: flex; flex-direction: column; padding: 20px 22px; border-radius: 18px; border-top: 5px solid var(--c, #8c1426); background: var(--bg);
      box-shadow: 8px 8px 20px var(--lo), -8px -8px 20px var(--hi); }
    .fee .cat { font-size: .8rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--c, #8c1426); }
    .fee h3 { margin: 2px 0 0; font-size: 1.15rem; }
    .fee .amt { margin: 10px 0; font-size: 1.6rem; font-weight: 700; font-variant-numeric: tabular-nums; }
    .fee .amt small { font-size: .9rem; font-weight: 400; color: var(--muted); }
    .bar { height: 9px; border-radius: 99px; overflow: hidden; box-shadow: inset 2px 2px 4px var(--lo), inset -2px -2px 4px var(--hi); }
    .bar i { display: block; height: 100%; width: 0; border-radius: 99px; background: var(--good); transition: width .8s cubic-bezier(.2,.8,.2,1); }
    .split { display: flex; justify-content: space-between; margin-top: 8px; font-size: .9rem; color: var(--muted); font-variant-numeric: tabular-nums; }
    .meta { display: flex; flex-wrap: wrap; gap: 6px 10px; margin-top: 12px; font-size: .9rem; color: var(--muted); }
    .fee .go { display: flex; flex-wrap: wrap; gap: 8px; margin-top: auto; padding-top: 16px; }
    .fee .go button { padding: 8px 14px; font-size: .9rem; }
    .fee .go button.danger { color: var(--bad); }
    .note { color: var(--muted); font-size: .9rem; }

    .tag { display: inline-block; padding: 3px 11px; font-size: .82rem; font-weight: 700; border-radius: 99px; white-space: nowrap; }
    .t-paid, .t-confirmed { color: #fff; background: var(--good); }
    .t-partial, .t-pending { color: #2a0a10; background: #e0b12a; }
    .t-unpaid { color: var(--ink); box-shadow: inset 2px 2px 4px var(--lo), inset -2px -2px 4px var(--hi); }
    .t-overdue, .t-rejected { color: #fff; background: var(--bad); }
    .t-due { color: #2a0a10; background: #f2d98a; }

    table td, table th { text-align: left; }
    td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
    td button { padding: 7px 14px; font-size: .88rem; margin: 2px; }
    .sub { display: block; font-size: .85rem; color: var(--muted); }

    dialog.wide { width: min(860px, 96vw); }
    #dlgFee { width: min(560px, 94vw); }
    .lab { display: block; margin: 2px 0 6px; font-size: .92rem; font-weight: 700; color: var(--muted); }
    .two { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .seg { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
    .check { display: flex; gap: 10px; align-items: center; margin: 4px 0 14px; }
    .check input { width: 20px; height: 20px; accent-color: var(--brand); }
    .dhead { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 12px; }
    .dhead h2 { margin: 0; flex: 1; }
    .scroll { max-height: 60vh; overflow: auto; }
    @media (max-width: 600px) { .two { grid-template-columns: 1fr; } }
  </style>
  <link rel="stylesheet" href="mobile.css">
</head>
<body>
  <div class="wrap">
    <header class="topbar noprint">
      <h1>School fees</h1>
      <div class="avatar" id="av"></div>
      <a class="navlink" href="teacher.php">Grades</a>
      <a class="navlink" href="attendance.php">Attendance</a>
      <button class="ghost" id="logoutBtn">Log out</button>
    </header>

    <p class="lead">Set the fees students pay, for the whole school, a grade level, a class or room, or a subject. Then check payments and give receipts.</p>
    <section class="stats" id="stats"></section>

    <div class="toolbar">
      <div class="chips grow" id="tabs" role="tablist"></div>
      <button type="button" id="newFee">+ New fee</button>
    </div>

    <section id="tabFees"></section>
    <section id="tabCheck" class="panel" hidden></section>
    <section id="tabDone" class="panel" hidden></section>
  </div>

  <!-- add or change a fee -->
  <dialog id="dlgFee" aria-labelledby="feeHead">
    <h2 id="feeHead">New fee</h2>
    <form id="feeForm">
      <label class="lab" for="fTitle">Name</label>
      <input class="field" id="fTitle" maxlength="100" placeholder="e.g. Laboratory fee, PTA fee, Field trip" required>
      <div class="two">
        <div><label class="lab" for="fCat">Type</label><select class="field" id="fCat"></select></div>
        <div><label class="lab" for="fAmt">Amount per student</label><input class="field" id="fAmt" type="number" min="1" step="0.01" inputmode="decimal" required></div>
      </div>
      <span class="lab">Who pays it?</span>
      <div class="seg" id="fTo"></div>
      <div id="fWhich"></div>
      <div class="two">
        <div><label class="lab" for="fDue">Due date (optional)</label><input class="field" id="fDue" type="date"></div>
        <div><label class="check" style="margin-top:30px"><input type="checkbox" id="fPartial" checked> Allow installments</label></div>
      </div>
      <label class="lab" for="fDesc">Note for students (optional)</label>
      <input class="field" id="fDesc" maxlength="255" placeholder="What the fee is for">
      <div class="actions"><button type="button" class="ghost" id="feeCancel">Cancel</button><button type="submit">Save fee</button></div>
    </form>
  </dialog>

  <!-- students who owe one fee -->
  <dialog id="dlgStu" class="wide" aria-labelledby="stuHead">
    <div class="dhead">
      <h2 id="stuHead"></h2>
      <input type="search" id="stuFind" placeholder="Search a student" aria-label="Search a student">
      <select id="stuShow" aria-label="Show"><option value="">Everyone</option><option value="open">Not fully paid</option><option value="paid">Fully paid</option></select>
    </div>
    <p class="note" id="stuSum"></p>
    <div class="scroll" id="stuList"></div>
    <div class="actions" style="margin-top:14px"><button type="button" class="ghost" id="stuCsv">Download CSV</button><button type="button" id="stuClose">Close</button></div>
  </dialog>

  <!-- record cash -->
  <dialog id="dlgCash" aria-labelledby="cashHead">
    <h2 id="cashHead">Record cash payment</h2>
    <form id="cashForm">
      <p class="note" id="cashWho"></p>
      <label class="lab" for="cAmt">Amount received</label>
      <input class="field" id="cAmt" type="number" min="1" step="0.01" inputmode="decimal" required>
      <label class="lab" for="cRef">Your own receipt or log number (optional)</label>
      <input class="field" id="cRef" maxlength="60">
      <div class="actions"><button type="button" class="ghost" id="cashCancel">Cancel</button><button type="submit">Record and give receipt</button></div>
    </form>
  </dialog>

  <div class="toast" id="toast" role="status"></div>

  <script type="module">
    import { get, post, protect, logout } from "./guard.js";
    const $ = id => document.getElementById(id);
    const el = (tag, cls, text) => { const e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; };
    const initials = n => n.split(/\s+/).map(w => w[0]).slice(0, 2).join("").toUpperCase();
    const peso = new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" });
    const money = n => peso.format(Number(n) || 0);
    const r2 = n => Math.round(n * 100) / 100;
    const dateOf = s => new Date(String(s).slice(0, 10) + "T00:00:00");
    const niceDate = s => s ? dateOf(s).toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" }) : "";
    const CAT = { tuition: ["Tuition", "#8c1426"], miscellaneous: ["Miscellaneous", "#6b4fa0"], laboratory: ["Laboratory", "#1f7a6d"], books: ["Books", "#2f6fb0"],
                  uniform: ["Uniform", "#b0482f"], activity: ["Activity", "#b7791f"], exam: ["Exam", "#7a1020"], other: ["Other", "#5c4f51"] };
    const METHOD_NAME = { gcash: "GCash", maya: "Maya", bank: "Bank transfer", cash: "Cash", online: "Online" };
    let toastTimer;
    function toast(text, bad = false) {
      const t = $("toast"); t.textContent = text; t.className = "toast show" + (bad ? " bad" : "");
      clearTimeout(toastTimer); toastTimer = setTimeout(() => t.className = "toast", 3600);
    }
    // A small window with a question; with a placeholder it also asks for a short text
    function ask(title, message, yes, placeholder) {
      return new Promise(resolve => {
        let done = false; const d = el("dialog");
        const finish = v => { if (done) return; done = true; try { d.close(); } catch (e) {} d.remove(); resolve(v); };
        const row = el("div", "actions"), no = el("button", "ghost", "No"), ok = el("button", null, yes);
        const input = placeholder ? el("input", "field") : null;
        if (input) { input.placeholder = placeholder; input.maxLength = 200; }
        no.type = ok.type = "button"; no.onclick = () => finish(false);
        ok.onclick = () => { if (input && input.value.trim().length < 3) { input.focus(); return; } finish(input ? input.value.trim() : true); };
        row.append(no, ok); d.append(el("h2", null, title), el("p", null, message)); if (input) d.appendChild(input); d.appendChild(row);
        d.addEventListener("cancel", e => { e.preventDefault(); finish(false); }); d.addEventListener("close", () => finish(false));
        document.body.appendChild(d); d.showModal(); (input || no).focus();
      });
    }
    $("logoutBtn").addEventListener("click", logout);

    let D = null, PAY = [], tab = "fees", editing = null, viewing = null, STU = [];
    const scopeText = f => f.applies_to === "grade" ? f.grade_level : f.applies_to === "class" ? "Class: " + (f.class_name || "") : f.applies_to === "subject" ? "Subject: " + f.subject : "Whole school";
    const daysTo = d => d ? Math.round((dateOf(d) - dateOf(D.today)) / 86400000) : null;

    function stats() {
      const exp = D.fees.reduce((s, f) => s + Number(f.expected), 0), col = D.fees.reduce((s, f) => s + Number(f.collected), 0);
      const wait = PAY.filter(p => p.status === "pending");
      const box = $("stats"); box.innerHTML = "";
      [["Fees", String(D.fees.length), D.fees.filter(f => f.mine).length + " made by you"],
       ["Expected", money(exp), "from every student who owes"],
       ["Collected", money(col), exp ? Math.round(col / exp * 100) + "% of expected" : ""],
       ["To check", String(wait.length), money(wait.reduce((s, p) => s + Number(p.amount), 0))]].forEach(([l, v, s]) => {
        const c = el("div", "stat"); c.append(el("span", null, l), el("b", null, v), el("small", null, s)); box.appendChild(c);
      });
    }

    function tabs() {
      const box = $("tabs"); box.innerHTML = "";
      const wait = PAY.filter(p => p.status === "pending").length;
      [["fees", "Fees"], ["check", "Payments to check", wait], ["done", "Checked payments"]].forEach(([k, label, n]) => {
        const b = el("button", "chip" + (tab === k ? " on" : ""), label); b.type = "button"; b.setAttribute("role", "tab"); b.setAttribute("aria-selected", String(tab === k));
        if (n) b.appendChild(el("span", "count", String(n)));
        b.onclick = () => { tab = k; tabs(); show(); };
        box.appendChild(b);
      });
    }
    function show() {
      $("tabFees").hidden = tab !== "fees"; $("tabCheck").hidden = tab !== "check"; $("tabDone").hidden = tab !== "done"; $("newFee").hidden = tab !== "fees";
      if (tab === "fees") feeCards(); else payTable(tab === "check" ? "pending" : "decided");
    }

    function feeCards() {
      const box = $("tabFees"); box.innerHTML = "";
      if (!D.fees.length) { box.appendChild(el("p", "empty", "No fees yet. Use New fee to add the first one.")); return; }
      const grid = el("div", "fees");
      D.fees.forEach(f => {
        const [cat, color] = CAT[f.category] || CAT.other, c = el("article", "fee"); c.style.setProperty("--c", color);
        c.append(el("div", "cat", cat), el("h3", null, f.title));
        const amt = el("div", "amt", money(f.amount)); amt.appendChild(el("small", null, " per student")); c.appendChild(amt);
        const bar = el("div", "bar"), fill = el("i"); bar.appendChild(fill); c.appendChild(bar);
        const pct = Number(f.expected) ? Number(f.collected) / Number(f.expected) * 100 : 0;
        requestAnimationFrame(() => fill.style.width = Math.min(100, pct) + "%");
        const sp = el("div", "split"); sp.append(el("span", null, money(f.collected) + " collected"), el("span", null, "of " + money(f.expected))); c.appendChild(sp);
        const meta = el("div", "meta"), d = daysTo(f.due_date);
        meta.append(el("span", null, scopeText(f)), el("span", null, "· " + f.students + " student" + (f.students == 1 ? "" : "s")));
        if (d !== null) meta.appendChild(el("span", d < 0 ? "tag t-overdue" : d <= 7 ? "tag t-due" : null, d < 0 ? "Past due " + niceDate(f.due_date) : "Due " + niceDate(f.due_date)));
        if (Number(f.waiting)) meta.appendChild(el("span", "tag t-pending", f.waiting + " to check"));
        if (!Number(f.allow_partial)) meta.appendChild(el("span", null, "· Full payment only"));
        meta.appendChild(el("span", null, "· Set by " + (f.mine ? "you" : f.set_by)));
        c.appendChild(meta);
        const go = el("div", "go"), btn = (t, fn, cls) => { const b = el("button", cls || "ghost", t); b.type = "button"; b.onclick = fn; go.appendChild(b); };
        btn("Students", () => openStudents(f), "");
        if (f.mine) { btn("Edit", () => openFee(f)); btn("Remove", () => removeFee(f), "ghost danger"); }
        c.appendChild(go); grid.appendChild(c);
      });
      box.appendChild(grid);
    }

    function payTable(kind) {
      const box = kind === "pending" ? $("tabCheck") : $("tabDone"); box.innerHTML = "";
      const rows = PAY.filter(p => kind === "pending" ? p.status === "pending" : p.status !== "pending");
      if (!rows.length) { box.appendChild(el("p", "empty", kind === "pending" ? "Nothing to check right now. Payments students send for your fees show up here." : "No checked payments yet.")); return; }
      if (kind === "pending") box.appendChild(el("p", "note", "Look for each reference number in the school's GCash, Maya or bank account before you confirm it."));
      const t = el("table"), hr = el("tr");
      (kind === "pending" ? [["Sent"], ["Student"], ["Fee"], ["Paid by"], ["Reference"], ["Amount", "num"], [""]]
                          : [["Checked"], ["Student"], ["Fee"], ["Paid by"], ["Receipt / reason"], ["Amount", "num"], ["Status"]]).forEach(([h, c]) => hr.appendChild(el("th", c, h)));
      t.appendChild(hr);
      rows.forEach(p => {
        const tr = el("tr");
        tr.appendChild(el("td", null, niceDate(kind === "pending" ? p.created_at : p.decided_at)));
        const who = el("td"); who.append(el("b", null, p.student), el("span", "sub", [p.school_id, p.grade_level].filter(Boolean).join(" · "))); tr.appendChild(who);
        tr.appendChild(el("td", null, p.fee_title));
        tr.appendChild(el("td", null, METHOD_NAME[p.method] || p.method));
        if (kind === "pending") {
          tr.appendChild(el("td", null, p.reference || "-"));
          tr.appendChild(el("td", "num", money(p.amount)));
          const act = el("td"), ok = el("button", null, "Confirm"), no = el("button", "ghost", "Reject");
          ok.type = no.type = "button";
          ok.onclick = async () => {
            if (!(await ask("Confirm this payment?", `${money(p.amount)} from ${p.student} by ${METHOD_NAME[p.method]}, reference ${p.reference}. The student gets a receipt.`, "Yes, confirm"))) return;
            try { await post("pay_decide", { id: p.id, confirm: true }); toast("Confirmed. A receipt was made."); load(); } catch (e) { toast(e.message, true); }
          };
          no.onclick = async () => {
            const why = await ask("Reject this payment?", "Tell the student why, so they can fix it.", "Reject", "e.g. Reference number not found");
            if (!why) return;
            try { await post("pay_decide", { id: p.id, confirm: false, note: why }); toast("Payment rejected."); load(); } catch (e) { toast(e.message, true); }
          };
          act.append(ok, no); tr.appendChild(act);
        } else {
          tr.appendChild(el("td", null, p.status === "confirmed" ? p.receipt_no : p.note || "-"));
          tr.appendChild(el("td", "num", money(p.amount)));
          const st = el("td"); st.appendChild(el("span", "tag t-" + p.status, p.status === "confirmed" ? "Confirmed" : "Rejected")); tr.appendChild(st);
        }
        t.appendChild(tr);
      });
      box.appendChild(t);
    }

    // ---------- Add or change a fee ----------
    const TO = [["school", "Whole school"], ["grade", "Grade level"], ["class", "Class / room"], ["subject", "Subject"]];
    Object.entries(CAT).forEach(([k, [label]]) => { const o = el("option", null, label); o.value = k; $("fCat").appendChild(o); });
    TO.forEach(([k, label]) => {
      const l = el("label"), r = el("input"); r.type = "radio"; r.name = "to"; r.value = k;
      r.onchange = which; l.append(r, label); l.className = ""; $("fTo").appendChild(l);
    });
    $("fTo").classList.add("seg");
    function which() {
      const k = $("feeForm").to.value, box = $("fWhich"); box.innerHTML = "";
      if (k === "school") return;
      const s = el("select", "field"); s.id = "fPick"; s.required = true; s.setAttribute("aria-label", "Which one");
      const opt = (v, t) => { const o = el("option", null, t); o.value = v; s.appendChild(o); };
      opt("", k === "grade" ? "Choose a grade level" : k === "class" ? "Choose a class" : "Choose a subject");
      if (k === "grade") D.grades.forEach(g => opt(g, g));
      if (k === "class") D.classes.forEach(c => opt(c.id, `${c.name} - ${c.subject} (${c.teacher})`));
      if (k === "subject") D.subjects.forEach(x => opt(x, x));
      if (editing) s.value = k === "grade" ? editing.grade_level || "" : k === "class" ? editing.class_id || "" : editing.subject || "";
      box.appendChild(s);
      if (k === "grade" && !D.grades.length) box.appendChild(el("p", "note", "No student has a grade level on their profile yet."));
    }
    function openFee(f) {
      editing = f || null;
      $("feeHead").textContent = f ? "Change fee" : "New fee";
      $("fTitle").value = f ? f.title : ""; $("fCat").value = f ? f.category : "miscellaneous"; $("fAmt").value = f ? f.amount : "";
      $("fDue").value = f && f.due_date ? f.due_date : ""; $("fPartial").checked = f ? Number(f.allow_partial) === 1 : true; $("fDesc").value = f ? f.description || "" : "";
      $("feeForm").querySelector(`input[name=to][value=${f ? f.applies_to : "school"}]`).checked = true;
      which(); $("dlgFee").showModal(); $("fTitle").focus();
    }
    $("newFee").onclick = () => openFee(null);
    $("feeCancel").onclick = () => $("dlgFee").close();
    $("feeForm").addEventListener("submit", async e => {
      e.preventDefault();
      const to = $("feeForm").to.value, pick = $("fPick") ? $("fPick").value : "";
      const data = { id: editing ? editing.id : 0, title: $("fTitle").value.trim(), category: $("fCat").value, amount: Number($("fAmt").value),
        applies_to: to, grade_level: to === "grade" ? pick : "", class_id: to === "class" ? Number(pick) : 0, subject: to === "subject" ? pick : "",
        due_date: $("fDue").value, allow_partial: $("fPartial").checked, description: $("fDesc").value.trim() };
      try { await post("fee_save", data); $("dlgFee").close(); toast(editing ? "Fee updated." : "Fee added. Students who owe it can see it now."); load(); }
      catch (err) { toast(err.message, true); }
    });
    async function removeFee(f) {
      if (!(await ask("Remove this fee?", "If nobody has paid it yet, it is deleted. If there are payments, it is hidden from students and the payment records are kept.", "Yes, remove"))) return;
      try { const r = await post("fee_remove", { id: f.id }); toast(r.archived ? "Fee hidden. Its payment records are kept." : "Fee deleted."); load(); } catch (e) { toast(e.message, true); }
    }

    // ---------- Who owes a fee ----------
    async function openStudents(f) {
      viewing = f; $("stuHead").textContent = f.title; $("stuFind").value = ""; $("stuShow").value = "";
      $("stuList").replaceChildren(el("p", "note", "Loading...")); $("dlgStu").showModal();
      try { const r = await get("fee_students", { id: f.id }); STU = r.students; viewing = { ...f, ...r.fee, mine: f.mine }; students(); }
      catch (e) { $("stuList").replaceChildren(el("p", "note", e.message)); }
    }
    function status(s) {
      const amount = Number(viewing.amount), bal = r2(amount - s.paid), d = daysTo(viewing.due_date);
      return bal <= 0 ? "paid" : d !== null && d < 0 ? "overdue" : s.paid > 0 ? "partial" : "unpaid";
    }
    const LABEL = { paid: "Paid", partial: "Partly paid", unpaid: "Not paid", overdue: "Overdue" };
    function students() {
      const q = $("stuFind").value.trim().toLowerCase(), want = $("stuShow").value, amount = Number(viewing.amount);
      const rows = STU.filter(s => (!q || (s.name + " " + (s.school_id || "")).toLowerCase().includes(q)) && (!want || (want === "paid") === (status(s) === "paid")));
      const paidAll = STU.filter(s => status(s) === "paid").length;
      $("stuSum").textContent = `${paidAll} of ${STU.length} fully paid · ${money(STU.reduce((t, s) => t + s.paid, 0))} collected · ${money(STU.reduce((t, s) => t + Math.max(0, amount - s.paid), 0))} still owed`;
      if (!rows.length) { $("stuList").replaceChildren(el("p", "empty", STU.length ? "No student matches." : "Nobody owes this fee yet.")); return; }
      const t = el("table"), hr = el("tr");
      [["Student"], ["Paid", "num"], ["Balance", "num"], ["Status"], [""]].forEach(([h, c]) => hr.appendChild(el("th", c, h)));
      t.appendChild(hr);
      rows.forEach(s => {
        const tr = el("tr"), who = el("td"), st = status(s), bal = r2(Math.max(0, amount - s.paid));
        who.append(el("b", null, s.name), el("span", "sub", [s.school_id, s.grade_level].filter(Boolean).join(" · ")));
        tr.append(who, el("td", "num", money(s.paid)), el("td", "num", money(bal)));
        const tg = el("td"); tg.appendChild(el("span", "tag t-" + st, LABEL[st])); if (s.pending > 0) tg.appendChild(el("span", "sub", money(s.pending) + " being checked")); tr.appendChild(tg);
        const act = el("td");
        if (viewing.mine && r2(bal - s.pending) > 0) { const b = el("button", "ghost", "Record cash"); b.type = "button"; b.onclick = () => openCash(s, r2(bal - s.pending)); act.appendChild(b); }
        tr.appendChild(act); t.appendChild(tr);
      });
      $("stuList").replaceChildren(t);
    }
    $("stuFind").addEventListener("input", students);
    $("stuShow").addEventListener("change", students);
    $("stuClose").onclick = () => $("dlgStu").close();
    $("stuCsv").onclick = () => {
      const esc = v => `"${String(v ?? "").replace(/"/g, '""')}"`, amount = Number(viewing.amount);
      const lines = [["Student", "ID number", "Grade level", "Fee", "Amount", "Paid", "Being checked", "Balance", "Status"].map(esc).join(",")]
        .concat(STU.map(s => [s.name, s.school_id, s.grade_level, viewing.title, amount.toFixed(2), s.paid.toFixed(2), s.pending.toFixed(2), Math.max(0, amount - s.paid).toFixed(2), LABEL[status(s)]].map(esc).join(",")));
      const a = el("a"); a.href = URL.createObjectURL(new Blob(["﻿" + lines.join("\r\n")], { type: "text/csv" }));
      a.download = viewing.title.replace(/[^\w -]+/g, "") + " payments.csv"; document.body.appendChild(a); a.click(); a.remove();
    };

    // ---------- Record cash ----------
    let cashFor = null;
    function openCash(s, max) {
      cashFor = s; $("cashWho").textContent = `${s.name} · ${viewing.title} · ${money(max)} left`;
      $("cAmt").value = max.toFixed(2); $("cAmt").max = max; $("cAmt").readOnly = Number(viewing.allow_partial) !== 1; $("cRef").value = "";
      $("dlgCash").showModal(); $("cAmt").focus();
    }
    $("cashCancel").onclick = () => $("dlgCash").close();
    $("cashForm").addEventListener("submit", async e => {
      e.preventDefault();
      try {
        const r = await post("pay_record", { fee_id: viewing.id, student_id: cashFor.id, amount: Number($("cAmt").value), reference: $("cRef").value.trim() });
        $("dlgCash").close(); toast("Recorded. Receipt " + r.receipt_no + ".");
        await load(); openStudents(D.fees.find(f => f.id == viewing.id) || viewing);
      } catch (err) { toast(err.message, true); }
    });

    async function load() {
      try {
        [D, { payments: PAY }] = await Promise.all([get("fee_list"), get("pay_list")]);
        stats(); tabs(); show();
      } catch (err) {
        $("tabFees").replaceChildren(el("p", "empty", err.message + (/Server error/.test(err.message) ? " Has update9.sql been imported?" : "")));
      }
    }
    protect("teacher", user => { $("av").textContent = initials(user.name); load(); });
  </script>
  <script src="logout-confirm.js"></script>
  <script type="module" src="nav.js"></script>
</body>
</html>
