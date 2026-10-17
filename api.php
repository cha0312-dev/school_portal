<?php
// School Portal API: one file, JSON in and JSON out.
ini_set('display_errors', '0');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
header('Content-Type: application/json; charset=utf-8');

// ---- SETTINGS (these are the XAMPP defaults) ----
const DB_HOST = 'localhost', DB_NAME = 'school_portal', DB_USER = 'root', DB_PASS = '';
const WEIGHTS = ['quizzes' => 30, 'activities' => 20, 'exam' => 50];   // must add up to 100
const SUBJECTS = [   // add, remove or rename subjects here (keep the quotes and commas)
  'Mathematics', 'Science', 'English', 'Filipino',
  'Araling Panlipunan', 'MAPEH', 'TLE', 'Values Education',
];
const SHOW_ONLY_LOCKED = true;
// Password reset emails. When the site goes online, set SITE_URL to its address (no slash at the end), for example
// 'https://school.example.com/school-portal', and make sure your hosting can send mail.
const SITE_URL = '';
const MAIL_FROM = 'no-reply@localhost';   // students see a quarter only after the teacher submits and locks it
// School fees (update9.sql). Students see these accounts on the Payments page: change them to the school's real ones.
const PAY_METHODS = [
  'gcash' => ['label' => 'GCash',              'account' => '0917 000 0000 (change this in api.php)',  'name' => 'School Portal Cashier'],
  'maya'  => ['label' => 'Maya',               'account' => '0918 000 0000 (change this in api.php)',  'name' => 'School Portal Cashier'],
  'bank'  => ['label' => 'Bank transfer',      'account' => 'Bank 0000-0000-0000 (change this in api.php)', 'name' => 'School Portal'],
  'cash'  => ['label' => 'Cash at the office', 'account' => "Cashier's office, Monday to Friday, 8 AM to 4 PM", 'name' => ''],
];
const ONLINE_PAYMENTS = false;   // turn on once a payment gateway is connected (see the 'pay_online_start' action)
// -------------------------------------------------

function out($data, $code = 200) { http_response_code($code); echo json_encode($data); exit; }
function fail($msg, $code = 400) { out(['error' => $msg], $code); }
function me() { return $_SESSION['user'] ?? null; }
function need($role) { $u = me(); if (!$u || $u['role'] !== $role) fail('Not allowed.', 403); return $u; }

function my_class($db, $id) {
  $u = need('teacher');
  $st = $db->prepare('SELECT id, subject FROM classes WHERE id = ? AND teacher_id = ?');
  $st->execute([$id, $u['id']]); $c = $st->fetch();
  if (!$c) fail('Class not found.', 404);
  return $c;
}
function is_locked($db, $cid, $q) {
  $st = $db->prepare('SELECT 1 FROM locks WHERE class_id = ? AND quarter = ?');
  $st->execute([$cid, $q]); return (bool)$st->fetch();
}
function audit($db, $action, $info) {
  $db->prepare('INSERT INTO audit_log (user_id, action, info) VALUES (?, ?, ?)')->execute([me()['id'], $action, $info]);
}

// ---- School fees ----
// The fees a student owes: whole school, their grade level, one of their classes, or a subject they take. Needs the student id 3 times.
const FEE_OWED = "f.active = 1 AND (f.applies_to = 'school'
  OR (f.applies_to = 'grade' AND f.grade_level = (SELECT grade_level FROM users WHERE id = ?))
  OR (f.applies_to = 'class' AND EXISTS (SELECT 1 FROM class_students cs WHERE cs.class_id = f.class_id AND cs.student_id = ?))
  OR (f.applies_to = 'subject' AND EXISTS (SELECT 1 FROM class_students cs JOIN classes c ON c.id = cs.class_id WHERE c.subject = f.subject AND cs.student_id = ?)))";
function owed_args($sid) { return [$sid, $sid, $sid]; }
function owed_fee($db, $fid, $sid) {
  $st = $db->prepare('SELECT f.* FROM fees f WHERE f.id = ? AND ' . FEE_OWED);
  $st->execute(array_merge([$fid], owed_args($sid))); $f = $st->fetch();
  if (!$f) fail('Fee not found.', 404);
  return $f;
}
function my_fee($db, $id) {   // teachers change only the fees they made
  $t = need('teacher');
  $st = $db->prepare('SELECT * FROM fees WHERE id = ? AND created_by = ? AND active = 1');
  $st->execute([$id, $t['id']]); $f = $st->fetch();
  if (!$f) fail('Fee not found, or another teacher made it.', 404);
  return $f;
}
function fee_students($db, $f) {   // everyone who owes this fee
  $base = "SELECT u.id, u.name, u.school_id, u.grade_level FROM users u WHERE u.role = 'student'";
  $by = [
    'grade'   => [' AND u.grade_level = ?', $f['grade_level']],
    'class'   => [' AND u.id IN (SELECT student_id FROM class_students WHERE class_id = ?)', $f['class_id']],
    'subject' => [' AND u.id IN (SELECT cs.student_id FROM class_students cs JOIN classes c ON c.id = cs.class_id WHERE c.subject = ?)', $f['subject']],
  ][$f['applies_to']] ?? null;
  $st = $db->prepare($base . ($by ? $by[0] : '') . ' ORDER BY u.name');
  $st->execute($by ? [$by[1]] : []);
  return $st->fetchAll();
}
function money($v) {
  if (!is_numeric($v)) fail('Please enter an amount.');
  $a = round((float)$v, 2);
  if ($a <= 0 || $a > 9999999) fail('Please enter an amount above zero.');
  return $a;
}
function check_amount($db, $f, $sid, $amt) {   // payments still waiting to be checked count as paid here, so nobody pays twice
  $st = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE fee_id = ? AND student_id = ? AND status IN ('confirmed', 'pending')");
  $st->execute([$f['id'], $sid]);
  $left = round((float)$f['amount'] - (float)$st->fetchColumn(), 2);
  if ($left <= 0) fail('Nothing is left to pay on this fee (payments waiting to be checked count too).');
  if ($amt > $left + 0.001) fail('That is more than what is left to pay (' . number_format($left, 2) . ').');
  if (!$f['allow_partial'] && abs($amt - $left) > 0.001) fail('This fee has to be paid in full (' . number_format($left, 2) . ').');
}
function receipt_no($id) { return 'OR-' . date('Y') . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT); }

function valid_day($d) {
  $x = DateTime::createFromFormat('Y-m-d', (string)$d);
  if (!$x || $x->format('Y-m-d') !== $d) fail('Invalid date.');
  return $d;
}

function has_col($db, $col) {
  $st = $db->prepare("SHOW COLUMNS FROM users LIKE ?"); $st->execute([$col]);
  return (bool)$st->fetch();
}

function is_local() { return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true); }

// Philippine holidays that fall on the same date every year (plus National Heroes Day, the last Monday of August).
// Holidays that move each year (Holy Week, Eid'l Fitr, Eid'l Adha...) are proclaimed yearly: teachers mark those as "no class".
function holidays($year) {
  return [
    "$year-01-01" => ['name' => "New Year's Day", 'kind' => 'regular'],
    "$year-04-09" => ['name' => 'Araw ng Kagitingan (Day of Valor)', 'kind' => 'regular'],
    "$year-05-01" => ['name' => 'Labor Day', 'kind' => 'regular'],
    "$year-06-12" => ['name' => 'Independence Day', 'kind' => 'regular'],
    "$year-08-21" => ['name' => 'Ninoy Aquino Day', 'kind' => 'special'],
    date('Y-m-d', strtotime("last monday of august $year")) => ['name' => 'National Heroes Day', 'kind' => 'regular'],
    "$year-11-01" => ['name' => "All Saints' Day", 'kind' => 'special'],
    "$year-11-30" => ['name' => 'Bonifacio Day', 'kind' => 'regular'],
    "$year-12-08" => ['name' => 'Feast of the Immaculate Conception', 'kind' => 'special'],
    "$year-12-24" => ['name' => 'Christmas Eve', 'kind' => 'special'],
    "$year-12-25" => ['name' => 'Christmas Day', 'kind' => 'regular'],
    "$year-12-30" => ['name' => 'Rizal Day', 'kind' => 'regular'],
    "$year-12-31" => ['name' => 'Last Day of the Year', 'kind' => 'special'],
  ];
}

// grade = sum of (score / total x weight); null if anything is missing or invalid
function grade($s) {
  $t = 0;
  foreach (WEIGHTS as $c => $w) {
    $e = $s[$c][0] ?? null; $m = $s[$c][1] ?? null;
    if (!is_numeric($e) || !is_numeric($m) || $m <= 0 || $e < 0) return null;
    $t += ($e / $m) * $w;
  }
  return round($t, 2);
}

set_exception_handler(fn($e) => fail('Server error.', 500));
try {
  $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) { fail('Cannot reach the database. Is MySQL running in XAMPP?', 500); }

$in = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) fail('Bad request.');
  $in = json_decode(file_get_contents('php://input'), true) ?: [];
}

switch ($_GET['action'] ?? '') {

  case 'me':     out(['user' => me()]);
  case 'config': out(['weights' => WEIGHTS, 'subjects' => SUBJECTS]);

  case 'register':
    $name = trim($in['name'] ?? ''); $email = strtolower(trim($in['email'] ?? ''));
    $pass = $in['password'] ?? ''; $role = $in['role'] ?? 'student';
    if ($name === '' || mb_strlen($name) > 100) fail('Please enter your full name.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email address.');
    if (strlen($pass) < 6) fail('Password must be at least 6 characters.');
    if (!in_array($role, ['student', 'pending_teacher'], true)) fail('Invalid role.');
    $sid = trim($in['school_id'] ?? ''); $grade = trim($in['grade_level'] ?? ''); $phone = trim($in['phone'] ?? '');
    if (!preg_match('/^[A-Za-z0-9-]{3,30}$/', $sid)) fail('Please enter a valid ID number (letters, numbers and dashes, 3 to 30 characters).');
    if ($role === 'student' && ($grade === '' || mb_strlen($grade) > 20)) fail('Please enter your grade or year level.');
    if ($role !== 'student') $grade = '';
    if ($phone !== '' && !preg_match('/^[0-9+() -]{7,20}$/', $phone)) fail('Please enter a valid contact number.');
    $st = $db->prepare('SELECT id FROM users WHERE school_id = ?'); $st->execute([$sid]);
    if ($st->fetch()) fail('That ID number is already registered.');
    $st = $db->prepare('SELECT id FROM users WHERE email = ?'); $st->execute([$email]);
    if ($st->fetch()) fail('That email is already registered.');
    $db->prepare('INSERT INTO users (name, email, password_hash, role, school_id, grade_level, phone) VALUES (?, ?, ?, ?, ?, ?, ?)')
       ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $sid, $grade ?: null, $phone ?: null]);
    if ($role === 'student') {
      session_regenerate_id(true);
      $_SESSION['user'] = ['id' => (int)$db->lastInsertId(), 'name' => $name, 'role' => $role];
    }
    out(['role' => $role]);

  case 'login':
    $st = $db->prepare('SELECT id, name, role, password_hash FROM users WHERE email = ?');
    $st->execute([strtolower(trim($in['email'] ?? ''))]); $u = $st->fetch();
    if (!$u || !password_verify($in['password'] ?? '', $u['password_hash'])) fail('Wrong email or password.', 401);
    if ($u['role'] === 'pending_teacher') fail('Your teacher account is waiting for approval.', 403);
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => (int)$u['id'], 'name' => $u['name'], 'role' => $u['role']];
    out(['role' => $u['role']]);

  case 'logout':
    $_SESSION = []; session_destroy(); out(['ok' => true]);

  case 'classes':    // teacher: my classes
    $t = need('teacher');
    $st = $db->prepare('SELECT id, name, subject FROM classes WHERE teacher_id = ? ORDER BY name');
    $st->execute([$t['id']]);
    out(['classes' => $st->fetchAll()]);

  case 'class_create':
    $t = need('teacher');
    $name = trim($in['name'] ?? ''); $subject = $in['subject'] ?? '';
    if ($name === '' || mb_strlen($name) > 80) fail('Please enter a class name.');
    if (!in_array($subject, SUBJECTS, true)) fail('Invalid subject.');
    $db->prepare('INSERT INTO classes (teacher_id, name, subject) VALUES (?, ?, ?)')->execute([$t['id'], $name, $subject]);
    out(['id' => (int)$db->lastInsertId()]);

  case 'roster':     // all students, marked if they are in this class
    $c = my_class($db, (int)($_GET['class_id'] ?? 0));
    $st = $db->prepare("SELECT u.id, u.name, cs.student_id IS NOT NULL AS member FROM users u
      LEFT JOIN class_students cs ON cs.student_id = u.id AND cs.class_id = ?
      WHERE u.role = 'student' ORDER BY u.name");
    $st->execute([$c['id']]);
    out(['students' => $st->fetchAll()]);

  case 'roster_save':
    $c = my_class($db, (int)($in['class_id'] ?? 0));
    $ids = array_unique(array_map('intval', $in['student_ids'] ?? []));
    $db->beginTransaction();
    $db->prepare('DELETE FROM class_students WHERE class_id = ?')->execute([$c['id']]);
    $add = $db->prepare("INSERT INTO class_students (class_id, student_id) SELECT ?, id FROM users WHERE id = ? AND role = 'student'");
    foreach ($ids as $sid) $add->execute([$c['id'], $sid]);
    $db->commit();
    audit($db, 'roster', "class {$c['id']}: " . count($ids) . ' students');
    out(['ok' => true]);

  case 'class_scores':   // students of the class with saved scores, and whether the quarter is locked
    $c = my_class($db, (int)($_GET['class_id'] ?? 0)); $q = (int)($_GET['quarter'] ?? 0);
    $st = $db->prepare('SELECT u.id, u.name, s.details FROM class_students cs
      JOIN users u ON u.id = cs.student_id
      LEFT JOIN scores s ON s.student_id = u.id AND s.subject = ? AND s.quarter = ?
      WHERE cs.class_id = ? ORDER BY u.name');
    $st->execute([$c['subject'], $q, $c['id']]);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) $r['details'] = $r['details'] ? json_decode($r['details'], true) : null;
    out(['students' => $rows, 'locked' => is_locked($db, $c['id'], $q)]);

  case 'save':       // the server recomputes every grade
    $c = my_class($db, (int)($in['class_id'] ?? 0)); $q = (int)($in['quarter'] ?? 0);
    if ($q < 1 || $q > 4) fail('Invalid quarter.');
    if (is_locked($db, $c['id'], $q)) fail('This quarter is locked.', 423);
    $member = $db->prepare('SELECT 1 FROM class_students WHERE class_id = ? AND student_id = ?');
    $ins = $db->prepare('INSERT INTO scores (student_id, teacher_id, subject, quarter, details, quarter_grade)
      VALUES (?, ?, ?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE teacher_id = VALUES(teacher_id), details = VALUES(details), quarter_grade = VALUES(quarter_grade)');
    $saved = 0;
    foreach (($in['rows'] ?? []) as $r) {
      $sid = (int)($r['student_id'] ?? 0); $s = $r['scores'] ?? []; $g = grade($s);
      $member->execute([$c['id'], $sid]);
      if ($g === null || !$member->fetch()) continue;
      $d = []; foreach (WEIGHTS as $k => $w) $d[$k] = [(float)$s[$k][0], (float)$s[$k][1]];
      $ins->execute([$sid, me()['id'], $c['subject'], $q, json_encode($d), $g]);
      $saved++;
    }
    audit($db, 'save', "class {$c['id']} Q$q: $saved grades");
    out(['saved' => $saved]);

  case 'lock':       // submit and lock a quarter
    $c = my_class($db, (int)($in['class_id'] ?? 0)); $q = (int)($in['quarter'] ?? 0);
    if ($q < 1 || $q > 4) fail('Invalid quarter.');
    $db->prepare('INSERT IGNORE INTO locks (class_id, quarter) VALUES (?, ?)')->execute([$c['id'], $q]);
    audit($db, 'lock', "class {$c['id']} Q$q locked");
    out(['ok' => true]);

  case 'mygrades':   // student: only their own grades
    $u = need('student');
    $sql = 'SELECT s.subject, s.quarter, s.quarter_grade, s.details FROM scores s WHERE s.student_id = ?';
    if (SHOW_ONLY_LOCKED) $sql .= ' AND EXISTS (SELECT 1 FROM class_students cs
      JOIN classes c ON c.id = cs.class_id
      JOIN locks l ON l.class_id = c.id AND l.quarter = s.quarter
      WHERE cs.student_id = s.student_id AND c.subject = s.subject)';
    $st = $db->prepare($sql); $st->execute([$u['id']]);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) $r['details'] = json_decode($r['details'], true);
    out(['rows' => $rows]);

  case 'profile':
    $u = me(); if (!$u) fail('Not signed in.', 401);
    $ext = has_col($db, 'birthdate');      // false until update7.sql has been imported
    $st = $db->prepare('SELECT name, email, role, school_id, grade_level, phone, created_at' . ($ext ? ', birthdate, address, guardian_name, guardian_phone, job_title' : '') . ' FROM users WHERE id = ?');
    $st->execute([$u['id']]);
    out(['profile' => $st->fetch(), 'extended' => $ext]);

  case 'change_password':
    $u = me(); if (!$u) fail('Not signed in.', 401);
    $new = $in['new'] ?? '';
    if (strlen($new) < 6) fail('New password must be at least 6 characters.');
    $st = $db->prepare('SELECT password_hash FROM users WHERE id = ?'); $st->execute([$u['id']]); $row = $st->fetch();
    if (!$row || !password_verify($in['current'] ?? '', $row['password_hash'])) fail('Your current password is wrong.', 403);
    $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
    session_regenerate_id(true);
    out(['ok' => true]);

  case 'attendance_get':   // teacher: the class list with the status already marked for a day
    $c = my_class($db, (int)($_GET['class_id'] ?? 0)); $day = valid_day($_GET['day'] ?? '');
    $st = $db->prepare('SELECT u.id, u.name, a.status, a.note FROM class_students cs
      JOIN users u ON u.id = cs.student_id
      LEFT JOIN attendance a ON a.class_id = cs.class_id AND a.student_id = u.id AND a.day = ?
      WHERE cs.class_id = ? ORDER BY u.name');
    $st->execute([$day, $c['id']]);
    out(['students' => $st->fetchAll()]);

  case 'attendance_save':
    $c = my_class($db, (int)($in['class_id'] ?? 0)); $day = valid_day($in['day'] ?? '');
    $ins = $db->prepare('INSERT INTO attendance (class_id, student_id, day, status, note)
      SELECT ?, student_id, ?, ?, ? FROM class_students WHERE class_id = ? AND student_id = ?
      ON DUPLICATE KEY UPDATE status = VALUES(status), note = VALUES(note)');
    $n = 0;
    foreach (($in['marks'] ?? []) as $m) {
      $s = $m['status'] ?? '';
      if (!in_array($s, ['present', 'late', 'absent', 'excused'], true)) continue;
      $note = $s === 'present' ? '' : mb_substr(trim($m['note'] ?? ''), 0, 120);
      $ins->execute([$c['id'], $day, $s, $note === '' ? null : $note, $c['id'], (int)($m['student_id'] ?? 0)]); $n++;
    }
    audit($db, 'attendance', "class {$c['id']} $day: $n marked");
    out(['saved' => $n]);

  case 'attendance_mine':  // student: own attendance only (excused days do not lower the rate)
    $u = need('student');
    $month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
    $s = $db->prepare('SELECT seen_at FROM att_seen WHERE user_id = ?'); $s->execute([$u['id']]);
    $seen = $s->fetchColumn() ?: null;
    $st = $db->prepare("SELECT c.name AS class_name, c.subject, SUM(a.status = 'present') AS present, SUM(a.status = 'late') AS late,
      SUM(a.status = 'absent') AS absent, SUM(a.status = 'excused') AS excused
      FROM attendance a JOIN classes c ON c.id = a.class_id WHERE a.student_id = ?
      GROUP BY c.id, c.name, c.subject ORDER BY c.name");
    $st->execute([$u['id']]); $summary = $st->fetchAll();
    $st = $db->prepare("SELECT a.day, a.status, a.note, c.name AS class_name, c.subject FROM attendance a
      JOIN classes c ON c.id = a.class_id WHERE a.student_id = ? AND DATE_FORMAT(a.day, '%Y-%m') = ? ORDER BY a.day");
    $st->execute([$u['id'], $month]); $days = $st->fetchAll();
    $st = $db->prepare("SELECT a.day, a.status, a.note, c.id AS class_id, c.name AS class_name, c.subject,
      (a.updated_at > COALESCE(?, '1970-01-01 00:00:01')) AS is_new,
      (SELECT r.status FROM absence_requests r WHERE r.class_id = a.class_id AND r.student_id = a.student_id AND r.day = a.day) AS request
      FROM attendance a JOIN classes c ON c.id = a.class_id
      WHERE a.student_id = ? AND a.status IN ('late', 'absent', 'excused') ORDER BY a.day DESC LIMIT 20");
    $st->execute([$seen, $u['id']]); $recent = $st->fetchAll();
    $db->prepare('INSERT INTO att_seen (user_id, seen_at) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE seen_at = NOW()')->execute([$u['id']]);
    out(['summary' => $summary, 'days' => $days, 'recent' => $recent, 'month' => $month]);

  case 'att_unread': // student: new late, absent or excused marks since the last visit to the attendance page
    $u = me();
    if (!$u || $u['role'] !== 'student') out(['count' => 0]);
    $st = $db->prepare("SELECT COUNT(*) FROM attendance a WHERE a.student_id = ? AND a.status IN ('late', 'absent', 'excused')
      AND a.updated_at > COALESCE((SELECT seen_at FROM att_seen WHERE user_id = ?), '1970-01-01 00:00:01')");
    $st->execute([$u['id'], $u['id']]);
    out(['count' => (int)$st->fetchColumn()]);

  case 'attendance_month':  // teacher: one month for the whole class
    $c = my_class($db, (int)($_GET['class_id'] ?? 0));
    $month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
    $st = $db->prepare('SELECT u.id, u.name FROM class_students cs JOIN users u ON u.id = cs.student_id WHERE cs.class_id = ? ORDER BY u.name');
    $st->execute([$c['id']]); $students = $st->fetchAll();
    $st = $db->prepare("SELECT student_id, DAY(day) AS d, status FROM attendance WHERE class_id = ? AND DATE_FORMAT(day, '%Y-%m') = ?");
    $st->execute([$c['id'], $month]);
    $marks = [];
    foreach ($st->fetchAll() as $r) $marks[(int)$r['student_id']][(int)$r['d']] = $r['status'];
    out(['month' => $month, 'days' => (int)date('t', strtotime($month . '-01')), 'students' => $students, 'marks' => (object)$marks]);

  case 'attendance_watch':  // teacher: students below 90% or absent 3 days in a row
    $c = my_class($db, (int)($_GET['class_id'] ?? 0));
    $st = $db->prepare('SELECT u.id, u.name, a.status FROM class_students cs JOIN users u ON u.id = cs.student_id
      JOIN attendance a ON a.class_id = cs.class_id AND a.student_id = u.id WHERE cs.class_id = ? ORDER BY u.name, a.day');
    $st->execute([$c['id']]);
    $by = [];
    foreach ($st->fetchAll() as $r) {
      $id = (int)$r['id'];
      if (!isset($by[$id])) $by[$id] = ['name' => $r['name'], 'p' => 0, 'l' => 0, 'a' => 0, 'streak' => 0];
      if ($r['status'] === 'present') { $by[$id]['p']++; $by[$id]['streak'] = 0; }
      elseif ($r['status'] === 'late') { $by[$id]['l']++; $by[$id]['streak'] = 0; }
      elseif ($r['status'] === 'absent') { $by[$id]['a']++; $by[$id]['streak']++; }
      // excused days count for nothing and neither start nor break a streak
    }
    $watch = [];
    foreach ($by as $id => $x) {
      $total = $x['p'] + $x['l'] + $x['a'];
      $rate = $total ? (($x['p'] + $x['l']) / $total) * 100 : 100;
      $why = [];
      if ($total >= 3 && $rate < 90) $why[] = 'Attendance ' . round($rate) . '%';
      if ($x['streak'] >= 3) $why[] = 'Absent ' . $x['streak'] . ' days in a row';
      if ($why) $watch[] = ['id' => $id, 'name' => $x['name'], 'reasons' => $why];
    }
    out(['watch' => $watch]);

  case 'excuse_request':  // student explains a day they were marked absent or late
    $u = need('student');
    $cid = (int)($in['class_id'] ?? 0); $day = valid_day($in['day'] ?? ''); $reason = trim($in['reason'] ?? '');
    if (mb_strlen($reason) < 3 || mb_strlen($reason) > 200) fail('Please write a short reason (3 to 200 characters).');
    $st = $db->prepare('SELECT status FROM attendance WHERE class_id = ? AND student_id = ? AND day = ?');
    $st->execute([$cid, $u['id'], $day]); $row = $st->fetch();
    if (!$row || !in_array($row['status'], ['absent', 'late'], true)) fail('You can only explain a day you were marked absent or late.');
    $db->prepare("INSERT INTO absence_requests (class_id, student_id, day, reason) VALUES (?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE reason = VALUES(reason), status = 'pending'")->execute([$cid, $u['id'], $day, $reason]);
    out(['ok' => true]);

  case 'excuse_list':     // teacher: explanations waiting for a decision
    $t = need('teacher');
    $st = $db->prepare("SELECT r.id, r.day, r.reason, u.name AS student, c.name AS class_name, c.subject
      FROM absence_requests r JOIN classes c ON c.id = r.class_id JOIN users u ON u.id = r.student_id
      WHERE c.teacher_id = ? AND r.status = 'pending' ORDER BY r.day DESC");
    $st->execute([$t['id']]);
    out(['requests' => $st->fetchAll()]);

  case 'excuse_decide':   // teacher: approve (marks the day excused) or decline
    $t = need('teacher');
    $st = $db->prepare("SELECT r.* FROM absence_requests r JOIN classes c ON c.id = r.class_id
      WHERE r.id = ? AND c.teacher_id = ? AND r.status = 'pending'");
    $st->execute([(int)($in['id'] ?? 0), $t['id']]); $r = $st->fetch();
    if (!$r) fail('Request not found.', 404);
    $approve = !empty($in['approve']);
    $db->beginTransaction();
    if ($approve) {
      $db->prepare("UPDATE attendance SET status = 'excused', note = ? WHERE class_id = ? AND student_id = ? AND day = ?")
         ->execute([mb_substr($r['reason'], 0, 120), $r['class_id'], $r['student_id'], $r['day']]);
    }
    $db->prepare('UPDATE absence_requests SET status = ? WHERE id = ?')->execute([$approve ? 'approved' : 'rejected', $r['id']]);
    $db->commit();
    audit($db, 'excuse', "request {$r['id']} " . ($approve ? 'approved' : 'declined'));
    out(['ok' => true]);

  case 'ann_post':
    $c = my_class($db, (int)($in['class_id'] ?? 0));
    $title = trim($in['title'] ?? ''); $body = trim($in['body'] ?? '');
    if ($title === '' || mb_strlen($title) > 120) fail('Please enter a title (up to 120 characters).');
    if ($body === '' || mb_strlen($body) > 2000) fail('Please write a message (up to 2000 characters).');
    $db->prepare('INSERT INTO announcements (class_id, teacher_id, title, body) VALUES (?, ?, ?, ?)')
       ->execute([$c['id'], me()['id'], $title, $body]);
    out(['ok' => true]);

  case 'ann_list':   // teachers see what they posted; students see posts for their classes
    $u = me();
    if (!$u || !in_array($u['role'], ['student', 'teacher'], true)) fail('Not allowed.', 403);
    $base = 'SELECT a.id, a.title, a.body, a.created_at, c.name AS class_name, t.name AS teacher
      FROM announcements a JOIN classes c ON c.id = a.class_id JOIN users t ON t.id = a.teacher_id';
    $st = $u['role'] === 'teacher'
      ? $db->prepare($base . ' WHERE a.teacher_id = ? ORDER BY a.created_at DESC LIMIT 50')
      : $db->prepare($base . ' JOIN class_students cs ON cs.class_id = c.id AND cs.student_id = ? ORDER BY a.created_at DESC LIMIT 50');
    $st->execute([$u['id']]);
    $items = $st->fetchAll(); $seen = null;
    if ($u['role'] === 'student') {
      $s = $db->prepare('SELECT seen_at FROM ann_seen WHERE user_id = ?'); $s->execute([$u['id']]);
      $seen = $s->fetchColumn() ?: null;
      $db->prepare('INSERT INTO ann_seen (user_id, seen_at) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE seen_at = NOW()')->execute([$u['id']]);
    }
    out(['items' => $items, 'mine' => $u['role'] === 'teacher', 'seen' => $seen]);

  case 'ann_unread': // student: how many announcements are new since the last visit
    $u = me();
    if (!$u || $u['role'] !== 'student') out(['count' => 0]);
    $st = $db->prepare("SELECT COUNT(*) FROM announcements a
      JOIN class_students cs ON cs.class_id = a.class_id AND cs.student_id = ?
      WHERE a.created_at > COALESCE((SELECT seen_at FROM ann_seen WHERE user_id = ?), '1970-01-01 00:00:01')");
    $st->execute([$u['id'], $u['id']]);
    out(['count' => (int)$st->fetchColumn()]);

  case 'ann_delete':
    $t = need('teacher');
    $db->prepare('DELETE FROM announcements WHERE id = ? AND teacher_id = ?')->execute([(int)($in['id'] ?? 0), $t['id']]);
    out(['ok' => true]);

  case 'ann_latest': // student: newest 3 announcements for the dashboard (does not mark them as read)
    $u = me();
    if (!$u || $u['role'] !== 'student') out(['items' => [], 'seen' => null]);
    $st = $db->prepare('SELECT a.id, a.title, a.body, a.created_at, c.name AS class_name, t.name AS teacher
      FROM announcements a JOIN classes c ON c.id = a.class_id JOIN users t ON t.id = a.teacher_id
      JOIN class_students cs ON cs.class_id = c.id AND cs.student_id = ?
      ORDER BY a.created_at DESC LIMIT 3');
    $st->execute([$u['id']]);
    $s = $db->prepare('SELECT seen_at FROM ann_seen WHERE user_id = ?'); $s->execute([$u['id']]);
    out(['items' => $st->fetchAll(), 'seen' => $s->fetchColumn() ?: null]);

  case 'my_status':  // student: my classes, and which quarters each teacher has submitted
    $u = need('student');
    $st = $db->prepare('SELECT c.id, c.name AS class_name, c.subject, t.name AS teacher, l.quarter
      FROM class_students cs JOIN classes c ON c.id = cs.class_id JOIN users t ON t.id = c.teacher_id
      LEFT JOIN locks l ON l.class_id = c.id
      WHERE cs.student_id = ? ORDER BY c.name');
    $st->execute([$u['id']]);
    $classes = [];
    foreach ($st->fetchAll() as $r) {
      $id = (int)$r['id'];
      if (!isset($classes[$id])) $classes[$id] = ['class_name' => $r['class_name'], 'subject' => $r['subject'], 'teacher' => $r['teacher'], 'quarters' => []];
      if ($r['quarter'] !== null) $classes[$id]['quarters'][] = (int)$r['quarter'];
    }
    out(['classes' => array_values($classes)]);

  case 'calendar':   // holidays and teacher-marked no-class days for one month (teachers and students)
    if (!me()) fail('Not signed in.', 401);
    $month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
    $hol = [];
    foreach (holidays((int)substr($month, 0, 4)) as $d => $h) if (substr($d, 0, 7) === $month) $hol[$d] = $h;
    $st = $db->prepare("SELECT day, reason FROM school_days_off WHERE DATE_FORMAT(day, '%Y-%m') = ? ORDER BY day");
    $st->execute([$month]);
    $off = [];
    foreach ($st->fetchAll() as $r) $off[$r['day']] = $r['reason'];
    out(['month' => $month, 'holidays' => (object)$hol, 'off' => (object)$off]);

  case 'day_off_set': // teacher: mark a day as "no classes for the whole school", or take the mark away
    need('teacher');
    $day = valid_day($in['day'] ?? '');
    if (!empty($in['off'])) {
      $reason = trim($in['reason'] ?? '');
      if (mb_strlen($reason) < 3 || mb_strlen($reason) > 100) fail('Please give a short reason (3 to 100 characters).');
      $db->prepare('INSERT INTO school_days_off (day, reason, marked_by) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE reason = VALUES(reason), marked_by = VALUES(marked_by)')->execute([$day, $reason, me()['id']]);
      audit($db, 'no_class', "$day: $reason");
    } else {
      $db->prepare('DELETE FROM school_days_off WHERE day = ?')->execute([$day]);
      audit($db, 'no_class', "$day: mark removed");
    }
    out(['ok' => true]);

  case 'profile_update':  // any signed-in user: edit own details (the ID number can be added once, if it is still empty)
    $u = me(); if (!$u) fail('Not signed in.', 401);
    $cur = $db->prepare('SELECT school_id FROM users WHERE id = ?'); $cur->execute([$u['id']]); $curId = $cur->fetchColumn();
    $name = trim($in['name'] ?? ''); $phone = trim($in['phone'] ?? ''); $grade = trim($in['grade_level'] ?? ''); $sid = trim($in['school_id'] ?? '');
    if ($name === '' || mb_strlen($name) > 100) fail('Please enter your full name.');
    if ($phone !== '' && !preg_match('/^[0-9+() -]{7,20}$/', $phone)) fail('Please enter a valid contact number.');
    if ($u['role'] === 'student') { if ($grade === '' || mb_strlen($grade) > 20) fail('Please enter your grade or year level.'); } else { $grade = ''; }
    $set = ['name = ?', 'phone = ?', 'grade_level = ?']; $args = [$name, $phone ?: null, $grade ?: null];
    if (!$curId && $sid !== '') {
      if (!preg_match('/^[A-Za-z0-9-]{3,30}$/', $sid)) fail('Please enter a valid ID number (letters, numbers and dashes, 3 to 30 characters).');
      $dup = $db->prepare('SELECT id FROM users WHERE school_id = ? AND id <> ?'); $dup->execute([$sid, $u['id']]);
      if ($dup->fetch()) fail('That ID number is already registered.');
      $set[] = 'school_id = ?'; $args[] = $sid;
    }
    if (has_col($db, 'birthdate')) {
      $birth = trim($in['birthdate'] ?? ''); $address = trim($in['address'] ?? '');
      $gname = trim($in['guardian_name'] ?? ''); $gphone = trim($in['guardian_phone'] ?? ''); $job = trim($in['job_title'] ?? '');
      if ($birth !== '') {
        $b = DateTime::createFromFormat('Y-m-d', $birth);
        if (!$b || $b->format('Y-m-d') !== $birth || $birth > date('Y-m-d') || $birth < '1900-01-01') fail('Please enter a valid birthdate.');
      }
      if (mb_strlen($address) > 200) fail('The address is too long (200 characters at most).');
      if ($u['role'] === 'student') {
        if (mb_strlen($gname) > 100) fail('The guardian name is too long.');
        if ($gphone !== '' && !preg_match('/^[0-9+() -]{7,20}$/', $gphone)) fail('Please enter a valid guardian contact number.');
        $job = '';
      } else {
        if (mb_strlen($job) > 80) fail('The position is too long (80 characters at most).');
        $gname = ''; $gphone = '';
      }
      array_push($set, 'birthdate = ?', 'address = ?', 'guardian_name = ?', 'guardian_phone = ?', 'job_title = ?');
      array_push($args, $birth ?: null, $address ?: null, $gname ?: null, $gphone ?: null, $job ?: null);
    }
    $args[] = $u['id'];
    $db->prepare('UPDATE users SET ' . implode(', ', $set) . ' WHERE id = ?')->execute($args);
    $_SESSION['user']['name'] = $name;
    out(['ok' => true]);

  case 'reset_request':   // anyone: ask for a reset link (the answer is always the same, so nobody can find out who has an account)
    $email = strtolower(trim($in['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email address.');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $c = $db->prepare('SELECT COUNT(*) FROM password_resets WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)'); $c->execute([$ip]);
    if ((int)$c->fetchColumn() >= 8) fail('Too many requests. Please try again in an hour.', 429);
    $st = $db->prepare("SELECT id, name FROM users WHERE email = ? AND role <> 'pending_teacher'"); $st->execute([$email]); $u = $st->fetch();
    $link = null;
    if ($u) {
      $per = $db->prepare('SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at > (NOW() - INTERVAL 1 HOUR)'); $per->execute([$u['id']]);
      if ((int)$per->fetchColumn() < 5) {
        $token = bin2hex(random_bytes(32));
        $db->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')->execute([$u['id']]);
        $db->prepare('INSERT INTO password_resets (user_id, token_hash, ip, expires_at) VALUES (?, ?, ?, NOW() + INTERVAL 30 MINUTE)')
           ->execute([$u['id'], hash('sha256', $token), $ip]);
        $base = SITE_URL !== '' ? rtrim(SITE_URL, '/')
          : (is_local() ? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') : '');
        if ($base !== '') {
          $link = $base . '/forgot.html?token=' . $token;
          $body = "Hello {$u['name']},\r\n\r\nWe received a request to reset your School Portal password.\r\nOpen this link within 30 minutes to choose a new password:\r\n\r\n$link\r\n\r\nIf you did not ask for this, you can ignore this message. Your password will not change.\r\n";
          @mail($email, 'Reset your School Portal password', $body, 'From: ' . MAIL_FROM . "\r\nContent-Type: text/plain; charset=UTF-8");
        }
      }
    } else {
      $db->prepare('INSERT INTO password_resets (user_id, ip) VALUES (NULL, ?)')->execute([$ip]);   // still counts toward the limit
    }
    // On this computer only (localhost) the link is also shown on screen, so you can test without an email server
    out(['ok' => true, 'dev_link' => ($u && is_local()) ? $link : null]);

  case 'reset_check':     // anyone with a link: is it still good?
    $st = $db->prepare('SELECT 1 FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()');
    $st->execute([hash('sha256', (string)($_GET['token'] ?? ''))]);
    out(['valid' => (bool)$st->fetch()]);

  case 'reset_apply':     // anyone with a good link: choose a new password (the link works once)
    $pass = (string)($in['password'] ?? '');
    if (strlen($pass) < 6) fail('The new password must be at least 6 characters.');
    $st = $db->prepare('SELECT user_id FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()');
    $st->execute([hash('sha256', (string)($in['token'] ?? ''))]); $r = $st->fetch();
    if (!$r) fail('This link has expired or was already used. Please ask for a new one.');
    $db->beginTransaction();
    $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $r['user_id']]);
    $db->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')->execute([$r['user_id']]);
    $db->commit();
    out(['ok' => true]);

  // ---------- School fees and payments (needs update9.sql) ----------

  case 'fees_mine':   // student: every fee I owe, what I've paid, and my payment history
    $u = need('student');
    $st = $db->prepare('SELECT name, school_id, grade_level FROM users WHERE id = ?'); $st->execute([$u['id']]); $who = $st->fetch();
    $st = $db->prepare("SELECT f.id, f.title, f.description, f.category, f.amount, f.applies_to, f.grade_level, f.subject, f.due_date, f.allow_partial,
        c.name AS class_name, t.name AS set_by,
        COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.fee_id = f.id AND p.student_id = ? AND p.status = 'confirmed'), 0) AS paid,
        COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.fee_id = f.id AND p.student_id = ? AND p.status = 'pending'), 0) AS pending
      FROM fees f LEFT JOIN classes c ON c.id = f.class_id JOIN users t ON t.id = f.created_by
      WHERE " . FEE_OWED . " ORDER BY f.due_date IS NULL, f.due_date, f.title");
    $st->execute(array_merge([$u['id'], $u['id']], owed_args($u['id'])));
    $fees = $st->fetchAll();
    $st = $db->prepare('SELECT p.id, p.fee_id, f.title AS fee_title, f.category, p.amount, p.method, p.reference, p.status, p.note, p.receipt_no,
        p.created_at, p.decided_at, d.name AS decided_by
      FROM payments p JOIN fees f ON f.id = p.fee_id LEFT JOIN users d ON d.id = p.decided_by
      WHERE p.student_id = ? ORDER BY p.created_at DESC');
    $st->execute([$u['id']]);
    out(['student' => $who, 'fees' => $fees, 'payments' => $st->fetchAll(), 'today' => date('Y-m-d'),
         'methods' => PAY_METHODS, 'online' => ONLINE_PAYMENTS]);

  case 'pay_submit':  // student: "I paid by GCash / Maya / bank, here is the reference number" (waits for a teacher to confirm)
    $u = need('student');
    $f = owed_fee($db, (int)($in['fee_id'] ?? 0), $u['id']);
    $amt = money($in['amount'] ?? null);
    $method = $in['method'] ?? '';
    if (!in_array($method, ['gcash', 'maya', 'bank'], true)) fail('Please choose how you paid.');
    $ref = trim($in['reference'] ?? '');
    if (!preg_match('/^[A-Za-z0-9 -]{4,60}$/', $ref)) fail('Please enter the reference number from your receipt (letters and numbers, 4 to 60 characters).');
    check_amount($db, $f, $u['id'], $amt);
    $dup = $db->prepare("SELECT 1 FROM payments WHERE method = ? AND reference = ? AND status <> 'rejected'"); $dup->execute([$method, $ref]);
    if ($dup->fetch()) fail('That reference number was already sent.');
    $db->prepare('INSERT INTO payments (fee_id, student_id, amount, method, reference) VALUES (?, ?, ?, ?, ?)')
       ->execute([$f['id'], $u['id'], $amt, $method, $ref]);
    out(['ok' => true]);

  case 'pay_cancel':  // student: take back a payment that hasn't been checked yet
    $u = need('student');
    $db->prepare("DELETE FROM payments WHERE id = ? AND student_id = ? AND status = 'pending'")->execute([(int)($in['id'] ?? 0), $u['id']]);
    out(['ok' => true]);

  case 'pay_online_start':  // student: pay with a card or e-wallet through a payment gateway
    $u = need('student');
    $f = owed_fee($db, (int)($in['fee_id'] ?? 0), $u['id']);
    check_amount($db, $f, $u['id'], money($in['amount'] ?? null));
    // PAYMENT GATEWAY GOES HERE. When the school has an account (PayMongo, Xendit, Dragonpay...):
    //   1. create a checkout session with the gateway's API using the school's secret key (keep the key on the server, never in the page),
    //   2. save a 'pending' payment with method 'online' and the gateway's checkout id as the reference,
    //   3. out(['redirect' => <the gateway's checkout URL>]);
    //   4. add a webhook action the gateway calls after payment, which checks the gateway's signature and then confirms the payment
    //      the same way pay_decide does (status 'confirmed' and a receipt number).
    fail('Online payment is not set up yet. Please pay by GCash, Maya, bank transfer or cash at the office.', 501);

  case 'fee_list':    // teacher: every fee with how much has been collected
    $t = need('teacher');
    $fees = $db->query("SELECT f.*, c.name AS class_name, u.name AS set_by,
        COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.fee_id = f.id AND p.status = 'confirmed'), 0) AS collected,
        (SELECT COUNT(*) FROM payments p WHERE p.fee_id = f.id AND p.status = 'pending') AS waiting
      FROM fees f LEFT JOIN classes c ON c.id = f.class_id JOIN users u ON u.id = f.created_by
      WHERE f.active = 1 ORDER BY f.due_date IS NULL, f.due_date, f.title")->fetchAll();
    foreach ($fees as &$f) {
      $f['students'] = count(fee_students($db, $f));
      $f['expected'] = round($f['students'] * (float)$f['amount'], 2);
      $f['mine'] = (int)$f['created_by'] === $t['id'];
    }
    unset($f);
    $grades = $db->query("SELECT DISTINCT grade_level FROM users WHERE role = 'student' AND grade_level IS NOT NULL AND grade_level <> '' ORDER BY grade_level")->fetchAll(PDO::FETCH_COLUMN);
    $classes = $db->query('SELECT c.id, c.name, c.subject, u.name AS teacher FROM classes c JOIN users u ON u.id = c.teacher_id ORDER BY c.name')->fetchAll();
    out(['fees' => $fees, 'grades' => $grades, 'classes' => $classes, 'subjects' => SUBJECTS, 'today' => date('Y-m-d')]);

  case 'fee_save':    // teacher: add a fee, or change one they made
    $t = need('teacher');
    $id = (int)($in['id'] ?? 0);
    if ($id) my_fee($db, $id);
    $title = trim($in['title'] ?? ''); $desc = trim($in['description'] ?? ''); $cat = $in['category'] ?? 'other';
    if ($title === '' || mb_strlen($title) > 100) fail('Please enter a name for the fee (up to 100 characters).');
    if (mb_strlen($desc) > 255) fail('The description is too long (255 characters at most).');
    if (!in_array($cat, ['tuition', 'miscellaneous', 'laboratory', 'books', 'uniform', 'activity', 'exam', 'other'], true)) fail('Invalid category.');
    $amt = money($in['amount'] ?? null);
    $to = $in['applies_to'] ?? ''; $grade = null; $cid = null; $subject = null;
    if ($to === 'grade') { $grade = trim($in['grade_level'] ?? ''); if ($grade === '' || mb_strlen($grade) > 20) fail('Please choose a grade level.'); }
    elseif ($to === 'class') {
      $cid = (int)($in['class_id'] ?? 0);
      $st = $db->prepare('SELECT 1 FROM classes WHERE id = ?'); $st->execute([$cid]); if (!$st->fetch()) fail('Please choose a class.');
    }
    elseif ($to === 'subject') { $subject = $in['subject'] ?? ''; if (!in_array($subject, SUBJECTS, true)) fail('Please choose a subject.'); }
    elseif ($to !== 'school') fail('Please choose who pays this fee.');
    $due = trim($in['due_date'] ?? ''); $due = $due === '' ? null : valid_day($due);
    $partial = !empty($in['allow_partial']) ? 1 : 0;
    $args = [$title, $desc ?: null, $cat, $amt, $to, $grade, $cid, $subject, $due, $partial];
    if ($id) {
      $db->prepare('UPDATE fees SET title = ?, description = ?, category = ?, amount = ?, applies_to = ?, grade_level = ?, class_id = ?, subject = ?, due_date = ?, allow_partial = ? WHERE id = ?')
         ->execute(array_merge($args, [$id]));
    } else {
      $db->prepare('INSERT INTO fees (title, description, category, amount, applies_to, grade_level, class_id, subject, due_date, allow_partial, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
         ->execute(array_merge($args, [$t['id']]));
      $id = (int)$db->lastInsertId();
    }
    audit($db, 'fee', "fee $id saved: $title " . number_format($amt, 2));
    out(['id' => $id]);

  case 'fee_remove':  // teacher: delete a fee nobody has paid yet; archive it (hide it) if there are payments
    my_fee($db, (int)($in['id'] ?? 0));
    $id = (int)$in['id'];
    $st = $db->prepare("SELECT COUNT(*) FROM payments WHERE fee_id = ? AND status <> 'rejected'"); $st->execute([$id]);
    if ((int)$st->fetchColumn()) {
      $db->prepare('UPDATE fees SET active = 0 WHERE id = ?')->execute([$id]);
      $db->prepare("UPDATE payments SET status = 'rejected', note = 'Fee was removed' WHERE fee_id = ? AND status = 'pending'")->execute([$id]);
      audit($db, 'fee', "fee $id archived"); out(['archived' => true]);
    }
    $db->prepare('DELETE FROM fees WHERE id = ?')->execute([$id]);
    audit($db, 'fee', "fee $id deleted"); out(['archived' => false]);

  case 'fee_students':  // teacher: who owes one fee, and how much each has paid
    need('teacher');
    $st = $db->prepare('SELECT * FROM fees WHERE id = ?'); $st->execute([(int)($_GET['id'] ?? 0)]); $f = $st->fetch();
    if (!$f) fail('Fee not found.', 404);
    $sums = [];
    $st = $db->prepare("SELECT student_id, SUM(CASE WHEN status = 'confirmed' THEN amount ELSE 0 END) AS paid,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) AS pending FROM payments WHERE fee_id = ? GROUP BY student_id");
    $st->execute([$f['id']]);
    foreach ($st->fetchAll() as $r) $sums[(int)$r['student_id']] = $r;
    $rows = [];
    foreach (fee_students($db, $f) as $s) {
      $p = $sums[(int)$s['id']] ?? ['paid' => 0, 'pending' => 0];
      $rows[] = $s + ['paid' => (float)$p['paid'], 'pending' => (float)$p['pending']];
    }
    out(['fee' => $f, 'students' => $rows, 'today' => date('Y-m-d')]);

  case 'pay_list':    // teacher: payments to check for my fees (pending first), plus the latest decided ones
    $t = need('teacher');
    $st = $db->prepare("SELECT p.*, f.title AS fee_title, f.amount AS fee_amount, u.name AS student, u.school_id, u.grade_level, d.name AS decided_name
      FROM payments p JOIN fees f ON f.id = p.fee_id JOIN users u ON u.id = p.student_id LEFT JOIN users d ON d.id = p.decided_by
      WHERE f.created_by = ? ORDER BY p.status = 'pending' DESC, COALESCE(p.decided_at, p.created_at) DESC LIMIT 200");
    $st->execute([$t['id']]);
    out(['payments' => $st->fetchAll()]);

  case 'pay_decide':  // teacher: confirm a payment (gives a receipt number) or reject it with a reason
    $t = need('teacher');
    $st = $db->prepare("SELECT p.*, f.amount AS fee_amount FROM payments p JOIN fees f ON f.id = p.fee_id
      WHERE p.id = ? AND f.created_by = ? AND p.status = 'pending'");
    $st->execute([(int)($in['id'] ?? 0), $t['id']]); $p = $st->fetch();
    if (!$p) fail('Payment not found, or it was already checked.', 404);
    if (!empty($in['confirm'])) {
      $s = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE fee_id = ? AND student_id = ? AND status = 'confirmed'");
      $s->execute([$p['fee_id'], $p['student_id']]);
      if ((float)$s->fetchColumn() + (float)$p['amount'] > (float)$p['fee_amount'] + 0.001) fail('This would be more than the fee. Reject it, or ask the student to send the right amount.');
      $db->prepare("UPDATE payments SET status = 'confirmed', receipt_no = ?, note = NULL, decided_by = ?, decided_at = NOW() WHERE id = ?")
         ->execute([receipt_no($p['id']), $t['id'], $p['id']]);
      audit($db, 'payment', "payment {$p['id']} confirmed");
    } else {
      $why = trim($in['note'] ?? '');
      if (mb_strlen($why) < 3 || mb_strlen($why) > 200) fail('Please tell the student why (3 to 200 characters).');
      $db->prepare("UPDATE payments SET status = 'rejected', note = ?, decided_by = ?, decided_at = NOW() WHERE id = ?")->execute([$why, $t['id'], $p['id']]);
      audit($db, 'payment', "payment {$p['id']} rejected");
    }
    out(['ok' => true]);

  case 'pay_record':  // teacher: record cash paid at the office (confirmed right away, with a receipt number)
    $t = need('teacher');
    $f = my_fee($db, (int)($in['fee_id'] ?? 0));
    $sid = (int)($in['student_id'] ?? 0);
    if (!in_array($sid, array_map(fn($s) => (int)$s['id'], fee_students($db, $f)), true)) fail('That student does not owe this fee.');
    $amt = money($in['amount'] ?? null);
    check_amount($db, $f, $sid, $amt);
    $db->prepare("INSERT INTO payments (fee_id, student_id, amount, method, reference, status, decided_by, decided_at) VALUES (?, ?, ?, 'cash', ?, 'confirmed', ?, NOW())")
       ->execute([$f['id'], $sid, $amt, mb_substr(trim($in['reference'] ?? ''), 0, 60) ?: null, $t['id']]);
    $pid = (int)$db->lastInsertId(); $no = receipt_no($pid);
    $db->prepare('UPDATE payments SET receipt_no = ? WHERE id = ?')->execute([$no, $pid]);
    audit($db, 'payment', "cash $pid recorded for student $sid");
    out(['receipt_no' => $no]);

  default: fail('Unknown action.', 404);
}