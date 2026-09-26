# Local database setup

1. Create the database:

```sql
CREATE DATABASE vaccination_management_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

2. Import `install.sql` for a clean hardened install.
3. For an existing database, import `../vaccination_management_system(1).sql` and then run `migrations/001_harden_schema.sql`.
4. Set `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, and `APP_TIMEZONE` in the web-server environment.
5. Use disposable test accounts only. Do not deploy the dump's local credentials or use MySQL `root` in production.

The migrations add account status, verified hospital onboarding, dose series, booking/schedule/record links, notification/outbox tables, hospital slots and inventory, auth recovery, audit logs, contact messages, exports, indexes, and integrity constraints.

For an existing database, `php scripts/migrate.php` applies only migrations not listed in `schema_migrations`. The disposable Docker test stack uses `database/install.sql` and `tests/fixtures/seed.sql`.
