<?php

namespace Tests\Feature;

use App\Models\HomepageClient;
use App\Models\HomepageProject;
use App\Models\HomepageSection;
use App\Models\Tenant;
use App\Models\User;
use App\Services\HomepageContentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HomepageCmsAdminTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $code = 'CMS'.substr(uniqid(), -6);
        $tenant = Tenant::create(['name' => 'CMS Tenant', 'code' => $code, 'status' => 'ACTIVE']);

        return User::create([
            'tenant_id' => $tenant->id, 'name' => 'CMS Admin', 'email' => 'cms-admin-'.$code.'@example.test',
            'password' => 'password', 'role' => 'ADMIN', 'status' => 'ACTIVE',
        ]);
    }

    private function regularUser(): User
    {
        $tenant = Tenant::create(['name' => 'CMS Tenant2', 'code' => 'CMS2', 'status' => 'ACTIVE']);

        return User::create([
            'tenant_id' => $tenant->id, 'name' => 'CMS User', 'email' => 'cms-user@example.test',
            'password' => 'password', 'role' => 'TECHNICIAN', 'status' => 'ACTIVE',
        ]);
    }

    private function makeSection(): HomepageSection
    {
        return HomepageSection::create([
            'section_key' => 'hero',
            'sort_order' => 1,
            'is_active' => true,
            'data_ar' => ['hero_title' => 'عنوان عربي'],
            'data_en' => ['hero_title' => 'English title'],
        ]);
    }

    public function test_admin_sees_sections_index(): void
    {
        $this->makeSection();
        $this->actingAs($this->admin())
            ->get(route('admin.homepage.sections.index'))
            ->assertOk()
            ->assertSee('hero')
            ->assertSee('Edit JSON');
    }

    public function test_admin_can_open_section_edit(): void
    {
        $section = $this->makeSection();
        $this->actingAs($this->admin())
            ->get(route('admin.homepage.sections.edit', $section))
            ->assertOk()
            ->assertSee('data_ar')
            ->assertSee('data_en');
    }

    public function test_admin_can_open_project_create_with_image_picker(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.homepage.projects.create'))
            ->assertOk()
            ->assertSee('Image Picker')
            ->assertSee('title_ar');
    }

    public function test_admin_can_open_client_create_with_image_picker(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.homepage.clients.create'))
            ->assertOk()
            ->assertSee('Image Picker')
            ->assertSee('name_ar');
    }

    public function test_admin_can_list_images(): void
    {
        $this->actingAs($this->admin())
            ->getJson(route('admin.homepage.images.list'))
            ->assertOk()
            ->assertJsonStructure(['images']);
    }

    public function test_non_admin_cannot_access_sections(): void
    {
        $this->actingAs($this->regularUser())
            ->get(route('admin.homepage.sections.index'))
            ->assertForbidden();
    }

    public function test_admin_can_edit_and_save_section_clearing_cache(): void
    {
        $section = $this->makeSection();
        $this->actingAs($this->admin())
            ->put(route('admin.homepage.sections.update', $section), [
                'data_ar' => json_encode(['hero_title' => 'عنوان محدث']),
                'data_en' => json_encode(['hero_title' => 'Updated title']),
                'sort_order' => 5,
            ])
            ->assertRedirect();

        $section->refresh();
        $this->assertSame('عنوان محدث', $section->data_ar['hero_title']);
        $this->assertSame(5, $section->sort_order);
    }

    public function test_admin_can_toggle_section(): void
    {
        $section = $this->makeSection();
        $this->actingAs($this->admin())
            ->post(route('admin.homepage.sections.toggle', $section))
            ->assertRedirect();
        $this->assertFalse($section->fresh()->is_active);
    }

    public function test_admin_can_create_and_delete_project(): void
    {
        $this->actingAs($this->admin())->post(route('admin.homepage.projects.store'), [
            'title_ar' => 'مشروع عربي',
            'title_en' => 'English project',
            'owner_ar' => 'مالك',
            'owner_en' => 'Owner',
            'location_ar' => 'مكان',
            'location_en' => 'Place',
            'scope_ar' => 'نطاق',
            'scope_en' => 'Scope',
            'year' => '2024-2025',
            'image' => '/images/home/projects/x.webp',
            'sort_order' => 0,
        ])->assertRedirect(route('admin.homepage.projects.index'));

        $this->assertDatabaseHas('homepage_projects', ['title_en' => 'English project']);

        $project = HomepageProject::where('title_en', 'English project')->firstOrFail();
        $this->actingAs($this->admin())->delete(route('admin.homepage.projects.destroy', $project))->assertRedirect();
        $this->assertDatabaseMissing('homepage_projects', ['id' => $project->id]);
    }

    public function test_admin_can_update_project(): void
    {
        $project = HomepageProject::create([
            'title_ar' => 'مشروع', 'title_en' => 'Proj', 'owner_ar' => 'م', 'owner_en' => 'O',
            'location_ar' => 'ل', 'location_en' => 'L', 'scope_ar' => 'ن', 'scope_en' => 'S',
            'year' => '2024', 'image' => '/x.webp', 'sort_order' => 0, 'is_active' => true,
        ]);
        $this->actingAs($this->admin())->put(route('admin.homepage.projects.update', $project), [
            'title_ar' => 'تدشين', 'title_en' => 'Launch', 'owner_ar' => 'م2', 'owner_en' => 'O2',
            'location_ar' => 'ل2', 'location_en' => 'L2', 'scope_ar' => 'ن2', 'scope_en' => 'S2',
            'year' => '2025', 'image' => '/y.webp', 'sort_order' => 1,
        ])->assertRedirect();
        $this->assertSame('Launch', $project->fresh()->title_en);
    }

    public function test_admin_can_create_and_delete_client(): void
    {
        $this->actingAs($this->admin())->post(route('admin.homepage.clients.store'), [
            'name_ar' => 'عميل',
            'name_en' => 'Client',
            'image' => '/images/home/clients/nwc.webp',
            'sort_order' => 0,
        ])->assertRedirect(route('admin.homepage.clients.index'));

        $this->assertDatabaseHas('homepage_clients', ['name_en' => 'Client']);

        $client = HomepageClient::where('name_en', 'Client')->firstOrFail();
        $this->actingAs($this->admin())->delete(route('admin.homepage.clients.destroy', $client))->assertRedirect();
        $this->assertDatabaseMissing('homepage_clients', ['id' => $client->id]);
    }

    public function test_public_homepage_renders_cms_data_and_fallback(): void
    {
        $this->get('/')->assertOk()->assertSee('شريك واحد لجميع احتياجات منشأتك', false);
        $this->get('/?lang=en')->assertOk()->assertSee('One partner for every facility need', false);
    }

    public function test_admin_create_project_is_forbidden_for_non_admin(): void
    {
        $this->actingAs($this->regularUser())
            ->post(route('admin.homepage.projects.store'), ['title_ar' => 'x', 'title_en' => 'x', 'owner_ar' => 'x', 'owner_en' => 'x', 'location_ar' => 'x', 'location_en' => 'x', 'scope_ar' => 'x', 'scope_en' => 'x', 'year' => '2024'])
            ->assertForbidden();
    }

    public function test_clear_all_cache_forgets_keys(): void
    {
        cache()->put('homepage_content_ar', ['x' => 1]);
        cache()->put('homepage_content_en', ['x' => 1]);
        HomepageContentService::clearAllCache();
        $this->assertFalse(cache()->has('homepage_content_ar'));
        $this->assertFalse(cache()->has('homepage_content_en'));
    }
}
