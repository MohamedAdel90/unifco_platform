<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Dashboard\OperationsManagerScopeService;
use Illuminate\Database\Eloquent\Builder;
use Tests\TestCase;

class OperationsManagerScopeTest extends TestCase
{
    public function test_operations_dashboard_scope_service_delegates_to_central_scope_service(): void
    {
        $user = new User(['status' => 'ACTIVE']);
        $query = $this->createMock(Builder::class);

        $scopeService = $this->mock(\App\Services\ScopeService::class);
        $scopeService->shouldReceive('apply')
            ->once()
            ->with($query, $user)
            ->andReturn($query);

        $service = app(OperationsManagerScopeService::class);

        $this->assertSame($query, $service->scoped($query, $user));
    }
}
