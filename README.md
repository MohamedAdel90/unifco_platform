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

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Seeded local account: `admin@unifco.local`. Set `UNIFCO_ADMIN_PASSWORD` before seeding; the fallback password is for local development only.

## Implementation roadmap

The codebase is being built from the approved UNIFCO v2.0 planning baseline. Next waves add RBAC/SoD, audit middleware, workflow approvals, finance journal posting, inventory receipt/issue, procurement-to-inventory-to-finance integration, module CRUD screens, automated tests, CI and staging qualification.
