<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\{RedirectResponse,Request,Response};
use Illuminate\Support\Facades\{Hash,Storage};
use Illuminate\Validation\Rules\Password;

class CustomerProfileController extends Controller
{
    private function customer(): Customer
    {
        $user = auth()->user();
        abort_unless($user && $user->role === 'CUSTOMER' && $user->customer_id, 403);
        return Customer::findOrFail($user->customer_id);
    }

    public function edit(Request $request): Response
    {
        $customer = $this->customer();
        $locale = $request->query('lang') === 'en' ? 'en' : 'ar';
        $html = view('customer.profile', compact('customer','locale'))->render();
        $direction = $locale === 'ar' ? 'rtl' : 'ltr';
        $html = str_replace('</head>', '<style id="profile-logo-position">.brand-row{direction:ltr}.hero-center{direction:'.$direction.'}</style></head>', $html);
        return response($html)->header('X-UNIFCO-Profile-Release','success-partner-20260821-1')->header('Cache-Control','no-cache, no-store, must-revalidate');
    }

    public function update(Request $request): RedirectResponse
    {
        $customer = $this->customer();
        $data = $request->validate([
            'contract_manager_name'=>['sometimes','nullable','string','max:180'],
            'contract_manager_title'=>['sometimes','nullable','string','max:180'],
            'email'=>['sometimes','required','email','max:180'],
            'phone'=>['sometimes','nullable','string','max:40'],
            'city'=>['sometimes','nullable','string','max:120'],
            'address'=>['sometimes','nullable','string','max:500'],
            'project_name'=>['sometimes','nullable','string','max:255'],
        ]);
        $customer->update($data);
        return back()->with('status', request('lang') === 'en' ? 'Profile field updated successfully.' : 'تم تحديث البيانات بنجاح.');
    }

    public function updateLogo(Request $request): RedirectResponse
    {
        $customer = $this->customer();
        $request->validate(['logo'=>['required','image','mimes:jpg,jpeg,png,webp','max:4096']]);
        if ($customer->logo_path) Storage::disk('public')->delete($customer->logo_path);
        $path = $request->file('logo')->store('customer-logos', 'public');
        $customer->update(['logo_path'=>$path]);
        return back()->with('status', request('lang') === 'en' ? 'Customer logo updated.' : 'تم تحديث شعار العميل.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $this->customer();
        $data = $request->validate([
            'current_password'=>['required','string'],
            'password'=>['required','confirmed',Password::min(12)->letters()->numbers()],
        ]);
        $user = auth()->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password'=>request('lang') === 'en' ? 'Current password is incorrect.' : 'كلمة المرور الحالية غير صحيحة.']);
        }
        $user->update(['password'=>$data['password']]);
        return back()->with('status', request('lang') === 'en' ? 'Password changed successfully.' : 'تم تغيير كلمة المرور بنجاح.');
    }
}
