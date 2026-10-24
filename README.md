# Veridian

**A web-based Employee Leave Request System** built with PHP, MySQL, HTML, CSS, and JavaScript.

Veridian lets employees request time off and track their balances. Administrators review requests, check who is away, and manage employee accounts.

## Requirements

- PHP 8.2 or newer with PDO MySQL, mbstring, and fileinfo enabled.
- MySQL or MariaDB.
- Apache with rewrite support and `.htaccess` enabled. XAMPP is suitable for local use.

## Run locally with XAMPP

1. Clone or download this repository into `C:\xampp\htdocs\veridian`.
2. Copy `config.example.php` to `config.php`.
3. Set the database connection in `config.php`. The example uses local XAMPP defaults: database `veridian`, user `root`, and an empty database password.
4. Start Apache and MySQL in XAMPP.
5. Open **http://localhost/veridian/**. Do not open PHP files directly from your file manager.
6. On a fresh installation, complete the administrator setup. The local setup creates the database tables when the database user has permission. Alternatively, create a database and import `schema.sql` first.
7. Sign in, open **Create Accounts**, select **Employee**, and create the employee accounts you need.

The landing page is **http://localhost/veridian/landing.html**.

`schema.sql` contains table definitions only. It does not contain employee records, passwords, or sample accounts. Existing installations should follow the upgrade notes in [Operations](docs/OPERATIONS.md); importing the schema alone does not add missing columns to existing tables.

## Local demonstration accounts

These are the sample credentials used in the original local project. They are intentionally documented for demonstrations. **They are not automatically created when you clone the repository.** On a new database, create the first administrator during setup and add employees through Create Accounts if you want to reproduce these examples.

| Role | Name / Username | Email | Demo password |
| --- | --- | --- | --- |
| Primary administrator | Admin A / `AdminA` | `adminalas@veridian.ph` | `AdminA123` |
| Employee | `ITAlas` | `employee_alas@veridian.ph` | `alas123` |
| Employee | `ITZafra` | `employee_zafra@veridian.ph` | `zafra123` |
| Employee | `ITTsukishima` | `employee_tsukishima@veridian.ph` | `tsukishima123` |

Sign in with the **username**, not the email address. Usernames are case-insensitive.

New passwords must contain **8–16 characters**; special characters are optional. The original `alas123` account has an older seven-character demo password. A fresh installation must use an eight-character or longer replacement when creating that account.

Use different, unique passwords before making an installation public. These demonstration passwords are public and should not protect real employee records.

## Employee guide

1. **Sign in** using your assigned username and password.
2. **Overview** shows your request counts and available leave balances.
3. **Request Leave**: choose a leave type, enter the number of weekdays, choose a start date, and add a brief reason. Veridian calculates the end date and checks your available balance.
4. **My Requests** shows your request history, decisions, and administrator remarks. You may cancel pending requests.
5. **Approved Leaves** separates confirmed requests from pending requests.
6. **Leave Calendar** shows your own approved leaves and current leave periods.
7. **Leave Balances** shows your annual allowance, approved usage, pending reservations, and available balance by year.
8. **Account Settings** lets you change your password and profile picture.

Keep sensitive medical or personal details out of request reasons unless required by your organization.

## Administrator guide

- **Leave Approvals**: open a pending request's details, optionally add remarks, and approve or reject it.
- **Approved Leaves**: view confirmed leave records separately.
- **Leave Calendar**: see all employees' approved leaves and current leave periods.
- **Employees**: search and sort employee records and view remaining balances.
- **Create Accounts**: select **Employee** to show the employee creation form.
- **Leave Balances**: review allowances and usage, select a year, and adjust full-day allowances. An allowance cannot be reduced below already committed days.

### Admin A permissions

The first administrator created during setup is the primary administrator—Admin A in the original project. This account can also:

- Select **Administrator** on Create Accounts to create another administrator.
- Reset an employee's password from the Employees list.

Other administrators can review leave requests, manage allowances, and create employees, but cannot create administrators or reset employee passwords.

### Forgotten employee password

1. Admin A finds the employee in **Employees** and clicks **Reset Password**.
2. A pop-up shows the employee username and a new temporary password over a blurred background.
3. Admin A tells the employee the temporary password **in person**. No email is sent.
4. Click the **X** to close the pop-up. The password is shown once and removed from the page when closed.
5. The employee signs in and must choose a different password before continuing.

Existing passwords cannot be viewed: the database stores password hashes. A reset invalidates the employee's existing sign-ins and remembered sessions.

## Leave policy

- **Annual:** 15 weekdays per calendar year by default.
- **Sick:** 15 weekdays per calendar year by default.
- **Emergency:** 7 weekdays per calendar year by default.
- **Personal:** uses the Annual allowance.
- **Fast Leave:** a separate allowance of **3 uses per calendar year**. Each use covers **one half-day (0.5 day)** and is not restricted to the afternoon. Select Fast Leave and one date; the form reserves one use.

Administrators can adjust Annual, Sick, and Emergency allowances per employee and year. Fast Leave has a fixed three-use limit.

Only **Monday–Friday** is counted. Public holidays are not configured and count when they fall on a weekday. Weekend starts are rejected. Leave dates must start today or later, and a request cannot span more than 365 calendar days.

Pending requests reserve credits. Rejected and cancelled requests release them. Requests cannot overlap another pending or approved request for the same employee. Cross-year requests allocate usage to the appropriate calendar year. There is no monthly accrual or carry-over.

Fast Leave still requires administrator approval. Other special leave types are handled separately by the administrator.

## Sign-in behavior

- Regular navigation, searches, filters, sorting, and calendar controls keep you signed in.
- Without **Remember Me**, visiting the landing page or sign-in entry signs you out.
- **Remember Me** can retain a sign-in for up to 30 days. Explicit Sign Out revokes it.
- Closing a page sends a best-effort close notification with a five-second reload grace period. A browser crash, force quit, or blocked notification can prevent close detection.

## Landing-page preview

The landing page uses the sample employee name **ITAlas**, but its displayed balances, requests, dates, and decisions are **fictional static examples**. It does not display that account's actual leave records.

## Hosting and GitHub

This repository contains application source and an empty database schema. It excludes `config.php`, database exports, local test records, and XAMPP data.

For hosted use, create a database, import the schema, configure the connection, use HTTPS, and set a strong `setup_key` before exposing first-time setup. Keep credentials in private configuration or environment variables.

Supported configuration variables are `VERIDIAN_DB_HOST`, `VERIDIAN_DB_PORT`, `VERIDIAN_DB_NAME`, `VERIDIAN_DB_USER`, `VERIDIAN_DB_PASSWORD`, and `VERIDIAN_SETUP_KEY`.

**Render deployment is not configured yet.** Running this PHP application on Render still requires a Docker configuration, a securely configured external database connection, and a database import. Pushing this repository to GitHub does not automatically publish a working site.

See [Operations](docs/OPERATIONS.md) for additional installation and maintenance details.
