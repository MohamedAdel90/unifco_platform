<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CurrentCustomerMaintenanceRequestController extends Controller
{
    public function create(): View
    {
        return view('public.current-customer-maintenance-request');
    }

    public function customer(Request $request): JsonResponse
    {
        $data = $request->validate(['customer_number' => ['required','string','max:80']]);
        $customer = DB::table('customers')->where('customer_code', trim($data['customer_number']))->first();
        if (! $customer) {
            return response()->json(['message' => 'لم يتم العثور على رقم العميل.'], 404);
        }

        $sites = DB::table('customer_sites')->where('customer_id', $customer->id)
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'INACTIVE'))
            ->orderBy('name')->get(['id','site_code','name','city','address','latitude','longitude','contact_name','contact_mobile']);

        $contracts = DB::table('service_contracts')->where('customer_id', $customer->id)
            ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', ['CANCELLED','EXPIRED']))
            ->orderByDesc('id')->get(['id','contract_no','title','starts_on','ends_on','status']);

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'name' => $customer->name,
                'email' => $customer->email,
                'contact_name' => $customer->contact_name,
                'phone' => $customer->phone,
                'city' => $customer->city,
                'address' => $customer->address,
            ],
            'sites' => $sites,
            'contracts' => $contracts,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function assets(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => ['required','integer'],
            'contract_no' => ['nullable','string','max:120'],
            'site_id' => ['nullable','integer'],
            'key' => ['nullable','string','max:200'],
        ]);

        $query = DB::table('assets as a')->leftJoin('customer_sites as s', 's.id', '=', 'a.customer_site_id')
            ->where('a.customer_id', $data['customer_id'])
            ->where(fn ($q) => $q->whereNull('a.lifecycle_status')->orWhere('a.lifecycle_status', '!=', 'RETIRED'));

        if (! empty($data['contract_no'])) {
            $query->where('a.contract_reference', $data['contract_no']);
        }
        if (! empty($data['site_id'])) {
            $query->where('a.customer_site_id', $data['site_id']);
        }
        if (! empty($data['key'])) {
            $key = trim($data['key']);
            if (filter_var($key, FILTER_VALIDATE_URL)) {
                $parts = parse_url($key); parse_str($parts['query'] ?? '', $params);
                $key = trim((string)($params['asset'] ?? $params['token'] ?? basename($parts['path'] ?? '')));
            }
            $key = preg_replace('/^UNIFCO-ASSET:/i', '', $key) ?? $key;
            $query->where(function ($q) use ($key) {
                $q->where('a.asset_code', $key)->orWhere('a.qr_token', $key)->orWhere('a.serial_no', $key)
                    ->orWhere('a.customer_asset_code', $key)->orWhere('a.manufacturer_asset_number', $key);
            });
        }

        $assets = $query->orderBy('a.name')->limit(200)->get([
            'a.id','a.asset_code','a.customer_asset_code','a.name','a.asset_category','a.asset_type','a.manufacturer','a.model_no','a.serial_no',
            'a.manufacturer_asset_number','a.operational_status','a.contract_reference','a.customer_site_id','s.name as site_name','s.city as site_city','s.address as site_address',
        ]);

        return response()->json(['assets' => $assets])->header('Cache-Control', 'no-store, private');
    }
}
