<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View { return view('auth.login'); }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email'=>['required','email'],'password'=>['required']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email'=>'Invalid credentials.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        $user=Auth::user();
        if ($user->status !== 'ACTIVE' || $user->locked_at) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403,'User account is unavailable.');
        }
        $user->forceFill(['last_login_at'=>now()])->save();
        $request->session()->put('account_session_version',(int)$user->session_version);

        if ($user->role === 'CUSTOMER') return redirect()->intended(route('customer.portal'));
        if ($user->role === 'STOREKEEPER') return redirect()->intended(route('inventory.warehouse.index'));
        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
