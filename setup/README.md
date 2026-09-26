# Database and production deployment

## One-command Docker deployment

From the `setup` directory:

```sh
cp .env.example .env
# Edit .env and set strong passwords.
docker compose -f docker-compose.production.yml up -d --build
```

The production entrypoint waits for MariaDB, creates the database, imports the complete installer on first boot, and applies pending migrations on later boots. Open `http://localhost:8080` or the configured `APP_PORT`.

The Docker database volume is persistent. Back it up before upgrades and never use the example passwords in production.

## One-command XAMPP local run

From the production application directory:

```sh
./setup/start-xampp.sh
```

The launcher starts XAMPP MySQL, creates/imports/migrates the database, and serves the application at `http://127.0.0.1:8080`. Override `XAMPP_ROOT`, `DB_PORT`, `DB_PASSWORD`, or `APP_PORT` when your XAMPP installation differs.

On Windows XAMPP, double-click `setup/start-xampp.bat` or run:

```bat
setup\start-xampp.bat
```

It uses the standard `C:\xampp` installation path. Set `XAMPP_ROOT` if XAMPP is installed elsewhere.
The Windows launcher starts XAMPP MySQL and uses XAMPP PHP on port `8080`, so it does not require Apache port 80. This avoids conflicts with IIS, another Apache instance, Skype, or other services.

1. Create the database:

```sql
CREATE DATABASE vaccination_management_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

2. Import `setup/database/install.sql` for a clean hardened install.
3. For an existing database, run `setup/database/migrations/001_harden_schema.sql` and any later numbered migrations.
4. Set `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, and `APP_TIMEZONE` in the web-server environment.
5. Use disposable test accounts only. Do not deploy the dump's local credentials or use MySQL `root` in production.

The migrations add account status, verified hospital onboarding, dose series, booking/schedule/record links, notification/outbox tables, hospital slots and inventory, auth recovery, audit logs, contact messages, exports, indexes, and integrity constraints.

For an existing database, `php setup/scripts/migrate.php` applies only migrations not listed in `schema_migrations`. The separate test stack uses the production installer with its own fixture seed.

For non-Docker hosting, set the same `DB_*` and `APP_TIMEZONE` environment variables and run `php setup/scripts/deploy.php` once. The web server only needs to point its document root at the production application directory and provide PHP 8.2+ with MySQLi/mysqlnd.
