<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProspectVerificationController extends Controller
{
    public function __invoke(Request $request, Customer $customer, CustomerLifecycleService $lifecycle): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && in_array($user->role, ['ADMIN','CRM_MANAGER','CUSTOMER_SERVICE'], true), 403, 'This role cannot verify customer prospects.');
        abort_unless((int) $customer->tenant_id === (int) $user->tenant_id, 404);
        abort_unless($customer->status === 'PROSPECT' || str_starts_with((string) $customer->customer_code, 'PROS-'), 422, 'Only pending prospects can be verified here.');

        $activated = $lifecycle->activateProspect($customer, (int) $user->id);

        return back()->with('status', 'Prospect verified and activated as customer '.$activated->customer_code.'.');
    }
}
