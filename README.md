# Workshop Registration Service

Updated full-stack project package. A small PHP + MySQL application for a community training centre. Built for a three-hour full-stack challenge with an emphasis on server-side permissions, seat capacity, and registration history.

## Stack

- PHP 8+ with PDO prepared statements
- MySQL / MariaDB using InnoDB transactions
- HTML, CSS and small vanilla JavaScript
- PHP sessions for authentication

## Local setup (XAMPP on macOS)

1. Copy this folder into `/Applications/XAMPP/xamppfiles/htdocs/workshop-registration`.
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin at `http://localhost/phpmyadmin/`.
4. Keep your existing database and Admin account. If you already created `users`, `workshops`, and `registrations`, import `upgrade_existing_database.sql` into phpMyAdmin. It creates the missing audit log table if needed and adds three sample workshops without deleting existing data. For a brand-new database only, import `schema.sql`.
5. Check `config/database.php`. Default XAMPP settings are host `localhost`, database `workshop_registration`, user `root`, and an empty password. Change these if your local setup differs.
6. Use your existing Admin account. This updated package intentionally does not include a public Admin-seeding page because your Admin account already exists.
7. Open `http://localhost/workshop-registration/login.php`.

## Roles

- **Admin:** create user accounts. Public sign-up is not provided.
- **Manager:** add/edit workshops, view workshops and history, register/cancel attendees.
- **Staff:** view workshops and history, register/cancel attendees.

Permissions are checked on the server, not just hidden in the UI.

## Preventing over-registration

Registration uses an InnoDB transaction and locks the workshop row with `SELECT ... FOR UPDATE` before checking active registrations and inserting. Concurrent registrations for the same workshop serialize on that row. Cancellation changes the registration status and records the actor and timestamp instead of deleting the row. Every registration/cancellation and several other changes are written to `audit_logs`.

## Notes / trade-offs

- This is a local challenge implementation, not a production-hardened deployment.
- Set secure session cookie flags and HTTPS in production, add login throttling, email/password reset flow, and stronger operational logging before public deployment.
- Workshop history is capped at the latest 500 rows in the history screen to keep the page responsive; pagination can be added.
- Optional waitlist is not implemented.
- Admin is intentionally routed to user management because the brief only grants Admin user-management access.
