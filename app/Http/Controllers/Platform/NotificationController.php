<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformNotification;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('platform.notifications.index',[
            'notifications'=>PlatformNotification::where('user_id',$request->user()->id)->latest()->paginate(30),
        ]);
    }

    public function read(Request $request, PlatformNotification $notification): RedirectResponse
    {
        abort_unless((int)$notification->user_id === (int)$request->user()->id,404);
        $notification->update(['read_at'=>now()]);
        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        PlatformNotification::where('user_id',$request->user()->id)->whereNull('read_at')->update(['read_at'=>now()]);
        return back()->with('status','All notifications marked as read.');
    }
}
