# OJT DTR Monitoring Management System

A web-based **Daily Time Record (DTR) Monitoring System** for On-the-Job Trainees (OJT), built with plain PHP, MySQL (PDO), and Tailwind CSS. It allows trainees to log attendance via QR code scanning and enables administrators to manage trainees, review records, and generate printable DTR reports.

---

## ✨ Features

### 👷 OJT Trainee Portal
- Secure login with session-based authentication
- QR code-based time-in / time-out logging (AM & PM split)
- Real-time DTR dashboard showing daily records, total hours, and status
- View accumulated hours vs. required hours progress
- Settings tab to update Training Supervisor name
- Prompted to set Training Supervisor on first login if not yet configured

### 🛡️ Admin Portal
- Separate admin login with role-based access control
- Manage trainees — add, view, and edit trainee accounts
- Review and edit individual attendance logs with reason tracking (audit trail)
- Reports section — select a trainee and date range to preview DTR
- Print-optimized DTR report with trainee and supervisor signature section

### 🔒 Security
- Passwords hashed with **bcrypt**
- PDO prepared statements (SQL injection protection)
- `.htaccess` hardening: security headers, PHP flags, blocked sensitive files
- Session cookie hardening (`httponly`, `samesite`, `strict_mode`)

---

## 🗂️ Project Structure

```
/
├── index.php                  # Entry point — redirects to OJT login
├── terms.php                  # Terms and conditions page
├── .htaccess                  # Root security rules
├── .gitignore
│
├── admin/
│   ├── login.php              # Admin login page
│   ├── dashboard.php          # Admin dashboard (trainees, attendance, reports)
│   └── report-print.php       # Print-optimized DTR report
│
├── ojt/
│   ├── login.php              # OJT trainee login page
│   └── dashboard.php          # OJT trainee dashboard
│
├── api/
│   ├── auth.php               # Login, logout, session endpoints
│   ├── attendance.php         # Add/get attendance logs
│   ├── admin.php              # Admin-only endpoints (reports, trainee list)
│   ├── edit.php               # Edit attendance with audit logging
│   └── .htaccess              # Restricts API to GET/POST only
│
├── includes/
│   ├── db.php                 # PDO database connection
│   ├── auth.php               # Session helpers & access guards
│   └── .htaccess              # Blocks all direct browser access
│
├── assets/
│   ├── css/
│   │   ├── input.css          # Tailwind source
│   │   └── style.css          # Compiled CSS output
│   ├── js/
│   │   ├── app.js             # OJT trainee dashboard logic
│   │   ├── admin.js           # Admin dashboard logic
│   │   ├── ojt-dashboard.js   # OJT QR scanning & UI logic
│   │   ├── jsqr.min.js        # QR code reader library
│   │   └── qrcode.min.js      # QR code generator library
│   └── img/
│       └── prclogo.png        # Application logo
│
└── database.sql               # Full DB schema + default admin seed
```

---

## 🗄️ Database Schema

| Table | Description |
|---|---|
| `users` | Stores admin and OJT trainee accounts (`role`: `admin` / `ojt`) |
| `attendance` | Per-day DTR records with AM/PM in-out times, total hours, status |
| `edit_logs` | Audit trail for every attendance record edit made by admin |

---

## ⚙️ Installation (Local — XAMPP)

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (PHP 8.0+, MySQL 5.7+ / MariaDB)
- Git

### Steps

1. **Clone the repository** into your XAMPP `htdocs` folder:
   ```bash
   git clone https://github.com/christianloyd/DTR-Monitoring.git "c:/xampp/htdocs/Projects/OJT DTR Monitoring Management System"
   ```

2. **Import the database:**
   - Open [phpMyAdmin](http://localhost/phpmyadmin)
   - Create a new database named `ojt_dtr_system` (or let the SQL do it)
   - Import `database.sql`

3. **Configure the database connection** in `includes/db.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'ojt_dtr_system');
   define('DB_USER', 'root');      // Change for production
   define('DB_PASS', '');          // Change for production
   ```

4. **Start Apache and MySQL** in the XAMPP Control Panel.

5. **Open the app** in your browser:
   ```
   http://localhost/Projects/OJT%20DTR%20Monitoring%20Management%20System/
   ```

---

## 🔑 Default Admin Account

| Field | Value |
|---|---|
| Email | `admin@ojt.com` |
| Username | `sysadmin` |
| Password | `Admin@1234` |

> ⚠️ **Change this password immediately after your first login in production.**

---

## 🚀 Deployment Checklist

Before going live on a production server:

- [ ] Change `DB_USER` and `DB_PASS` in `includes/db.php` to a dedicated MySQL user
- [ ] Enable SSL/HTTPS on the server
- [ ] Uncomment the HTTPS redirect in `.htaccess`
- [ ] Uncomment `Strict-Transport-Security` (HSTS) header in `.htaccess`
- [ ] Uncomment `session.cookie_secure 1` in `.htaccess`
- [ ] Delete or restrict `migrate_am_pm.php` if it still exists
- [ ] Set `log_errors` path in `php.ini` / `.htaccess` to a writable server log

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8+ (vanilla, no framework) |
| Database | MySQL / MariaDB via PDO |
| Frontend | HTML, Vanilla JS, Tailwind CSS |
| QR Scanning | [jsQR](https://github.com/cozmo/jsQR) |
| QR Generation | [qrcode.js](https://github.com/davidshimjs/qrcodejs) |
| Server | Apache (XAMPP / any Apache host) |

---

## 📄 License

This project is private. All rights reserved.
