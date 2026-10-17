<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="referrer" content="no-referrer"><!-- keeps the secret in a reset link from being passed on to other sites -->
  <title>School Portal - Forgot password</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="auth.css">
  <style>
    [hidden] { display: none !important; }
    .ic { display: grid; place-items: center; width: 64px; height: 64px; margin: 0 auto 14px; border-radius: 50%; color: #4a0812;
      background: linear-gradient(135deg, #f6d56a, #e0b12a); box-shadow: 0 8px 18px -6px rgba(122,16,32,.5), inset 0 0 0 3px rgba(255,255,255,.4); }
    .ic.ok { color: #fff; background: linear-gradient(135deg, #2f9e55, #1a7f37); } .ic.warn { color: #fff; background: linear-gradient(135deg, #d9534f, #b3261e); }
    .view { width: 100%; max-width: 360px; }
    .view p { margin: 0 0 14px; }
    .crumb { display: none; margin: 0 0 6px; font-size: .85rem; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
    .back { display: inline-block; margin-top: 18px; }
    .meter { height: 8px; margin: -2px 0 6px; border-radius: 99px; overflow: hidden; box-shadow: inset 2px 2px 4px var(--dark), inset -2px -2px 4px var(--light); }
    .meter i { display: block; height: 100%; width: 0; border-radius: 99px; transition: width .25s, background .25s; }
    .hint { margin: 0 0 12px !important; min-height: 20px; font-size: .88rem; color: var(--muted); }
    .showpw { display: flex; align-items: center; gap: 8px; margin: 0 0 8px; font-size: .92rem; color: var(--muted); cursor: pointer; }
    .showpw input { width: 18px; height: 18px; margin: 0; padding: 0; box-shadow: none; accent-color: var(--navy); }
    .dev { margin: 0 0 16px; padding: 12px 14px; border-radius: 12px; text-align: left; font-size: .9rem; background: rgba(224,177,42,.25); }
    .dev a { font-weight: 700; word-break: break-all; }
    .btn[disabled] { opacity: .55; cursor: not-allowed; }
    .spin { width: 34px; height: 34px; margin: 0 auto 14px; border: 4px solid var(--dark); border-top-color: var(--navy); border-radius: 50%; animation: spin .9s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { .spin { animation: none; } }
    .steps { list-style: none; margin: 22px 0 0; padding: 0; display: grid; gap: 12px; text-align: left; }
    .steps li { display: flex; align-items: center; gap: 12px; opacity: .6; transition: opacity .2s; }
    .steps b { display: grid; place-items: center; flex: none; width: 30px; height: 30px; border-radius: 50%; background: rgba(255,255,255,.2); }
    .steps li.on { opacity: 1; font-weight: 700; } .steps li.on b { color: #4a0812; background: #e0b12a; }
    .steps li.done { opacity: .9; } .steps li.done b { color: #8c1426; background: #fff; }
    .lock { display: grid; place-items: center; width: 72px; height: 72px; margin: 0 auto 16px; border-radius: 22px; background: rgba(255,255,255,.14); box-shadow: inset 0 0 0 2px rgba(255,255,255,.25); }
    @media (max-width: 720px) { .crumb { display: block; } .view { max-width: none; } }
  </style>
  <link rel="stylesheet" href="mobile.css">
</head>
<body>
  <div class="container">
    <div class="form">

      <div class="view" id="vCheck" hidden><div class="spin" aria-hidden="true"></div><p class="sub">Checking your link...</p></div>

      <div class="view" id="vRequest" hidden>
        <p class="crumb">Step 1 of 3</p>
        <div class="ic" aria-hidden="true"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3.5 7.5l8.5 6 8.5-6"/></svg></div>
        <h1>Forgot your password?</h1>
        <p class="sub">Enter the email you registered with. We will send you a link to choose a new password.</p>
        <form id="fReq">
          <input type="email" id="email" placeholder="Email" required autocomplete="email">
          <button class="btn" type="submit" id="reqBtn">Send reset link</button>
          <p class="msg" id="msgReq" role="status"></p>
        </form>
        <a class="back" href="login.php">&larr; Back to sign in</a>
      </div>

      <div class="view" id="vSent" hidden>
        <p class="crumb">Step 2 of 3</p>
        <div class="ic ok" aria-hidden="true"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></div>
        <h1>Check your email</h1>
        <p class="sub">If an account exists for <b id="sentTo"></b>, we sent a link to reset the password. It works for 30 minutes.</p>
        <div class="dev" id="devBox" hidden><b>Testing on this computer:</b> no email server is needed here, so the link is shown for you.<br><a id="devLink" href="#">Open the reset link</a></div>
        <p class="hint">Cannot find it? Look in your spam folder.</p>
        <button class="btn" type="button" id="resend">Resend link</button>
        <p class="msg" id="msgSent" role="status"></p>
        <a class="back" href="#" id="otherEmail">Use a different email</a>
      </div>

      <div class="view" id="vReset" hidden>
        <p class="crumb">Step 3 of 3</p>
        <div class="ic" aria-hidden="true"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg></div>
        <h1>Create a new password</h1>
        <p class="sub">Use at least 6 characters.</p>
        <form id="fReset">
          <input type="password" id="pw1" placeholder="New password" required minlength="6" autocomplete="new-password">
          <div id="meterBox" hidden><div class="meter" aria-hidden="true"><i id="meter"></i></div><p class="hint" id="hint"></p></div>
          <input type="password" id="pw2" placeholder="Confirm new password" required autocomplete="new-password">
          <label class="showpw"><input type="checkbox" id="showPw"> Show passwords</label>
          <button class="btn" type="submit" id="resetBtn">Reset password</button>
          <p class="msg" id="msgReset" role="status"></p>
        </form>
      </div>

      <div class="view" id="vDone" hidden>
        <p class="crumb">All done</p>
        <div class="ic ok" aria-hidden="true"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></div>
        <h1>Password changed</h1>
        <p class="sub">You can now sign in with your new password.</p>
        <a class="btn" href="login.php">Go to sign in</a>
      </div>

      <div class="view" id="vBad" hidden>
        <div class="ic warn" aria-hidden="true"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5M12 17h.01"/><circle cx="12" cy="12" r="9"/></svg></div>
        <h1>This link has expired</h1>
        <p class="sub">Reset links work once and only for 30 minutes. Please ask for a new one.</p>
        <a class="btn" href="forgot.php">Get a new link</a>
        <br><a class="back" href="login.php">&larr; Back to sign in</a>
      </div>
    </div>

    <div class="overlay">
      <div class="ov">
        <div class="lock" aria-hidden="true"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg></div>
        <h1>Locked out?</h1>
        <p>It happens to everyone. You will be back in within minutes.</p>
        <ol class="steps" id="steps">
          <li data-s="1"><b>1</b><span>Enter your email</span></li>
          <li data-s="2"><b>2</b><span>Open the link we send you</span></li>
          <li data-s="3"><b>3</b><span>Choose a new password</span></li>
        </ol>
      </div>
    </div>
  </div>
  <div class="toast" id="toast" role="status"></div>

  <script>
    // Plain script so a clear message still shows if the page is opened the wrong way
    document.querySelectorAll("form").forEach(f => f.addEventListener("submit", e => e.preventDefault()));
    if (location.protocol === "file:") document.body.insertAdjacentHTML("afterbegin", '<p style="padding:12px;text-align:center;color:#b3261e">Open this site at http://localhost/school-portal/forgot.php with XAMPP running, not by double-clicking the file.</p>');
  </script>
  <script type="module">
    import { get, post } from "./guard.js";
    const $ = id => document.getElementById(id);
    const say = (id, text, ok = false) => { $(id).textContent = text; $(id).className = "msg" + (ok ? " ok" : ""); };
    const friendly = e => e.message === "Server error." ? "The server could not do that. Check that you imported update8.sql in phpMyAdmin." : e.message;
    const token = new URLSearchParams(location.search).get("token");

    const VIEWS = ["vCheck", "vRequest", "vSent", "vReset", "vDone", "vBad"];
    const STEP = { vCheck: 1, vRequest: 1, vSent: 2, vReset: 3, vDone: 4, vBad: 1 };
    function view(name) {
      VIEWS.forEach(v => $(v).hidden = v !== name);
      const s = STEP[name];
      document.querySelectorAll("#steps li").forEach(li => { const n = Number(li.dataset.s); li.className = n < s ? "done" : n === s ? "on" : ""; });
    }

    // ---- step 1: ask for the link ----
    let lastEmail = "", timer;
    function countdown(seconds) {
      clearInterval(timer); const b = $("resend"); let left = seconds; b.disabled = true;
      const tick = () => { if (left > 0) { b.textContent = `Resend link (${left}s)`; left--; } else { b.textContent = "Resend link"; b.disabled = false; clearInterval(timer); } };
      tick(); timer = setInterval(tick, 1000);
    }
    async function sendLink(email) {
      const r = await post("reset_request", { email });
      lastEmail = email; $("sentTo").textContent = email;
      $("devBox").hidden = !r.dev_link;
      if (r.dev_link) $("devLink").href = r.dev_link;
      say("msgSent", ""); view("vSent"); countdown(30);
    }
    $("fReq").addEventListener("submit", async e => {
      e.preventDefault(); const btn = $("reqBtn"); btn.disabled = true; say("msgReq", "Sending...", true);
      try { await sendLink($("email").value.trim()); say("msgReq", ""); } catch (err) { say("msgReq", friendly(err)); }
      btn.disabled = false;
    });
    $("resend").addEventListener("click", async () => {
      try { await sendLink(lastEmail); say("msgSent", "We sent the link again.", true); } catch (err) { say("msgSent", friendly(err)); }
    });
    $("otherEmail").addEventListener("click", e => { e.preventDefault(); clearInterval(timer); view("vRequest"); $("email").focus(); });

    // ---- step 3: choose the new password ----
    $("pw1").addEventListener("input", () => {
      const v = $("pw1").value; $("meterBox").hidden = !v;
      const score = (v.length >= 6) + (v.length >= 10) + (/[a-z]/.test(v) && /[A-Z]/.test(v)) + /\d/.test(v) + /[^A-Za-z0-9]/.test(v);
      const level = !v ? 0 : score <= 1 ? 1 : score <= 3 ? 2 : 3;
      $("meter").style.width = [0, 33, 66, 100][level] + "%"; $("meter").style.background = ["transparent", "#c62828", "#e0b12a", "#1a7f37"][level];
      $("hint").textContent = ["", "Weak. Try a longer password.", "Fair. Add numbers or symbols to make it stronger.", "Strong."][level];
    });
    $("showPw").addEventListener("change", e => ["pw1", "pw2"].forEach(id => $(id).type = e.target.checked ? "text" : "password"));
    $("fReset").addEventListener("submit", async e => {
      e.preventDefault();
      if ($("pw1").value.length < 6) return say("msgReset", "The new password needs at least 6 characters.");
      if ($("pw1").value !== $("pw2").value) return say("msgReset", "The two passwords do not match.");
      const btn = $("resetBtn"); btn.disabled = true; say("msgReset", "Saving...", true);
      try { await post("reset_apply", { token, password: $("pw1").value }); history.replaceState(null, "", "forgot.php"); view("vDone"); }
      catch (err) { say("msgReset", friendly(err)); }
      btn.disabled = false;
    });

    // ---- which step do we start on? ----
    if (token) {
      view("vCheck");
      try { const r = await get("reset_check", { token }); view(r.valid ? "vReset" : "vBad"); if (r.valid) $("pw1").focus(); }
      catch (err) { view("vBad"); }
    } else { view("vRequest"); $("email").focus(); }
  </script>
</body>
</html>