# UNIFCO System Administrator Architecture

## Decision

UNIFCO uses `RBAC + scope-based access + separation of duties + append-only audit`. System authority and business authority are separate. A System Administrator may configure who can approve a business transaction but cannot perform that approval without a second, independent business role.

## Existing foundation reused

- Laravel authentication, hashed passwords and CSRF middleware.
- `role_permissions` and the central `AuthorizationService` entry point.
- `user_permission_overrides`, now treated as exceptional and auditable.
- Existing user management and user profile screens, evolved into User 360.
- `customer_portal_user_scopes`, migrated into the common scope model while retaining a compatibility read path.
- `session_version` remains as a safe all-session revocation fallback.
- Existing `audit_logs` and `AuditService`, extended rather than replaced.

## Access evaluation

1. Reject inactive or locked accounts.
2. Resolve all active `user_roles`.
3. Resolve role permission effects.
4. Apply `Explicit DENY > ALLOW` across all roles.
5. Apply non-expired user exceptions; a deny still wins.
6. Require an active matching scope for resource-aware checks.
7. Authorize on the server; UI visibility is only a presentation layer.

`users.role` remains temporarily as a compatibility field. The migration backfills every existing record into `user_roles`. Application-managed creation and editing write both structures during the transition. The compatibility field must not be removed until production backfill verification and a full integration audit are complete.

## Data model

- `roles`: tenant or global master roles; distinguishes system and business authority.
- `permissions`: stable permission keys, module/action metadata, risk and scope flags.
- `role_permissions`: existing table extended with normalized FKs and `ALLOW|DENY` effect.
- `user_roles`: many-to-many assignments with primary marker, actor, time, reason and revocation.
- `access_scopes`: Global, Company, Department, Branch, Project, Site, Customer, Contract, Asset, Own and Assigned records.
- `user_scopes`: multiple, expiring assignments with source, actor and reason.
- `user_permission_overrides`: existing exceptions extended with reason and expiry.
- `user_sessions`: per-session device, browser, IP, activity, status and revocation.
- `user_invitations`: hashed invite token, lifecycle and expiry; no reusable password is stored or displayed.
- `security_events`: login and technical-security events.
- `approval_authorities`: approval type, role, level, scope, future amount limit and conditions.
- `audit_logs`: IP, session, reason, metadata and chained integrity hashes.

## System Administrator baseline

The `SYSTEM_ADMIN` master role includes user, role, scope, session, invitation, integration, security-event, master-data and audit administration. Business permissions are deliberately absent and therefore denied by default. They are not encoded as role-level explicit denies, because that would prevent a separately assigned business role from granting the authority. Explicit denies remain available for genuinely overriding policies and win across all roles.

## Migration safety

- No existing user, role string, customer scope or audit row is deleted.
- Existing users are backfilled to one primary role and an access scope.
- Existing internal users receive a migration-only Global scope to preserve visibility; administrators should narrow it after role review.
- Existing customer Site/Contract/Asset scopes are copied to the normalized tables.
- Rollback removes only the new structures and added columns.
- The legacy authorization fallback applies only when no structured assignment exists. Remove it after all external identity writers have moved to `user_roles`.

## Remaining rollout gates

- Connect the production email provider before enabling actual invitation delivery.
- Add MFA enrollment/provider integration; the schema/UI placeholder does not claim MFA is active.
- Apply `ScopeService::apply()` to each remaining list/report query before marking that module scope-complete.
- Replace remaining direct `role === ...` checks module by module with permissions/policies.
- Add database-level append-only protection if the production database account has trigger privileges; application routes already expose no audit mutation.
- Add approval workflow enforcement before activating `requires_approval` for sensitive role changes.
