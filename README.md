# UNIFCO Platform

Laravel 13 + Blade implementation of the UNIFCO Platform v2.0 engineering baseline.

## Implemented foundation

- Multi-tenant tenant/organization/user model
- Session authentication and active-user enforcement
- Nine business modules: Finance, HR, Procurement, Inventory, CRM, Projects, Manufacturing, Maintenance, EAM
- Tenant-scoped Eloquent models
- Core database schema and initial administrator seeder
- Blade dashboard and module workspaces
- Audit-log schema ready for application services

## Local setup

MySQL 8 with the `pdo_mysql` PHP extension is required. Create an empty `unifco` database and a dedicated application user, then set the `DB_*` values in `.env` before migrating.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Seeded local account: `admin@unifco.local`. Set `UNIFCO_ADMIN_PASSWORD` before seeding; the fallback password is for local development only.

Tests use the dedicated `unifco_testing` MySQL database enforced by `tests/bootstrap.php`. Never point that configuration at an application database because the suite rebuilds tables.

## SQLite cutover

For an existing SQLite installation, take the application offline and back up `database/database.sqlite`. Configure an empty MySQL database, run `php artisan migrate:fresh --force`, and then import the backup:

```bash
php artisan unifco:import-sqlite database/database.sqlite --force
```

The importer requires both `pdo_mysql` and `pdo_sqlite`. It is intended only for a disposable, freshly migrated MySQL target: it truncates target application tables and MySQL cannot roll those truncations back if an import fails. The source remains untouched. The command preserves primary keys, resets auto-increment values, and validates SQLite integrity, every table row count, and all declared MySQL foreign keys. Keep the SQLite backup until MySQL smoke tests and backup restoration have been verified.

The staging Compose stack also uses MySQL. Its volume is disposable qualification data; preserve and migrate any separately managed staging data before replacing an older PostgreSQL volume.

## Implementation roadmap

The codebase is being built from the approved UNIFCO v2.0 planning baseline. Next waves add RBAC/SoD, audit middleware, workflow approvals, finance journal posting, inventory receipt/issue, procurement-to-inventory-to-finance integration, module CRUD screens, automated tests, CI and staging qualification.
