<?php

namespace Tests\Unit;

use App\Models\ServiceRequest;
use App\Services\CustomerRequestStatusPresenter;
use PHPUnit\Framework\TestCase;

class CustomerRequestStatusPresenterTest extends TestCase
{
    public function test_internal_review_stages_are_hidden_behind_customer_safe_status(): void
    {
        $presenter=new CustomerRequestStatusPresenter();

        foreach(['TRIAGE','SALES_REVIEW','TECHNICAL_REVIEW','OPERATIONS_REVIEW'] as $stage){
            $request=new ServiceRequest(['workflow_stage'=>$stage]);
            $status=$presenter->present($request);
            $this->assertSame('UNDER_REVIEW',$status['code']);
            $this->assertStringNotContainsString($stage,$status['label']);
        }
    }

    public function test_finance_and_closure_are_presented_as_finalizing_not_internal_finance(): void
    {
        $presenter=new CustomerRequestStatusPresenter();

        foreach(['FINANCE_REVIEW','CLOSURE'] as $stage){
            $status=$presenter->present(new ServiceRequest(['workflow_stage'=>$stage]));
            $this->assertSame('FINALIZING',$status['code']);
            $this->assertStringNotContainsString('FINANCE',$status['label']);
        }
    }

    public function test_customer_action_stages_are_explicit(): void
    {
        $presenter=new CustomerRequestStatusPresenter();
        foreach(['CUSTOMER_APPROVAL','CUSTOMER_ACCEPTANCE','CUSTOMER_DELIVERY'] as $stage){
            $this->assertSame('ACTION_REQUIRED',$presenter->present(new ServiceRequest(['workflow_stage'=>$stage]))['code']);
        }
    }
}
