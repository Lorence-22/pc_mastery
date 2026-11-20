# PC Mastery - Learning Management System (all phases)

PHP 8.1+ / MySQL / Bootstrap 5 / Chart.js / SweetAlert2. Runs on XAMPP.

## Install
1. Copy the `pc_mastery` folder to `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** in XAMPP.
3. phpMyAdmin > **Import** > `database/pc_mastery_db.sql` (creates DB `pc_mastery_db`, tables, parts, admin).
4. phpMyAdmin > select `pc_mastery_db` > **Import** > `database/seed_content.sql`
   (18 assembly steps with components, 3 sample reviewer topics, 1 sample quiz).
   If you already imported the first file earlier, only this second file is needed.
5. If MySQL has a root password, edit `config/database.php`. If the folder name differs, change `BASE_URL`.
6. Open http://localhost/pc_mastery/ and log in: **admin / Admin@12345** (you must change it at first login).
7. Set your school name and certificate signatory in Admin > System Settings.

## PDF certificates / reports (Dompdf)
- With Composer: in the project folder run `composer install` (uses `composer.json`) to install Dompdf into `vendor/`.
  Certificates and PDF reports then download as real PDF files.
- Without Dompdf: the same pages open as print-ready layouts and the browser's Print > "Save as PDF" is used. Everything else works.

## Feature map
| Area | Admin | Student |
|---|---|---|
| Accounts | Students CRUD, activate/deactivate, reset password, profile view | Register, profile, password, picture |
| Parts | Parts + categories CRUD | 16 lessons, studied tracking, identification game |
| Reviewer | Topics, PDF/image uploads (secure delivery) | Read, view PDFs, download if permitted, progress |
| Quizzes | PDF method + interactive builder (6 question types), review/grading | Timer, autosave, instant results, history |
| Assembly | Edit module, steps, components, passing score | Practice (hints) + Assessment, server-validated drag and drop |
| Certificates | List, search, revoke/restore | View, print, PDF; public verification at /verify.php |
| Analytics | Filters, 5 charts, per-student reports, PDF + Excel export | Learning progress, leaderboard (configurable) |
| System | Settings, announcements, activity logs | Notification center |

## Security notes
PDO prepared statements, bcrypt, CSRF tokens on every POST (and AJAX header), role checks on every page, session ID regeneration at login,
login lockout, upload validation by extension + MIME, random filenames, `uploads/pdf` and `uploads/reviewers` are denied to direct web access
(files are served through `api/file.php` after permission checks), uploaded scripts cannot execute, and all scoring/certificate issuing is server-side.

## Notes
- Excel export is an HTML-table `.xls` that opens in Excel/LibreOffice (no extra library needed).
- The assembly board is a drawn motherboard + tower case (SVG). Parts appear in place when installed, the case must be opened first, thermal paste follows the CPU, and the board moves into the case at step 8.
  Zone labels are shown in Practice mode and hidden in Assessment mode.
- Assembly scoring: full points on the first correct try, half on the second, none afterwards; the pass mark defaults to 80%.
- Only passing the **Assessment** mode issues a certificate (practice mode never does).
