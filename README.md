# School Portal: grades, attendance, announcements and fees (PHP + MySQL)

## Setup on localhost (XAMPP)

1. Install XAMPP and start **Apache** and **MySQL**.
2. Copy this folder into `C:\xampp\htdocs\` so you have `C:\xampp\htdocs\school-portal`.
3. Open http://localhost/phpmyadmin, go to **Import**, and import the SQL files **in this order**, one at a time:
   1. `database.sql` (creates the `school_portal` database with the users and scores tables)
   2. `update1.sql` to `update9.sql`, in number order (classes, attendance, announcements, profiles, password resets, fees and payments)

   If phpMyAdmin says "No database selected", click **New**, create a database named `school_portal`, select it, then import again.
4. Check the settings at the top of `api.php`. If your MySQL root user has a password, set `DB_PASS`:
   ```php
   const DB_HOST = 'localhost', DB_NAME = 'school_portal', DB_USER = 'root', DB_PASS = '';
   ```
5. Open http://localhost/school-portal/ (it takes you to the sign-in page, `login.php`).

## First accounts

**Students** sign up at http://localhost/school-portal/register.php and can sign in right away.

**Teachers** sign up on the same page, but a new teacher account waits for approval. There is no admin page yet (`admin.html` is empty), so approve a teacher in phpMyAdmin:

1. Open http://localhost/phpmyadmin, select `school_portal`, and open the **SQL** tab.
2. Run this, with the teacher's email:
   ```sql
   UPDATE users SET role = 'teacher' WHERE email = 'teacher@example.com';
   ```
3. The teacher can now sign in at http://localhost/school-portal/login.php.

Anyone can change their password later on the **Profile** page.

## Try it out

1. Register one teacher (approve it as above) and two or three students.
2. As the teacher: create a class, add the students to it, enter quiz, activity and exam scores, then **submit and lock** the quarter. Students only see a quarter after it is locked.
3. Mark attendance, post an announcement, and add a fee on the **Fees** page.
4. Sign in as a student to see grades, attendance, announcements and the **Payments** page.

## Settings in `api.php`

- `SUBJECTS`: the subject list teachers pick from.
- `WEIGHTS`: quizzes / activities / exam percentages (must add up to 100).
- `PAY_METHODS`: the GCash, Maya, bank and cash details students see. Change them to the school's real accounts.
- `SITE_URL` and `MAIL_FROM`: needed for "Forgot your password?" emails once the site is online. On plain XAMPP, mail is usually not sent.

## Notes

- Payments are recorded in the database only. A student sends a reference number and a teacher confirms it; `ONLINE_PAYMENTS` stays `false` until a real payment gateway is connected.
- Old `.html` links still work: `.htaccess` sends them to the matching `.php` page.
- Passwords are hashed and all queries use prepared statements.
- GitHub Pages and Firebase Hosting cannot run PHP or MySQL. To put the portal online, use hosting with PHP and MySQL, and use HTTPS.
