# Perioda — XAMPP Setup Guide

## 1. Place the project folder
Copy the whole `perioda` folder into your XAMPP `htdocs` directory:

```
C:\xampp\htdocs\perioda\
```

(On Mac: `/Applications/XAMPP/htdocs/perioda/`)

## 2. Start Apache and MySQL
Open the **XAMPP Control Panel** and click **Start** next to both:
- Apache
- MySQL

## 3. Create the database
1. Open **phpMyAdmin**: http://localhost/phpmyadmin
2. Click **Import** in the top menu.
3. Choose the file: `perioda/database/perioda.sql`
4. Click **Go**.

This creates the `perioda` database with all 6 tables, a default admin account, and 8 sample health tips.

## 4. Open the app
Go to: **http://localhost/perioda/**

## 5. Login credentials

**Default Admin Account**
- Email: `admin@kathford.edu.np`
- Password: `Admin@123`

**Normal Users**
Register a new account from the landing page. The email must end in `@kathford.edu.np`.

## 6. Database connection settings
If your MySQL setup uses a different username/password than the XAMPP default
(root, no password), edit `perioda/includes/db.php`:

```php
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "perioda";
```

## Project structure
```
perioda/
├── index.php              Landing page
├── login.php / register.php / logout.php
├── user/                  Pages for logged-in normal users
│   ├── dashboard.php
│   ├── period.php         Period tracker (CRUD)
│   ├── calendar.php
│   ├── symptoms.php       (CRUD)
│   ├── moods.php          (CRUD)
│   ├── notes.php          (CRUD)
│   ├── health-tips.php    (browse)
│   └── profile.php
├── admin/                 Pages for the admin account
│   ├── dashboard.php
│   ├── users.php          View/disable users
│   ├── records.php        Aggregate record counts (privacy-safe)
│   └── health-tips.php    (CRUD)
├── includes/               Shared PHP: db connection, auth guards,
│                           cycle-calculation helpers, shared layout
├── assets/css/style.css   All styling
├── assets/js/             Client-side validation + small helpers
└── database/perioda.sql   Full schema + seed data
```

## What was tested
The full app was run and tested end-to-end (registration, login/logout,
period/symptom/mood/note CRUD, cycle estimate calculations, calendar
rendering, admin login, admin health-tip CRUD, disabling a user account,
and record-ownership checks preventing one user from editing another
user's data) before being packaged here.

## Notes on the design decisions
- **Privacy**: every private-record query filters by `user_id`, and every
  edit/delete re-checks that the record actually belongs to the logged-in
  user before touching it (see `period.php`, `symptoms.php`, etc.).
- **Security**: passwords are hashed with `password_hash()` / verified with
  `password_verify()`, all SQL uses prepared statements, and all output is
  escaped with `htmlspecialchars()` via the `h()` helper.
- **Validation**: the same rules (name letters-only, `@kathford.edu.np`
  email, 10-digit phone starting with 97/98, 8+ character password with
  letters and numbers) are enforced in both `assets/js/validation.js`
  (UX) and the PHP files (real security).
- **Estimates only**: cycle predictions are clearly labelled "Estimated"
  and computed from a simple average of your own past records — never
  presented as a medical diagnosis.
