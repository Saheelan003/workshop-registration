# Design decisions — Workshop Registration Service

## Stack
PHP and MySQL were selected because they are available in the local XAMPP environment and allow a compact full-stack implementation without an extra build step. The frontend uses semantic HTML, plain CSS and a small amount of vanilla JavaScript.

## Data model
`users` stores staff identities, password hashes and one of three roles. `workshops` stores the code, title, instructor, start date/time, capacity and status. `registrations` stores attendee contact details, active/cancelled state, who registered them and when, and who cancelled them and when. `audit_logs` records selected account, workshop and registration actions.

## Access control
Protected pages call shared authentication and role checks. Admin can create Manager and Staff accounts. Manager can create/edit workshops. Manager and Staff can view workshops/history and register or cancel attendees. Public signup is disabled.

## Capacity and history
Registration starts a database transaction and locks the target workshop row with `SELECT ... FOR UPDATE`. It counts active registrations while holding the lock and inserts only if the workshop is scheduled and has space. This serializes registrations for the same workshop when requests arrive together. Cancellation is a status update, not a delete, and stores the actor and timestamp.

## Trade-offs and skipped work
The UI is deliberately plain and responsive rather than heavily animated. The latest 500 registration-history rows are shown; pagination and a waitlist were left out to prioritize permissions, end-to-end workflows and the capacity guarantee. For production, add HTTPS, secure session-cookie settings, login throttling, account recovery, monitoring and a deployment secrets strategy.
