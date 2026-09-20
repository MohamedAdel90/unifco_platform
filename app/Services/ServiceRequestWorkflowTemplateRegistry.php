<?php

namespace App\Services;

use App\Models\ServiceRequest;

class ServiceRequestWorkflowTemplateRegistry
{
    public const MAINTENANCE = 'MAINTENANCE';
    public const EMERGENCY_MAINTENANCE = 'EMERGENCY_MAINTENANCE';
    public const QUOTATION = 'QUOTATION';
    public const MAINTENANCE_CONTRACT_QUOTATION = 'MAINTENANCE_CONTRACT_QUOTATION';
    public const SPARE_PARTS_QUOTATION = 'SPARE_PARTS_QUOTATION';
    public const TECHNICAL_VISIT = 'TECHNICAL_VISIT';
    public const TECHNICAL_CONSULTATION = 'TECHNICAL_CONSULTATION';

    public function keyFor(ServiceRequest $request): string
    {
        $subtype = strtoupper((string) ($request->request_subtype ?? ''));
        $type = strtoupper((string) ($request->request_type ?: self::MAINTENANCE));
        $priority = strtoupper((string) $request->priority);

        if ($type === 'CONSULTATION' || in_array($subtype, ['TECHNICAL_CONSULTATION', 'CONSULTATION'], true)) {
            return self::TECHNICAL_CONSULTATION;
        }

        if (in_array($subtype, ['MAINTENANCE_CONTRACT_QUOTE', 'MAINTENANCE_CONTRACT_QUOTATION', 'CONTRACT_MAINTENANCE_QUOTE'], true)) {
            return self::MAINTENANCE_CONTRACT_QUOTATION;
        }

        if (in_array($subtype, ['SPARE_PARTS_QUOTE','SPARE_PARTS_QUOTATION','PARTS_QUOTE','SPARE_PARTS'], true)) {
            return self::SPARE_PARTS_QUOTATION;
        }

        if (in_array($subtype, ['TECHNICAL_VISIT','TECHNICAL_VISIT_QUOTE','SITE_VISIT_QUOTE','VISIT_QUOTATION'], true)) {
            return self::TECHNICAL_VISIT;
        }

        if ($type === 'QUOTATION') {
            return self::QUOTATION;
        }

        if ($priority === 'EMERGENCY' || in_array($subtype, ['URGENT_MAINTENANCE', 'EMERGENCY_MAINTENANCE'], true)) {
            return self::EMERGENCY_MAINTENANCE;
        }

        return self::MAINTENANCE;
    }

    public function template(string $key, array $context = []): array
    {
        $hasCost = (bool) ($context['has_cost'] ?? false)
            || (float) ($context['estimated_value'] ?? 0) > 0
            || (bool) ($context['chargeable'] ?? false);
        $needsQuality = (bool) ($context['quality_required'] ?? false);
        $needsHse = (bool) ($context['hse_required'] ?? false) || strtoupper((string) ($context['risk_level'] ?? 'NORMAL')) === 'HIGH';
        $procurement = (bool) ($context['procurement_required'] ?? false);
        $adminApproval = (bool) ($context['administrative_approval_required'] ?? true);

        $templates = [
            self::MAINTENANCE => [
                ['stage' => 'TRIAGE', 'role' => 'OPERATIONS_MANAGER', 'department' => 'OPERATIONS'],
                ['stage' => 'PROJECT_MANAGER_REVIEW', 'role' => 'PROJECT_MANAGER', 'department' => 'OPERATIONS'],
                ['stage' => 'MAINTENANCE_MANAGER_REVIEW', 'role' => 'MAINTENANCE_MANAGER', 'department' => 'MAINTENANCE'],
                ['stage' => 'TECHNICAL_ASSESSMENT', 'role' => 'MAINTENANCE_ENGINEER', 'department' => 'MAINTENANCE'],
                ...($needsHse ? [['stage' => 'HSE_CLEARANCE', 'role' => 'HSE', 'department' => 'HSE']] : []),
                ['stage' => 'TECHNICIAN_ASSIGNMENT', 'role' => 'TECHNICAL_SUPERVISOR', 'department' => 'OPERATIONS'],
                ['stage' => 'EXECUTION', 'role' => 'TECHNICIAN', 'department' => 'OPERATIONS'],
                ...($needsQuality ? [['stage' => 'QUALITY_VERIFICATION', 'role' => 'QUALITY', 'department' => 'QUALITY']] : []),
                ['stage' => 'CUSTOMER_ACCEPTANCE', 'role' => 'CUSTOMER', 'department' => 'CUSTOMER'],
                ...($hasCost ? [['stage' => 'FINANCE_REVIEW', 'role' => 'FINANCE_MANAGER', 'department' => 'FINANCE']] : []),
                ['stage' => 'CLOSURE', 'role' => 'OPERATIONS_MANAGER', 'department' => 'OPERATIONS'],
                ['stage' => 'CSAT', 'role' => 'CUSTOMER', 'department' => 'CUSTOMER'],
            ],
            self::EMERGENCY_MAINTENANCE => [
                ['stage' => 'EMERGENCY_DISPATCH', 'role' => 'OPERATIONS_MANAGER', 'department' => 'OPERATIONS'],
                ['stage' => 'PROJECT_MANAGER_REVIEW', 'role' => 'PROJECT_MANAGER', 'department' => 'OPERATIONS'],
                ['stage' => 'MAINTENANCE_MANAGER_REVIEW', 'role' => 'MAINTENANCE_MANAGER', 'department' => 'MAINTENANCE'],
                ...($needsHse ? [['stage' => 'HSE_CLEARANCE', 'role' => 'HSE', 'department' => 'HSE']] : []),
                ['stage' => 'TECHNICIAN_ASSIGNMENT', 'role' => 'TECHNICAL_SUPERVISOR', 'department' => 'OPERATIONS'],
                ['stage' => 'EXECUTION', 'role' => 'TECHNICIAN', 'department' => 'OPERATIONS'],
                ['stage' => 'TECHNICAL_REVIEW', 'role' => 'MAINTENANCE_ENGINEER', 'department' => 'MAINTENANCE'],
                ...($needsQuality ? [['stage' => 'QUALITY_VERIFICATION', 'role' => 'QUALITY', 'department' => 'QUALITY']] : []),
                ['stage' => 'CUSTOMER_ACCEPTANCE', 'role' => 'CUSTOMER', 'department' => 'CUSTOMER'],
                ...($hasCost ? [['stage' => 'FINANCE_REVIEW', 'role' => 'FINANCE_MANAGER', 'department' => 'FINANCE']] : []),
                ['stage' => 'CLOSURE', 'role' => 'OPERATIONS_MANAGER', 'department' => 'OPERATIONS'],
                ['stage' => 'CSAT', 'role' => 'CUSTOMER', 'department' => 'CUSTOMER'],
            ],
            self::QUOTATION => [
                ['stage' => 'SALES_REVIEW', 'role' => 'SALES', 'department' => 'SALES'],
                ['stage' => 'TECHNICAL_REVIEW', 'role' => 'MAINTENANCE_ENGINEER', 'department' => 'OPERATIONS'],
                ...($procurement ? [['stage' => 'PRICING_PROCUREMENT', 'role' => 'PROCUREMENT', 'department' => 'PROCUREMENT']] : [['stage' => 'PRICING', 'role' => 'SALES', 'department' => 'SALES']]),
                ['stage' => 'CONTRACT_REVIEW', 'role' => 'TENDERS_CONTRACTS', 'department' => 'CONTRACTS'],
                ...($adminApproval ? [['stage' => 'INTERNAL_APPROVAL', 'role' => 'OPERATIONS_MANAGER', 'department' => 'OPERATIONS']] : []),
                ['stage' => 'CUSTOMER_DECISION', 'role' => 'CUSTOMER', 'department' => 'CUSTOMER'],
                ['stage' => 'PO_OR_CONTRACT', 'role' => 'TENDERS_CONTRACTS', 'department' => 'CONTRACTS'],
                ['stage' => 'COMPLETED', 'role' => 'SALES', 'department' => 'SALES'],
            ],
            self::MAINTENANCE_CONTRACT_QUOTATION => [
                ['stage' => 'SALES_REVIEW', 'role' => 'SALES', 'department' => 'SALES'],
                ['stage' => 'CONTRACT_REVIEW', 'role' => 'TENDERS_CONTRACTS', 'department' => 'CONTRACTS'],
                ['stage' => 'OPERATIONS_FEASIBILITY', 'role' => 'OPERATIONS_MANAGER', 'department' => 'OPERATIONS'],
                ['stage' => 'FINANCE_REVIEW', 'role' => 'FINANCE_MANAGER', 'department' => 'FINANCE'],
                ['stage' => 'EXECUTIVE_APPROVAL', 'role' => 'CEO', 'department' => 'MANAGEMENT'],
                ['stage' => 'CUSTOMER_DECISION', 'role' => 'CUSTOMER', 'department' => 'CUSTOMER'],
                ['stage' => 'ONBOARDING', 'role' => 'CUSTOMER_SERVICE', 'department' => 'CRM'],
            ],
            self::SPARE_PARTS_QUOTATION => [
                ['stage' => 'SALES_REVIEW', 'role' => 'SALES', 'department' => 'SALES'],
                ['stage' => 'TECHNICAL_REVIEW', 'role' => 'MAINTENANCE_ENGINEER', 'department' => 'OPERATIONS'],
                ['stage' => 'PRICING_PROCUREMENT', 'role' => 'PROCUREMENT', 'department' => 'PROCUREMENT'],
                ['stage' => 'CONTRACT_REVIEW', 'role' => 'TENDERS_CONTRACTS', 'department' => 'CONTRACTS'],
                ...($adminApproval ? [['stage' => 'INTERNAL_APPROVAL', 'role' => 'OPERATIONS_MANAGER', 'department' => 'OPERATIONS']] : []),
                ['stage' => 'CUSTOMER_DECISION', 'role' => 'CUSTOMER', 'department' => 'CUSTOMER'],
                ['stage' => 'PROCUREMENT_HANDOFF', 'role' => 'PROCUREMENT', 'department' => 'PROCUREMENT'],
                ['stage' => 'COMPLETED', 'role' => 'SALES', 'department' => 'SALES'],
            ],
            self::TECHNICAL_VISIT => [
                ['stage' => 'SALES_REVIEW', 'role' => 'SALES', 'department' => 'SALES'],
                ['stage' => 'PROJECT_MANAGER_REVIEW', 'role' => 'PROJECT_MANAGER', 'department' => 'OPERATIONS'],
                ['stage' => 'TECHNICIAN_ASSIGNMENT', 'role' => 'TECHNICAL_SUPERVISOR', 'department' => 'OPERATIONS'],
                ['stage' => 'SITE_VISIT', 'role' => 'TECHNICIAN', 'department' => 'OPERATIONS'],
                ['stage' => 'TECHNICAL_REPORT', 'role' => 'MAINTENANCE_ENGINEER', 'department' => 'OPERATIONS'],
                ['stage' => 'PRICING', 'role' => 'SALES', 'department' => 'SALES'],
                ['stage' => 'CONTRACT_REVIEW', 'role' => 'TENDERS_CONTRACTS', 'department' => 'CONTRACTS'],
                ['stage' => 'CUSTOMER_DECISION', 'role' => 'CUSTOMER', 'department' => 'CUSTOMER'],
                ['stage' => 'COMPLETED', 'role' => 'SALES', 'department' => 'SALES'],
            ],
            self::TECHNICAL_CONSULTATION => [
                ['stage' => 'OPERATIONS_REVIEW', 'role' => 'OPERATIONS_MANAGER', 'department' => 'OPERATIONS'],
                ['stage' => 'PROJECT_MANAGER_REVIEW', 'role' => 'PROJECT_MANAGER', 'department' => 'OPERATIONS'],
                ['stage' => 'TECHNICIAN_ASSIGNMENT', 'role' => 'TECHNICAL_SUPERVISOR', 'department' => 'OPERATIONS'],
                ['stage' => 'SITE_VISIT', 'role' => 'TECHNICIAN', 'department' => 'OPERATIONS'],
                ['stage' => 'TECHNICAL_REPORT', 'role' => 'MAINTENANCE_ENGINEER', 'department' => 'OPERATIONS'],
                ['stage' => 'CUSTOMER_DELIVERY', 'role' => 'CUSTOMER', 'department' => 'CUSTOMER'],
                ['stage' => 'CLOSURE', 'role' => 'OPERATIONS_MANAGER', 'department' => 'OPERATIONS'],
            ],
        ];

        return $templates[$key] ?? $templates[self::MAINTENANCE];
    }
}
