# ImmuniCare test report

## Automated checks

- PHP syntax check: passed for all PHP files.
- IDE lint check: no diagnostics in edited files.
- PDO scan: no PDO usage found.
- CSS constraint: no `display: flex` declaration was found in a `body` rule.
- POST CSRF audit: all detected POST forms include a CSRF field/token.
- Internal route scan: no missing application PHP targets remain after resolving shared-include relative paths.

## Browser smoke checks

- `http://localhost:8000/`: passed at desktop and 320px mobile viewport.
- `http://localhost:8000/about.php`: passed at 320px mobile viewport with valid document structure.
- Homepage accessibility tree exposes navigation, content headings, calls to action, and footer links.

## Database-dependent checks

Blocked in this environment because the local MySQL server is not running and the bundled Homebrew server could not start. Before release, run:

1. Import `database/install.sql` into a clean MariaDB database.
2. Set `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, and `APP_TIMEZONE`.
3. Test login and inactive-account rejection for Parent, Hospital, and Admin.
4. Test Parent child ownership, booking, cancellation, rescheduling, and history.
5. Test Hospital approval, rejection, scheduling, vaccination, missed status, and notifications.
6. Test Admin user/hospital/vaccine/booking/schedule/record management.
7. Test crafted ID changes, missing CSRF tokens, duplicate bookings, duplicate records, and invalid dates.

SMS/WhatsApp, MFA, PWA/offline support, and production monitoring remain external deployment integrations and are not enabled by this local PHP build.

## Repeatable Docker test run

With Docker running:

```sh
docker compose up -d db app
docker compose run --rm test
docker compose run --rm browser
docker compose down -v
```

The test service imports `database/install.sql`, runs PHPUnit, and uses disposable MariaDB data. The browser service runs the Playwright public/responsive smoke suite. CI configuration is in `.github/workflows/tests.yml`.

The suite includes unit validation/workflow tests, migration/schema checks, JSON health/booking/notification endpoint tests, route/security static checks, and Playwright public/responsive checks. A literal guarantee of every possible external or concurrent production scenario is not possible; coverage and acceptance gates are explicit so regressions fail the build.

The current local environment passed PHP syntax, Composer configuration, static security constraints, CSRF inventory, and Docker Compose configuration checks. Full MariaDB/PHPUnit/Playwright execution requires the Docker daemon and package downloads.
