<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageClient;
use App\Services\HomepageContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomepageClientController extends Controller
{
    public function index(Request $request): View
    {
        $this->adminOnly($request);
        $clients = HomepageClient::query()->orderBy('sort_order')->get();

        return view('admin.homepage.clients.index', compact('clients'));
    }

    public function create(Request $request): View
    {
        $this->adminOnly($request);

        return view('admin.homepage.clients.form', ['client' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->adminOnly($request);
        HomepageClient::create($this->validated($request));
        HomepageContentService::clearAllCache();

        return redirect()->route('admin.homepage.clients.index')->with('status', 'Client created.');
    }

    public function edit(Request $request, HomepageClient $client): View
    {
        $this->adminOnly($request);

        return view('admin.homepage.clients.form', compact('client'));
    }

    public function update(Request $request, HomepageClient $client): RedirectResponse
    {
        $this->adminOnly($request);
        $client->update($this->validated($request));
        HomepageContentService::clearAllCache();

        return redirect()->route('admin.homepage.clients.index')->with('status', 'Client updated.');
    }

    public function destroy(Request $request, HomepageClient $client): RedirectResponse
    {
        $this->adminOnly($request);
        $client->delete();
        HomepageContentService::clearAllCache();

        return redirect()->route('admin.homepage.clients.index')->with('status', 'Client deleted.');
    }

    public function toggle(Request $request, HomepageClient $client): RedirectResponse
    {
        $this->adminOnly($request);
        $client->update(['is_active' => ! $client->is_active]);
        HomepageContentService::clearAllCache();

        return back()->with('status', 'Client '.($client->is_active ? 'enabled' : 'disabled').'.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()?->role === 'ADMIN', 403);
    }
}
