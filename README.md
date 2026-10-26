# Veridian

**A web-based Employee Leave Request System** built with PHP, MySQL, HTML, CSS, and JavaScript.

Veridian lets employees request time off and track their balances. Administrators review requests, check who is away, and manage employee accounts.

## Requirements

- PHP 8.2 or newer with PDO MySQL, mbstring, and fileinfo enabled.
- MySQL or MariaDB.
- Apache with rewrite support and `.htaccess` enabled. XAMPP is suitable for local use.

## Run locally with XAMPP

1. Clone or download this repository into `C:\xampp\htdocs\veridian`.
2. Copy `config/config.example.php` to `config/config.php`.
3. Set the database connection in `config/config.php`. The example uses local XAMPP defaults: database `veridian`, user `root`, and an empty database password.
4. Start Apache and MySQL in XAMPP.
5. Open **http://localhost/veridian/**. Do not open PHP files directly from your file manager.
6. On a fresh installation, complete the administrator setup. The local setup creates the database tables when the database user has permission. Alternatively, create a database and import `database/schema.sql` first.
7. Employees can register through **Create Employee Account** on the shared sign-in page. Administrators may also create employees through **Create Accounts**. Select **Employee** or, for Admin A, **Administrator**, then fill out the form shown below.

The landing page is **http://localhost/veridian/landing.html**.

`database/schema.sql` contains table definitions only. It does not contain employee records, passwords, or sample accounts. Existing installations should follow the upgrade notes in [Operations](docs/OPERATIONS.md); importing the schema alone does not add missing columns to existing tables.

## Updating an existing installation

Back up your database first. Before deploying this version, run "database/migrate-personnel.sql" once if the users table does not already have job_title and department columns. Keep your existing config/config.php. The migration adds empty job fields and preserves existing accounts and leave records. New installations use database/schema.sql instead.

## Local demonstration accounts

These are the sample credentials used in the original local project. They are intentionally documented for demonstrations. **They are not automatically created when you clone the repository.** On a new database, create the first administrator during setup and add employees through Create Accounts if you want to reproduce these examples.

| Role | Name / Username | Email | Demo password |
| --- | --- | --- | --- |
| Primary administrator | Admin A / `AdminA` | `adminalas@veridian.ph` | `AdminA123` |
| Employee | `ITAlas` | `employee_alas@veridian.ph` | `alas123` |
| Employee | `ITZafra` | `employee_zafra@veridian.ph` | `zafra123` |
| Employee | `ITTsukishima` | `employee_tsukishima@veridian.ph` | `tsukishima123` |

Employees and administrators use the same sign-in page. The stored account role determines which workspace and actions are available.

Sign in with the **username**, not the email address. Usernames are case-insensitive.

New passwords must contain **8–16 characters**; special characters are optional. The original `alas123` account has an older seven-character demo password. A fresh installation must use an eight-character or longer replacement when creating that account.

Use different, unique passwords before making an installation public. These demonstration passwords are public and should not protect real employee records.

## Employee guide

1. On the sign-in page, select **Create Employee Account** to register with your name, username, email, job role, department or office, and a confirmed password. Then **Sign in** using your username and password. If Admin A already created your account, use those credentials instead.
2. **Overview** shows your request counts and available leave balances.
3. **Request Leave**: choose a leave type, enter the number of weekdays, choose a start date, and add a brief reason. Veridian calculates the end date and checks your available balance.
4. **My Requests** shows your request history, decisions, and administrator remarks. You may cancel pending requests.
5. **Approved Leaves** separates confirmed requests from pending requests.
6. **Leave Calendar** shows your own approved leaves and current leave periods.
7. **Leave Balances** shows your annual allowance, approved usage, pending reservations, and available balance by year.
8. **Account Settings** lets employees change their username using their current password, update job role and department, change password, and manage their profile picture.

Registration creates an **employee** account only. Usernames and email addresses must be unique. Password confirmation and job details are required. Registration attempts are rate-limited; email verification is not implemented. Administrator accounts are created inside the protected dashboard by Admin A.

Keep sensitive medical or personal details out of request reasons unless required by your organization.

## Administrator guide

- **Leave Approvals**: open a pending request's details, optionally add remarks, and approve or reject it.
- **Approved Leaves**: view confirmed leave records separately.
- **Leave Calendar**: see all employees' approved leaves and current leave periods.
- **Employees**: search and sort names, usernames, emails, job roles, departments, request counts, and remaining balances. Account-creation forms are on a separate page.
- **Create Accounts**: select **Employee** to show the employee creation form. Admin A also sees the **Administrator** button. Only the selected form appears.
- **Leave Balances**: review allowances and usage, select a year, and adjust full-day allowances. An allowance cannot be reduced below already committed days.

### Admin A permissions

The first administrator created during setup is the primary administrator—Admin A in the original project. This account can also:

- Select **Administrator** on Create Accounts to create another administrator.
- Reset an employee's password from the Employees list.

Other administrators can review leave requests, manage allowances, and create employees, but cannot create administrators, reset employee passwords, or delete accounts.

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
- **Fast Leave:** a separate allowance of **3 uses per calendar year**. Each use covers **one half-day (0.5 day)** on the chosen weekday. Select Fast Leave and one date; the form reserves one use.

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

The landing page uses the sample employee name **ITAlas**, but its displayed balances, requests, dates, and decisions are **fictional static examples**. It does not query or display that account's actual leave records. Preview counts represent a fictional history; the visible rows are only a recent-request sample. The preview follows the actual employee navigation, four request statuses, full-day balance units, and separate Fast Leave uses.

## Hosting and GitHub

This repository contains application source and an empty database schema. It excludes `config/config.php`, database exports, local test records, and XAMPP data.

For hosted use, create a database, import the schema, configure the connection, use HTTPS, and set a strong `setup_key` before exposing first-time setup. Keep credentials in private configuration or environment variables.

Supported configuration variables are `VERIDIAN_DB_HOST`, `VERIDIAN_DB_PORT`, `VERIDIAN_DB_NAME`, `VERIDIAN_DB_USER`, `VERIDIAN_DB_PASSWORD`, and `VERIDIAN_SETUP_KEY`.

**Render deployment is not configured yet.** Running this PHP application on Render still requires a Docker configuration, a securely configured external database connection, and a database import. Pushing this repository to GitHub does not automatically publish a working site.

See [Operations](docs/OPERATIONS.md) for additional installation and maintenance details.

## Project folders

```text
veridian/
├── app/                 Authentication, security, profiles, and leave helpers
├── assets/
│   ├── css/             Stylesheets
│   └── js/              Browser interactions
├── config/              Private configuration and safe example
├── database/            Empty database schema
├── docs/                Operations notes
├── views/               Page templates and table partials
├── index.php            Application entry point
├── landing.php          Public landing-page handler
├── avatar.php           Authenticated profile-picture endpoint
└── session-state.php    Page-close session endpoint
```

The public landing address remains `landing.html`; Apache routes it through `landing.php`. Internal folders are blocked from direct web access. Copy `config/config.example.php` to `config/config.php` when installing.

## Shared sign-in and access control
Employees and administrators sign in through `index.php?auth=login`. Legacy `/admin` links redirect to this shared page. Public registration always creates an employee. Admin A alone can create administrators, reset employee passwords, or delete accounts. Administrators review requests, adjust allowances, and create employees. Employees can submit/cancel their own pending requests and read their own records. All users can update only their own account details. Job titles and departments never grant account privileges. Every protected POST action is checked against the central permissions in `app/access.php` before its handler runs.

## Job roles and departments
Employee registration and Admin A's account forms require Job Role and Department / Office. Employee options include teaching, administrative, academic support, security and service personnel. Department examples include IT, INDUS, CBA and support offices. Other allows a custom title or office. Administrator titles include IT Head, Maintenance Head and Head of Registrar.

Job information does not alter account permissions or leave allowances. Existing users can add their details in Account Settings > Work Details; existing records are not assigned guessed titles. The employee directory shows and sorts these fields. Existing databases require the additive `database/migrate-personnel.sql` migration before the updated code is installed; run it once only. Fresh installs use `database/schema.sql`.

The role examples were informed by published careers pages: [OB Montessori Center](https://obmontessori.edu.ph/careers), [Xavier School](https://www.xs.edu.ph/index.php/jobs/), and [National University](https://www.nu.edu.ph/nu-nazareth/talent-hub/jobs). They are editable examples, not a universal staffing standard. Catalog options are maintained in `app/personnel.php`.

### Delete accounts

Only Admin A can delete employee or other administrator accounts from **Employees**. Other administrators appear in a separate list below the employee directory. Select **Delete Account**, type the exact username, then confirm. Deletion permanently removes the account, its leave requests, allowances, profile picture, and remembered sessions. Existing sign-ins lose access on their next request. Decisions made by a deleted administrator on other employees’ requests remain, with the reviewer reference cleared. Admin A’s own account is protected. Cancel or close the dialog to keep the account.

## Changing an employee username

Employees can open Account Settings > Change Username, enter a new unique username and their current password, then select Update Username. Use 3–30 letters, numbers, dots, hyphens, or underscores. Usernames are case-insensitive when signing in and checking uniqueness. Only the signed-in employee’s own username can be changed; administrators do not have this option. Leave records, balances, profile, and account access stay attached to the same account ID. The employee stays signed in and uses the new username on future sign-ins. An account with a temporary password must change that password first.
