# PC Mastery – Learning Management System

A comprehensive **Learning Management System (LMS)** for PC hardware education, featuring interactive lessons, quizzes, motherboard assembly simulations, student progress tracking, certificates, and administrative analytics.

**Technology Stack:** PHP 8.1+ · MySQL · Bootstrap 5 · Chart.js · SweetAlert2

**Development Environment:** XAMPP (Apache and MySQL)

---

## 1. Installation and Setup

### Prerequisites
- XAMPP with Apache and MySQL
- PHP 8.1 or higher
- A modern web browser
- Composer (optional, required for native PDF generation)

### Installation Steps

1. Copy the `pc_mastery` project folder into:

   `C:\xampp\htdocs\`

2. Open XAMPP Control Panel and start:
   - Apache
   - MySQL

3. Open phpMyAdmin:

   `http://localhost/phpmyadmin/`

4. Import the main database:
   - Navigate to **Import**.
   - Select `database/pc_mastery_db.sql`.
   - Execute the import.

   This creates the `pc_mastery_db` database, its tables, PC components, and the default administrator account.

5. Import the sample learning content:
   - Select the `pc_mastery_db` database.
   - Navigate to **Import**.
   - Select `database/seed_content.sql`.
   - Execute the import.

   This adds:
   - 18 PC assembly steps with corresponding components.
   - 3 sample reviewer topics.
   - 1 sample quiz.

   **Note:** If you have already imported the main database file, you only need to import `seed_content.sql`.

6. Configure the database connection.

   If your MySQL root account has a password, update the credentials in:

   `config/database.php`

   If you change the project folder name, update the `BASE_URL` configuration accordingly.

7. Open the application:

   `http://localhost/pc_mastery/`

8. Log in using the default administrator credentials:

   - **Username:** `admin`
   - **Password:** `Admin@12345`

   You must change the default password upon your first login.

9. Configure your institution's information:
   - Navigate to **Admin > System Settings**.
   - Set your school name.
   - Configure the certificate signatory.

---

## 2. PDF Certificates and Reports (Dompdf)

PC Mastery supports PDF generation through Dompdf.

### Option A: Using Composer (Recommended)

Open a terminal in the project directory and execute:

```bash
composer install
```

Composer will use `composer.json` to install Dompdf and its dependencies into the `vendor/` directory.

Once installed, certificates and PDF reports can be downloaded as actual PDF files.

### Option B: Without Composer

If Dompdf is not installed, the application will display print-ready layouts.

You can generate PDF copies using your browser:

1. Open the certificate or report.
2. Press **Ctrl + P**.
3. Select **Save as PDF** as the destination.
4. Save the generated file.

All other application features remain functional without Dompdf.

---

## 3. System Features and Modules

| Module | Administrator Features | Student Features |
|---|---|---|
| **Accounts** | Student management (CRUD), account activation/deactivation, password reset, profile viewing | Registration, profile management, password changes, profile picture |
| **PC Parts** | Parts and category management (CRUD) | 16 lessons, study progress tracking, component identification game |
| **Reviewer** | Topic management, PDF and image uploads, secure file delivery | Read learning materials, view PDFs, download permitted files, track progress |
| **Quizzes** | PDF-based quizzes, interactive quiz builder with 6 question types, review and grading | Timed quizzes, autosave, instant results, quiz history |
| **PC Assembly** | Assembly module configuration, step and component management, passing score settings | Guided practice with hints, assessments, server-validated drag-and-drop assembly |
| **Certificates** | Certificate listing, searching, revocation and restoration | View, print, download PDF, public certificate verification |
| **Analytics** | Filters, 5 charts, individual student reports, PDF and Excel exports | Learning progress monitoring and configurable leaderboard |
| **System Management** | System settings, announcements, activity logs | Notification center |

---

## 4. Security Features

PC Mastery implements multiple security measures to protect user accounts, application data, and uploaded files.

- **PDO Prepared Statements** – Helps prevent SQL injection attacks.
- **Password Hashing** – Uses bcrypt for secure password storage.
- **CSRF Protection** – CSRF tokens are implemented on all POST requests, including AJAX requests through request headers.
- **Role-Based Access Control** – Enforces administrator and student permissions on every protected page.
- **Session Security** – Session IDs are regenerated after successful login.
- **Login Protection** – Includes login attempt lockout mechanisms.
- **File Upload Validation** – Uploaded files are validated by extension and MIME type.
- **Randomized Filenames** – Prevents predictable uploaded file paths.
- **Protected File Storage** – Direct web access to `uploads/pdf` and `uploads/reviewers` is denied.
- **Secure File Delivery** – Files are served through `api/file.php` after permission checks.
- **Script Execution Protection** – Uploaded scripts are prevented from executing.
- **Server-Side Validation** – Quiz scoring and certificate issuance are processed and validated on the server.

---

## 5. Additional Notes and Implementation Details

### Excel Export
The Excel export feature generates HTML tables using the `.xls` file extension.

These files can be opened using Microsoft Excel or LibreOffice without requiring additional libraries.

### PC Assembly Simulation

The assembly module features a visually interactive motherboard and tower case, rendered using SVG.

Important assembly mechanics include:

- Components appear in their designated positions after successful installation.
- The tower case must be opened before assembly can proceed.
- Thermal paste must be applied after installing the CPU.
- The motherboard is transferred into the tower case at Step 8.
- Component zone labels are visible in Practice mode but hidden in Assessment mode.

### Assembly Scoring System

The assembly assessment uses an attempt-based scoring mechanism:

| Attempt | Score |
|---|---|
| First correct attempt | 100% of the item's points |
| Second correct attempt | 50% of the item's points |
| Third attempt and subsequent attempts | 0 points |

The default passing score is **80%**.

### Certificate Issuance

Certificates are awarded only after a student successfully passes the **Assessment** mode of the PC Assembly module.

- **Practice Mode:** Designed for learning and familiarization. Does not issue certificates.
- **Assessment Mode:** Evaluates the student's assembly skills and awards a certificate upon achieving the required passing score.

Certificates can be viewed, printed, and downloaded. Their authenticity can also be checked through the public verification page:

`http://localhost/pc_mastery/verify.php`

---

## 6. Default Configuration Summary

| Configuration | Default Value |
|---|---|
| Project Folder | `pc_mastery` |
| Database Name | `pc_mastery_db` |
| Default Username | `admin` |
| Default Password | `Admin@12345` |
| PHP Requirement | PHP 8.1+ |
| Database | MySQL |
| Web Server | Apache |
| UI Framework | Bootstrap 5 |
| Chart Library | Chart.js |
| PDF Generator | Dompdf (optional) |
| Local Development Environment | XAMPP |

---

## 7. Important Reminders

- Change the default administrator password immediately after your first login.
- Configure the database credentials before running the application if your MySQL setup differs from the default XAMPP configuration.
- Import both SQL files in the correct order when setting up a fresh installation.
- Install Dompdf through Composer if you want direct PDF downloads.
- Configure your school information and certificate signatory before issuing student certificates.
- Back up your database and uploaded files regularly.

---

**PC Mastery – Learn, Practice, and Master PC Hardware Assembly.**