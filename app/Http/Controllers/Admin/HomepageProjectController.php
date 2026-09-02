<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageProject;
use App\Services\HomepageContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomepageProjectController extends Controller
{
    public function index(Request $request): View
    {
        $this->adminOnly($request);
        $projects = HomepageProject::query()->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.homepage.projects.index', compact('projects'));
    }

    public function create(Request $request): View
    {
        $this->adminOnly($request);

        return view('admin.homepage.projects.form', ['project' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->adminOnly($request);
        HomepageProject::create($this->validated($request));
        HomepageContentService::clearAllCache();

        return redirect()->route('admin.homepage.projects.index')->with('status', 'Project created.');
    }

    public function edit(Request $request, HomepageProject $project): View
    {
        $this->adminOnly($request);

        return view('admin.homepage.projects.form', compact('project'));
    }

    public function update(Request $request, HomepageProject $project): RedirectResponse
    {
        $this->adminOnly($request);
        $project->update($this->validated($request));
        HomepageContentService::clearAllCache();

        return redirect()->route('admin.homepage.projects.index')->with('status', 'Project updated.');
    }

    public function destroy(Request $request, HomepageProject $project): RedirectResponse
    {
        $this->adminOnly($request);
        $project->delete();
        HomepageContentService::clearAllCache();

        return redirect()->route('admin.homepage.projects.index')->with('status', 'Project deleted.');
    }

    public function toggle(Request $request, HomepageProject $project): RedirectResponse
    {
        $this->adminOnly($request);
        $project->update(['is_active' => ! $project->is_active]);
        HomepageContentService::clearAllCache();

        return back()->with('status', 'Project '.($project->is_active ? 'enabled' : 'disabled').'.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'owner_ar' => ['required', 'string', 'max:255'],
            'owner_en' => ['required', 'string', 'max:255'],
            'location_ar' => ['required', 'string', 'max:255'],
            'location_en' => ['required', 'string', 'max:255'],
            'scope_ar' => ['required', 'string', 'max:255'],
            'scope_en' => ['required', 'string', 'max:255'],
            'year' => ['required', 'string', 'max:30'],
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
