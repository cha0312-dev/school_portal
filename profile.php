<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My profile</title>
  <link rel="stylesheet" href="portal.css">
  <style>
    /* Styles for this page live here so it looks right even with an older portal.css */
    [hidden] { display: none !important; }
    .hero { display: flex; align-items: center; gap: 22px; margin-bottom: 24px; padding: 26px; border-top: 5px solid var(--gold, #e0b12a); }
    .big-av { display: grid; place-items: center; flex: none; width: 92px; height: 92px; border-radius: 50%; font-size: 2rem; font-weight: 700; color: #4a0812;
      background: linear-gradient(135deg, #f6d56a, #e0b12a); box-shadow: 0 10px 22px -8px rgba(122,16,32,.55), inset 0 0 0 4px rgba(255,255,255,.4); }
    .hero h2 { margin: 0 0 8px; font-size: 1.6rem; line-height: 1.15; }
    .tags { display: flex; flex-wrap: wrap; gap: 8px; }
    .tag { padding: 4px 14px; font-size: .9rem; font-weight: 700; color: var(--ink); border-radius: 99px; box-shadow: inset 2px 2px 4px var(--lo), inset -2px -2px 4px var(--hi); }
    .tag.role { color: #fff; background: var(--brand, #7a1020); box-shadow: none; }
    .facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 24px; }
    .fact { padding: 16px 18px; border-radius: 16px; box-shadow: 6px 6px 14px var(--lo), -6px -6px 14px var(--hi); }
    .fact span { display: block; font-size: .85rem; color: var(--muted); }
    .fact b { display: block; margin-top: 2px; overflow-wrap: anywhere; }
    .pair { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .notice { margin: 0 0 16px; padding: 12px 16px; border-radius: 12px; font-size: .92rem; background: rgba(224,177,42,.25); }
    .two { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; align-items: start; }
    .card h2 { margin: 0 0 4px; font-size: 1.25rem; }
    .card .sub { margin: 0 0 16px; color: var(--muted); font-size: .92rem; }
    .lab { display: block; margin: 0 0 6px; font-size: .9rem; font-weight: 700; color: var(--muted); }
    .field { width: 100%; padding: 12px 16px; margin-bottom: 14px; font: inherit; color: var(--ink); background: var(--bg); border: 0; border-radius: 10px;
      box-shadow: inset 3px 3px 7px var(--lo), inset -3px -3px 7px var(--hi); }
    .field:disabled { opacity: .65; cursor: not-allowed; }
    .actions { display: flex; justify-content: flex-end; gap: 10px; }
    .meter { height: 8px; margin: -4px 0 6px; border-radius: 99px; overflow: hidden; box-shadow: inset 2px 2px 4px var(--lo), inset -2px -2px 4px var(--hi); }
    .meter i { display: block; height: 100%; width: 0; border-radius: 99px; transition: width .25s, background .25s; }
    .hint { margin: 0 0 14px; min-height: 22px; font-size: .88rem; color: var(--muted); }
    .showpw { display: flex; align-items: center; gap: 8px; margin: 0 0 14px; font-size: .92rem; color: var(--muted); cursor: pointer; }
    .showpw input { width: 18px; height: 18px; accent-color: var(--brand, #7a1020); }
    .classes { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 12px; margin-top: 8px; }
    .cl { padding: 14px 16px; border-radius: 14px; box-shadow: inset 2px 2px 5px var(--lo), inset -2px -2px 5px var(--hi); }
    .cl b { display: block; } .cl span { font-size: .9rem; color: var(--muted); }
    .none { margin: 8px 0 0; color: var(--muted); }
    @media (max-width: 800px) { .two { grid-template-columns: 1fr; } }
    @media (max-width: 600px) { .pair { grid-template-columns: 1fr; } }
    @media (max-width: 600px) { .hero { flex-direction: column; text-align: center; padding: 22px 16px; } .tags { justify-content: center; } }
  </style>
  <link rel="stylesheet" href="mobile.css">
</head>
<body>
  <div class="wrap">
    <header class="topbar noprint">
      <h1>My profile</h1>
      <div class="avatar" id="av"></div>
      <a class="navlink" id="home" href="login.php">Grades</a>
      <button class="ghost" id="logoutBtn">Log out</button>
    </header>

    <section class="panel hero">
      <div class="big-av" id="heroAv"></div>
      <div>
        <h2 id="heroName">Loading...</h2>
        <div class="tags" id="heroTags"></div>
      </div>
    </section>

    <section class="facts" id="facts"></section>

    <div class="two">
      <section class="panel card">
        <h2>Edit my details</h2>
        <p class="sub">Fill in what is missing and press Save. Your email can only be changed by the administrator.</p>
        <p class="notice" id="extNote" hidden>To save the birthdate, address and other new details, import <b>update7.sql</b> in phpMyAdmin once.</p>
        <form id="formProfile">
          <label class="lab" for="pName">Full name</label>
          <input class="field" id="pName" maxlength="100" required autocomplete="name">
          <div class="pair">
            <div><label class="lab" for="pEmail">Email</label><input class="field" id="pEmail" disabled></div>
            <div><label class="lab" for="pId">ID number</label><input class="field" id="pId" maxlength="30" autocomplete="off"></div>
          </div>
          <p class="hint" id="idHint"></p>
          <div class="pair">
            <div><label class="lab" for="pPhone">Contact number</label><input class="field" id="pPhone" type="tel" maxlength="20" placeholder="Optional" autocomplete="tel"></div>
            <div><label class="lab" for="pBirth">Birthdate</label><input class="field" id="pBirth" type="date"></div>
          </div>
          <label class="lab" for="pAddress">Address</label>
          <input class="field" id="pAddress" maxlength="200" placeholder="Street, town or city, province" autocomplete="street-address">
          <div id="studentOnly" hidden>
            <label class="lab" for="pGrade">Grade level</label>
            <input class="field" id="pGrade" maxlength="20" placeholder="For example Grade 8">
            <div class="pair">
              <div><label class="lab" for="pGName">Parent or guardian name</label><input class="field" id="pGName" maxlength="100" placeholder="Optional"></div>
              <div><label class="lab" for="pGPhone">Guardian contact number</label><input class="field" id="pGPhone" type="tel" maxlength="20" placeholder="Optional"></div>
            </div>
          </div>
          <div id="teacherOnly" hidden>
            <label class="lab" for="pJob">Position or department</label>
            <input class="field" id="pJob" maxlength="80" placeholder="For example Mathematics teacher">
          </div>
          <div class="actions"><button type="submit" id="saveBtn">Save changes</button></div>
        </form>
      </section>

      <section class="panel card">
        <h2>Change my password</h2>
        <p class="sub">Use at least 6 characters. A longer password with numbers and symbols is stronger.</p>
        <form id="formPw">
          <label class="lab" for="pwCur">Current password</label>
          <input class="field" id="pwCur" type="password" required autocomplete="current-password">
          <label class="lab" for="pwNew">New password</label>
          <input class="field" id="pwNew" type="password" required minlength="6" autocomplete="new-password">
          <div id="meterBox" hidden><div class="meter" aria-hidden="true"><i id="meter"></i></div>
          <p class="hint" id="hint"></p></div>
          <label class="lab" for="pwNew2">Confirm new password</label>
          <input class="field" id="pwNew2" type="password" required autocomplete="new-password">
          <label class="showpw"><input type="checkbox" id="showPw"> Show passwords</label>
          <div class="actions"><button type="submit">Change password</button></div>
        </form>
      </section>
    </div>

    <section class="panel card">
      <h2 id="classTitle">My classes</h2>
      <div class="classes" id="classList"></div>
      <p class="none" id="noClasses" hidden></p>
    </section>
  </div>
  <div class="toast" id="toast" role="status"></div>

  <script type="module">
    import { get, post, logout } from "./guard.js";
    const $ = id => document.getElementById(id);
    const el = (tag, cls, text) => { const e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; };
    const initials = n => n.split(/\s+/).map(w => w[0]).slice(0, 2).join("").toUpperCase();
    const friendly = e => e.message === "Server error." ? "The server could not do that. Check that you imported the latest .sql files in phpMyAdmin." : e.message;
    let toastTimer;
    function toast(text, bad = false) {
      const t = $("toast"); t.textContent = text; t.className = "toast show" + (bad ? " bad" : "");
      clearTimeout(toastTimer); toastTimer = setTimeout(() => t.className = "toast", 3200);
    }
    $("logoutBtn").addEventListener("click", logout);

    let user;
    try { ({ user } = await get("me")); } catch (e) {}
    if (!user || !["teacher", "student"].includes(user.role)) { location.href = "login.php"; throw new Error("not signed in"); }
    const isStudent = user.role === "student";
    $("av").textContent = initials(user.name);
    $("home").href = isStudent ? "student.php" : "teacher.php";
    $("studentOnly").hidden = !isStudent; $("teacherOnly").hidden = isStudent;
    $("pBirth").max = new Date().toLocaleDateString("en-CA");

    let hasId = false;
    function show(p, extended) {
      $("heroAv").textContent = initials(p.name); $("heroName").textContent = p.name;
      const tags = $("heroTags"); tags.innerHTML = "";
      tags.append(el("span", "tag role", isStudent ? "Student" : "Teacher"));
      if (p.school_id) tags.append(el("span", "tag", (isStudent ? "ID " : "Employee ID ") + p.school_id));
      if (isStudent && p.grade_level) tags.append(el("span", "tag", p.grade_level));
      if (!isStudent && p.job_title) tags.append(el("span", "tag", p.job_title));
      const fmt = d => d ? new Date(d + "T00:00").toLocaleDateString(undefined, { year: "numeric", month: "long", day: "numeric" }) : "Not added";
      const since = p.created_at ? new Date(p.created_at.replace(" ", "T")).toLocaleDateString(undefined, { year: "numeric", month: "long" }) : "-";
      const rows = [["Email", p.email], ["Contact number", p.phone || "Not added"]];
      if (extended) {
        rows.push(["Birthdate", fmt(p.birthdate)], ["Address", p.address || "Not added"]);
        if (isStudent) rows.push(["Parent or guardian", [p.guardian_name, p.guardian_phone].filter(Boolean).join(" - ") || "Not added"]);
      }
      rows.push(["Member since", since]);
      const facts = $("facts"); facts.innerHTML = "";
      rows.forEach(([l, v]) => { const f = el("div", "fact"); f.append(el("span", null, l), el("b", null, v)); facts.appendChild(f); });

      $("pName").value = p.name; $("pEmail").value = p.email; $("pPhone").value = p.phone || ""; $("pGrade").value = p.grade_level || "";
      hasId = !!p.school_id;
      $("pId").value = p.school_id || ""; $("pId").disabled = hasId;
      $("pId").placeholder = isStudent ? "Student ID number" : "Employee ID number";
      $("idHint").textContent = hasId ? "Only the administrator can change your ID number." : "You can add your ID number once. After that only the administrator can change it.";
      $("extNote").hidden = !!extended;
      ["pBirth", "pAddress", "pGName", "pGPhone", "pJob"].forEach(id => $(id).disabled = !extended);
      if (extended) {
        $("pBirth").value = p.birthdate || ""; $("pAddress").value = p.address || "";
        $("pGName").value = p.guardian_name || ""; $("pGPhone").value = p.guardian_phone || ""; $("pJob").value = p.job_title || "";
      }
    }

    try { const r = await get("profile"); show(r.profile, r.extended); } catch (err) { toast(friendly(err), true); }

    // ---- classes ----
    $("classTitle").textContent = isStudent ? "My classes" : "Classes I teach";
    try {
      const list = isStudent ? (await get("my_status")).classes.map(c => [c.class_name, c.subject + " \u00B7 " + c.teacher])
                             : (await get("classes")).classes.map(c => [c.name, c.subject]);
      list.forEach(([a, b]) => { const c = el("div", "cl"); c.append(el("b", null, a), el("span", null, b)); $("classList").appendChild(c); });
      if (!list.length) { $("noClasses").hidden = false; $("noClasses").textContent = isStudent ? "You are not in any class yet. Your teacher adds you to one." : "You have not created a class yet. Create one on the grades page."; }
    } catch (err) { /* older server file: just leave this card empty */ }

    // ---- edit details ----
    $("formProfile").addEventListener("submit", async e => {
      e.preventDefault();
      const phone = $("pPhone").value.trim(), gphone = $("pGPhone").value.trim(), sid = $("pId").value.trim(), ph = /^[0-9+() -]{7,20}$/;
      if (!hasId && sid && !/^[A-Za-z0-9-]{3,30}$/.test(sid)) return toast("Enter a valid ID number: letters, numbers and dashes, 3 to 30 characters.", true);
      if (phone && !ph.test(phone)) return toast("Enter a valid contact number.", true);
      if (gphone && !ph.test(gphone)) return toast("Enter a valid guardian contact number.", true);
      const btn = $("saveBtn"); btn.disabled = true;
      try {
        await post("profile_update", {
          name: $("pName").value.trim(), phone, school_id: hasId ? "" : sid, grade_level: isStudent ? $("pGrade").value.trim() : "",
          birthdate: $("pBirth").value, address: $("pAddress").value.trim(),
          guardian_name: isStudent ? $("pGName").value.trim() : "", guardian_phone: isStudent ? gphone : "", job_title: isStudent ? "" : $("pJob").value.trim()
        });
        toast("Your details were saved.");
        setTimeout(() => location.reload(), 900);                 // so the sidebar shows the new name too
      } catch (err) { toast(friendly(err), true); btn.disabled = false; }
    });

    // ---- password ----
    const meter = $("meter"), hint = $("hint");
    $("pwNew").addEventListener("input", () => {
      const v = $("pwNew").value;
      $("meterBox").hidden = !v;
      const score = (v.length >= 6) + (v.length >= 10) + (/[a-z]/.test(v) && /[A-Z]/.test(v)) + /\d/.test(v) + /[^A-Za-z0-9]/.test(v);
      const level = !v ? 0 : score <= 1 ? 1 : score <= 3 ? 2 : 3;
      meter.style.width = [0, 33, 66, 100][level] + "%"; meter.style.background = ["transparent", "#c62828", "#e0b12a", "#1a7f37"][level];
      hint.textContent = ["", "Weak. Try a longer password.", "Fair. Add numbers or symbols to make it stronger.", "Strong."][level];
    });
    $("showPw").addEventListener("change", e => ["pwCur", "pwNew", "pwNew2"].forEach(id => $(id).type = e.target.checked ? "text" : "password"));
    $("formPw").addEventListener("submit", async e => {
      e.preventDefault();
      if ($("pwNew").value.length < 6) return toast("The new password needs at least 6 characters.", true);
      if ($("pwNew").value !== $("pwNew2").value) return toast("The two new passwords do not match.", true);
      try {
        await post("change_password", { current: $("pwCur").value, new: $("pwNew").value });
        $("formPw").reset(); $("meterBox").hidden = true; meter.style.width = "0"; hint.textContent = ""; toast("Password changed.");
      } catch (err) { toast(friendly(err), true); }
    });
  </script>
  <script type="module" src="badge.js"></script>
  <script src="logout-confirm.js"></script>
  <script type="module" src="nav.js"></script>
</body>
</html>