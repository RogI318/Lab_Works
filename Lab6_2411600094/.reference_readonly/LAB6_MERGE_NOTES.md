# Lab 6 merge notes

Built from: Lab 5 of this project (base) + the Lab 6 reference project (Lab 6 features) + this project's own Lab 6 theme/layout.
Database settings in `.env` are the same as Lab 5, so your Lab 5 data is kept.

## Run (inside the project folder)
    composer install
    php artisan migrate          # adds the Lab 6 changes on top of your Lab 5 tables (use migrate:fresh --seed for a clean start)
    php artisan optimize:clear
    php artisan serve
    php artisan queue:work       # second terminal: sends the low stock alerts (bell + email)

## What Lab 6 added
- Transactions (Stock In / Stock Out list, form, details, admin-only delete): /transactions
- Stock adjustments (physical count): /adjustments/create
- Reports + CSV export: /reports
- Low stock bell notifications (sent to admins when stock drops to the reorder level)
- Live dashboard numbers and Recent Transactions (refresh every 30 seconds, /api/...)
- Stock is now updated by an observer (app/Observers/InventoryTransactionObserver.php).
  The Lab 5 Stock In / Stock Out form on the product page still works the same way for the user.

## Added from the second Lab 6 reference project
- Soft delete + Trash (Inventory > Trash, Restore)
- Staff stock-out alert to admins; low stock alerts go to admin and staff
- Gates: manage-stock (admin + staff) and manage-users (admin only) protect the routes
- Stock before / after stored on every transaction (Transactions list + details, running balance on the product page)
- Unknown URLs redirect to the nearest real page (or dashboard / login)
- Bell refreshes every 5 seconds (public/js/bell-live.js)
- Emails to a user when an admin changes their name/role or deletes the account
- /api/dashboard/charts (app/Support/ChartData.php)

## Audit trail
- Table `audit_logs` (who, action, record, old/new values, IP, time). Admin page: Audit Log (/audit-logs).
- Logged: create / update / delete / restore of products, brands, categories, users and transactions; logins, logouts and failed logins.
- Passwords are never written to the log. Seeders and artisan commands are not logged.
- Page views: every page a signed-in user opens (including the Audit Log itself) is logged as "viewed". Same-page refreshes within 5 seconds count once; background refreshes (API, bell check) are not logged. CSV exports and "alert read" are logged too.
