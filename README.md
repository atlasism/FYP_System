# SPInE FYP Inventory System (Laravel 13)

This checkout is the Laravel 13 / PHP 8.4 conversion of the SPInE Final Year Project system from `atlasism/FYP_System`. It uses Laravel routing, Blade, session authentication, role middleware, request validation, private file storage, and database migrations.

The original plain PHP implementation is retained under `legacy/` as a migration reference. It is not routed or served by the Laravel application. Existing document downloads are served through an authenticated controller; new uploads are stored on Laravel's private local disk.

## Requirements and setup

- PHP 8.4 with `fileinfo`, `mbstring`, `openssl`, and `pdo_mysql`
- Composer 2
- MySQL or MariaDB

```powershell
Copy-Item .env.example .env
php artisan key:generate
# Create fyp_inventory_db in Laragon MySQL, or set DB_DATABASE in .env
composer install
php artisan migrate
php artisan serve
```

The Laragon defaults in `.env.example` are MySQL at `127.0.0.1:3306`, database `fyp_inventory_db`, user `root`, and a blank password. Change those values in `.env` if your MySQL instance uses different credentials. To restore an existing database backup, import the SQL file into the selected database first and then run `php artisan migrate`; the app migrations skip project tables already present and add Laravel's support tables. The supplied `fyp_inventory_db.sql` was imported into the local Laragon database for this checkout, but its data dump is not copied into this repository. Back up your database before importing or changing existing data. Do not run `migrate:fresh` against a database that contains project records.

For the legacy 100 MB submission limit, set PHP's `upload_max_filesize=100M` and `post_max_size=105M` in the active `php.ini`, then restart PHP/web server services.

Laravel 13 requires PHP 8.3 or newer; this project deliberately sets both its Composer constraint and Composer platform to PHP 8.4.

The project-table migrations are non-destructive on rollback because these tables can contain records imported from the legacy system. Drop or rebuild a database only after taking and checking a backup.

The group-number migration aligns the 18 JTMK teams in Session 1 2026/2027 with the supplied `00 DFT50194 Senarai Nama Projek & Pelajar.pdf` order. A follow-up migration also sets the 18 official project titles and corrects five imported matric-number prefixes and four student names. The imported application records use course code `DFT50114`; these roster migrations do not change the application's course configuration. Run `php artisan migrate` after importing the projects on each installation. The legacy import and reorder SQL files contain the earlier group mapping for manual imports.

For hosted deployments, configure the server's own MySQL host, database, username, and password in its private `.env`; the Laragon defaults from `.env.example` are only for a local machine. Run migrations on the hosted database after its connection is verified, and set `APP_DEBUG=false` before making the site public.

## Converted application flows

- Public SPInE landing page, student registration, and sign-out
- Students sign in with matric number and IC number as password; supervisors, admins, and panel members sign in with email and IC number as password. Existing password hashes are replaced with IC hashes after a successful sign-in.
- Role-specific Student, Supervisor, Admin, and Panel dashboards
- Student project/team registration and private PDF, DOCX, or ZIP submission upload
- Supervisor dashboard and scoped document status review
- Admin account creation and role changes, project status control, deadline management, system settings, and project reports
- Authenticated, role-scoped downloads of both newly uploaded and retained legacy project documents
- Laravel migrations for the tables present in the supplied MySQL dump

The legacy source is a larger application than the flows listed above. Its detailed assessment forms, panel-token submissions, profile editing, group workflows, logbook verification, reminders, and imports still need to be moved into Laravel controllers and views before calling the conversion feature-complete. Use `legacy/` and the SQL migrations there as the reference while porting those modules.
