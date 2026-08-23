<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandingController extends Controller
{
    public function index(Request $request): View
    {
        $this->adminOnly($request);

        return view('admin.branding.index', [
            'branding' => BrandingSetting::query()->first(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->adminOnly($request);

        $data = $request->validate([
            'logo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        $file = $data['logo'];
        $branding = BrandingSetting::query()->first() ?? new BrandingSetting();

        if ($branding->logo_path && Storage::disk('local')->exists($branding->logo_path)) {
            Storage::disk('local')->delete($branding->logo_path);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');
        $path = $file->storeAs('branding', 'website-logo-'.now()->format('YmdHis').'.'.$extension, 'local');

        $branding->fill([
            'logo_path' => $path,
            'logo_mime' => $file->getMimeType() ?: 'application/octet-stream',
            'logo_original_name' => $file->getClientOriginalName(),
            'updated_by' => $request->user()->id,
        ])->save();

        return back()->with('status', 'Website logo updated. The new logo is now used across the platform.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $this->adminOnly($request);

        $branding = BrandingSetting::query()->first();
        if ($branding) {
            if ($branding->logo_path && Storage::disk('local')->exists($branding->logo_path)) {
                Storage::disk('local')->delete($branding->logo_path);
            }
            $branding->delete();
        }

        return back()->with('status', 'Website logo reset to the default UNIFCO logo.');
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()?->role === 'ADMIN', 403);
    }
}
