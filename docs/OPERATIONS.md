# Veridian

Working PHP/MySQL employee leave request system with a green responsive interface.

## Run in XAMPP
1. Put this folder in C:\xampp\htdocs\veridian.
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost/veridian/ (use HTTP, not a file URL).
4. Create the first administrator on the setup screen.
5. Sign in, open Create Accounts, and create employee accounts.
6. Use another browser/private window to sign in as an employee, request leave, and check its status.

Employees can submit leave, see their own history, cancel pending requests, and change their password. Administrators create employee accounts and approve or reject pending requests with remarks. Reviewed and cancelled requests remain in the history.

## Rules
- Weekdays (Monday–Friday) count. Holidays are not configured and count as weekdays. Weekend-only requests are rejected.
- Requests start today or later and cover at most 365 days.
- Pending or approved requests for the same employee cannot overlap.
- Only pending requests can be reviewed or cancelled.
- Allowances, approved usage, pending reservations and remaining days are calculated per employee and calendar year. The landing page uses illustrative data.
- No public signup, email notifications, or attachments are included. Admin A provides employee password recovery through one-time temporary passwords.

## Database
Local defaults in config/config.php: 127.0.0.1, port 3306, database veridian, XAMPP root user with an empty password. These are local-development defaults only. Environment variables are supported: VERIDIAN_DB_HOST, VERIDIAN_DB_PORT, VERIDIAN_DB_NAME, VERIDIAN_DB_USER, VERIDIAN_DB_PASSWORD, VERIDIAN_SETUP_KEY.

## Hosting
Create a hosting database, import database/schema.sql with phpMyAdmin, then configure config/config.php with the assigned credentials. Set setup_key to a long random secret before exposing setup publicly; the remote setup form requires it. Do not upload local test files or database exports. Use HTTPS, strong unique passwords, server-level login rate limiting, a least-privilege database user, and regular backups before using real employee information. Keep config/config.php out of GitHub when it contains real credentials; copy config/config.example.php to config/config.php when installing from Git.

Setup closes after an administrator exists. There are no default application accounts. Database credentials and server errors are never shown to users. Passwords are hashed; forms use CSRF protection; permissions and date checks are enforced server-side; employee requests are scoped to their account.

Google Fonts is optional; the system falls back to Arial when unavailable.

## Sign-in and Remember Me
Without Remember Me, visiting landing.html or index.php?auth=login clears the current login. Normal navigation between workspace screens keeps the session active. Remember Me is optional and lasts at most 30 days on the device. It uses an HttpOnly, SameSite cookie and stores only a token hash in the database, not the password. Explicit sign-out revokes the device token; changing a password revokes all remembered tokens for that user. HTTPS enables Secure cookies.

The .htaccess rewrite sends landing.html through landing.php so homepage sign-out is enforced by PHP, even without JavaScript. Keep these files together and enable Apache rewrite support. Browser back/forward-cache restores trigger revalidation; no-store headers prevent protected response caching. Existing installations must import the remember_tokens CREATE TABLE statement from database/schema.sql before using Remember Me.

## Security checks and hardening
Login throttling is stored in login_throttles, with a 5-attempt account/IP limit and a separate IP limit over five minutes. Starting a new session does not reset these counters. Import the table from database/schema.sql when upgrading an existing installation.

Normal sessions are bound to the current password hash; changing a password invalidates older sessions. Remembered sessions are also bound to the password version. PHP errors are logged without exposing stack traces. The application sends Content Security Policy, frame protection, no-store, and capability restriction headers. Apache blocks direct requests to internal PHP files, SQL files, logs, archives, and hidden files, and disables directory listing.

Regression checks cover employee request isolation, administrator-only actions, CSRF rejection, HTML escaping in reasons and remarks, invalid identifiers, SQL-like login input, fresh-session throttling, password session invalidation, and Remember Me expiry/revocation. Temporary fixture users and requests were removed after testing.

Before public hosting, use HTTPS, a dedicated database account with only required permissions, and a strong administrator password. The local XAMPP database defaults are for development only. These checks are targeted application regression checks, not an independent penetration test.

## Usernames and password hints
Login uses a unique username, not an email address. Usernames allow 3–30 letters, numbers, dots, hyphens, or underscores and are matched without case sensitivity. Email is retained for employee account details. Each field has a short format hint.

New accounts and password changes require 8–16 characters. Special characters are optional. Existing supplied sample passwords are preserved, including any shorter demo password, so login does not apply the new-account length check. The sample accounts use ITAlas, ITZafra, and ITTsukishima. The existing administrator uses AdminA. Demo credentials are not listed in this document.

## Company employee portal and profile pictures
Veridian is presented as the company's internal employee portal, keeping the existing leave-request workflows. The dashboard uses a forest-green sidebar, lime branding, a pale-green active item, and readable dark text on light content panels.

Every account has a deterministic illustrated profile picture by default. Users can replace it in Account Settings with JPG, PNG, or WebP files up to 500 KB and 4096 × 4096 pixels, or restore the illustration. Uploaded bytes are stored in the database; filenames are ignored. Format signatures, MIME type, dimensions, size, login state, and CSRF are checked. The avatar endpoint permits only the account owner or an administrator and uses no-store responses and nosniff headers. No files are saved to an executable uploads directory. Original image metadata is retained; use pictures suitable for an employee profile.

Existing installations need the avatar_data (MEDIUMBLOB NULL), avatar_mime (VARCHAR(32) NULL), and avatar_version (INT UNSIGNED NOT NULL DEFAULT 0) columns in users. Fresh installations include them in database/schema.sql.

Verified valid image upload/restore, SVG and oversize rejection, guest/employee access restrictions, image loading for AdminA and all three sample employees, and layout at 320px, 768px, and 1440px.

## Leave policy and leave management
The default yearly allowances are 15 Annual, 15 Sick, and 7 Emergency weekdays. Personal requests share the Annual pool. Allowances are granted up front, with no carry-over or monthly accrual. Administrators can save per-employee/year overrides. Other leave types are handled separately by the administrator.

Existing installs: import the additive leave_allowances CREATE TABLE statement from database/schema.sql. Missing employee/year rows use the default allowances until an admin saves an override. Existing requests remain intact and count toward balances; if old usage exceeds a default, the negative balance is displayed for administrator correction. Cross-year requests split weekday usage by calendar year. New requests and approvals enforce available quota while locking the employee row. Allowances cannot be reduced below approved plus pending commitments. Rejected/cancelled requests release reserved days.

Approved Leaves is separate from pending review. Request and employee tables support search and clickable sort headings; tables scroll within their panels on small screens. Approved requests show start-date countdowns in calendar days, current-leave weekday reminders, or Completed.

The earliest administrator (Admin A in this installation) can create additional administrators under Create Accounts. This authorization is checked on the server using the administrator ID. Other administrators can review requests, create employees and adjust allowances, but cannot create administrators. There is no public administrator registration.

## Calendar and weekday planner
Leave Calendar shows approved leaves by month, with previous/next controls, month selection, a current-leave list, and an agenda. Admins see all employees; employees see only their own approved records. Reasons and remarks are not exposed in calendar events. Weekends have no leave events or charges, although an approved date range can span them.

Employees enter a whole number of leave days and a weekday start date. JavaScript previews the calculated inclusive end date; PHP independently recalculates it on submission, ignoring any posted end_date. Weekends are skipped, holidays still count. Weekend starts are rejected. Existing saved ranges remain intact. Existing quota, overlap, future-date and 365-calendar-day span checks still apply. No JavaScript is required to submit a request; the server calculates the end date.

Requests use whole days, except Fast Leave, which covers one half-day and is counted in uses. Hourly leave is not implemented.

## Navigation and closing a page
Search, Reset, sorting, filters, calendar controls, forms, and sidebar links preserve normal workspace sessions. Stale invalid Remember Me cookies are cleared without resetting ordinary sessions or their CSRF tokens.

Without Remember Me, sign-in and landing entries sign out as before. A page-exit beacon marks an ordinary session for closing, with a five-second grace period to allow refresh/reload. The next protected request expires a marked session after that period; a new workspace document invalidates late messages from the previous page. Remembered sessions ignore exit markers. Close detection is best effort: browser crashes, force quits, blocked JavaScript or failed unload delivery may prevent a close message. There are no visibility or tab-switch sign-out handlers.

## Fast Leave
Fast Leave covers one half-day (0.5 day) on a weekday, for urgent time off, without restriction to a particular part of the day. It has a separate fixed allowance of 3 uses per calendar year; it does not deduct Annual, Sick or full-day Emergency credits. Pending and approved requests each consume one use. Rejection/cancellation releases the reservation. Multiple-day requests and weekend starts are rejected; overlaps with other approved/pending leave remain prohibited. Admin approval is required. The calendar identifies Fast Leave, and the balance and employee tables display uses.

Existing installations must extend leave_requests.leave_type with the enum value Fast. Password recovery also requires users.password_must_change TINYINT(1) NOT NULL DEFAULT 0. Existing passwords and requests remain intact.

## Employee password recovery
Admin A can reset an employee password from Employees. Existing password hashes cannot be viewed. A random temporary password is shown once to Admin A, stored only as a hash in users, and must be changed by the employee before normal use. Resetting revokes remembered tokens and invalidates existing sessions through password-version checks. Share the temporary password privately. Other admins and employees cannot use the reset action. No actual employee password was changed during feature verification; tests use disposable fixtures.

Password resets open an accessible dialog showing the employee username and temporary password over a blurred backdrop. The X or Escape closes it and clears the password from the DOM. The credential is flashed only once; the admin shares it with the employee in person. No email is sent.

## Separate account creation
Employees contains the searchable, sortable employee directory and recovery controls. Create Accounts is a separate administrator page for employee creation; Admin A also sees administrator creation there. Employees cannot access that page or either creation action. Successful creation stays on Create Accounts with confirmation and a link back to the employee list.
