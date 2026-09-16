<?php

namespace App\Observers;

use App\Models\ServiceRequest;
use App\Services\Operations\OperationsRoutingService;

class ServiceRequestOperationsObserver
{
    public function created(ServiceRequest $request): void
    {
        app(OperationsRoutingService::class)->assign($request);
    }

    public function updated(ServiceRequest $request): void
    {
        if ($request->wasChanged(['asset_id','operational_domain_id','customer_id','customer_site_id','service_contract_id'])) {
            app(OperationsRoutingService::class)->assign($request);
        }
    }
}
