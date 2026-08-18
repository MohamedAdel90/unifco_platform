# Customer Portal Advanced Operations

This wave extends Contract & Asset 360 with customer-facing and back-office operations:

- Downloadable PDF invoices, service contracts, and technical visit reports.
- Maintenance visit reports per asset/work order.
- Before/after maintenance images and PDF attachments.
- SLA response/resolution timestamps and targets on customer service requests.
- New visit/maintenance requests from authenticated customer accounts.
- Live request status in the customer dashboard.
- Customer approval/rejection of linked CRM quotations.
- Asset warranty visibility and installed spare-parts history.
- Dashboard filtering by contract, asset, and site/location.
- Dynamic maintenance, invoice, and contract-expiry alerts.
- Back-office endpoints for acknowledging/resolving requests, creating visit reports, and uploading maintenance evidence.

All customer-facing reads and actions enforce `customer_id` ownership checks.
