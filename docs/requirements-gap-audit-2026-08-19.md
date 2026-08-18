# UNIFCO Requirements Gap Audit — 2026-08-19

This audit reconciles the historical UNIFCO business baseline, later ERP/operations implementation waves, and the current Laravel main branch.

## Implemented before this gap-closure wave

- Identity/RBAC administration, permissions, API tokens, audit trail and approval workflow.
- Multi-tenant Laravel data model and organization context.
- Finance journals, posting, AR/AP documents, payments, periods and responsive finance workspace.
- Inventory stock movements and goods receipt integration.
- Procurement purchase orders and approvals.
- HR employee and operational records.
- CRM customers, leads, opportunities and quotations.
- Projects, manufacturing, maintenance and EAM operational models/workspaces.
- Outbox publisher/event baseline and application audit pipeline.
- Public UNIFCO website, guest RFQ intake and emergency-maintenance intake.
- RFQ conversion to CRM Lead -> Opportunity -> Quotation.
- Emergency request conversion to Service Request -> Work Order.
- Customer Portal / Contract 360 with customer isolation, contracts, assets, PM/CM work, spare parts, invoices/payments, SLA, visit reports, attachments, quotation decisions, warranty/parts history, filters and alerts.
- UNIFCO official logo and navy/red/white identity, materialized deployable logo asset.
- Laravel CI and PostgreSQL staging qualification workflows.

## Gaps found in historical business requirements and closed in this wave

### E-005 Scheduling & Dispatch
- Added technician assignment/scheduling records.
- Added dispatch lifecycle and dispatch board.
- Added technician notification on dispatch.

### E-006 Technician Mobile
- Added technician-specific responsive workspace.
- Added assigned-work lifecycle: Accept -> Arrived -> In Progress -> Completed.
- Added browser local offline queue for field status changes with automatic retry after connectivity returns.
- Linked application users to employee/technician records.

### E-007 Inspection
- Added inspection templates/checklists.
- Added work-order/asset/technician inspection execution and history.
- Added findings, recommendations and completion state.

### E-013 AI Assistant
- Added authenticated contextual assistant UI.
- Added operational data retrieval for maintenance/inventory questions.
- Added citations/action recommendations and full interaction audit storage.
- Sensitive operational actions remain human-controlled.
- External model-provider integration remains provider/configuration dependent; the platform fallback is deterministic and deployable without external credentials.

### Identity/IAM implementation requirement
- Added signed HS256 JWT issuance and enforcement.
- Added tenant claim enforcement and X-Tenant-Id mismatch protection.
- Added JWT `/auth/me`, Users and Role/Permission administration APIs.
- Retained legacy scoped API-token endpoints for backward compatibility.

### Customer Portal profile requirement
- Added customer self-service contact/profile fields and secured edit/update flow.

## Already present — verified as not a gap

- Notifications: in-app notification model/controller/read tracking already existed; scheduling now produces dispatch notifications.
- Reporting: executive reporting already exists. Advanced scheduled external report distribution remains an operations/integration enhancement rather than a blocking business-workflow gap.
- Documents/files: authenticated document repository and customer maintenance attachments exist.
- Public website and anonymous quote/emergency request intake exist.
- Customer portal data isolation and near-real-time operational status exist.

## External / production-readiness items that cannot be solved only by repository code

The historical Go-Live pack still requires environment evidence before declaring Production Accepted:

- VPS/production secrets and environment values.
- Domain/DNS/TLS and reverse-proxy configuration.
- Real production database provisioning and backup/restore drill.
- Real external provider credentials where used (mail/SMS/push, external AI, OIDC, Kafka if enabled externally).
- Legacy/source data mapping, migration rehearsal and financial reconciliation with real source data.
- Production monitoring/alert ownership and SLO verification.
- Security/performance testing in the real staging/production-like environment.
- Business UAT sign-off, Go/No-Go and rollback rehearsal.

These are deployment/acceptance gates, not missing Laravel business implementation.
