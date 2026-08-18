<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

class CustomerProfileController extends Controller
{
    public function edit(): View
    {
        $user=auth()->user(); abort_unless($user->role==='CUSTOMER' && $user->customer_id,403);
        return view('customer.profile',['customer'=>Customer::findOrFail($user->customer_id)]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user=auth()->user(); abort_unless($user->role==='CUSTOMER' && $user->customer_id,403);
        $data=$request->validate(['contact_name'=>['nullable','string','max:180'],'email'=>['required','email','max:180'],'phone'=>['nullable','string','max:32'],'city'=>['nullable','string','max:120'],'address'=>['nullable','string','max:2000']]);
        Customer::whereKey($user->customer_id)->update($data);
        return back()->with('status','Customer profile updated.');
    }
}
