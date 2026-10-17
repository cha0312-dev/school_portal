<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>School Portal - Sign in</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="auth.css">
  <link rel="stylesheet" href="mobile.css">
</head>
<body>
  <div class="container">
    <form class="form" id="inForm">
      <h1>Sign in to School Portal</h1>
      <p class="sub">Use your portal account</p>
      <input type="email" id="email" placeholder="Email" required autocomplete="username">
      <input type="password" id="password" placeholder="Password" required autocomplete="current-password">
      <a href="forgot.php" class="forgot" id="forgot">Forgot your password?</a>
      <button class="btn" type="submit">Sign in</button>
      <p class="msg" id="msg"></p>
      <a href="register.php" class="swap">New here? Create an account</a>
    </form>

    <div class="overlay">
      <div class="ov">
        <h1>Hello, friend!</h1>
        <p>Create an account to see your grades or record your students' scores.</p>
        <a class="ghost" href="register.php">Sign up</a>
      </div>
    </div>
  </div>

  <script>
    // Plain script so a clear message still shows if the page is opened the wrong way
    document.querySelectorAll("form").forEach(f => f.addEventListener("submit", e => e.preventDefault()));
    if (location.protocol === "file:") {
      const m = document.getElementById("msg");
      m.textContent = "Open this site at http://localhost/school-portal/login.php with XAMPP running, not by double-clicking the file.";
      m.className = "msg";
    }
  </script>
  <script src="auth-transition.js"></script>
  <script type="module">
    import { post } from "./guard.js";
    const $ = id => document.getElementById(id);
    const msg = $("msg");
    const pages = { admin: "admin.php", teacher: "teacher.php", student: "student.php" };
    const say = (text, ok = false) => { msg.textContent = text; msg.className = "msg" + (ok ? " ok" : ""); };

    $("inForm").addEventListener("submit", async e => {
      e.preventDefault();
      say("Signing in...", true);
      try {
        const { role } = await post("login", { email: $("email").value.trim(), password: $("password").value });
        location.href = pages[role] || "login.php";
      } catch (err) { say(err.message); }
    });

  </script>
</body>
</html>