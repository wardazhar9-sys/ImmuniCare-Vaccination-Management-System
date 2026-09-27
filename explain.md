# ImmuniCare — Complete System Guide

## 1. What this project is

ImmuniCare is a child vaccination management system with three protected portals:

- **Parent Portal** — manages children, books appointments, follows schedules, and views vaccination history.
- **Hospital Portal** — manages appointment requests, creates slots and schedules, and records vaccination outcomes.
- **Admin Portal** — manages users, hospitals, vaccines, vaccine doses, inventory, bookings, schedules, records, reports, and support messages.

The public website provides the product introduction, vaccine information, hospital listing, contact form, privacy page, and terms page.

The system uses:

- PHP 8.2 or newer.
- MySQLi only.
- MariaDB/MySQL with InnoDB.
- HTML/CSS/vanilla JavaScript.
- Server-side sessions and role-based access.
- CSRF-protected POST forms.
- Prepared SQL statements for request-driven data.

PDO is not used.

---

## 2. Production versus testing files

Only production application files belong in:

```text
/Users/tahir/Desktop/Wifi/vacine/Child Vaccination Management System
```

Deployment files are grouped under:

```text
setup/
```

The test suite is intentionally outside the production application:

```text
/Users/tahir/Desktop/Wifi/vacine/test_suite
```

The test suite contains PHPUnit, Playwright, Docker test services, fixtures, coverage output, and test-only dependencies. It is not required on the production web server.

---

## 3. One-command local setup

### Windows XAMPP

Copy the project into:

```text
C:\xampp\htdocs\Child Vaccination Management System
```

Start the project from Command Prompt:

```bat
cd /d "C:\xampp\htdocs\Child Vaccination Management System"
setup\start-xampp.bat
```

The Windows launcher:

1. Finds XAMPP PHP.
2. Starts XAMPP MySQL if it is not already responding.
3. Waits for MySQL.
4. Creates the database if needed.
5. Imports `setup/database/install.sql` on a new database.
6. Applies pending migrations.
7. Starts PHP on port `8080`.
8. Opens the application.

Open:

```text
http://127.0.0.1:8080/index.php
```

The launcher intentionally uses PHP’s built-in local server instead of Apache port 80. This avoids conflicts with IIS, another Apache installation, Skype, or another service already using port 80.

If XAMPP is not installed at `C:\xampp`:

```bat
set "XAMPP_ROOT=D:\xampp"
setup\start-xampp.bat
```

If MySQL uses another port:

```bat
set "DB_PORT=3307"
setup\start-xampp.bat
```

If the XAMPP root account has a password:

```bat
set "DB_PASSWORD=your_password"
setup\start-xampp.bat
```

### macOS XAMPP

From the production application directory:

```sh
./setup/start-xampp.sh
```

The default XAMPP path is:

```text
/Applications/XAMPP
```

Override it if necessary:

```sh
XAMPP_ROOT="/custom/XAMPP/path" ./setup/start-xampp.sh
```

The local macOS launcher uses XAMPP MySQL and PHP on port `8080`.

### Docker production deployment

From the `setup` directory:

```sh
cp .env.example .env
```

Set strong values in `.env`, then run:

```sh
docker compose -f docker-compose.production.yml up -d --build
```

The production Docker entrypoint:

- waits for MariaDB;
- creates the configured database;
- imports the hardened installer on first startup;
- applies pending migrations on later startups;
- starts Apache and PHP.

The database is stored in a persistent Docker volume. Back it up before upgrades.

### Non-Docker PHP hosting

Set these environment variables:

```text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASSWORD
APP_TIMEZONE
APP_ENV
```

Run once:

```sh
php setup/scripts/deploy.php
```

Then point Apache or Nginx/PHP-FPM to the production application directory.

---

## 4. Database setup and migrations

The canonical database installer is:

```text
setup/database/install.sql
```

The database name used by default is:

```text
vaccination_management_system
```

The numbered migrations are:

```text
setup/database/migrations/001_harden_schema.sql
setup/database/migrations/002_domain_expansion.sql
setup/database/migrations/003_reporting_preferences.sql
setup/database/migrations/004_booking_slots.sql
```

The migrations add:

- account status;
- hospital verification;
- child archival;
- booking timestamps and slot relation;
- booking-to-schedule-to-record relationships;
- vaccine doses;
- hospital hours/holidays/slots/inventory tables;
- notification links and read timestamps;
- email verification and password reset tokens;
- authentication events;
- API tokens;
- notification outbox;
- user preferences;
- report exports;
- audit logs;
- contact messages.

For an existing database, use:

```sh
php setup/scripts/migrate.php
```

The migration runner uses the `schema_migrations` table and skips versions already applied.

Always back up an existing database before applying migrations.

---

## 5. Environment and database connection

The database connection is configured in:

```text
config/db.php
```

Supported values:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=vaccination_management_system
DB_USER=root
DB_PASSWORD=
APP_TIMEZONE=Asia/Karachi
APP_ENV=local
```

For production:

- do not use MySQL root;
- use a dedicated least-privilege user;
- use a strong password;
- use HTTPS;
- set `APP_ENV=production`;
- do not display database errors to users.

In local mode, password-recovery links are displayed as a local preview because real email delivery is deferred.

---

## 6. Public website

### Home

File:

```text
index.php
```

The homepage contains:

- ImmuniCare branding.
- Smart vaccination management badge.
- Hero message.
- Get Started and Learn More actions.
- Vaccine scheduling feature.
- Secure records feature.
- Trusted hospital feature.
- Reminder feature.
- Service explanation.
- How-it-works section.
- Registration calls to action.
- Footer links.

The hero image is:

```text
assets/images/immunicare-hero-visual.png
```

### Public vaccine catalog

File:

```text
vaccines.php
```

This page displays:

- vaccine name;
- description;
- recommended age group;
- dose number;
- availability;
- client-side search.

The page is informational. Booking is performed inside the Parent Portal, where server-side availability is enforced.

### Public hospital directory

File:

```text
hospitals.php
```

Displays active hospitals with:

- name;
- location;
- city;
- phone;
- active status.

Only hospitals with active operational status are listed publicly.

### About page

File:

```text
about.php
```

Explains:

- the vaccination-management problem;
- the ImmuniCare concept;
- Parent Portal;
- Hospital Portal;
- vaccination records;
- product vision.

### Contact page

File:

```text
contact.php
```

The contact form stores:

- name;
- email;
- subject;
- message;
- status;
- created timestamp.

Messages can be managed by Admin under:

```text
Admin/contact_messages.php
```

### Privacy and terms

Files:

```text
privacy.php
terms.php
```

These pages explain privacy, role-limited access, security expectations, and acceptable system use.

---

## 7. Authentication

### Login

File:

```text
login.php
```

Login behavior:

1. User submits email and password.
2. Server validates the CSRF token.
3. User is loaded with a prepared MySQLi query.
4. Password is checked using `password_verify`.
5. Account status must be `Active`.
6. Session ID is regenerated.
7. User is redirected by role.

Redirects:

```text
parent   -> Parent/dashboard.php
hospital -> Hospital/dashboard.php
admin    -> Admin/dashboard.php
```

The shared session uses:

- explicit session name;
- HttpOnly cookie;
- SameSite cookie;
- strict session mode;
- root cookie path;
- eight-hour garbage-collection lifetime.

### Registration

File:

```text
register.php
```

Parent registration requires:

- full name;
- email;
- password;
- password confirmation;
- account type.

Hospital registration additionally requires:

- phone;
- city;
- address;
- location.

Hospital registration creates:

- an active user account;
- a pending hospital profile;
- an email verification token;
- a local notification-outbox event.

Hospital approval is controlled by Admin.

### Password recovery

Files:

```text
forgot_password.php
reset_password.php
verify_email.php
```

In local mode:

- the recovery token is generated;
- it is stored hashed;
- a local recovery link is displayed;
- the link expires;
- the link is single-use.

In production:

- the token is placed in the outbox;
- real email delivery requires a future SMTP/provider integration;
- the token is not displayed in the UI.

SMS, WhatsApp, MFA, and production email are intentionally deferred.

---

## 8. Parent Portal

All Parent pages use:

```text
Parent/sidebar.php
includes/portal_header.php
```

### Parent dashboard

File:

```text
Parent/dashboard.php
```

Shows:

- registered children;
- completed vaccinations;
- upcoming appointments;
- vaccination progress;
- next scheduled vaccination;
- quick actions;
- notification modal.

Progress is based on linked schedule and vaccination records rather than unrelated aggregate rows.

### My Children

File:

```text
Parent/children.php
```

Actions:

- add child;
- edit child in a modal;
- archive child;
- view profile details.

Child fields:

- name;
- date of birth;
- gender;
- blood group;
- address.

Edit behavior:

- stays on the same page;
- opens a modal;
- fills current values;
- submits through a CSRF-protected POST;
- validates that the child belongs to the current Parent.

Children with medical history are archived rather than physically deleted.

### Vaccines

File:

```text
Parent/vaccines.php
```

Shows:

- vaccine description;
- age group;
- dose;
- availability.

Unavailable vaccines do not display a booking action.

### Book Appointment

File:

```text
Parent/book_appointment.php
```

Booking requires:

- a child owned by the Parent;
- an available vaccine;
- an open hospital slot;
- future date/time represented by that slot.

The Parent does not manually choose arbitrary hospital dates or times.

Slot cards display:

- hospital;
- city;
- date;
- time;
- remaining capacity.

If a hospital creates no slots, the Parent sees no available appointment option.

The server rechecks:

- child ownership;
- vaccine availability;
- hospital status;
- slot status;
- slot capacity;
- duplicate appointment conflicts.

### My Bookings

File:

```text
Parent/bookings.php
```

Shows:

- child;
- vaccine;
- hospital;
- date;
- time;
- status.

Parent actions:

- cancel pending/approved booking;
- reschedule by selecting another open hospital slot.

Cancellation releases the reserved slot capacity.

Rescheduling:

- rejects closed/full slots;
- checks child conflicts;
- releases the old slot;
- reserves the new slot;
- returns the booking to hospital approval.

### Vaccination Schedule

File:

```text
Parent/schedule.php
```

Shows:

- child;
- vaccine;
- dose;
- date;
- time;
- scheduled/completed/missed status.

### Vaccination History

File:

```text
Parent/vaccination_history.php
```

Shows records created by Hospital:

- vaccine;
- dose;
- hospital;
- vaccination date;
- result status;
- remarks.

`Vaccinated` and `Not Vaccinated` are not treated as the same outcome.

### Parent profile

Files:

```text
Parent/profile.php
Parent/edit_profile.php
Parent/change_password.php
```

Profile features:

- view account details;
- update name/email;
- change password using current password;
- eight-character minimum password;
- CSRF protection;
- audit entry for changes.

---

## 9. Hospital Portal

All Hospital pages use:

```text
Hospital/sidebar.php
includes/portal_header.php
```

### Hospital dashboard

File:

```text
Hospital/dashboard.php
```

Shows:

- pending appointments;
- approved appointments;
- today’s appointments;
- recorded vaccinations;
- recent appointments;
- notification modal.

An inactive hospital cannot use operational pages.

### Hospital profile and onboarding

File:

```text
Hospital/profile.php
```

Hospital registration starts in pending status. Admin approval is required before the Hospital can manage appointments.

Profile displays:

- hospital name;
- phone;
- address;
- city;
- location;
- operational status;
- account status;
- creation date.

### Appointments

File:

```text
Hospital/appointments.php
```

Hospital can:

- approve pending appointments;
- reject pending appointments.

Approval/rejection:

- is scoped to the current Hospital;
- uses a valid state transition;
- creates a Parent notification;
- records an audit event.

### Appointment Slots

File:

```text
Hospital/slots.php
```

Hospital creates:

- date;
- time;
- capacity.

Capacity meaning:

```text
Capacity 2 = a maximum of two active bookings for that exact slot.
```

When capacity is reached:

- Parent cannot select the slot;
- direct requests are rejected;
- cancelled/rejected bookings release capacity.

### Vaccination Schedule

File:

```text
Hospital/schedule.php
```

Only approved appointments can receive a schedule.

Each schedule is linked to:

- booking;
- child;
- vaccine;
- dose;
- Hospital;
- date/time;
- schedule status.

Parent receives a schedule notification.

### Vaccinations

File:

```text
Hospital/vaccinations.php
```

Hospital records:

- vaccination date;
- vaccinated/not vaccinated result;
- remarks;
- staff attribution when available;
- vaccine dose relation.

The record, schedule, and booking updates are performed atomically.

If the result is not administered:

- the schedule becomes missed;
- the booking is not treated as a successful vaccination;
- Parent receives a notification.

---

## 10. Admin Portal

All Admin pages use:

```text
Admin/sidebar.php
includes/portal_header.php
```

### Admin dashboard

File:

```text
Admin/dashboard.php
```

Shows:

- Parent count;
- Hospital count;
- child count;
- vaccine count;
- booking count;
- pending/completed counts;
- vaccination records;
- recent bookings;
- notifications.

### Users

File:

```text
Admin/users.php
```

Admin can:

- search users;
- filter by role;
- edit name/email;
- activate/deactivate accounts.

Safety rules:

- Admin cannot deactivate itself;
- the last active Admin cannot be deactivated;
- role conversion is blocked to prevent orphaned Hospital profiles;
- every mutation requires CSRF and audit logging.

### Hospitals

File:

```text
Admin/hospitals.php
```

Admin can:

- search hospitals;
- view details;
- update contact/location data;
- approve/activate;
- deactivate/reject.

Activation updates verification status and verified timestamp.

### Children

File:

```text
Admin/children.php
```

Admin can:

- search children and Parents;
- view child details;
- update profile data;
- archive child records.

Medical-history records are not physically deleted.

### Vaccines

File:

```text
Admin/vaccines.php
```

Admin can:

- add vaccine;
- edit vaccine;
- set availability;
- search vaccine name, description, and age group.

### Vaccine Doses

File:

```text
Admin/vaccine_doses.php
```

Admin can define:

- dose number;
- recommended age in days;
- minimum interval;
- clinical source/version;
- active status.

### Vaccine versus Vaccine Dose

The **Vaccines** page defines the vaccine itself:

- vaccine name;
- description;
- general age group;
- global catalog availability;
- basic dose information.

Example:

```text
Polio
Protects children against poliovirus
Age group: At Birth - 5 Years
```

The **Vaccine Doses** page defines the dose series for that vaccine. One vaccine can have multiple dose definitions:

```text
Polio - Dose 1
Polio - Dose 2
Polio - Dose 3
Polio - Booster
```

### Vaccine dose fields

**Dose Number**

Identifies the position in the series, such as Dose 1, Dose 2, Dose 3, or Booster.

**Recommended Age**

The recommended age is stored as days from the child's date of birth.

Examples:

```text
42 days  = approximately 6 weeks
70 days  = approximately 10 weeks
270 days = approximately 9 months
```

The application can use this value to calculate when a dose becomes due.

**Minimum Interval**

The minimum number of days that must pass after the previous dose before the next dose is allowed.

Example:

```text
Minimum interval: 28 days
```

The next dose should not be booked before the 28-day interval is complete.

**Catch-up Rule**

Describes what should happen if a child misses the normal schedule. Examples include continuing from the last completed dose, applying a minimum interval, or requiring clinical review.

**Clinical Source/Version**

Records the medical source and version used for the schedule, such as a national immunization schedule or a WHO schedule revision. Vaccination rules should be reviewed when the clinical source changes.

### How the records connect

The intended vaccination flow is:

```text
Vaccine
  -> Vaccine Dose
  -> Child Due Date
  -> Hospital Slot
  -> Booking
  -> Vaccination Schedule
  -> Vaccination Record
```

These concepts are different:

- Vaccine availability describes whether the catalog item can be booked.
- Hospital inventory describes whether a specific hospital has stock.
- Vaccine dose describes which dose the child needs and when it is due.
- Hospital slot describes when the hospital can receive the child.

The dose-definition fields are stored by the system and are intended to drive due-date, interval, and catch-up validation. The clinical rules must be confirmed against the approved medical schedule before production use.

### Inventory

File:

```text
Admin/inventory.php
```

Admin manages hospital-specific:

- vaccine quantity;
- reorder level.

Inventory is separate from the global vaccine catalog availability.

### Bookings

File:

```text
Admin/bookings.php
```

Admin can inspect bookings and approve/reject pending requests. Completed status should come from the Hospital vaccination workflow rather than an arbitrary Admin click.

### Schedules

File:

```text
Admin/schedules.php
```

Admin can search and inspect schedules and update valid future schedule times where permitted.

### Vaccination Records

File:

```text
Admin/vaccination_records.php
```

Read-only/audited record listing with:

- child;
- Parent;
- vaccine;
- Hospital;
- vaccination date;
- status;
- remarks.

Medical-record corrections should create an audit event.

### Reports

File:

```text
Admin/reports.php
```

Exports booking activity as CSV and records the export in the audit log.

### Contact Messages

File:

```text
Admin/contact_messages.php
```

Admin can move support messages through:

- New;
- In Progress;
- Resolved;
- Spam.

### Admin profile

File:

```text
Admin/profile.php
```

Shows:

- Admin overview;
- role;
- active status;
- member date;
- email verification state;
- total users;
- total Hospitals;
- active children;
- vaccination records;
- editable name/email.

---

## 11. Notifications

Notifications are handled by:

```text
includes/portal_header.php
includes/app.php
notifications.php
api/v1/notifications.php
```

The portal header notification icon opens a modal. Users do not need to leave the current page.

The modal supports:

- unread indicator;
- notification list;
- related-record open action;
- mark one as read;
- mark all as read;
- close button;
- backdrop close;
- Escape-key close;
- mobile layout.

Events include:

- booking created;
- booking approved;
- booking rejected;
- booking cancelled;
- booking rescheduled;
- schedule created;
- vaccination recorded;
- missed vaccination;
- account status change;
- hospital status change.

Real email, SMS, WhatsApp, and MFA delivery are deferred integrations. Local recovery/outbox behavior remains testable without external credentials.

---

## 12. API

API files:

```text
api/v1/health.php
api/v1/bookings.php
api/v1/notifications.php
```

Authenticated endpoints use:

```http
Authorization: Bearer <token>
```

Health:

```http
GET /api/v1/health.php
```

Bookings:

```http
GET /api/v1/bookings.php
POST /api/v1/bookings.php
```

Notifications:

```http
GET /api/v1/notifications.php
POST /api/v1/notifications.php
```

The API uses the same ownership, availability, slot-capacity, and transaction rules as the web workflow.

---

## 13. Security behavior

The application includes:

- MySQLi prepared statements;
- CSRF tokens on POST forms;
- role checks;
- active-account checks;
- Parent-child ownership checks;
- Hospital ownership checks;
- secure password hashing;
- session regeneration on login;
- HttpOnly/SameSite session cookies;
- security headers;
- audit logs;
- generic database error messages;
- transaction-safe booking and vaccination workflows.

Production requirements:

- use HTTPS;
- use a dedicated database user;
- set strong database passwords;
- keep `APP_ENV=production`;
- do not expose local recovery previews;
- back up the database before migrations.

---

## 14. Common problems

### MySQL connection refused

Start XAMPP MySQL and verify port 3306:

```bat
C:\xampp\mysql\bin\mysqladmin.exe -h 127.0.0.1 -P 3306 -u root ping
```

### Existing database is old

Run:

```bat
setup\start-xampp.bat
```

This applies pending migrations. Back up first.

### Login returns to login page

Check:

- cookies are enabled;
- the application is accessed consistently through the same host;
- `users.status` exists and is `Active`;
- migrations completed;
- PHP session storage is writable.

### Hospital has no appointments

The Parent must book a slot belonging to the same active Hospital. A booking for another Hospital will not appear in that Hospital’s portal.

### No Parent slots appear

The Hospital must create an open slot first. Parent booking does not fall back to arbitrary date/time values.

### Empty local password-recovery message

Set local mode through the XAMPP launcher. Local mode displays a recovery preview link. Production mode queues recovery through the deferred outbox.

---

## 15. Important scope limitations

The following integrations are intentionally deferred:

- WhatsApp;
- SMS;
- MFA provider;
- production SMTP/email provider;
- production monitoring/alerting;
- PWA offline synchronization.

The local system still provides in-app notifications, local outbox records, and local password-recovery previews so the application workflow can be tested without those providers.

---

## 16. Vaccine doses and next-dose behavior

`vaccines` stores the general vaccine information. `vaccine_doses` stores the dose schedule for that vaccine:

- dose number;
- recommended age in days;
- minimum interval after the previous dose;
- clinical source/version;
- Active or Inactive status.

Dose definitions are managed inside **Admin Portal → Vaccines → Dose schedule management**. The old Vaccine Doses URL redirects to this section so existing bookmarks continue to work.

The Parent Portal does not show every dose as a free choice. For each child, the system:

1. checks completed `Vaccinated` records;
2. identifies the next dose number;
3. checks the child’s age against `recommended_age_days`;
4. checks the interval since the last vaccination;
5. shows the dose only when it is currently eligible.

The selected dose is stored as `vaccine_dose_id` on the booking, then copied to the hospital schedule and vaccination record. This prevents a parent from changing a dose ID manually or booking Dose 3 before Dose 2.

Existing legacy rows are backfilled from `vaccines.dose_number`. That column remains only for compatibility; new workflow decisions use `vaccine_doses`.

---

## 17. Inventory workflow

Inventory is maintained per hospital and vaccine in `hospital_inventory`.

Admin usage:

1. Open **Admin Portal → Inventory**.
2. Select the hospital and vaccine.
3. Enter the current quantity and reorder level.
4. Save the adjustment.

The current balance is also recorded in `inventory_transactions`. The ledger records opening balances, admin adjustments, and vaccination consumption.

When a hospital records a vaccination as **Vaccinated**:

- the inventory row is locked inside the same database transaction;
- the quantity must be at least 1;
- exactly one unit is deducted;
- a transaction ledger row is written;
- the vaccination, booking, schedule, stock change, and notification either all commit or all roll back.

When the status is **Not Vaccinated**, stock is not deducted. Duplicate vaccination records are rejected before inventory can be changed. A zero-stock vaccine cannot be recorded until Admin replenishes it.

The reorder level is a warning threshold. Inventory rows at or below that threshold display **Reorder required** and are included in inventory reports.

---

## 18. Reports

Open **Admin Portal → Reports** and select a report type:

- **Bookings** — parent, child, hospital, vaccine, dose, date, time, and booking status.
- **Vaccination records** — completed/missed result, dose, hospital, date, and remarks.
- **Inventory balances** — current quantity, reorder level, and stock warning.
- **Inventory transactions** — every opening balance, adjustment, and vaccination deduction.

Optional filters include date range, hospital, vaccine, dose number, and status where applicable. Click **Apply filters** to view results on the page, or **Download ... CSV** to export the same filtered result.

CSV exports are generated from prepared queries and recorded in the admin audit log. An empty report is a valid result and displays a clear no-records message rather than failing silently.

---

## 19. Demo scenario data

The migration `005_demo_scenario_data.sql` creates a repeatable local demo dataset. It rebuilds only accounts and operational rows belonging to the `demo.*@immunicare.local` users, while preserving unrelated valid data.

All demo accounts use:

```text
Password: TestPassword123!
```

Accounts:

- `demo.admin@immunicare.local`
- `demo.parent1@immunicare.local`
- `demo.parent2@immunicare.local`
- `demo.hospital.alpha@immunicare.local`
- `demo.hospital.beta@immunicare.local`

The demo includes:

- a fully vaccinated child with three completed Polio doses;
- a child with a previous dose and a scheduled next dose;
- a child with a pending first appointment;
- a child with a missed vaccination;
- a rejected appointment;
- multiple parents and hospitals;
- open, completed, cancelled, approved, rejected, and pending bookings;
- hospital slots with different capacities;
- hospital vaccine inventory, reorder levels, opening balances, and consumption ledger entries;
- unread/read notifications and pending outbox data;
- audit and report-export records.

Run the normal launcher after applying or updating the code:

```bat
setup\start-xampp.bat
```

The migration runner applies the demo migration once. Re-running it refreshes only the demo-owned scenario records.
