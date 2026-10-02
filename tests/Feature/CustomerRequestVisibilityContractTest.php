<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerRequestVisibilityContractTest extends TestCase
{
    public function test_customer_request_list_does_not_expose_internal_stage_or_status_filters(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/CustomerServiceRequestController.php'));
        $view = file_get_contents(resource_path('views/customer/service-requests/index.blade.php'));

        $this->assertStringNotContainsString("query('stage'", $controller);
        $this->assertStringNotContainsString("query('status'", $controller);
        $this->assertStringNotContainsString("'workflow_stage'=>'stage'", $controller);
        $this->assertStringNotContainsString("'status'=>'status'", $controller);

        $this->assertStringNotContainsString('name="stage"', $view);
        $this->assertStringNotContainsString('name="status"', $view);
        $this->assertStringNotContainsString('$label($requestItem->status)', $view);
        $this->assertStringContainsString('$customerStatus=$statusPresenter->present($requestItem)', $view);
        $this->assertStringContainsString('$customerState', $view);
    }
}
