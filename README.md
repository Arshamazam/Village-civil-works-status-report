# PRSWSSP Daily Site Mobilization & Progress Reporting System

> **Client:** Punjab Rural Municipal Services Company (PRMSC)  
> **Project:** Punjab Rural Sustainable Water Supply & Sanitation Project (PRSWSSP)  
> **Target Output:** Automated Daily Site Mobilization Report (Form DSM-1) & Live Executive Dashboard  
> **Deployment Target:** Hostinger Business Shared Hosting (PHP 8.2+, MySQL/MariaDB, database queue, cron)

---

## 1. System Overview

The **PRSWSSP Daily Site Mobilization & Progress Reporting System** replaces paper and WhatsApp returns (Form DSM-1) with a validated digital daily return workflow. Field officers submit daily returns on smartphones (PWA), Tehsil in-charges review and approve them, and the automated rollup engine computes executive mobilization KPIs and emails a 3-page executive landscape PDF report to project leadership every reporting day at 14:00 PKT.

### Key Capabilities
- **Form DSM-1 Digital Capture:** Mobile-first PWA with offline caching (IndexedDB) and idempotent sync via `client_uuid`.
- **GPS Validation & Geofencing:** Device coordinates validated against site geofences (&plusmn;500m) with mandatory flags and remarks if outside site bounds.
- **Photo Evidence:** Device-side compression and EXIF/timestamp watermarking (1 to 5 photos per visit) with server-side thumbnail generation.
- **Tehsil Review & Cut-off:** Strict 13:00 PKT daily submission cut-off with automatic "Not Reported" (NR) marking; Tehsil In-charge review, returns with comments, and administrative unlocking.
- **Rollup Engine:** Computes consecutive idle days skipping gazetted holidays and Sundays; categorizes idle age bands (Watch 1–2d, Warn 3–4d, Escalate 5–6d, Critical 7+d); tracks completion priorities (A–D) against deadlines and countdown rings.
- **Live Executive Dashboard:** Fast (< 3s) dashboard with Chart.js donut and 14-day history trend, Leaflet interactive map, village register, and contractor/tehsil scorecards sorted worst-performers first.
- **Executive PDF Report (A4 Landscape):** 3-page mobilization report reproducing the official layout with server-generated SVG charts (Page 1: Executive Summary, Page 2: Site Mobilization Register, Page 3: Scorecards Annex).
- **Scheduled Email Dispatches:** 12:00 PKT reminder emails, 14:00 PKT report emails with attached PDF, delivery run auditing, and manual "Send Now" / "Resend" tools.

---

## 2. Default Seeded User Credentials

| Role | Email | Password | Scope |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@prmsc.punjab.gov.pk` | `Password123!` | All tehsils & administrative master data |
| **HQ Manager** | `hq@prmsc.punjab.gov.pk` | `Password123!` | Read-only executive dashboard & PDF reports |
| **Tehsil In-charge** | `incharge@prmsc.punjab.gov.pk` | `Password123!` | Assigned tehsils (review, approve, return, unlock) |
| **Field Officer** | `field@prmsc.punjab.gov.pk` | `Password123!` | Assigned tehsils (daily return Form DSM-1 entry) |

*(Note: Passwords are encrypted with bcrypt. Accounts are locked for 15 minutes after 5 consecutive failed attempts).*

---

## 3. Daily Cron Automation Schedule (`Asia/Karachi` PKT)

All background jobs run in shared hosting via `schedule:run` without requiring long-running server daemons:

| Time (PKT) | Artisan Command | Description |
| :--- | :--- | :--- |
| **12:00** | `returns:remind` | Sends reminder emails to field officers with pending sites for today. |
| **13:00** | `returns:mark-not-reported` | Closes entry window; marks all unsubmitted ongoing sites as Not Reported (NR). |
| **13:30** | `progress:rollup` | Computes idle days, priority countdowns, scorecards, and caches `daily_summaries`. |
| **14:00** | `report:daily` | Builds 3-page A4 landscape PDF and queues emails to active report recipients. |
| **Every min** | `queue:work --stop-when-empty --max-time=50` | Processes queued emails and thumbnail jobs on shared hosting cron. |

---

## 4. Hostinger Business Shared Hosting Deployment Guide

### Step 1: Prepare Database & Domain in hPanel
1. Log into Hostinger hPanel and navigate to **Databases &rarr; Management**.
2. Create a new MySQL Database (e.g., `u123456789_prswssp`) and User with full permissions.
3. Under **Domains &rarr; Manage**, create or select your domain or subdomain (e.g., `reports.prmsc.punjab.gov.pk`) and enable **Let's Encrypt SSL**.

### Step 2: Deploy Application Code
Deploy code to `/home/u123456789/domains/yourdomain/` via SSH or Git:
```bash
cd /home/u123456789/domains/yourdomain/
git clone <repository_url> .
composer install --no-dev --optimize-autoloader
```

### Step 3: Document Root Configuration
In Hostinger shared hosting, point the web root to `/public`:
- In hPanel, go to **Websites &rarr; Manage &rarr; General Settings** and set **Document Root** to `/public`.
- Alternatively, move contents of `public/` into `public_html/` and update paths in `index.php`.

### Step 4: Environment Configuration (`.env`)
Create `.env` based on `.env.example`:
```ini
APP_NAME="PRSWSSP Progress System"
APP_ENV=production
APP_KEY=base64:... (run php artisan key:generate)
APP_DEBUG=false
APP_URL=https://reports.prmsc.punjab.gov.pk
APP_TIMEZONE=Asia/Karachi

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u123456789_prswssp
DB_USERNAME=u123456789_user
DB_PASSWORD=your_secure_db_password

QUEUE_CONNECTION=database
SESSION_DRIVER=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=reports@prmsc.punjab.gov.pk
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="reports@prmsc.punjab.gov.pk"
MAIL_FROM_NAME="PRMSC Daily Reports"
```

### Step 5: Run Migrations, Seeders & Storage Symlink
Run the following commands over SSH:
```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 6: Configure Single Cron Job in Hostinger hPanel
Under **Advanced &rarr; Cron Jobs** in hPanel, create one cron job executing every minute:
- **Type:** Custom
- **Command:**
```sh
/usr/bin/php /home/u123456789/domains/yourdomain/artisan schedule:run >> /dev/null 2>&1
```
- **Interval:** `* * * * *` (Every minute)

This single cron job triggers Laravel's internal scheduler in `routes/console.php` which executes the 12:00, 13:00, 13:30, and 14:00 PKT commands and drains the database queue without any VPS background worker processes.

---

## 5. Automated Tests & Quality Assurance

The application features comprehensive automated tests verifying:
- Master data CRUD, Excel import preview/commit, and Excel export
- Form DSM-1 submission rules, Active/Dormant validations, geofence checks (&plusmn;500m), and IndexedDB sync API
- Idle days calculator skipping Sundays and holidays; traffic-light idle bands (Watch, Warn, Escalate, Critical)
- Scorecard service sorting worst-performers first
- Rollup engine execution and fast (< 3s) cached summary loading
- 3-page A4 landscape PDF generation and SVG chart rendering
- Daily report email dispatch and 12:00 PKT reminder alerts
- Tehsil-boundary security policies, 5-attempt account lockout, and role permissions

Run the complete test suite:
```bash
php artisan test
```
All 43 tests pass cleanly with 148 assertions.
