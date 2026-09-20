<?php

namespace Tests\Unit;

use App\Models\ServiceRequest;
use App\Services\ServiceRequestWorkflowTemplateRegistry;
use PHPUnit\Framework\TestCase;

class ServiceRequestWorkflowTemplateRegistryTest extends TestCase
{
    private ServiceRequestWorkflowTemplateRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new ServiceRequestWorkflowTemplateRegistry();
    }

    public function test_routine_maintenance_routes_from_operations_to_customer_and_closure(): void
    {
        $request = new ServiceRequest(['request_type' => 'MAINTENANCE', 'request_subtype' => 'ROUTINE_MAINTENANCE', 'priority' => 'NORMAL']);
        $key = $this->registry->keyFor($request);
        $stages = array_column($this->registry->template($key), 'stage');

        $this->assertSame(ServiceRequestWorkflowTemplateRegistry::MAINTENANCE, $key);
        $this->assertSame('TRIAGE', $stages[0]);
        $this->assertContains('PROJECT_MANAGER_REVIEW', $stages);
        $this->assertContains('EXECUTION', $stages);
        $this->assertContains('CUSTOMER_ACCEPTANCE', $stages);
        $this->assertContains('CLOSURE', $stages);
        $this->assertContains('CSAT', $stages);
    }

    public function test_emergency_maintenance_skips_normal_triage(): void
    {
        $request = new ServiceRequest(['request_type' => 'MAINTENANCE', 'request_subtype' => 'URGENT_MAINTENANCE', 'priority' => 'EMERGENCY']);
        $key = $this->registry->keyFor($request);
        $stages = array_column($this->registry->template($key), 'stage');

        $this->assertSame(ServiceRequestWorkflowTemplateRegistry::EMERGENCY_MAINTENANCE, $key);
        $this->assertSame('EMERGENCY_DISPATCH', $stages[0]);
        $this->assertNotContains('TRIAGE', $stages);
    }

    public function test_spare_parts_quotation_includes_procurement_contract_and_customer_decision(): void
    {
        $request = new ServiceRequest(['request_type' => 'QUOTATION', 'request_subtype' => 'SPARE_PARTS_QUOTE']);
        $key = $this->registry->keyFor($request);
        $stages = array_column($this->registry->template($key, ['procurement_required' => true]), 'stage');

        $this->assertSame(ServiceRequestWorkflowTemplateRegistry::SPARE_PARTS_QUOTATION, $key);
        $this->assertContains('SALES_REVIEW', $stages);
        $this->assertContains('PRICING_PROCUREMENT', $stages);
        $this->assertContains('CONTRACT_REVIEW', $stages);
        $this->assertContains('CUSTOMER_DECISION', $stages);
        $this->assertContains('PROCUREMENT_HANDOFF', $stages);
    }


    public function test_technical_visit_uses_project_team_visit_and_commercial_route(): void
    {
        $request = new ServiceRequest(['request_type' => 'QUOTATION', 'request_subtype' => 'TECHNICAL_VISIT']);
        $key = $this->registry->keyFor($request);
        $template = $this->registry->template($key);
        $stages = array_column($template, 'stage');
        $roles = array_column($template, 'role');

        $this->assertSame(ServiceRequestWorkflowTemplateRegistry::TECHNICAL_VISIT, $key);
        $this->assertSame([
            'SALES_REVIEW','PROJECT_MANAGER_REVIEW','TECHNICIAN_ASSIGNMENT','SITE_VISIT',
            'TECHNICAL_REPORT','PRICING','CONTRACT_REVIEW','CUSTOMER_DECISION','COMPLETED',
        ], $stages);
        $this->assertSame([
            'SALES','PROJECT_MANAGER','TECHNICAL_SUPERVISOR','TECHNICIAN',
            'MAINTENANCE_ENGINEER','SALES','TENDERS_CONTRACTS','CUSTOMER','SALES',
        ], $roles);
    }

    public function test_maintenance_contract_quotation_has_operations_finance_and_executive_approval(): void
    {
        $request = new ServiceRequest(['request_type' => 'QUOTATION', 'request_subtype' => 'MAINTENANCE_CONTRACT_QUOTE']);
        $key = $this->registry->keyFor($request);
        $stages = array_column($this->registry->template($key), 'stage');

        $this->assertSame(ServiceRequestWorkflowTemplateRegistry::MAINTENANCE_CONTRACT_QUOTATION, $key);
        $this->assertSame(['SALES_REVIEW','CONTRACT_REVIEW','OPERATIONS_FEASIBILITY','FINANCE_REVIEW','EXECUTIVE_APPROVAL','CUSTOMER_DECISION','ONBOARDING'], $stages);
    }

    public function test_technical_consultation_follows_visit_report_customer_route(): void
    {
        $request = new ServiceRequest(['request_type' => 'CONSULTATION', 'request_subtype' => 'TECHNICAL_CONSULTATION']);
        $key = $this->registry->keyFor($request);
        $stages = array_column($this->registry->template($key), 'stage');

        $this->assertSame(ServiceRequestWorkflowTemplateRegistry::TECHNICAL_CONSULTATION, $key);
        $this->assertSame(['OPERATIONS_REVIEW','PROJECT_MANAGER_REVIEW','TECHNICIAN_ASSIGNMENT','SITE_VISIT','TECHNICAL_REPORT','CUSTOMER_DELIVERY','CLOSURE'], $stages);
    }

    public function test_conditional_cost_quality_and_hse_stages_are_added_only_when_needed(): void
    {
        $request = new ServiceRequest(['request_type' => 'MAINTENANCE', 'request_subtype' => 'ROUTINE_MAINTENANCE']);
        $key = $this->registry->keyFor($request);
        $stages = array_column($this->registry->template($key, [
            'has_cost' => true,
            'quality_required' => true,
            'hse_required' => true,
        ]), 'stage');

        $this->assertContains('QUALITY_VERIFICATION', $stages);
        $this->assertContains('HSE_VERIFICATION', $stages);
        $this->assertContains('FINANCE_REVIEW', $stages);
    }
}
