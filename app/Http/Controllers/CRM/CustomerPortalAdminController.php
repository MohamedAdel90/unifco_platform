<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\{Asset,Customer,FinancialDocument,ServiceContract,User};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CustomerPortalAdminController extends Controller
{
    public function show(Customer $customer): View
    {
        return view('crm.customers.portal', [
            'customer' => $customer,
            'portalUsers' => User::where('customer_id', $customer->id)->orderBy('name')->get(),
            'contracts' => ServiceContract::where('customer_id', $customer->id)->latest('starts_on')->get(),
            'assets' => Asset::where(fn ($q) => $q->whereNull('customer_id')->orWhere('customer_id', $customer->id))->orderBy('asset_code')->get(),
            'invoices' => FinancialDocument::where('document_type','AR_INVOICE')
                ->where(fn ($q) => $q->whereNull('customer_id')->orWhere('customer_id', $customer->id))
                ->latest('document_date')->limit(100)->get(),
        ]);
    }

    public function provisionUser(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required','string','max:180'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','confirmed',Password::min(12)->letters()->numbers()],
        ]);

        User::create([
            'tenant_id' => Auth::user()->tenant_id,
            'organization_id' => Auth::user()->organization_id,
            'customer_id' => $customer->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'CUSTOMER',
            'status' => 'ACTIVE',
        ]);

        return back()->with('status', 'Customer portal user created.');
    }

    public function storeContract(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'contract_no' => ['required','string','max:60'],
            'title' => ['required','string','max:255'],
            'starts_on' => ['required','date'],
            'ends_on' => ['required','date','after_or_equal:starts_on'],
            'contract_value' => ['required','numeric','min:0'],
            'currency' => ['required','string','size:3'],
            'billing_cycle' => ['required','string','max:30'],
            'scope' => ['nullable','string','max:5000'],
            'sla_summary' => ['nullable','string','max:5000'],
        ]);

        ServiceContract::create($data + [
            'tenant_id' => Auth::user()->tenant_id,
            'organization_id' => Auth::user()->organization_id,
            'customer_id' => $customer->id,
            'status' => 'ACTIVE',
        ]);

        return back()->with('status', 'Service contract created.');
    }

    public function assignAsset(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate(['asset_id' => ['required','integer','exists:assets,id']]);
        Asset::whereKey($data['asset_id'])->update(['customer_id' => $customer->id]);
        return back()->with('status', 'Asset assigned to customer.');
    }

    public function linkInvoice(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate(['financial_document_id' => ['required','integer','exists:financial_documents,id']]);
        FinancialDocument::whereKey($data['financial_document_id'])->where('document_type','AR_INVOICE')->update(['customer_id' => $customer->id]);
        return back()->with('status', 'Invoice linked to customer portal.');
    }
}
